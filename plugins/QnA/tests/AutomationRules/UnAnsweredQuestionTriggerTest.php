<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\QnA\AutomationRules;

use Vanilla\AutomationRules\Actions\AddDiscussionToCollectionAction;
use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Vanilla\AutomationRules\Actions\CreateEscalationAction;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\QnA\AutomationRules\Triggers\UnAnsweredQuestionTrigger;
use VanillaTests\APIv2\QnaApiTestTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use LogModel;

class UnAnsweredQuestionTriggerTest extends SiteTestCase
{
    use ExpectExceptionTrait, CommunityApiTestTrait, QnaApiTestTrait;

    public static $addons = ["vanilla", "QnA"];

    private LogModel $logModel;
    public function setup(): void
    {
        \Gdn::config()->set("Feature.CommunityManagementBeta.Enabled", true);
        \Gdn::config()->set("Feature.escalations.Enabled", true);
        parent::setUp();
        $this->logModel = $this->container()->get(LogModel::class);
        $this->resetTable("Discussion");
    }
    /**
     * Test validations for the trigger
     *
     * @param array $body
     * @param string $errorMessage
     * @return void
     * @dataProvider validationTestDataProvider
     */
    public function testValidation(array $body, string $errorMessage)
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
                        "actionType" => CreateEscalationAction::getType(),
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
     * Generate an automation record for the test
     *
     * @param array $triggerValue
     * @param array $action
     * @return array
     */
    private function getAutomationRecord(array $triggerValue, array $action)
    {
        return [
            "name" => UnAnsweredQuestionTrigger::getType() . "_" . microtime(),
            "trigger" => [
                "triggerType" => UnAnsweredQuestionTrigger::getType(),
                "triggerValue" => array_merge(
                    [
                        "applyToNewContentOnly" => true,
                        "triggerTimeDelay" => [
                            "length" => 1,
                            "unit" => "day",
                        ],
                    ],
                    $triggerValue
                ),
            ],
            "action" => $action,
        ];
    }

