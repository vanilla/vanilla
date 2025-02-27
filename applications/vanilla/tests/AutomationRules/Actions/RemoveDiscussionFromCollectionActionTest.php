<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace AutomationRules\Actions;

use Vanilla\AutomationRules\Actions\RemoveDiscussionFromCollectionAction;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\AutomationRules\Triggers\LastActiveDiscussionTrigger;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\CurrentTimeStamp;
use Vanilla\Models\CollectionRecordModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\SiteTestCase;
use LogModel;

class RemoveDiscussionFromCollectionActionTest extends SiteTestCase
{
    use TestDiscussionModelTrait, CommunityApiTestTrait;

    private CollectionRecordModel $collectionRecordModel;
    private AutomationRuleDispatchesModel $automationRuleDispatchesModel;

    private LogModel $logModel;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->collectionRecordModel = $this->container()->get(CollectionRecordModel::class);
        $this->automationRuleDispatchesModel = $this->container()->get(AutomationRuleDispatchesModel::class);
        $this->logModel = $this->container()->get(LogModel::class);
        self::resetTable("collection");
        self::resetTable("collectionRecord");
        self::resetTable("Discussion");
    }

    /**
     * Test that a discussion is removed from a collection when it becomes stale.
     *
     * @return void
     */
    public function testStaleDiscussionRemoveFromCollection(): void
    {
        CurrentTimeStamp::mockTime("2020-01-01");

        //create  category
        $this->createCategory();

        //create discussions
        $discussions = $this->insertDiscussions(5, [
            "CategoryID" => $this->lastInsertedCategoryID,
            "DateInserted" => CurrentTimeStamp::getMySQL(),
            "Type" => "discussion",
        ]);

        //create a collection
        $records = $this->getCollectionRecordsFromDiscussionData($discussions);
        $collection = $this->createCollection($records);

        $this->assertEquals(5, $this->collectionRecordModel->getCount(["CollectionID" => $collection["collectionID"]]));

        //set current time to now
        // Now move time forward by 2.5 days
        CurrentTimeStamp::mockTime("2020-01-03 12:00:00");

        //Create stale Discussion Trigger rule
        $automationRecord = [
            "name" => "Stale Discussion Automation Rule",
            "trigger" => [
                "triggerType" => StaleDiscussionTrigger::getType(),
                "triggerValue" => [
                    "applyToNewContentOnly" => false,
                    "triggerTimeLookBackLimit" => [
                        "length" => 3,
                        "unit" => "day",
                    ],
                    "triggerTimeDelay" => [
                        "length" => 2,
                        "unit" => "day",
                    ],
                    "postType" => ["discussion"],
                ],
            ],
            "action" => [
                "actionType" => RemoveDiscussionFromCollectionAction::getType(),
                "actionValue" => [
                    "collectionID" => [$collection["collectionID"]],
                ],
            ],
            "status" => AutomationRuleModel::STATUS_INACTIVE,
        ];
        $result = $this->api()->post("automation-rules", $automationRecord);
        $this->assertEquals(201, $result->getStatusCode());
        $StaleDiscussionRule = $result->getBody();

        // Now test the record Count
        $staleDiscussionTrigger = new StaleDiscussionTrigger();
        $where = $staleDiscussionTrigger->getWhereArray($StaleDiscussionRule["trigger"]["triggerValue"]);

        $this->assertEquals(5, $staleDiscussionTrigger->getRecordCountsToProcess($where));

        //Now trigger the rule manually
        $this->api()->post("automation-rules/{$StaleDiscussionRule["automationRuleID"]}/trigger");
        // Now make sure all the records are removed from the collection
        $this->assertEquals(0, $this->collectionRecordModel->getCount(["CollectionID" => $collection["collectionID"]]));
    }

    /**
     * Test that a discussion is removed from a collection when its part of a collection.
     *
     * @return void
     */
    public function testRemoveDiscussionFromCollectionAction()
    {
        CurrentTimeStamp::mockTime("2020-01-01");

        $category = $this->createCategory();
        // Create 3 Discussions
        $discussion1 = $this->createDiscussion([
            "categoryID" => $category["categoryID"],
        ]);
        $discussion2 = $this->createDiscussion([
            "categoryID" => $category["categoryID"],
        ]);
        $discussion3 = $this->createDiscussion([
            "categoryID" => $category["categoryID"],
        ]);

        // Now create two collections
        // Collection 1 has discussion 1 and 2
        $collection1 = $this->createCollection([
            ["RecordID" => $discussion1["discussionID"], "RecordType" => "discussion"],
            ["RecordID" => $discussion2["discussionID"], "RecordType" => "discussion"],
        ]);
        // Collection 2 has only discussion 2
        $collection2 = $this->createCollection([
            ["RecordID" => $discussion2["discussionID"], "RecordType" => "discussion"],
        ]);
        // Discusssion 3 is not part of any collection

        // Move time forwards 10 days
        CurrentTimeStamp::mockTime("2020-01-11");

        // Create the automation rule
        $automationRecord = [
            "name" => "Remove Discussion from Collection Automation Rule",
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
                "actionType" => RemoveDiscussionFromCollectionAction::getType(),
                "actionValue" => [
                    "collectionID" => [$collection1["collectionID"], $collection2["collectionID"]],
                ],
            ],
        ];

        $result = $this->api()->post("automation-rules", $automationRecord);
        $this->assertEquals(201, $result->getStatusCode());
        $automationRule = $result->getBody();

        // Run the automation rule manually
        $this->api()->post("automation-rules/{$automationRule["automationRuleID"]}/trigger");

        // Get the dispatch Record
        $dispatches = $this->automationRuleDispatchesModel->getRecentDispatchByAutomationRuleRevisionIDs([
            $automationRule["automationRuleRevisionID"],
        ]);
        $dispatches = array_shift($dispatches);
        $dispatches["attributes"] = json_decode($dispatches["attributes"], true);
        $this->assertEquals(3, $dispatches["attributes"]["estimatedRecordCount"]);
        $this->assertEquals(2, $dispatches["attributes"]["affectedRecordCount"]);

        // Get the log data
        $logs = $this->logModel->getWhere([
            "DispatchUUID" => $dispatches["automationRuleDispatchUUID"],
            "AutomationRuleRevisionID" => $dispatches["automationRuleRevisionID"],
        ]);
        $logRecordIDS = array_column($logs, "RecordID");
        $this->assertNotContains($discussion3["discussionID"], $logRecordIDS);

        foreach ($logs as $log) {
            if ($log["RecordID"] == $discussion1["discussionID"]) {
                $this->assertEquals(
                    [$collection1["collectionID"]],
                    $log["Data"]["removeDiscussionFromCollection"]["collectionID"]
                );
            } else {
                $this->assertEquals(
                    [$collection1["collectionID"], $collection2["collectionID"]],
                    $log["Data"]["removeDiscussionFromCollection"]["collectionID"]
                );
            }
        }
    }

    /**
     * Test that a discussion is removed from a collection when it is inactive.
     *
     * @return void
     */
    public function testLastActiveDiscussionRemoveFromCollection()
    {
        // Move back time to a month ago
        CurrentTimeStamp::mockTime("2020-01-01");
        //create  category
        $this->createCategory();

        //create discussions under category 2
        $discussions = $this->insertDiscussions(5, [
            "CategoryID" => $this->lastInsertedCategoryID,
            "DateInserted" => CurrentTimeStamp::getMySQL(),
            "Type" => "discussion",
        ]);

        //create some comments for first 3 discussions
        foreach ($discussions as $key => $discussion) {
            if ($key == 3) {
                break;
            }
            $this->createComment(["discussionID" => $discussion["DiscussionID"]]);
        }

        //Add recent comments to the last 2 discussions
        $now = strtotime("-2 day");
        CurrentTimeStamp::mockTime($now);

        foreach ($discussions as $key => $discussion) {
            if ($key < 3) {
                continue;
            }
            $this->createComment(["discussionID" => $discussion["DiscussionID"], "dateInserted" => $now]);
        }

        $records = $this->getCollectionRecordsFromDiscussionData($discussions);
        $collection = $this->createCollection($records);
        $this->assertEquals(5, $this->collectionRecordModel->getCount(["CollectionID" => $collection["collectionID"]]));

        // Move time forwards a month
        CurrentTimeStamp::mockTime("2020-02-01");

        //Create Last Active Discussion Trigger rule
        $automationRecord = [
            "name" => "Last Active Discussion Automation Rule",
            "trigger" => [
                "triggerType" => LastActiveDiscussionTrigger::getType(),
                "triggerValue" => [
                    "applyToNewContentOnly" => false,
                    "triggerTimeLookBackLimit" => [
                        "length" => 7,
                        "unit" => "week",
                    ],
                    "triggerTimeDelay" => [
                        "length" => 3,
                        "unit" => "week",
                    ],
                    "postType" => ["discussion"],
                ],
            ],
            "action" => [
                "actionType" => RemoveDiscussionFromCollectionAction::getType(),
                "actionValue" => [
                    "collectionID" => [$collection["collectionID"]],
                ],
            ],
            "status" => AutomationRuleModel::STATUS_INACTIVE,
        ];

        $result = $this->api()->post("automation-rules", $automationRecord);
        $this->assertEquals(201, $result->getStatusCode());
        $LastActiveDiscussionRule = $result->getBody();

        // Now test the record Count
        $staleDiscussionTrigger = new LastActiveDiscussionTrigger();
        $where = $staleDiscussionTrigger->getWhereArray($LastActiveDiscussionRule["trigger"]["triggerValue"]);

        $this->assertEquals(3, $staleDiscussionTrigger->getRecordCountsToProcess($where));

        // Initiate a manual trigger
        $this->api()->post("automation-rules/{$LastActiveDiscussionRule["automationRuleID"]}/trigger");

        // Now make only 3 records are removed from the collection
        $this->assertEquals(2, $this->collectionRecordModel->getCount(["CollectionID" => $collection["collectionID"]]));
    }

    /**
     * Create collection record value form discussion data
     *
     * @param array $discussionData
     * @return array
     */
    private function getCollectionRecordsFromDiscussionData(array $discussionData)
    {
        $collectionRecords = [];
        foreach ($discussionData as $discussion) {
            $collectionRecords[] = [
                "RecordID" => $discussion["DiscussionID"],
                "RecordType" => "discussion",
            ];
        }
        return $collectionRecords;
    }
}
