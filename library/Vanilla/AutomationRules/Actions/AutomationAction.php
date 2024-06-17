<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Actions;

use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Schema;
use InvalidArgumentException;
use LogModel;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Vanilla\AutomationRules\Models\AutomationRuleLongRunnerGenerator;
use Vanilla\AutomationRules\Trigger\AutomationTrigger;
use Vanilla\AutomationRules\Trigger\TimedAutomationTriggerInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;

/**
 * Base class for all automation actions
 */
abstract class AutomationAction
{
    use LoggerAwareTrait;
    private int $automationRuleID;
    private array $automationRule;
    protected string $dispatchType;
    protected bool $dispatched = false;
    private string $dispatchUUID;
    private ?int $dispatchedJobID;
    private LongRunner $longRunner;

    protected AutomationRuleModel $automationRuleModel;

    protected AutomationRuleDispatchesModel $automationRuleDispatchesModel;

    protected LogModel $logModel;

    const ACTION_TYPE = "actionType";
    const ACTION_NAME = "name";
    const ACTION_TRIGGERS = "actionTriggers";

    /**
     * @param int $automationRuleID
     * @param string $dispatchType
     * @param string|null $dispatchUUID
     * @throws NoResultsException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function __construct(
        int $automationRuleID,
        string $dispatchType = AutomationRuleDispatchesModel::TYPE_TRIGGERED,
        ?string $dispatchUUID = null
    ) {
        $this->automationRuleModel = \Gdn::getContainer()->get(AutomationRuleModel::class);
        $this->logger = \Gdn::getContainer()->get(LoggerInterface::class);
        $this->automationRuleDispatchesModel = \Gdn::getContainer()->get(AutomationRuleDispatchesModel::class);
        $this->logModel = \Gdn::getContainer()->get(LogModel::class);
        $this->longRunner = \Gdn::getContainer()->get(LongRunner::class);
        $this->setAutomationRuleID($automationRuleID);
        $this->dispatchType = $dispatchType;
        if ($dispatchUUID) {
            // In case of long-running jobs we may create the dispatch UUID before the action execution is invoked
            $this->dispatchUUID = $dispatchUUID;
        } else {
            $this->setDispatchUUID();
        }
    }

    /**
     * Get the action record
     *
     * @return array
     */
    public function getRecord(): array
    {
        return self::getBaseSchemaArray();
    }

    /**
     * Provide base schema array for all action types.
     *
     * @return array
     */
    public static function getBaseSchemaArray(): array
    {
        return [
            self::ACTION_TYPE => static::getType(),
            self::ACTION_NAME => static::getName(),
            self::ACTION_TRIGGERS => static::getTriggers(),
        ];
    }

    /**
     * Get the automation rule
     *
     * @return array
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     */
    public function getAutomationRule(): array
    {
        if (empty($this->automationRule)) {
            $this->automationRule = $this->automationRuleModel->getAutomationRuleByID($this->automationRuleID);
        }
        return $this->automationRule;
    }

    /**
     * Get the automation rule ID
     *
     * @return int|null
     */
    public function getAutomationRuleID(): ?int
    {
        return $this->automationRuleID;
    }

    /**
     * Set the automation rule ID
     *
     * @param int $automationRuleID
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function setAutomationRuleID(int $automationRuleID): void
    {
        $this->automationRuleID = $automationRuleID;
        try {
            $this->getAutomationRule();
        } catch (NoResultsException $e) {
            throw new InvalidArgumentException("Invalid automation rule ID");
        }
    }

    /**
     * set dispatched job ID
     */
    public function setDispatchedJobID(int $dispatchedJobID): void
    {
        $this->dispatchedJobID = $dispatchedJobID;
    }

    /**
     * Get dispatched job ID
     */
    protected function getDispatchedJobID(): ?int
    {
        return $this->dispatchedJobID ?? null;
    }

    /**
     * Set the dispatch status
     *
     * @param bool $dispatched
     * @return void
     */
    public function setDispatched(bool $dispatched = true): void
    {
        $this->dispatched = $dispatched;
    }

    /**
     * Generate a unique dispatch UUID for each dispatch
     *
     * @return void
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     */
    protected function setDispatchUUID(): void
    {
        $automationRule = $this->getAutomationRule();
        $data = [
            "automationRuleID" => $automationRule["automationRuleID"],
            "automationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
            "triggerType" => $automationRule["trigger"]["triggerType"],
            "triggerValue" => $automationRule["trigger"]["triggerValue"],
            "actionType" => $automationRule["action"]["actionType"],
            "actionValue" => $automationRule["action"]["actionValue"],
            "dispatchType" => $this->dispatchType,
        ];

        $this->dispatchUUID = $this->automationRuleDispatchesModel::generateDispatchUUID($data);
    }

