<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\AutomationRules\Models;

use Vanilla\AutomationRules\Actions\UserFollowCategoryAction;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\Dashboard\AutomationRules\Triggers\UserEmailDomainTrigger;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Dashboard\Models\AutomationRuleRevisionModel;
use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Vanilla\AutomationRules\Models\AutomationRuleLongRunnerGenerator;
use Vanilla\CurrentTimeStamp;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Scheduler\LongRunnerAction;
use VanillaTests\AutomationRules\AutomationRulesTestTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Models\TestCommentModelTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

/**
 * AutomationRuleDispatches Test
 */
class AutomationRuleDispatchesTest extends SiteTestCase
{
    use AutomationRulesTestTrait;
    use TestDiscussionModelTrait;
    use TestCommentModelTrait;
    use NotificationsApiTestTrait;
    use ExpectExceptionTrait;
    use SchedulerTestTrait;

    /** @var \DiscussionModel */
    protected $discussionModel;

    /** @var AutomationRuleDispatchesModel */
    protected AutomationRuleDispatchesModel $automationRuleDispatchesModel;

    /** @var AutomationRuleService */
    private AutomationRuleService $automationRuleService;

    /**
     * Setup
     */
    public function setup(): void
    {
        parent::setUp();
        $this->discussionModel = $this->container()->get(\DiscussionModel::class);
        $this->automationRuleModel = \Gdn::getContainer()->get(AutomationRuleModel::class);
        $this->automationRuleRevisionModel = \Gdn::getContainer()->get(AutomationRuleRevisionModel::class);
        $this->automationRuleDispatchesModel = \Gdn::getContainer()->get(AutomationRuleDispatchesModel::class);
        $this->automationRuleService = $this->container()->get(AutomationRuleService::class);
        $this->resetTable("automationRule");
        $this->resetTable("automationRuleRevision");
    }

