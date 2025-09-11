<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Tests\Triggers;

use Gdn;
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
use VanillaTests\Models\TestCommentModelTrait;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * TimeSinceLastActiveTriggerTest
 */
class TimeSinceLastActiveTriggerTest extends SiteTestCase
{
    use AutomationRulesTestTrait;
    use UsersAndRolesApiTestTrait;
    use TestCommentModelTrait;
    use NotificationsApiTestTrait;
    use SchedulerTestTrait;

    private AutomationRuleService $automationRuleService;
    private array $role;
    /**
     * Setup
     */
    public function setup(): void
    {
        parent::setUp();
        $this->automationRuleModel = \Gdn::getContainer()->get(AutomationRuleModel::class);
        $this->automationRuleRevisionModel = \Gdn::getContainer()->get(AutomationRuleRevisionModel::class);
        $this->automationRuleDispatchesModel = \Gdn::getContainer()->get(AutomationRuleDispatchesModel::class);
        $this->automationRuleService = $this->container()->get(AutomationRuleService::class);
        $this->resetTable("automationRule");
        $this->resetTable("automationRuleRevision");
        $this->role = $this->createRole(["Name" => "testRole"]);
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
     * @dataProvider timeSinceLastActiveDataProvider
     */
    public function testLastActionRuleLongRunner(array $body, $results)
    {
        // Create Automation Rule
        $body["action"]["value"]["addRoleID"] = $this->role["roleID"];
        $this->createAutomationRule($body["trigger"], $body["action"]);
        $rule = $this->automationRuleModel->getAutomationRuleByID($this->lastRuleID);
        // Move back time 5.5 days
        $now = time() - 3600 * 24 * 5.5;
        CurrentTimeStamp::mockTime($now);
        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $users[] = $this->createUser();
        }
        // Will have newer last activity -- too new to be picked up.
        $extraUser = $this->createUser();

        // Will not have new activity - too old to be picked up.
        $oldUser = $this->createUser();

        // set Last AActive 3.5 days ago
        $now = time() - 3600 * 24 * 3.5;
        CurrentTimeStamp::mockTime($now);
        foreach ($users as $user) {
            $this->startSession($user["userID"]);
        }
        CurrentTimeStamp::mockTime(time());
        // update 1 user's last active date, should not be picked up by the rule
        $this->startSession($extraUser["userID"]);

        // Limit to 1 loop of the job.
        $this->getLongRunner()->setMaxIterations(1);
        $triggerClass = $this->automationRuleService->getAutomationTrigger($rule["trigger"]["triggerType"]);
        $action = $this->automationRuleService->getAction($rule["action"]["actionType"]);
        if (!$action) {
            $this->fail("Action class not found");
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
        // If no users to process  $callbackPayload should be null
        if ($results == 0) {
            $this->assertNull($callbackPayload);
        } else {
            $this->assertNotNull($callbackPayload);
            // Asserting that only $results users as assigned the role
            $usersCount = $this->roleModel->getUserCount($this->role["roleID"]);

            $this->assertSame(1, $usersCount);
            // Resume and finish.
            $this->getLongRunner()->setMaxIterations(100);
            $response = $this->resumeLongRunner($callbackPayload);
            $this->assertEquals(200, $response->getStatusCode());
            $body = $response->getBody();

            $this->assertNull($body["callbackPayload"]);
            $this->assertCount($results - 1, $body["progress"]["successIDs"]);
        }
        $usersCount = $this->roleModel->getUserCount($this->role["roleID"]);

        $this->assertSame($results, $usersCount);
    }

    /**
     * start session
     *
     * @param int $userID
     * @return void
     */
    public function startSession(int $userID): void
    {
        $now = CurrentTimeStamp::getMySQL();
        $user = $this->userModel->getID($userID, "DATASET_TYPE_ARRAY");
        $this->assertNotSame($now, $user["DateLastActive"]);
        $session = $this->getSession();
        $session->start($userID);
        $session->start();
        $_Identity = Gdn::factory("Identity");
        $_Identity->UserID = null;
        $_Identity->SessionID = "";
        $user = $this->userModel->getID($userID, "DATASET_TYPE_ARRAY");
        $this->assertSame($now, $user["DateLastActive"]);
    }

    /**
     * Data provider for testAutomationRuleLongrunner
     *
     * @return array[]
     */
    public function timeSinceLastActiveDataProvider()
    {
        return [
            "TimeSinceLastActive Trigger addRole action" => [
                [
                    "name" => "testRecipe",
                    "trigger" => [
                        "type" => "timeSinceLastActiveTrigger",
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
                        ],
                    ],
                    "action" => ["type" => "addRemoveRoleAction", "value" => ["addRoleID" => 0]],
                ],
                10,
            ],
            "TimeSinceLastActive Trigger addRole action fails" => [
                [
                    "name" => "testRecipe",
                    "trigger" => [
                        "type" => "timeSinceLastActiveTrigger",
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
                        ],
                    ],
                    "action" => ["type" => "addRemoveRoleAction", "value" => ["addRoleID" => 0]],
                ],
                0,
            ],
        ];
    }
}
