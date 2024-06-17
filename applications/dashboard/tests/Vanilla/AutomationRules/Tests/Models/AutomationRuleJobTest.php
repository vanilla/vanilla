<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\AutomationRules;

use Exception;
use Vanilla\AutomationRules\Actions\MoveDiscussionToCategoryAction;
use Vanilla\AutomationRules\Actions\RemoveDiscussionFromTriggerCollectionAction;
use Vanilla\AutomationRules\Triggers\StaleCollectionTrigger;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\AutomationRules\Jobs\AutomationRuleJob;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the AutomationRuleJob class.
 */
class AutomationRuleJobTest extends SiteTestCase
{
    use AutomationRulesTestTrait, TestDiscussionModelTrait, CommunityApiTestTrait;

    private array $testData;
    private AutomationRuleJob $automationRuleJob;

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->initialize();
        $this->testData = $this->createTestData();
        $this->automationRuleJob = $this->container()->get(AutomationRuleJob::class);
    }

    /**
     * Create test data for automation Job test
     *
     * @return array
     * @throws Exception
     */
    private function createTestData(): array
    {
        $data = [];
        // Create multiple timed rules

        // Mock the time to 3 days ago
        $now = time() - 3600 * 24 * 3;
        CurrentTimeStamp::mockTime($now);

        // Create a category to hold active discussions
        $this->createCategory([
            "name" => "Active Discussion Category",
        ]);
        $activeCategoryId = $this->lastInsertedCategoryID;

        // Create a category to move inactive discussions
        $this->createCategory([
            "name" => "Inactive Discussion Category",
        ]);
        $inactiveCategoryId = $this->lastInsertedCategoryID;
        $data["categories"]["active"] = $activeCategoryId;
        $data["categories"]["inactive"] = $inactiveCategoryId;

        // Create 5 new discussions in the active category
        $discussions = $this->insertDiscussions(5, [
            "Type" => "discussion",
            "Name" => "Common Discussion for - %s",
            "CategoryID" => $activeCategoryId,
            "DateInserted" => CurrentTimeStamp::getMySQL(),
        ]);
        $data["discussions"] = [];
        $records = [];
        foreach ($discussions as $discussion) {
            $records[] = [
                "recordID" => $discussion["DiscussionID"],
                "recordType" => "discussion",
                "dateInserted" => CurrentTimeStamp::getMySQL(),
            ];
            $data["discussions"][] = $discussion["DiscussionID"];
        }

        // Create a collection to hold active discussions
        $this->createCollection($records, [
            "name" => "Active Discussion Collection",
            "dateInserted" => CurrentTimeStamp::getMySQL(),
        ]);
        $collectionID = $this->lastInsertedCollectionID;
        $data["collection"]["active"] = $collectionID;

        // Create a automation rule for stale discussion trigger
        $this->createAutomationRule(
            [
                "type" => StaleDiscussionTrigger::getType(),
                "value" => [
                    "maxTimeThreshold" => "5",
                    "maxTimeUnit" => "day",
                    "triggerTimeThreshold" => "1",
                    "triggerTimeUnit" => "day",
                    "postType" => ["discussion"],
                ],
            ],
            [
                "type" => MoveDiscussionToCategoryAction::getType(),
                "value" => [
                    "categoryID" => $inactiveCategoryId,
                ],
            ]
        );
        $data["rules"]["staleDiscussion"] = $this->lastRuleID;

        // Create an automation rule for stale collection trigger
        $this->createAutomationRule(
            [
                "type" => StaleCollectionTrigger::getType(),
                "value" => [
                    "maxTimeThreshold" => "5",
                    "maxTimeUnit" => "day",
                    "triggerTimeThreshold" => "1",
                    "triggerTimeUnit" => "day",
                    "collectionID" => [$collectionID],
                ],
            ],
            [
                "type" => RemoveDiscussionFromTriggerCollectionAction::getType(),
                "value" => [],
            ]
        );
        $data["rules"]["staleCollection"] = $this->lastRuleID;

        // Clear the time back to orginal.
        CurrentTimeStamp::clearMockTime();

        return $data;
    }

    /**
     * Test automation rule job
     *
     * @return void
     */
    public function testAutomationRuleJob()
    {
        // Verify the records before testing;
        $discussion = $this->discussionModel->getID($this->testData["discussions"][0], DATASET_TYPE_ARRAY);
        // Make sure the categoryID for the discussion is the active category
        $this->assertEquals($this->testData["categories"]["active"], $discussion["CategoryID"]);
        // Make sure the active collection has records
        $response = $this->api()->get("/collections/{$this->testData["collection"]["active"]}");
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertCount(5, $body["records"]);

        // Run the automation job
        $result = $this->automationRuleJob->run();
        $this->assertEquals("success", $result);

        // Verify that there were 2 dispatches
        $dispatches = $this->automationRuleDispatchesModel->select([], ["orderFields" => ["automationRuleID"]]);
        $this->assertCount(2, $dispatches);
        foreach ($dispatches as $key => $dispatch) {
            $this->assertEquals("success", $dispatch["status"]);
            $this->assertEquals("triggered", $dispatch["dispatchType"]);
            $this->assertNotEmpty($dispatch["dispatchedJobID"]);
            $this->assertEquals("Discussion", $dispatch["attributes"]["affectedRecordType"]);
            $this->assertEquals(count($this->testData["discussions"]), $dispatch["attributes"]["affectedRecordCount"]);
            $this->assertEquals(
                $key == 0 ? $this->testData["rules"]["staleDiscussion"] : $this->testData["rules"]["staleCollection"],
                $dispatch["automationRuleID"]
            );
        }

        // Check affected rows from the `automation-rules/dispatched` GET API endpoint.
        $apiRecords = $this->api()
            ->get("automation-rules/dispatches")
            ->getBody();
        $expectedAffectedRows = ["post" => 5];
        foreach ($apiRecords as $apiRecord) {
            $this->assertEquals($expectedAffectedRows, $apiRecord["affectedRows"]);
        }

        // Now verify the records after testing

        // Make sure the discussion is moved to the inactive category
        $discussion = $this->discussionModel->getID($this->testData["discussions"][0], DATASET_TYPE_ARRAY);
        $this->assertEquals($this->testData["categories"]["inactive"], $discussion["CategoryID"]);

        // Make sure the active collection has no records
        $response = $this->api()->get("/collections/{$this->testData["collection"]["active"]}");
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertCount(0, $body["records"]);
    }
}