    /**
     * Test automation rule longrunner, with restart.
     *
     * @param array $body
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws NoResultsException
     * @dataProvider automationRuleLongrunnerDataProvider
     */
    public function testAutomationRuleLongRunner(array $body)
    {
        // Create Automation Recipe
        $this->createAutomationRule($body["trigger"], $body["action"]);
        $rule = $this->automationRuleModel->getAutomationRuleByID($this->lastRuleID);
        // Move back time 3.5 days
        $now = time() - 3600 * 24 * 3.5;
        CurrentTimeStamp::mockTime($now);
        // Create 11 discussions
        $newDiscussions = $this->insertDiscussions(11, [
            "Type" => "discussion",
            "DateInserted" => gmdate(MYSQL_DATE_FORMAT, $now),
        ]);
        $discussionID = array_column($newDiscussions, "DiscussionID");
        // Add discussion to 1
        $comment = $this->insertComments(1, ["DiscussionID" => $newDiscussions[0]["DiscussionID"]])[0];
        CurrentTimeStamp::mockTime(time());
        // Create 2 new discussions.
        $newDiscussions = $this->insertDiscussions(2, [
            "Type" => "discussion",
            "DateInserted" => gmdate(MYSQL_DATE_FORMAT, time()),
        ]);
        $discussionID = array_merge($discussionID, array_column($newDiscussions, "DiscussionID"));
        // Limit to 1 loop of the job.
        $this->getLongRunner()->setMaxIterations(1);
        $triggerClass = $this->automationRuleService->getAutomationTrigger($rule["trigger"]["triggerType"]);
        $action = $this->automationRuleService->getAction($rule["action"]["actionType"]);
        if (!$action) {
            $this->fail("Action not found");
        }
        $actionClass = new $action($this->lastRuleID);

        $longRunnerParams = [
            "automationRuleID" => $rule["automationRuleID"],
            "actionType" => $rule["action"]["actionType"],
            "triggerType" => $rule["trigger"]["triggerType"],
            "dispatchUUID" => $actionClass->getDispatchUUID(),
        ];

        $count = $actionClass->getLongRunnerRuleItemCount(true, $triggerClass, $longRunnerParams);
        $this->assertEquals(10, $count);
        $dispatch = [
            "automationRuleDispatchUUID" => $actionClass->getDispatchUUID(),
            "automationRuleID" => $rule["automationRuleID"],
            "automationRuleRevisionID" => $rule["automationRuleRevisionID"],
            "dispatchType" => "manual",
            "dispatchedJobID" => "",
            "dateDispatched" => CurrentTimeStamp::getMySQL(),
            "status" => AutomationRuleDispatchesModel::STATUS_QUEUED,
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
        // Asserting that only 10 discussions are closed
        $discussions = $this->discussionModel
            ->getWhere([
                "Closed" => 1,
                "DiscussionID" => $discussionID,
            ])
            ->resultArray();

        $this->assertCount(1, $discussions);
        // Resume and finish.
        $this->getLongRunner()->setMaxIterations(100);
        $response = $this->resumeLongRunner($callbackPayload);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();

        $this->assertNull($body["callbackPayload"]);
        $this->assertCount(9, $body["progress"]["successIDs"]);

        $discussions = $this->discussionModel
            ->getWhere([
                "Closed" => 1,
                "DiscussionID" => $discussionID,
            ])
            ->resultArray();

        $this->assertCount(10, $discussions);
    }

    /**
     * Data provider for testAutomationRuleLongrunner
     *
     * @return array[]
     */
    public function automationRuleLongrunnerDataProvider()
    {
        $body = [
            "name" => "testRecipe",
            "trigger" => [
                "type" => "staleDiscussionTrigger",
                "value" => [
                    "maxTimeThreshold" => "4",
                    "maxTimeUnit" => "day",
                    "triggerTimeThreshold" => "1",
                    "triggerTimeUnit" => "day",
                    "postType" => ["discussion"],
                ],
            ],
            "action" => ["type" => "closeDiscussionAction", "value" => []],
        ];
        return [
            "StaleDiscussion Close discussion action" => [$body],
        ];
    }

    /**
     * Test that LongRunner Job marks a dispatch with appropriate status when an internal error occurs.
     *
     * @return void
     * @throws \Exception
     */
    public function testAutomationRuleDispatchStatusIsUpdatedOnErrors()
    {
        $this->automationRuleService->addAutomationAction(\TestAction::class);
        \Gdn::getContainer()->setInstance(AutomationRuleService::class, $this->automationRuleService);
        // Move back time 2 day
        CurrentTimeStamp::mockTime(strtotime("-2 day"));
        $discussions = $this->insertDiscussions(2, [
            "Type" => "discussion",
            "DateInserted" => CurrentTimeStamp::getMySQL(),
        ]);
        //set current time to now
        CurrentTimeStamp::clearMockTime();

        $automationRecord = [
            "name" => "Test Automation Rule",
            "trigger" => [
                "type" => StaleDiscussionTrigger::getType(),
                "value" => [
                    "maxTimeThreshold" => 3,
                    "maxTimeUnit" => "day",
                    "triggerTimeThreshold" => 2,
                    "triggerTimeUnit" => "day",
                    "postType" => ["discussion"],
                ],
            ],
            "action" => ["type" => \TestAction::getType(), "value" => []],
        ];
        $testRule = $this->createAutomationRule($automationRecord["trigger"], $automationRecord["action"], false, [
            "name" => $automationRecord["name"],
        ]);

        $testAction = new \TestAction($testRule["automationRuleID"], AutomationRuleDispatchesModel::TYPE_MANUAL);
        $triggerClass = $this->automationRuleService->getAutomationTrigger($automationRecord["trigger"]["type"]);
        $result = $testAction->triggerLongRunnerRule($triggerClass, false);
        $dispatchUUID = $result["automationRuleDispatchUUID"];

        //check currentDispatch
        $currentDispatch = $this->automationRuleDispatchesModel->getAutomationRuleDispatchByUUID($dispatchUUID);
        $this->assertEquals(AutomationRuleDispatchesModel::STATUS_WARNING, $currentDispatch["status"]);
        $this->assertEquals(0, $currentDispatch["attributes"]["affectedRecordCount"]);
        $this->assertCount(2, $currentDispatch["attributes"]["failedRecords"]);
        $this->assertNotEmpty($currentDispatch["errorMessage"]);

        //update the action value for the rule
        $this->automationRuleRevisionModel->update(
            ["actionValue" => ["InvalidField" => "InvalidValue"]],
            ["automationRuleRevisionID" => $testRule["automationRuleRevisionID"]]
        );
        $testAction = new \TestAction($testRule["automationRuleID"], AutomationRuleDispatchesModel::TYPE_MANUAL);
        $triggerClass = $this->automationRuleService->getAutomationTrigger($automationRecord["trigger"]["type"], true);
        try {
            $result = $testAction->triggerLongRunnerRule($triggerClass, false);
        } catch (\Error $e) {
            $this->assertEquals("this has failed", $e->getMessage());
            // Now test that the dispatch is marked as failed
            $currentDispatch = $this->automationRuleDispatchesModel->getAutomationRuleDispatchByUUID(
                $testAction->getDispatchUUID()
            );
            $this->assertEquals(AutomationRuleDispatchesModel::STATUS_FAILED, $currentDispatch["status"]);
        }
    }

    /**
     * Test that AutomationRuleDispatchesModel::countDispatches() and AutomationRuleDispatchesModel::getAutomationRuleDispatches()
     * gives back only records having estimated counts greater than 0
     *
     * @return void
     */
    public function testAutomationRuleAndCountDispatchesOmitsRecordHavingNoEstimatedCounts()
    {
        $this->resetTable("automationRuleDispatches");
        // Create a dummy automation rule and some dispatches with estimated counts

        $automationRule1 = $this->createAutomationRule(
            [
                "type" => UserEmailDomainTrigger::getType(),
                "value" => [
                    "emailDomain" => "example.com",
                ],
            ],
            ["type" => UserFollowCategoryAction::getType(), "value" => ["categoryID" => 3]]
        );

        $dispatch1 = [
            "automationRuleID" => $automationRule1["automationRuleID"],
            "automationRuleRevisionID" => $automationRule1["automationRuleRevisionID"],
            "dispatchType" => "initial",
            "dispatchedJobID" => "",
            "status" => AutomationRuleDispatchesModel::STATUS_SUCCESS,
            "attributes" => [
                "affectedRecordType" => "discussion",
                "estimatedRecordCount" => 10,
                "affectedRecordCount" => 8,
            ],
        ];
        $this->createAutomationDispatches($dispatch1, 1);
        $dispatch1["dispatchType"] = "triggered";
        $dispatch1["attributes"]["estimatedRecordCount"] = 5;
        $dispatch1["attributes"]["affectedRecordCount"] = 4;
        $this->createAutomationDispatches($dispatch1, 1);

        $dispatch1["attributes"]["estimatedRecordCount"] = 0;
        $dispatch1["attributes"]["affectedRecordCount"] = 0;
        $dispatch1["status"] = AutomationRuleDispatchesModel::STATUS_WARNING;
        $this->createAutomationDispatches($dispatch1, 1);

        $dispatch1["attributes"] = [
            "affectedRecordType" => "discussion",
        ];
        $dispatch1["status"] = AutomationRuleDispatchesModel::STATUS_FAILED;
        $this->createAutomationDispatches($dispatch1, 1);

        $this->assertEquals(3, $this->automationRuleDispatchesModel->getCountAutomationRuleDispatches());
        $dispatchRecords = $this->automationRuleDispatchesModel->getAutomationRuleDispatches();
        $this->assertCount(3, $dispatchRecords);
        $statuses = array_column($dispatchRecords, "dispatchStatus");
        $this->assertNotContains(AutomationRuleDispatchesModel::STATUS_WARNING, $statuses);
    }
}