    /**
     * Get the dispatch UUID
     *
     * @return string
     */
    public function getDispatchUUID(): string
    {
        return $this->dispatchUUID;
    }

    /**
     * @inheridoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        return;
    }

    /**
     * Insert a new dispatch entry
     *
     * @param string $dispatchStatus
     * @param string|null $errorMessages
     * @param array|null $attributes
     * @return string
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     */
    protected function logDispatched(
        string $dispatchStatus = AutomationRuleDispatchesModel::STATUS_SUCCESS,
        ?string $errorMessages = null,
        ?array $attributes = null
    ): string {
        $automationRule = $this->getAutomationRule();
        $dispatches = [
            "automationRuleDispatchUUID" => $this->getDispatchUUID(),
            "automationRuleID" => $automationRule["automationRuleID"],
            "automationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
            "dispatchType" => $this->dispatchType,
            "status" => $dispatchStatus,
            "dispatchUserID" => \Gdn::session()->UserID,
            "dispatchedJobID" => $this->getDispatchedJobID(),
            "attributes" => $attributes ?? "",
        ];
        if ($errorMessages) {
            $dispatches["errorMessage"] = $errorMessages;
        }
        $dispatchUUID = $this->automationRuleDispatchesModel->insert($dispatches);
        $this->dispatched = true;
        return $dispatchUUID;
    }

    /**
     * Make a log entry
     *
     * @param array $logData
     * @return int
     */
    protected function insertLogEntry(array $logData): int
    {
        $operation = $logData["Operation"] ?? "Automation";
        $recordType = $logData["RecordType"] ?? null;
        //Make sure the log data has a record type and data
        assert(!empty($logData["RecordType"]), "You should provide a record type for the log");
        assert(!empty($logData["Data"]) && is_array($logData["Data"]), "You should provide the data you are updating ");
        return LogModel::insert($operation, $recordType, $logData);
    }

    /**
     * Add log entry for timed discussion automation rules
     *
     * @param int $recordID
     * @param array $logData
     * @return int
     * @throws NoResultsException
     */
    protected function insertTimedDiscussionLog(int $recordID, array $logData): int
    {
        $automationRule = $this->getAutomationRule();
        $log = [
            "RecordType" => "Discussion",
            "RecordID" => $recordID,
            "AutomationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
            "Data" => $logData,
            "DispatchUUID" => $this->getDispatchUUID(),
        ];
        return $this->insertLogEntry($log);
    }

    /**
     * Get the action schema
     *
     * @return Schema
     */
    abstract static function getSchema(): Schema;

    /**
     * Get the action description
     *
     * @return string
     */
    abstract static function getName(): string;

    /**
     * Get the action type
     *
     * @return string
     */
    abstract static function getType(): string;

    /**
     * Get the action triggers
     *
     * @return array
     */
    abstract static function getTriggers(): array;

    /**
     * Add valid action based where clause to limit list of objects to act on.
     *
     * @param array $where current where clause
     * @param array $actionValue
     * @return array
     */
    public function addWhereArray(array $where, array $actionValue): array
    {
        return $where;
    }