    /**
     * Test Escalation Action
     */
    public function testUnAnsweredRecordFilter()
    {
        $currentTimeStamp = strtotime("now");
        $yesterday = strtotime("-1 day");
        CurrentTimeStamp::mockTime($yesterday);
        // Create a parent category and a sub category
        $masterCategory = $this->createCategory(["name" => "Master Category"]);
        $subCategory = $this->createCategory([
            "name" => "Sub Category",
            "parentCategoryID" => $masterCategory["categoryID"],
        ]);

        //Create a discussion each in the parent category and sub category
        $discussion1 = $this->createDiscussion(["categoryID" => $masterCategory["categoryID"]]);
        $discussion2 = $this->createDiscussion(["categoryID" => $subCategory["categoryID"]]);

        //Create 2 questions in the parent category
        $question1 = $this->createQuestion(["categoryID" => $masterCategory["categoryID"]]);
        $question2 = $this->createQuestion(["categoryID" => $masterCategory["categoryID"]]);

        //Create 2 questions in the sub category

        $question3 = $this->createQuestion(["categoryID" => $subCategory["categoryID"]]);
        $question4 = $this->createQuestion(["categoryID" => $subCategory["categoryID"]]);

        //Create an answer for question 1 and Question 2

        $q1Answer = $this->createAnswer(["discussionID" => $question1["discussionID"]]);
        $q2Answer = $this->createAnswer(["discussionID" => $question2["discussionID"]]);

        //Reject answer for question 2
        $this->rejectAnswer($question2, $q2Answer);
        CurrentTimeStamp::mockTime($currentTimeStamp);

        $triggerValue = [
            "applyToNewContentOnly" => true,
            "triggerTimeDelay" => [
                "length" => 1,
                "unit" => "day",
            ],
            "categoryID" => [$masterCategory["categoryID"]],
        ];

        $unAnsweredQuestionTrigger = new UnAnsweredQuestionTrigger();
        $where = $unAnsweredQuestionTrigger->getWhereArray($triggerValue, null);
        $recordCount = $unAnsweredQuestionTrigger->getRecordCountsToProcess($where);
        $this->assertEquals(1, $recordCount);
        $recordsToProcess = [];
        foreach ($unAnsweredQuestionTrigger->getRecordsToProcess(null, $where) as $discussionID => $record) {
            $recordsToProcess[] = $discussionID;
        }
        $this->assertEquals([$question2["discussionID"]], $recordsToProcess);

        // Test with subcategories
        $triggerValue["includeSubcategories"] = true;
        $where = $unAnsweredQuestionTrigger->getWhereArray($triggerValue, null);
        $recordCount = $unAnsweredQuestionTrigger->getRecordCountsToProcess($where);
        $this->assertEquals(3, $recordCount);
        $recordsToProcess = [];
        foreach ($unAnsweredQuestionTrigger->getRecordsToProcess(null, $where) as $discussionID => $record) {
            $recordsToProcess[] = $discussionID;
        }
        $this->assertEquals(
            [$question2["discussionID"], $question3["discussionID"], $question4["discussionID"]],
            $recordsToProcess
        );

        // Now test with tags

        $tag1 = $this->createTag(["name" => "Great Question"]);
        $tag2 = $this->createTag(["name" => "Powerful Question"]);

        $this->addTagToDiscussion($question1["discussionID"], [$tag1["tagID"], $tag2["tagID"]]);
        $this->addTagToDiscussion($question2["discussionID"], [$tag1["tagID"]]);
        $this->addTagToDiscussion($question4["discussionID"], [$tag1["tagID"], $tag2["tagID"]]);

        unset($triggerValue["categoryID"], $triggerValue["includeSubcategories"]);
        $triggerValue["tagID"] = [$tag1["tagID"], $tag2["tagID"]];
        $where = $unAnsweredQuestionTrigger->getWhereArray($triggerValue, null);
        $recordCount = $unAnsweredQuestionTrigger->getRecordCountsToProcess($where);
        $this->assertEquals(2, $recordCount);
        $recordsToProcess = [];
        foreach ($unAnsweredQuestionTrigger->getRecordsToProcess(null, $where) as $discussionID => $record) {
            $recordsToProcess[] = $discussionID;
        }
        $this->assertEquals([$question2["discussionID"], $question4["discussionID"]], $recordsToProcess);
    }

    /**
     *  Add Tag to the discussion
     *
     * @param int $discussionID
     * @param array $tagIDs
     */
    public function addTagToDiscussion(int $discussionID, array $tagIDs)
    {
        return $this->api()
            ->post("/discussions/{$discussionID}/tags", ["tagIDs" => $tagIDs])
            ->getBody();
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
        $masterCategory = $this->createCategory(["name" => "Escalation Category"]);

        //Create 2 questions
        $question1 = $this->createQuestion(["categoryID" => $masterCategory["categoryID"]]);
        $question2 = $this->createQuestion(["categoryID" => $masterCategory["categoryID"]]);

        $recipe = $this->getAutomationRecord(
            [
                "applyToNewContentOnly" => false,
                "triggerTimeLookBackLimit" => ["length" => 2, "unit" => "day"],
            ],
            ["actionType" => CreateEscalationAction::getType(), "actionValue" => ["recordIsLive" => true]]
        );
        $automationRule = $this->api->post("automation-rules", $recipe)->getBody();

        CurrentTimeStamp::mockTime($currentTimeStamp);

        //Trigger a manual run;
        $data = $this->api()
            ->post("automation-rules/{$automationRule["automationRuleID"]}/trigger")
            ->getBody();

        $this->assertEquals("success", $data["status"]);
        $this->assertEmpty($data["errorMessage"]);

        //check if the escalations has been created
        $this->assertLogMessage("Post escalated.");
        $escalations = $this->api()
            ->get("escalations", [
                "recordType" => "discussion",
                "placeRecordType" => "category",
                "placeRecordID" => $masterCategory["categoryID"],
            ])
            ->getBody();

        $this->assertCount(2, $escalations);
        $this->assertEquals($question1["discussionID"], $escalations[0]["recordID"]);
        $this->assertEquals($question2["discussionID"], $escalations[1]["recordID"]);

        //Test that attachments are generated

        $attachment1 = $this->api()
            ->get("attachments", ["recordType" => "discussion", "recordID" => $question1["discussionID"]])
            ->getBody();
        $this->assertNotEmpty($attachment1);
        $attachment2 = $this->api()
            ->get("attachments", ["recordType" => "discussion", "recordID" => $question2["discussionID"]])
            ->getBody();
        $this->assertNotEmpty($attachment2);

        $where = [
            "Operation" => "Automation",
            "RecordType" => "discussion",
            "DispatchUUID" => $data["automationRuleDispatchUUID"],
        ];

        //Test log Entries are created
        $logEntries = $this->logModel->getWhere($where);
        $this->assertCount(2, $logEntries);
        $this->assertEquals($question1["discussionID"], $logEntries[0]["RecordID"]);
        $this->assertEquals($question2["discussionID"], $logEntries[1]["RecordID"]);

        // Trigger the rule again and check if the escalations are not created again

        $this->api()
            ->post("automation-rules/{$automationRule["automationRuleID"]}/trigger")
            ->getBody();

        $reports = $this->api()
            ->get("reports", [
                "recordType" => "discussion",
                "recordID" => [$question1["discussionID"], $question2["discussionID"]],
            ])
            ->getBody();

        $this->assertCount(4, $reports);

        //Trigger a triggered execution
        $automationRuleModel = $this->container()->get(AutomationRuleModel::class);
        $automationRuleModel->updateAutomationRuleStatus(
            $automationRule["automationRuleID"],
            AutomationRuleModel::STATUS_ACTIVE
        );

        $this->assertLogMessage("The report has been escalated previously.");
    }

