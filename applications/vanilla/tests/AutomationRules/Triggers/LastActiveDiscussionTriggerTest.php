<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace AutomationRules\Triggers;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Vanilla\AutomationRules\Models\AutomationRuleLongRunnerGenerator;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Scheduler\LongRunnerAction;
use VanillaTests\AutomationRules\AutomationRulesTestTrait;
use VanillaTests\Models\TestCommentModelTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

/**
 * LastActiveDiscussionTriggerTest
 */
class LastActiveDiscussionTriggerTest extends SiteTestCase
{
    use AutomationRulesTestTrait;
    use TestDiscussionModelTrait;
    use TestCommentModelTrait;
    use NotificationsApiTestTrait;
    use SchedulerTestTrait;

    private AutomationRuleService $automationRuleService;

    /**
     * Setup
     */
    public function setup(): void
    {
        parent::setUp();
        $this->discussionModel = $this->container()->get(\DiscussionModel::class);
        $this->automationRuleService = $this->container()->get(AutomationRuleService::class);
        $this->initialize();
    }

    /**
     * Test automation rule longrunner, with restart.
     *
     * @param array $body
     * @param $results
     * @return void
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     * @dataProvider automationRuleLongrunnerDataProvider
     */
    public function testLastActionRuleLongRunner(array $body, $results)
    {
        // Create Automation Rule
        $this->createAutomationRule($body["trigger"], $body["action"]);
        $rule = $this->automationRuleModel->getAutomationRuleByID($this->lastRuleID);
        // Move back time 3.5 days
        $now = time() - 3600 * 24 * 3.5;
        CurrentTimeStamp::mockTime($now);
        // Create 11 discussions
        $newDiscussions = $this->insertDiscussions(5, [
            "Type" => "discussion",
            "DateInserted" => gmdate(MYSQL_DATE_FORMAT, $now),
        ]);
        $discussionID = array_column($newDiscussions, "DiscussionID");
        // Add discussion to 1
        foreach ($newDiscussions as $newDiscussion) {
            $this->insertComments(1, ["DiscussionID" => $newDiscussion["DiscussionID"]]);
        }
        $noCommentDiscussions = $this->insertDiscussions(5, [
            "Type" => "discussion",
            "DateInserted" => gmdate(MYSQL_DATE_FORMAT, $now),
        ]);
        $discussionID = array_merge($discussionID, array_column($noCommentDiscussions, "DiscussionID"));
        CurrentTimeStamp::clearMockTime();
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
        $this->assertEquals($results, $count);
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
            new LongRunnerAction(AutomationRuleLongRunnerGenerator::class, $triggerClass->getLongRunnerMethod(), [
                null,
                null,
                $longRunnerParams,
            ])
        );
        $callbackPayload = $result->getCallbackPayload();
        // If no discussions to process  $callbackPayload should be null
        if ($results == 0) {
            $this->assertNull($callbackPayload);
        } else {
            $this->assertNotNull($callbackPayload);
            // Asserting that only $results discussions are closed
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
        }
        $discussions = $this->discussionModel
            ->getWhere([
                "Closed" => 1,
                "DiscussionID" => $discussionID,
            ])
            ->resultArray();

        $this->assertCount($results, $discussions);
    }

    /**
     * Data provider for testAutomationRuleLongrunner
     *
     * @return array[]
     */
    public function automationRuleLongrunnerDataProvider()
    {
        return [
            "LastActiveDiscussion Close discussion action" => [
                [
                    "name" => "testRecipe",
                    "trigger" => [
                        "type" => "lastActiveDiscussionTrigger",
                        "value" => [
                            "applyToNewContentOnly" => false,
                            "triggerTimeLookBackLimit" => [
                                "length" => 4,
                                "unit" => "day",
                            ],
                            "triggerTimeDelay" => [
                                "length" => 1,
                                "unit" => "day",
                            ],
                            "postType" => ["discussion"],
                        ],
                    ],
                    "action" => ["type" => "closeDiscussionAction", "value" => []],
                ],
                10,
            ],
            "LastActiveDiscussion Close discussion action fails" => [
                [
                    "name" => "testRecipe",
                    "trigger" => [
                        "type" => "lastActiveDiscussionTrigger",
                        "value" => [
                            "applyToNewContentOnly" => false,
                            "triggerTimeLookBackLimit" => [
                                "length" => 2,
                                "unit" => "hour",
                            ],
                            "triggerTimeDelay" => [
                                "length" => 1,
                                "unit" => "hour",
                            ],
                            "postType" => ["discussion"],
                        ],
                    ],
                    "action" => ["type" => "closeDiscussionAction", "value" => []],
                ],
                0,
            ],
        ];
    }
}