    /**
     * Trigger Long Runner Automation Rule
     *
     * @param AutomationTrigger $triggerClass
     * @param bool $firstRun
     * @return array|string[]
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function triggerLongRunnerRule(AutomationTrigger $triggerClass, bool $firstRun = false): array
    {
        if (
            !$triggerClass instanceof TimedAutomationTriggerInterface &&
            $this->dispatchType === AutomationRuleDispatchesModel::TYPE_TRIGGERED
        ) {
            throw new \Exception(
                "The trigger must implement the TimedAutomationTriggerInterface or triggered manually."
            );
        }
        $automationRule = $this->getAutomationRule();
        $longRunnerParams = [
            "triggerType" => $automationRule["trigger"]["triggerType"],
            "actionType" => $automationRule["action"]["actionType"],
            "automationRuleID" => $automationRule["automationRuleID"],
        ];

        $count = $this->getLongRunnerRuleItemCount($firstRun, $triggerClass, $longRunnerParams);
        if ($count > 0) {
            $this->logDispatched(AutomationRuleDispatchesModel::STATUS_QUEUED, null, [
                "affectedRecordType" => $this->affectedRecordType,
                "estimatedRecordCount" => $count,
            ]);
            $longRunnerParams["dispatchUUID"] = $this->getDispatchUUID();
            $longRunnerParams["dispatchType"] = $this->dispatchType;
            // Only run the long runner if there are actionable items.
            $runRuleAction = new LongRunnerAction(
                AutomationRuleLongRunnerGenerator::class,
                $triggerClass->getLongRunnerMethod(),
                [null, null, $longRunnerParams]
            );

            $trackingData = $this->longRunner->runDeferred($runRuleAction);
            if (is_object($trackingData)) {
                $jobID = $trackingData->getTrackingID();
                if (!empty($jobID)) {
                    $this->automationRuleDispatchesModel->update(
                        ["dispatchedJobID" => $jobID],
                        ["automationRuleDispatchUUID" => $this->getDispatchUUID()]
                    );
                }
            }

            return $this->automationRuleDispatchesModel->getAutomationRuleDispatchByUUID($this->getDispatchUUID());
        }
        // This is a special case where the rule is activated for the first time and there are no actionable items.
        if (
            $firstRun &&
            $this->dispatchType === AutomationRuleDispatchesModel::TYPE_INITIAL &&
            $count === 0 &&
            $triggerClass instanceof TimedAutomationTriggerInterface
        ) {
            //Create a dispatch entry with status of warning to indicate that the rule is active but no actionable items found.
            $this->logDispatched(
                AutomationRuleDispatchesModel::STATUS_WARNING,
                "initial dispatch with 0 entries for future iterations ",
                [
                    "affectedRecordType" => $this->affectedRecordType,
                    "estimatedRecordCount" => 0,
                    "affectedRecordCount" => 0,
                ]
            );
        }
        // Log a debug message if no actionable items found for the automation rule (triggered manually
        $this->logger->debug("No actionable items found for the automation rule.", [
            "tags" => ["automationRules"],
            "automationRuleID" => $automationRule["automationRuleID"],
            "automationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
            "dispatchType" => $this->dispatchType,
            "DateTime" => CurrentTimeStamp::getDateTime(),
        ]);
        return ["message" => "No actionable items found."];
    }

    /**
     * Get the count of records to be processed.
     *
     * @param bool $isFirstRun
     * @param AutomationTrigger $triggerClass
     * @param array $longRunnerParams
     * @return int
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     */
    public function getLongRunnerRuleItemCount(
        bool $isFirstRun,
        AutomationTrigger $triggerClass,
        array &$longRunnerParams
    ): int {
        $automationRule = $this->getAutomationRule();
        $lastRunDate = null;
        if ($triggerClass instanceof TimedAutomationTriggerInterface) {
            // If not first run, check the last run date for the offsets
            if (!$isFirstRun) {
                $lastRunDate = $this->calculateTimeInterval(
                    $automationRule["automationRuleID"],
                    $automationRule["automationRuleRevisionID"]
                );
            }
            $where = $triggerClass->getWhereArray($automationRule["trigger"]["triggerValue"], $lastRunDate);
            $where = $this->addWhereArray($where, $automationRule["action"]["actionValue"] ?? []);

            // Set the time interval to be passed to the long runner
            $longRunnerParams["lastRunDate"] = $lastRunDate;
        } else {
            $automationRule = $this->getAutomationRule();
            $where = $automationRule["trigger"]["triggerValue"];
        }
        return $triggerClass->getRecordCountsToProcess($where);
    }

    /**
     * Calculate the time interval for the trigger
     *
     * @param int $automationRuleID
     * @param int $automationRevisionID
     * @return \DateTimeImmutable|null
     * @throws ContainerException
     * @throws NotFoundException
     */
    private function calculateTimeInterval(int $automationRuleID, int $automationRevisionID): ?\DateTimeImmutable
    {
        $lastRun = $this->automationRuleDispatchesModel->getAutomationRuleDispatches([
            "automationRuleID" => $automationRuleID,
            "automationRuleRevisionID" => $automationRevisionID,
            "sort" => ["-dateDispatched"],
            "statuses" => [
                AutomationRuleDispatchesModel::STATUS_SUCCESS,
                AutomationRuleDispatchesModel::STATUS_WARNING,
            ],
            "offset" => 0,
            "limit" => 1,
        ]);
        $lastRunDate = $lastRun[0]["dateFinished"] ?? null;
        if ($lastRunDate) {
            return $lastRunDate = new \DateTimeImmutable($lastRunDate);
        }
        return null;
    }

    /**
     * Expand log Data
     *
     * @param array $logData
     * @return string
     */
    public function expandLogData(array $logData): string
    {
        return "&nbsp;";
    }
}
