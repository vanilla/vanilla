<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace AutomationRules\Triggers;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\ValidationException;
use Vanilla\AutomationRules\Actions\RemoveDiscussionFromTriggerCollectionAction;
use Vanilla\AutomationRules\Models\AutomationRuleLongRunnerGenerator;
use Vanilla\AutomationRules\Triggers\StaleCollectionTrigger;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Models\CollectionRecordModel;
use Vanilla\Scheduler\LongRunnerAction;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

class StaleCollectionTriggerTest extends SiteTestCase
{
    use CommunityApiTestTrait, ExpectExceptionTrait, SchedulerTestTrait;

    private CollectionRecordModel $collectionRecordModel;
    private AutomationRuleService $automationRuleService;

    private AutomationRuleDispatchesModel $automationRuleDispatchesModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->collectionRecordModel = $this->container()->get(CollectionRecordModel::class);
        $this->automationRuleService = $this->container()->get(AutomationRuleService::class);
        $this->automationRuleDispatchesModel = $this->container()->get(AutomationRuleDispatchesModel::class);
    }

    /**
     * Create a new collection
     *
     * @param array $collectionRecord
     * @return array
     */
    private function createCollection(array $collectionRecord): array
    {
        $result = $this->api()->post("collections", $collectionRecord);
        $this->assertEquals(201, $result->getStatusCode());
        return $result->getBody();
    }

    /**
     * Get a collection record with discussions
     *
     * @param string $collectionName
     * @param int $recordCount
     * @return array
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    private function getRecord(string $collectionName, int $recordCount): array
    {
        for ($i = 0; $i < $recordCount; $i++) {
            $this->createDiscussion();
            $records[] = [
                "recordID" => $this->lastInsertedDiscussionID,
                "recordType" => "discussion",
                "sort" => $i + 1,
            ];
        }

        return [
            "name" => $collectionName ?? "Test Collection - " . date("i-s"),
            "records" => $records,
        ];
    }

    /**
     * @param array $body
     * @param string $errorMessage
     * @return void
     * @dataProvider validationTestDataProvider
     */
    public function testValidationsForStaleCollectionAutomationRule(array $body, string $errorMessage): void
    {
        $this->runWithExpectedExceptionMessage($errorMessage, function () use ($body) {
            $this->api()->post("automation-rules", $body);
        });
    }

    /**
     * Get the automation rule record
     *
     * @param array $triggerOverRides
     * @return array
     */
    private function automationRuleRecord(array $triggerOverRides = []): array
    {
        $body = [
            "name" => "Stale Collection Automation Rule - " . date("U"),
            "trigger" => [
                "triggerType" => StaleCollectionTrigger::getType(),
                "triggerValue" => [
                    "applyToNewContentOnly" => false,
                    "triggerTimeLookBackLimit" => [
                        "length" => "",
                        "unit" => null,
                    ],
                    "triggerTimeDelay" => [
                        "length" => "",
                        "unit" => "",
                    ],
                    "collectionID" => [1],
                ],
            ],
            "action" => [
                "actionType" => RemoveDiscussionFromTriggerCollectionAction::getType(),
                "actionValue" => [
                    "recordType" => "discussion",
                ],
            ],
        ];

        foreach ($triggerOverRides as $key => $value) {
            $body["trigger"]["triggerValue"][$key] = $value;
        }
        return $body;
    }

    /**
     * Data provider for testValidationsForStaleCollectionAutomationRule
     *
     * @return array
     */
    public function validationTestDataProvider(): array
    {
        return [
            "testEmptyMaxTimeUnit" => [
                "body" => $this->automationRuleRecord([
                    "applyToNewContentOnly" => false,
                    "triggerTimeLookBackLimit" => [
                        "length" => 2,
                    ],
                    "triggerTimeDelay" => [
                        "length" => 1,
                        "unit" => "hour",
                    ],
                    "collectionID" => [1],
                ]),
                "errorMessage" => "Field is required.",
            ],
            "test validation is thrown when maxTimeThreshold is less than triggerTimeThreshold" => [
                "body" => $this->automationRuleRecord([
                    "triggerTimeLookBackLimit" => [
                        "length" => 1,
                        "unit" => "hour",
                    ],
                    "triggerTimeDelay" => [
                        "length" => 4,
                        "unit" => "hour",
                    ],
                    "collectionID" => [1],
                ]),
                "errorMessage" => "Look-back Limit should be greater than Trigger Delay.",
            ],
        ];
    }

    /**
     * Test manual execution of stale collection automation rule
     *
     * @return void
     */
    public function testStaleCollectionAutomationRule(): void
    {
        $collectionRecord = $this->getRecord("Stale Collection", 5);
        $collection = $this->createCollection($collectionRecord);
        $collectionID = $collection["collectionID"];
        $dateNow = CurrentTimeStamp::getDateTime();
        // Set the dateInserted to 1 day before
        $this->collectionRecordModel->update(
            ["dateInserted" => $dateNow->modify("-1 day")->format(CurrentTimeStamp::MYSQL_DATE_FORMAT)],
            ["collectionID" => $collectionID]
        );

        // Create a new automation rule for stale collection
        $automationRuleRecord = $this->automationRuleRecord([
            "triggerTimeLookBackLimit" => [
                "length" => 1,
                "unit" => "day",
            ],
            "triggerTimeDelay" => [
                "length" => 1,
                "unit" => "hour",
            ],
            "collectionID" => [$collectionID],
        ]);
        $result = $this->api()->post("automation-rules", $automationRuleRecord);
        $this->assertEquals(201, $result->getStatusCode());
        $automationRuleCollectionRecord = $result->getBody();

        // Now trigger run once of the automation rule
        $result = $this->api()->post("automation-rules/{$automationRuleCollectionRecord["automationRuleID"]}/trigger");
        $this->assertEquals(201, $result->getStatusCode());
        $dispatchedRecord = $result->getBody();
        $this->assertEquals(AutomationRuleDispatchesModel::STATUS_SUCCESS, $dispatchedRecord["status"]);
        $this->assertEquals($automationRuleCollectionRecord["automationRuleID"], $dispatchedRecord["automationRuleID"]);

        // Check if the records are removed from the collection
        $this->assertEmpty($this->collectionRecordModel->getCount(["collectionID" => $collectionID]));
    }

    /**
     * Test long runner execution
     *
     * @return void
     */
    public function testLongRunnerExecution()
    {
        $this->resetTable("collectionRecord");
        $this->resetTable("collection");
        // Create two collection records
        $collectionRecord1 = $this->createCollection($this->getRecord("Stale Collection -1", 5));
        $collectionRecord2 = $this->createCollection($this->getRecord("Stale Collection -2", 4));

        // Update the second collection with some records from the first collection
        $collectionRecord2["records"] = array_merge(
            $collectionRecord2["records"],
            array_slice($collectionRecord1["records"], 0, 2)
        );
        $result = $this->api()->patch("collections/{$collectionRecord2["collectionID"]}", [
            "records" => $collectionRecord2["records"],
        ]);
        $collectionRecord2 = $result->getBody();

        // Update the dates so that we can test the stale collection
        $updateDate = CurrentTimeStamp::getDateTime()
            ->sub(new \DateInterval("P1D"))
            ->format("Y-m-d H:i:s");
        // We only update the records that are part of 1st collection and also part of 2nd collection
        $this->collectionRecordModel->update(
            ["dateInserted" => $updateDate],
            [
                "collectionID" => [$collectionRecord2["collectionID"], $collectionRecord1["collectionID"]],
                "recordID" => array_column($collectionRecord1["records"], "recordID"),
            ]
        );

        // Create a new automation rule for stale collection
        $automationRuleRecord = $this->automationRuleRecord([
            "triggerTimeLookBackLimit" => [
                "length" => 1,
                "unit" => "day",
            ],
            "triggerTimeDelay" => [
                "length" => 1,
                "unit" => "hour",
            ],
            "collectionID" => [$collectionRecord2["collectionID"], $collectionRecord1["collectionID"]],
        ]);
        $result = $this->api()->post("automation-rules", $automationRuleRecord);
        $staleCollectionRule = $result->getBody();
        $actionClass = $this->automationRuleService->getAction($staleCollectionRule["action"]["actionType"]);
        if (!$actionClass) {
            $this->fail("Action class not found");
        }
        $triggerClass = $this->automationRuleService->getAutomationTrigger(
            $staleCollectionRule["trigger"]["triggerType"]
        );
        $action = new $actionClass(
            $staleCollectionRule["automationRuleID"],
            $this->automationRuleDispatchesModel::TYPE_MANUAL
        );

        // Now lets call the long Runner
        $longRunner = $this->getLongRunner()->setMaxIterations(1);
        $longRunnerParams = [
            "automationRuleID" => $staleCollectionRule["automationRuleID"],
            "actionType" => $staleCollectionRule["action"]["actionType"],
            "triggerType" => $staleCollectionRule["trigger"]["triggerType"],
            "dispatchUUID" => $action->getDispatchUUID(),
        ];
        $count = $action->getLongRunnerRuleItemCount(true, $triggerClass, $longRunnerParams);
        $this->assertEquals(7, $count);
        $dispatch = [
            "automationRuleDispatchUUID" => $action->getDispatchUUID(),
            "automationRuleID" => $staleCollectionRule["automationRuleID"],
            "automationRuleRevisionID" => $staleCollectionRule["automationRuleRevisionID"],
            "dispatchType" => $this->automationRuleDispatchesModel::TYPE_MANUAL,
            "dispatchedJobID" => "",
            "dateDispatched" => CurrentTimeStamp::getMySQL(),
            "status" => $this->automationRuleDispatchesModel::STATUS_QUEUED,
            "attributes" => ["affectedRecordType" => "discussion", "estimatedRecordCount" => $count],
        ];
        $this->automationRuleDispatchesModel->insert($dispatch);
        $result = $this->getLongRunner()->runImmediately(
            new LongRunnerAction(AutomationRuleLongRunnerGenerator::class, "runAutomationRule", [
                null,
                null,
                $longRunnerParams,
            ])
        );
        $callbackPayload = $result->getCallbackPayload();
        $this->assertNotNull($callbackPayload);
        $this->assertCount(
            4,
            $this->collectionRecordModel->select(["collectionID" => $collectionRecord1["collectionID"]])
        );

        $longRunner->setMaxIterations(10);
        $result = $this->resumeLongRunner($callbackPayload);
        $longRunnerResponse = $result->getBody();
        $this->assertCount(6, $longRunnerResponse["progress"]["successIDs"]);

        // Now assert that the records are removed from the collection
        $this->assertEmpty(
            $this->collectionRecordModel->getCount([
                "collectionID" => $collectionRecord1["collectionID"],
                "recordID" => array_column($collectionRecord1["records"], "recordID"),
            ])
        );

        // There should be 4 records in the collection which are part of collection 2
        $this->assertCount(4, $this->collectionRecordModel->select());
    }
}
