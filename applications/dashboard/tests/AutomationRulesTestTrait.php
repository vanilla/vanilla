<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\AutomationRules;

use Exception;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Gdn_Configuration;
use Ramsey\Uuid\Uuid;
use Vanilla\AutomationRules\Actions\AutomationAction;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Dashboard\Models\AutomationRuleRevisionModel;

trait AutomationRulesTestTrait
{
    /** @var int|null */
    protected $lastRuleID = null;

    protected AutomationRuleModel $automationRuleModel;
    protected AutomationRuleRevisionModel $automationRuleRevisionModel;

    protected AutomationRuleDispatchesModel $automationRuleDispatchesModel;

    /**
     * Initialize the trait. Should be used in the setUp method of a test.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function initialize(): void
    {
        $this->automationRuleModel = self::container()->get(AutomationRuleModel::class);
        $this->automationRuleRevisionModel = self::container()->get(AutomationRuleRevisionModel::class);
        $this->automationRuleDispatchesModel = self::container()->get(AutomationRuleDispatchesModel::class);

        /** @var Gdn_Configuration $config */
        $config = self::container()->get(Gdn_Configuration::class);
        $config->set("Feature.AutomationRules.Enabled", true);
        $this->resetTable("automationRule");
        $this->resetTable("automationRuleRevision");
        $this->resetTable("automationRuleDispatches");
    }

    /**
     * Create an automation rule through the DB.
     * @param array $trigger
     * @param array $action
     * @param bool $active
     * @param array $options
     * @return array
     * @throws Exception
     */
    protected function createAutomationRule(
        array $trigger,
        array $action,
        bool $active = true,
        array $options = []
    ): array {
        if ($this->lastRuleID === null) {
            $this->lastRuleID = $this->automationRuleModel->getMaxAutomationRuleID();
        }
        $automationRevision = [
            "automationRuleID" => ++$this->lastRuleID,
            "triggerType" => $trigger["type"],
            "triggerValue" => $trigger["value"],
            "actionType" => $action["type"],
            "actionValue" => $action["value"],
        ];
        $triggerRevisionID = $this->automationRuleRevisionModel->insert($automationRevision);
        $automationRule = [
            "name" => "Test Recipe - {$automationRevision["automationRuleID"]}",
            "automationRuleID" => $this->lastRuleID,
            "automationRuleRevisionID" => $triggerRevisionID,
            "status" => $active ? AutomationRuleModel::STATUS_ACTIVE : AutomationRuleModel::STATUS_INACTIVE,
        ];

        $automationRule = array_merge($automationRule, $options);

        $this->lastRuleID = $this->automationRuleModel->insert($automationRule);

        return $automationRule;
    }

    /**
     * Create an automation rule dispatches through the DB.
     *
     * @param array $options
     * @param int $count
     *
     * @return void
     * @throws Exception
     */
    protected function createAutomationDispatches(array $options, int $count = 1): void
    {
        $defaults = [
            "dispatchType" => "triggered",
            "dateDispatched" => date("Y-m-d H:i:s"),
            "status" => AutomationRuleDispatchesModel::STATUS_QUEUED,
        ];
        for ($index = 0; $index < $count; $index++) {
            $automationDispatchRow = array_merge($defaults, $options);
            if (!isset($automationDispatchRow["automationRuleDispatchUUID"])) {
                $automationDispatchRow["automationRuleDispatchUUID"] = Uuid::uuid4()->toString();
            }
            if (!isset($automationDispatchRow["dispatchedJobID"])) {
                $automationDispatchRow["dispatchedJobID"] = Uuid::uuid4()->toString();
            }
            $this->automationRuleDispatchesModel->insert($automationDispatchRow);
        }
    }

    /**
     * Get logs for the dispatched rules
     *
     * @param array $conditions
     * @return array
     * @throws Exception
     */
    public function getLogs(array $conditions = []): array
    {
        if (empty($conditions)) {
            $conditions = ["Operation" => "Automation"];
        }
        return $this->logModel->getWhere($conditions);
    }

    /**
     * Get dispatched rules
     *
     * @param ?int $automationRuleID
     * @param array $status
     * @param string $dispatchType
     * @return array
     */
    public function getDispatchedRules(
        ?int $automationRuleID = null,
        array $status = ["success", "partial", "failed"],
        string $dispatchType = AutomationRuleDispatchesModel::TYPE_TRIGGERED
    ): array {
        $where = [
            "dispatchType" => $dispatchType,
            "status" => $status,
        ];
        if ($automationRuleID !== null) {
            $where["automationRuleID"] = $automationRuleID;
        }

        return $this->automationRuleDispatchesModel->select($where, [
            "orderFields" => "dateFinished",
            "orderDirection" => "asc",
        ]);
    }

    /**
     * Trigger an action on a single discussion and returns it.
     *
     * @param string $actionType
     * @param array $actionValues
     * @return array
     */
    public function triggerDiscussionAction(
        string $actionType,
        array $actionValues = [],
        array $discussionOverrides = []
    ): array {
        CurrentTimeStamp::mockTime(strtotime("-10 days"));
        $discussion = $this->createDiscussion($discussionOverrides);
        CurrentTimeStamp::clearMockTime();

        $automationRecord = [
            "name" => "Stale Discussion Automation Rule",
            "trigger" => [
                "triggerType" => StaleDiscussionTrigger::getType(),
                "triggerValue" => [
                    "applyToNewContentOnly" => false,
                    "triggerTimeLookBackLimit" => [
                        "length" => 12,
                        "unit" => "day",
                    ],
                    "triggerTimeDelay" => [
                        "length" => 6,
                        "unit" => "day",
                    ],
                    "postType" => ["discussion"],
                ],
            ],
            "action" => [
                "actionType" => $actionType,
                "actionValue" => $actionValues,
            ],
            "status" => AutomationRuleModel::STATUS_ACTIVE,
        ];

        $this->api()->post("automation-rules", $automationRecord);

        return $discussion;
    }
}
