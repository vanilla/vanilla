<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace AutomationRules\Actions;

use Vanilla\AutomationRules\Actions\AddDiscussionToCollectionAction;
use Vanilla\AutomationRules\Triggers\LastActiveDiscussionTrigger;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Models\CollectionRecordModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use LogModel;

class AddDiscussionToCollectionActionTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    private CollectionRecordModel $collectionRecordModel;
    private AutomationRuleDispatchesModel $automationRuleDispatchesModel;

    private LogModel $logModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->collectionRecordModel = $this->container()->get(CollectionRecordModel::class);
        $this->automationRuleDispatchesModel = $this->container()->get(AutomationRuleDispatchesModel::class);
        $this->logModel = $this->container()->get(LogModel::class);
    }

    /**
     * Test that only valid discussions are added to a collection.
     *
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Schema\ValidationException
     */
    public function testAddDiscussionToCollectionAction()
    {
        CurrentTimeStamp::mockTime(strtotime("-2 days"));
        $this->createCategory();

        //Create 3 discussions

        $discussion1 = $this->createDiscussion(
            [
                "categoryID" => $this->lastInsertedCategoryID,
            ],
            ["Type" => "discussion"]
        );
        // We don't provide Type for below records as we need to make sure that it gets selected when type is left empty.
        $discussion2 = $this->createDiscussion([
            "categoryID" => $this->lastInsertedCategoryID,
        ]);
        $discussion3 = $this->createDiscussion([
            "categoryID" => $this->lastInsertedCategoryID,
            "DateInserted" => CurrentTimeStamp::getMySQL(),
        ]);

        // Create 2 different collection

        /**
         *  Discussion1 is part of collection1 and collection2
         *  Discussion2 is part of collection2
         *  Discussion3 is not part of any collection
         */

        $collection1 = $this->createCollection(
            [["recordID" => $discussion1["discussionID"], "recordType" => "discussion"]],
            ["name" => "collection A"]
        );

        $collection2 = $this->createCollection(
            [
                ["recordID" => $discussion1["discussionID"], "recordType" => "discussion"],
                ["recordID" => $discussion2["discussionID"], "recordType" => "discussion"],
            ],
            ["name" => "collection B"]
        );

        // Create the automation rule
        $automationRecord = [
            "name" => "Add Discussion To Collection Automation Rule",
            "trigger" => [
                "triggerType" => LastActiveDiscussionTrigger::getType(),
                "triggerValue" => [
                    "applyToNewContentOnly" => false,
                    "triggerTimeLookBackLimit" => [
                        "length" => 3,
                        "unit" => "day",
                    ],
                    "triggerTimeDelay" => [
                        "length" => 1,
                        "unit" => "day",
                    ],
                    "postType" => ["discussion"],
                ],
            ],
            "action" => [
                "actionType" => AddDiscussionToCollectionAction::getType(),
                "actionValue" => [
                    "collectionID" => [$collection1["collectionID"], $collection2["collectionID"]],
                ],
            ],
        ];

        $result = $this->api()->post("automation-rules", $automationRecord);
        $this->assertEquals(201, $result->getStatusCode());
        $automationRule = $result->getBody();

        CurrentTimeStamp::clearMockTime();

        // Trigger the rule manually
        $this->api()->post("automation-rules/{$automationRule["automationRuleID"]}/trigger");

        // Make sure a dispatch is generated and only the discussions that were not a part of the collection is added to the collection
        $dispatches = $this->automationRuleDispatchesModel->getRecentDispatchByAutomationRuleRevisionIDs([
            $automationRule["automationRuleRevisionID"],
        ]);

        // Make sure that the dispatch is generated and all the discussions were selected for processing
        $this->assertCount(1, $dispatches);
        $dispatches = array_shift($dispatches);
        $dispatches["attributes"] = json_decode($dispatches["attributes"], true);
        $this->assertEquals(3, $dispatches["attributes"]["estimatedRecordCount"]);

        // As discussion 1 was already part of both the collections,It should not be processed
        $this->assertEquals(2, $dispatches["attributes"]["affectedRecordCount"]);

        // Get the logs for the dispatch
        $logs = $this->logModel->getWhere([
            "DispatchUUID" => $dispatches["automationRuleDispatchUUID"],
            "AutomationRuleRevisionID" => $dispatches["automationRuleRevisionID"],
        ]);
        $this->assertCount(2, $logs);

        foreach ($logs as $log) {
            if ($log["RecordID"] == $discussion2["discussionID"]) {
                // Make sure Discussion 2 was added to only collection 1
                $this->assertEquals(
                    [$collection1["collectionID"]],
                    $log["Data"]["addDiscussionToCollection"]["collectionID"]
                );
            } else {
                $this->assertEquals($discussion3["discussionID"], $log["RecordID"]);
                $this->assertEquals(
                    [$collection1["collectionID"], $collection2["collectionID"]],
                    $log["Data"]["addDiscussionToCollection"]["collectionID"]
                );
            }
        }
    }
}
