<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace AutomationRules\Triggers;

use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Vanilla\AutomationRules\Actions\CreateEscalationAction;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\CurrentTimeStamp;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests to cover Stale Discussion Trigger
 */
class StaleDiscussionTriggerTest extends SiteTestCase
{
    use ExpectExceptionTrait, CommunityApiTestTrait;

    private \TagModel $tagModel;
    private \LogModel $logModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        \Gdn::config()->set("Feature.CommunityManagementBeta.Enabled", true);
        \Gdn::config()->set("Feature.escalations.Enabled", true);
        parent::setUp();
        $this->tagModel = $this->container()->get(\TagModel::class);
        $this->logModel = $this->container()->get(\LogModel::class);
    }
    /**
     * Create a new automation record
     *
     * @param array $triggerValues
     * @param array $action
     * @param string $name
     * @return array
     */
    public function getAutomationRecord(array $triggerValues, array $action, string $name = ""): array
    {
        return [
            "name" => !empty($name) ? $name : StaleDiscussionTrigger::getType() . "_" . microtime(),
            "trigger" => [
                "triggerType" => StaleDiscussionTrigger::getType(),
                "triggerValue" => array_merge(
                    [
                        "postType" => ["discussion"],
                        "applyToNewContentOnly" => true,
                        "triggerTimeDelay" => [
                            "length" => 1,
                            "unit" => "day",
                        ],
                    ],
                    $triggerValues
                ),
            ],
            "action" => $action,
        ];
    }

    /**
     * Test validations for the trigger
     *
     * @param array $body
     * @param string $errorMessage
     * @return void
     * @dataProvider validationTestDataProvider
     */
    public function testValidation(array $body, string $errorMessage): void
    {
        $this->runWithExpectedExceptionMessage($errorMessage, function () use ($body) {
            $this->api()->post("automation-rules", $body);
        });
    }

    /**
     * Data provider for testValidation
     *
     * @return array
     */
    public function validationTestDataProvider(): array
    {
        return [
            "test passing invalid categoryID" => [
                "body" => $this->getAutomationRecord(
                    ["categoryID" => [55], "includeSubcategories" => false],
                    [
                        "actionType" => BumpDiscussionAction::getType(),
                        "actionValue" => [],
                    ]
                ),
                "errorMessage" => "The category 55 is not a valid category",
            ],
            "test passing invalid tagID" => [
                "body" => $this->getAutomationRecord(
                    ["tagID" => [25]],
                    [
                        "actionType" => BumpDiscussionAction::getType(),
                        "actionValue" => [],
                    ]
                ),
                "errorMessage" => "The tag 25 is not a valid tag",
            ],
        ];
    }

    /**
     * Test the category filter for the trigger
     *
     * @return void
     */
    public function testStaleDiscussionCategoryRecordFilter(): void
    {
        $currentTimeStamp = new \DateTimeImmutable();
        $yesterday = $currentTimeStamp->sub(new \DateInterval("P1DT10S"));
        CurrentTimeStamp::mockTime($yesterday->getTimestamp());

        $parentCategory = $this->createCategory();
        $childCategory1 = $this->createCategory(["parentCategoryID" => $parentCategory["categoryID"]]);
        $childCategory2 = $this->createCategory(["parentCategoryID" => $parentCategory["categoryID"]]);
        $subcategory1 = $this->createCategory(["parentCategoryID" => $childCategory1["categoryID"]]);
        $subcategory2 = $this->createCategory(["parentCategoryID" => $childCategory2["categoryID"]]);

        $discussion1 = $this->createDiscussion(["categoryID" => $parentCategory["categoryID"]]);
        $discussion2 = $this->createDiscussion(["categoryID" => $childCategory1["categoryID"]]);
        $discussion3 = $this->createDiscussion(["categoryID" => $childCategory2["categoryID"]]);
        $discussion4 = $this->createDiscussion(["categoryID" => $subcategory1["categoryID"]]);
        $discussion5 = $this->createDiscussion(["categoryID" => $subcategory2["categoryID"]]);

        $automationRule = $this->api()
            ->post(
                "automation-rules",
                $this->getAutomationRecord(
                    ["categoryID" => [$parentCategory["categoryID"]], "includeSubcategories" => true],
                    [
                        "actionType" => BumpDiscussionAction::getType(),
                        "actionValue" => [],
                    ]
                )
            )
            ->getBody();

        CurrentTimeStamp::clearMockTime();

        $triggerValue = $automationRule["trigger"]["triggerValue"];
        $staleDiscussionTrigger = new StaleDiscussionTrigger($triggerValue);
        $where = $staleDiscussionTrigger->getWhereArray($triggerValue);

        $this->assertEquals(5, $staleDiscussionTrigger->getRecordCountsToProcess($where));
        $recordsToProcess = [];
        foreach ($staleDiscussionTrigger->getRecordsToProcess(null, $where) as $discussionId => $record) {
            $recordsToProcess[] = $discussionId;
        }
        $this->assertEquals(
            [
                $discussion1["discussionID"],
                $discussion2["discussionID"],
                $discussion3["discussionID"],
                $discussion4["discussionID"],
                $discussion5["discussionID"],
            ],
            $recordsToProcess
        );
    }

    /**
     * Test filter by tagID for the trigger
     *
     * @return void
     */
    public function testStaleDiscussionTagIDRecordFilter(): void
    {
        $currentTimeStamp = new \DateTimeImmutable();
        $yesterday = $currentTimeStamp->sub(new \DateInterval("P1DT10S"));
        CurrentTimeStamp::mockTime($yesterday->getTimestamp());

        $category = $this->createCategory(["parentCategoryID" => -1]);
        $category2 = $this->createCategory(["parentCategoryID" => -1]);

        $tag1 = $this->createTag(["name" => "foo"]);
        $tag2 = $this->createTag(["name" => "bar"]);

        $discussion1 = $this->createDiscussion(["categoryID" => $category["categoryID"]]);
        $discussion2 = $this->createDiscussion(["categoryID" => $category["categoryID"]]);

        $discussion3 = $this->createDiscussion(["categoryID" => $category2["categoryID"]]);

        $this->tagModel->addDiscussion($discussion1["discussionID"], [$tag1["tagID"], $tag2["tagID"]]);
        $this->tagModel->addDiscussion($discussion2["discussionID"], [$tag1["tagID"]]);

        $this->tagModel->addDiscussion($discussion3["discussionID"], [$tag1["tagID"], $tag2["tagID"]]);

        $automationRecord = $this->getAutomationRecord(
            ["tagID" => [$tag1["tagID"], $tag2["tagID"]]],
            [
                "actionType" => BumpDiscussionAction::getType(),
                "actionValue" => [],
            ]
        );
        CurrentTimeStamp::clearMockTime();

        $triggerValue = $automationRecord["trigger"]["triggerValue"];
        $staleDiscussionTrigger = new StaleDiscussionTrigger($triggerValue);
        $where = $staleDiscussionTrigger->getWhereArray($triggerValue);

        $this->assertEquals(3, $staleDiscussionTrigger->getRecordCountsToProcess($where));
        $recordsToProcess = [];
        foreach ($staleDiscussionTrigger->getRecordsToProcess(null, $where) as $discussionId => $record) {
            $recordsToProcess[] = $discussionId;
        }
        $this->assertEquals(
            [$discussion1["discussionID"], $discussion2["discussionID"], $discussion3["discussionID"]],
            $recordsToProcess
        );

        // Now add category to the filter
        $triggerValue["categoryID"] = [$category["categoryID"]];
        $triggerValue["includeSubcategories"] = false;
        $where = $staleDiscussionTrigger->getWhereArray($triggerValue);

        $recordsToProcess = [];
        foreach ($staleDiscussionTrigger->getRecordsToProcess(null, $where) as $discussionId => $record) {
            $recordsToProcess[] = $discussionId;
        }
        $this->assertEquals(2, $staleDiscussionTrigger->getRecordCountsToProcess($where));
        $this->assertEquals([$discussion1["discussionID"], $discussion2["discussionID"]], $recordsToProcess);
    }

    /**
     * Test Escalation Action
     */
    public function testEscalationAction()
    {
        $currentTimeStamp = CurrentTimeStamp::getDateTime();
        $yesterday = $currentTimeStamp->sub(new \DateInterval("PT25H"));
        CurrentTimeStamp::mockTime($yesterday);

        // Create category
        $category = $this->createCategory(["name" => "Escalation Category"]);

        // Create 2 questions
        $discussion1 = $this->createDiscussion(["categoryID" => $category["categoryID"]]);
        $discussion2 = $this->createDiscussion(["categoryID" => $category["categoryID"]]);

        $recipe = $this->getAutomationRecord(
            [
                "applyToNewContentOnly" => false,
                "triggerTimeLookBackLimit" => ["length" => 2, "unit" => "day"],
                "categoryID" => [$category["categoryID"]],
                "includeSubcategories" => false,
            ],
            ["actionType" => CreateEscalationAction::getType(), "actionValue" => ["recordIsLive" => true]]
        );
        $automationRule = $this->api->post("automation-rules", $recipe)->getBody();

        CurrentTimeStamp::mockTime($currentTimeStamp);

        // Trigger a manual run;
        $data = $this->api()
            ->post("automation-rules/{$automationRule["automationRuleID"]}/trigger")
            ->getBody();

        $this->assertEquals("success", $data["status"]);
        $this->assertEmpty($data["errorMessage"]);

        // Check if the escalations has been created
        $this->assertLogMessage("Post escalated.");
        $escalations = $this->api()
            ->get("escalations", [
                "recordType" => "discussion",
                "placeRecordType" => "category",
                "placeRecordID" => $category["categoryID"],
            ])
            ->getBody();

        $this->assertCount(2, $escalations);
        $this->assertEquals($discussion1["discussionID"], $escalations[0]["recordID"]);
        $this->assertEquals($discussion2["discussionID"], $escalations[1]["recordID"]);

        // Test that attachments are generated

        $attachment1 = $this->api()
            ->get("attachments", ["recordType" => "discussion", "recordID" => $discussion1["discussionID"]])
            ->getBody();
        $this->assertNotEmpty($attachment1);
        $attachment2 = $this->api()
            ->get("attachments", ["recordType" => "discussion", "recordID" => $discussion2["discussionID"]])
            ->getBody();
        $this->assertNotEmpty($attachment2);

        $where = [
            "Operation" => "Automation",
            "RecordType" => "discussion",
            "DispatchUUID" => $data["automationRuleDispatchUUID"],
        ];

        // Test log Entries are created
        $logEntries = $this->logModel->getWhere($where);
        $this->assertCount(2, $logEntries);
        $this->assertEquals($discussion1["discussionID"], $logEntries[0]["RecordID"]);
        $this->assertEquals($discussion2["discussionID"], $logEntries[1]["RecordID"]);
    }
}