    /**
     * Test add to collection action
     */
    public function testAddToCollectionAction()
    {
        $this->createCategory(["name" => "Category Collection"]);
        $categoryID = $this->lastInsertedCategoryID;
        $this->createDiscussion();
        $discussionID = $this->lastInsertedDiscussionID;
        $collection = $this->createCollection(
            [["recordID" => $discussionID, "recordType" => "discussion"]],
            ["name" => "collection A"]
        );

        $currentTimeStamp = CurrentTimeStamp::getDateTime();
        $yesterday = $currentTimeStamp->sub(new \DateInterval("PT25H"));
        CurrentTimeStamp::mockTime($yesterday);
        $this->createQuestion(["categoryID" => $categoryID]);
        $questionID = $this->lastInsertedQuestionID;
        CurrentTimeStamp::mockTime($currentTimeStamp);
        $automationRecord = $this->getAutomationRecord(
            [
                "applyToNewContentOnly" => false,
                "triggerTimeLookBackLimit" => ["length" => 3, "unit" => "day"],
                "triggerTimeDelay" => ["length" => 1, "unit" => "day"],
                "postType" => ["discussion"],
            ],
            [
                "actionType" => AddDiscussionToCollectionAction::getType(),
                "actionValue" => ["collectionID" => [$collection["collectionID"]]],
            ]
        );
        $automationRule = $this->api->post("automation-rules", $automationRecord)->getBody();

        $data = $this->api()
            ->post("automation-rules/{$automationRule["automationRuleID"]}/trigger")
            ->getBody();

        $this->assertEquals("success", $data["status"]);
        $where = [
            "Operation" => "Automation",
            "RecordType" => "discussion",
            "DispatchUUID" => $data["automationRuleDispatchUUID"],
        ];
        $logRecord = $this->logModel->getWhere($where);
        $this->assertCount(1, $logRecord);
        $this->assertEquals(
            [
                "addDiscussionToCollection" => [
                    "collectionID" => [$collection["collectionID"]],
                    "recordID" => $questionID,
                ],
            ],
            $logRecord[0]["Data"]
        );
        $collectionRecord = $this->api()
            ->get("collections/{$collection["collectionID"]}")
            ->getBody();
        $this->assertCount(2, $collectionRecord["records"]);
        $this->assertEquals($questionID, $collectionRecord["records"][1]["recordID"]);
    }
}
