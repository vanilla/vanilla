<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace AutomationRules\Actions;

use Vanilla\AutomationRules\Actions\AddTagToDiscussionAction;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use VanillaTests\SiteTestCase;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use TagModel;
use LogModel;

/**
 * Test the AddTagToDiscussionAction.
 */
class AddTagToDiscussionTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    private TagModel $tagModel;
    private AutomationRuleDispatchesModel $automationRuleDispatchesModel;

    private LogModel $logModel;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tagModel = $this->container()->get(TagModel::class);
        $this->automationRuleDispatchesModel = $this->container()->get(AutomationRuleDispatchesModel::class);
        $this->logModel = $this->container()->get(LogModel::class);
    }

    /**
     * Test that a tag is added to a discussion.
     *
     * @return void
     */
    public function testAddTagToDiscussionAction()
    {
        CurrentTimeStamp::mockTime(strtotime("-10 days"));
        $this->createCategory();

        // Create 3 discussions
        $discussion1 = $this->createDiscussion([
            "categoryID" => $this->lastInsertedCategoryID,
        ]);
        $discussion2 = $this->createDiscussion([
            "categoryID" => $this->lastInsertedCategoryID,
        ]);
        $discussion3 = $this->createDiscussion([
            "categoryID" => $this->lastInsertedCategoryID,
        ]);

        // Create 2 tags
        $tag1 = $this->createTag(["name" => "Tag1"]);
        $tag2 = $this->createTag(["name" => "Tag2"]);

        // Add the tag to the discussion
        $this->tagModel->addDiscussion($discussion1["discussionID"], [$tag1["tagID"], $tag2["tagID"]]);
        $this->tagModel->addDiscussion($discussion2["discussionID"], [$tag1["tagID"]]);

        CurrentTimeStamp::clearMockTime();

        // Create a new automation rule

        $automationRecord = [
            "name" => "Add Discussion To Collection Automation Rule",
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
                "actionType" => AddTagToDiscussionAction::getType(),
                "actionValue" => [
                    "tagID" => [$tag1["tagID"], $tag2["tagID"]],
                ],
            ],
        ];

        $result = $this->api()->post("automation-rules", $automationRecord);
        $this->assertEquals(201, $result->getStatusCode());
        $automationRule = $result->getBody();

        // Trigger the rule manually
        $this->api()->post("automation-rules/{$automationRule["automationRuleID"]}/trigger");

        $dispatches = $this->automationRuleDispatchesModel->getRecentDispatchByAutomationRuleRevisionIDs([
            $automationRule["automationRuleRevisionID"],
        ]);

        $this->assertCount(1, $dispatches);

        $dispatches = array_shift($dispatches);
        $dispatches["attributes"] = json_decode($dispatches["attributes"], true);

        $this->assertEquals(3, $dispatches["attributes"]["estimatedRecordCount"]);
        // It should only affect 2 records because the first discussion already has the tags
        $this->assertEquals(2, $dispatches["attributes"]["affectedRecordCount"]);

        $logs = $this->logModel->getWhere([
            "DispatchUUID" => $dispatches["automationRuleDispatchUUID"],
            "AutomationRuleRevisionID" => $dispatches["automationRuleRevisionID"],
        ]);
        $this->assertCount(2, $logs);
        // Iterate over the logs and check if the tags were added to the discussions
        foreach ($logs as $log) {
            if ($log["RecordID"] == $discussion2["discussionID"]) {
                // Make sure Discussion 2 got only applied with Tag 2
                $this->assertEquals([$tag2["tagID"]], $log["Data"]["addTagToDiscussion"]["tagID"]);
            } else {
                // Make sure Discussion 3 got applied with both tags
                $this->assertEquals($discussion3["discussionID"], $log["RecordID"]);
                $this->assertEquals([$tag1["tagID"], $tag2["tagID"]], $log["Data"]["addTagToDiscussion"]["tagID"]);
            }
        }
    }
}
