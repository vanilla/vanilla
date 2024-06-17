<?php

namespace VanillaTests\AutomationRules\Actions;

use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Vanilla\AutomationRules\Triggers\LastActiveDiscussionTrigger;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\SiteTestCase;

class BumpDiscussionActionTest extends SiteTestCase
{
    use TestDiscussionModelTrait, CommunityApiTestTrait;

    private AutomationRuleDispatchesModel $automationRuleDispatchesModel;
    public function setUp(): void
    {
        parent::setUp();
        $this->automationRuleDispatchesModel = $this->container()->get(AutomationRuleDispatchesModel::class);
    }

    /**
     * Test that a discussion is bumped when it becomes stale.
     *
     * @return void
     */
    public function testManualBumpDiscussionAction()
    {
        $this->createCategory();

        // Move back time 4 day
        $now = time() - 3600 * 24 * 4;
        CurrentTimeStamp::mockTime($now);

        $discussionSet1 = $this->insertDiscussions(3, [
            "CategoryID" => $this->lastInsertedCategoryID,
            "DateInserted" => CurrentTimeStamp::getMySQL(),
            "Type" => "discussion",
        ]);

        CurrentTimeStamp::mockTime(strtotime("-2 days"));

        $discussionSet2 = $this->insertDiscussions(2, [
            "CategoryID" => $this->lastInsertedCategoryID,
            "DateInserted" => CurrentTimeStamp::getMySQL(),
            "Type" => "discussion",
        ]);

        // Set current time to now
        CurrentTimeStamp::clearMockTime();

        // Create the automation rule
        $automationRecord = [
            "name" => "Stale Discussion Automation Rule",
            "trigger" => [
                "triggerType" => LastActiveDiscussionTrigger::getType(),
                "triggerValue" => [
                    "maxTimeThreshold" => 5,
                    "maxTimeUnit" => "day",
                    "triggerTimeThreshold" => 4,
                    "triggerTimeUnit" => "day",
                    "postType" => ["discussion"],
                ],
            ],
            "action" => [
                "actionType" => BumpDiscussionAction::getType(),
            ],
            "status" => AutomationRuleModel::STATUS_INACTIVE,
        ];

        $result = $this->api()->post("automation-rules", $automationRecord);
        $this->assertEquals(201, $result->getStatusCode());
        $LastActiveDiscussionRule = $result->getBody();

        $lastActiveDiscussionTrigger = new LastActiveDiscussionTrigger();
        $where = $lastActiveDiscussionTrigger->getWhereArray($LastActiveDiscussionRule["trigger"]["triggerValue"]);
        $this->assertEquals(3, $lastActiveDiscussionTrigger->getRecordCountsToProcess($where));

        //Now trigger the rule manually
        $result = $this->api()->post("automation-rules/{$LastActiveDiscussionRule["automationRuleID"]}/trigger");
        $triggeredData = $result->getBody();
        $dispatchRecord = $this->automationRuleDispatchesModel->getAutomationRuleDispatchByUUID(
            $triggeredData["automationRuleDispatchUUID"]
        );
        $this->assertEquals(AutomationRuleDispatchesModel::STATUS_SUCCESS, $dispatchRecord["status"]);
        $this->assertEquals(3, $dispatchRecord["attributes"]["affectedRecordCount"]);
    }
}
