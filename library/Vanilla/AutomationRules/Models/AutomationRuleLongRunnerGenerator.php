<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\AutomationRules\Models;

use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Psr\Log\LoggerInterface;
use Vanilla\AutomationRules\Trigger\TimedAutomationTriggerInterface;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Logger;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Web\SystemCallableInterface;

/**
 * Class for running Automation Rules LongRunner.
 */
class AutomationRuleLongRunnerGenerator implements SystemCallableInterface
{
    const LOCK_KEY = "ARTrigger-%sAction-%s";
    const BUCKET_SIZE = 50;

    private \StealableLock $stealableLock;
    private \Gdn_Cache $cache;
    private AutomationRuleService $automationRuleService;
    private AutomationRuleDispatchesModel $automationRuleDispatchesModel;
    private LoggerInterface $logger;

    /**
     * DI.
     *
     * @param \Gdn_Cache $cache
     * @param AutomationRuleService $automationRuleService
     * @param AutomationRuleDispatchesModel $automationRuleDispatchesModel
     */
    public function __construct(
        \Gdn_Cache $cache,
        AutomationRuleService $automationRuleService,
        AutomationRuleDispatchesModel $automationRuleDispatchesModel
    ) {
        $this->automationRuleService = $automationRuleService;
        $this->automationRuleDispatchesModel = $automationRuleDispatchesModel;
        $this->cache = $cache;
        $this->logger = \Gdn::getContainer()->get(LoggerInterface::class);
    }

    /**
     * @inheritDoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["runAutomationRule"];
    }

    /**
     * Execute long runner for Automation Rule.
     *
     * @param int|string|null $lastRecordID
     * @param string|null $cacheLockCombo
     * @param array $params
     * @return \Generator
     * @throws NoResultsException
     * @throws \InvalidArgumentException
     * @throws \LockStolenException
     */
    public function runAutomationRule(
        $lastRecordID = null,
        ?string $cacheLockCombo = null,
        array $params = []
    ): \Generator {
        $lastRecordID = $lastRecordID ?? 0;
        try {
            $count = $params["currentCount"] ?? 0;
            if (empty($params)) {
                throw new \InvalidArgumentException("Long runner parameters is not set");
            }
            $automationRuleID = $params["automationRuleID"];
            $dispatchUUID = $params["dispatchUUID"];
            $action = $this->automationRuleService->getAction($params["actionType"]);
            if (!$action) {
                throw new \InvalidArgumentException("Action class not found");
            }
            $actionClass = new $action(
                $automationRuleID,
                $params["dispatchType"] ?? AutomationRuleDispatchesModel::TYPE_TRIGGERED,
                $dispatchUUID
            );
            $automationRule = $actionClass->getAutomationRule();
            $actionValue = $automationRule["action"]["actionValue"] ?? [];
            $triggerClass = $this->automationRuleService->getAutomationTrigger($params["triggerType"]);

            // We don't want to recreate new dispatches for the same rule
            $actionClass->setDispatched();
            $lock = sprintf(self::LOCK_KEY, $params["triggerType"], $params["actionType"]);
            $this->stealableLock = new \StealableLock($this->cache, $lock);
            if (isset($cacheLockCombo)) {
                $this->stealableLock->refresh($cacheLockCombo);
            } else {
                $cacheLockCombo = $this->stealableLock->steal();
            }
            try {
                if ($lastRecordID == 0) {
                    $this->automationRuleDispatchesModel->updateDispatchStatus(
                        $dispatchUUID,
                        AutomationRuleDispatchesModel::STATUS_RUNNING
                    );
                }
                if ($triggerClass instanceof TimedAutomationTriggerInterface) {
                    $lastRunDate = $params["lastRunDate"];
                    $where = $triggerClass->getWhereArray($automationRule["trigger"]["triggerValue"], $lastRunDate);
                    $where = $actionClass->addWhereArray($where, $actionValue);
                } else {
                    $where = $automationRule["trigger"]["triggerValue"];
                }
                $toProcess = $triggerClass->getRecordsToProcess($lastRecordID, $where);
                foreach ($toProcess as $primaryKey => $objects) {
                    $lastRecordID = $primaryKey;
                    try {
                        $status = $actionClass->executeLongRunner($actionValue, $objects);
                        if ($status) {
                            $count++;
                        }
                        yield new LongRunnerSuccessID($primaryKey);
                    } catch (LongRunnerTimeoutException $e) {
                        throw $e;
                    } catch (\Exception $exception) {
                        $this->automationRuleDispatchesModel->updateDispatchStatus(
                            $dispatchUUID,
                            AutomationRuleDispatchesModel::STATUS_WARNING,
                            [
                                "failedRecords" => [$primaryKey],
                            ],
                            $exception->getMessage()
                        );
                        yield new LongRunnerFailedID($primaryKey);
                    } catch (\Throwable $exception) {
                        // Execution has failed
                        $this->markDispatchAsFailed($dispatchUUID, $automationRule, $exception);
                        throw $exception;
                    }
                }
            } catch (LongRunnerTimeoutException $timeoutException) {
                $params["currentCount"] = $count;
                return new LongRunnerNextArgs([$lastRecordID, $cacheLockCombo, $params]);
            }
        } catch (\LockStolenException $lockE) {
            $this->stealableLock->release();
            $this->automationRuleDispatchesModel->updateDispatchStatus(
                $dispatchUUID,
                AutomationRuleDispatchesModel::STATUS_FAILED,
                [
                    "affectedRecordCount" => $count,
                ],
                $lockE->getMessage()
            );
            throw $lockE;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Throwable $exception) {
            $this->markDispatchAsFailed($dispatchUUID, $automationRule, $exception);
            throw $exception;
        }
        // Get the current dispatch status as there might have been exceptions happened during the execution
        $currentDispatch = $this->automationRuleDispatchesModel->selectSingle(
            ["automationRuleDispatchUUID" => $dispatchUUID],
            [$this->automationRuleDispatchesModel::OPT_SELECT => "status"]
        );
        $currentStatus = $currentDispatch["status"];
        // Update the dispatch status to success only if the status is still running
        $this->automationRuleDispatchesModel->updateDispatchStatus(
            $dispatchUUID,
            $currentStatus == AutomationRuleDispatchesModel::STATUS_RUNNING
                ? AutomationRuleDispatchesModel::STATUS_SUCCESS
                : $currentStatus,
            ["affectedRecordCount" => $count]
        );
        $this->logger->info("Finished processing automation rule", [
            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
            Logger::FIELD_TAGS => ["automation-rule", "long-runner"],
            "automationRuleID" => $automationRule["automationRuleID"],
            "automationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
            "dispatchUUID" => $dispatchUUID,
        ]);
        $this->stealableLock->release();

        return LongRunner::FINISHED;
    }

    /**
     * Mark the dispatch as failed and log the error.
     *
     * @param string $dispatchUUID
     * @param string $message
     * @return void
     * @throws NoResultsException
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    private function markDispatchAsFailed(string $dispatchUUID, array $automationRule, \Throwable $exception): void
    {
        $this->automationRuleDispatchesModel->updateDispatchStatus(
            $dispatchUUID,
            AutomationRuleDispatchesModel::STATUS_FAILED,
            [],
            $exception->getMessage()
        );
        $this->logger->error("Automation rule long runner execution failed", [
            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
            Logger::FIELD_TAGS => ["automation-rule", "long-runner"],
            "automationRuleID" => $automationRule["automationRuleID"],
            "automationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
            "dispatchUUID" => $dispatchUUID,
            "exception" => $exception,
        ]);
    }
}
