<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\AutomationRules\APIv2;

use Exception;
use Gdn;
use Vanilla\AddonManager;
use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Vanilla\AutomationRules\Actions\CloseDiscussionAction;
use Vanilla\AutomationRules\Actions\UserFollowCategoryAction;
use Vanilla\AutomationRules\Triggers\LastActiveDiscussionTrigger;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\Dashboard\AutomationRules\Triggers\UserEmailDomainTrigger;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Dashboard\Models\AutomationRuleRevisionModel;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\AutomationRules\AutomationRulesTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Automation rule controller test
 */
class AutomationRulesDispatchTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait, AutomationRulesTestTrait, CommunityApiTestTrait;

    private AddonManager $addonManager;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        Gdn::sql()->truncate("automationRule");
        Gdn::sql()->truncate("automationRuleDispatches");
        Gdn::sql()->truncate("automationRuleRevision");
        $this->automationRuleModel = $this->container()->get(AutomationRuleModel::class);
        $this->automationRuleRevisionModel = $this->container()->get(AutomationRuleRevisionModel::class);
        $this->automationRuleDispatchesModel = $this->container()->get(AutomationRuleDispatchesModel::class);
        $this->addonManager = $this->container()->get(AddonManager::class);
        $this->createUserFixtures();
    }

    /**
     * Test getting the list of Automation Rule Dispatches.
     *
     * @return void
     */
    public function testGetListOfAutomationRuleDispatches(): void
    {
        $this->runWithUser(function () {
            $category = $this->createCategory();
            $trigger = ["type" => UserEmailDomainTrigger::getType(), "value" => ["emailDomain" => "test.com"]];
            $action = [
                "type" => UserFollowCategoryAction::getType(),
                "value" => ["followedCategory" => ["categoryID" => [$category["categoryID"]]]],
            ];
            $automationRule = $this->createAutomationRule($trigger, $action);
            $this->createAutomationDispatches(
                [
                    "automationRuleID" => $automationRule["automationRuleID"],
                    "automationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
                    "status" => AutomationRuleDispatchesModel::STATUS_SUCCESS,
                    "attributes" => [
                        "affectedRecordType" => "User",
                        "estimatedRecordCount" => 1,
                        "affectedRecordCount" => 1,
                    ],
                ],
                10
            );

            $response = $this->api()
                ->get("automation-rules/{$automationRule["automationRuleID"]}/dispatches")
                ->getBody();
            $this->assertCount(10, $response);
            $this->assertSame($automationRule["automationRuleID"], $response[0]["automationRule"]["automationRuleID"]);
        }, $this->adminID);
    }

    /**
     * Test expands when getting the list of Automation Rule Dispatches.
     *
     * @dataProvider expandProvider()
     * @param array $expand
     * @param array $has
     * @param array $hasNot
     * @return void
     * @throws Exception
     */
    public function testListOfAutomationRuleExpandUser(array $expand, array $has, array $hasNot): void
    {
        $category = $this->createCategory();

        // Create an automation rule with trigger(email domain is test.com) and action(follow the new category).
        $trigger = ["type" => UserEmailDomainTrigger::getType(), "value" => ["emailDomain" => "test.com"]];
        $action = [
            "type" => UserFollowCategoryAction::getType(),
            "value" => ["followedCategory" => ["categoryID" => [$category["categoryID"]]]],
        ];
        $automationRule = $this->createAutomationRule($trigger, $action);
        $this->createAutomationDispatches([
            "automationRuleID" => $automationRule["automationRuleID"],
            "automationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
            "attributes" => [
                "affectedRecordType" => "User",
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => 1,
            ],
            "status" => "success",
        ]);

        $response = $this->api()
            ->get("automation-rules/{$automationRule["automationRuleID"]}/dispatches", ["expand" => $expand])
            ->getBody();
        $firstRecord = $response[0];

        foreach ($has as $key) {
            $this->assertArrayHasKey($key, $firstRecord);
            $this->assertArrayHasKey("userID", $firstRecord[$key]);
            $this->assertCount(7, $firstRecord[$key]);
        }

        foreach ($hasNot as $key) {
            $this->assertArrayNotHasKey($key, $firstRecord);
        }
    }

    /**
     * Data provider for testListOfAutomationRuleExpandUser()
     *
     * @return array[]
     */
    public function expandProvider(): array
    {
        return [
            [
                "expand" => ["insertUser"],
                "has" => ["insertUser"],
                "hasNot" => ["updateUser"],
            ],
            [
                "expand" => ["updateUser"],
                "has" => ["updateUser"],
                "hasNot" => ["insertUser"],
            ],
            [
                "expand" => ["insertUser", "updateUser"],
                "has" => ["insertUser", "updateUser"],
                "hasNot" => [],
            ],
            [
                "expand" => ["all"],
                "has" => ["insertUser", "updateUser"],
                "hasNot" => [],
            ],
            [
                "expand" => [],
                "has" => [],
                "hasNot" => ["insertUser", "updateUser"],
            ],
        ];
    }

    /**
     * Test various filters for the list of Automation Rule Dispatches.
     *
     * @return void
     * @throws Exception
     */
    public function testListOfAutomationRuleFilters(): void
    {
        $category = $this->createCategory();

        // Create a first automation rule with trigger(email domain is test.com) and action(follow the new category).
        $trigger1 = ["type" => UserEmailDomainTrigger::getType(), "value" => ["emailDomain" => "test.com"]];
        $action1 = [
            "type" => UserFollowCategoryAction::getType(),
            "value" => ["followedCategory" => ["categoryID" => [$category["categoryID"]]]],
        ];
        $automationRule1 = $this->createAutomationRule($trigger1, $action1);
        $this->createAutomationDispatches([
            "automationRuleID" => $automationRule1["automationRuleID"],
            "automationRuleRevisionID" => $automationRule1["automationRuleRevisionID"],
            "attributes" => [
                "affectedRecordType" => "User",
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => 1,
            ],
        ]);

        // Create a second automation rule for bumping a stale discussion.
        $trigger2 = [
            "type" => StaleDiscussionTrigger::getType(),
            "value" => [
                "triggerTimeLookBackLimit" => [
                    "length" => 1,
                    "unit" => "week",
                ],
                "triggerTimeDelay" => [
                    "length" => 1,
                    "unit" => "day",
                ],
            ],
        ];
        $action2 = ["type" => BumpDiscussionAction::getType(), "value" => []];
        $automationRule2 = $this->createAutomationRule($trigger2, $action2);
        $this->createAutomationDispatches([
            "automationRuleID" => $automationRule2["automationRuleID"],
            "automationRuleRevisionID" => $automationRule2["automationRuleRevisionID"],
            "attributes" => [
                "affectedRecordType" => "discussion",
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => 1,
            ],
        ]);

        // Get all the automation rule dispatches.
        $response = $this->api()
            ->get("automation-rules/dispatches")
            ->getBody();
        $this->assertCount(2, $response);
        // Test filtering by actionType.
        $response = $this->api()
            ->get("automation-rules/dispatches", ["actionType" => "bumpDiscussionAction"])
            ->getBody();
        $this->assertCount(1, $response);
        // Test filtering by dispatchStatus.
        $response = $this->api()
            ->get("automation-rules/dispatches", [
                "dispatchStatus" => [
                    AutomationRuleDispatchesModel::STATUS_RUNNING,
                    AutomationRuleDispatchesModel::STATUS_SUCCESS,
                    AutomationRuleDispatchesModel::STATUS_WARNING,
                    AutomationRuleDispatchesModel::STATUS_FAILED,
                ],
            ])
            ->getBody();
        $this->assertCount(0, $response);
        $response = $this->api()
            ->get("automation-rules/dispatches", [
                "dispatchStatus" => [AutomationRuleDispatchesModel::STATUS_QUEUED],
            ])
            ->getBody();
        $this->assertCount(2, $response);
    }

    /**
     * Test date based filters for the list of Automation Rule Dispatches.
     *
     * @return void
     * @throws Exception
     */
    public function testListOfAutomationRuleDateFilters(): void
    {
        $category = $this->createCategory();
        $trigger = ["type" => UserEmailDomainTrigger::getType(), "value" => ["emailDomain" => "test.com"]];
        $action = [
            "type" => UserFollowCategoryAction::getType(),
            "value" => ["followedCategory" => ["categoryID" => [$category["categoryID"]]]],
        ];

        // We create 3 separate automation rules.
        // Automation Rule 1: dateLastRun = 2020-06-06 17:00:00
        $automationRule1 = $this->createAutomationRule($trigger, $action, true, [
            "dateLastRun" => "2020-06-06 17:00:00",
        ]);
        $this->createAutomationDispatches([
            "automationRuleID" => $automationRule1["automationRuleID"],
            "automationRuleRevisionID" => $automationRule1["automationRuleRevisionID"],
            "attributes" => [
                "affectedRecordType" => "User",
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => 1,
            ],
        ]);

        // Automation Rule 2: dateLastRun = 2021-06-07 17:00:00
        $automationRule2 = $this->createAutomationRule($trigger, $action, true, [
            "dateLastRun" => "2020-06-07 17:00:00",
        ]);
        $this->createAutomationDispatches([
            "automationRuleID" => $automationRule2["automationRuleID"],
            "automationRuleRevisionID" => $automationRule2["automationRuleRevisionID"],
            "attributes" => [
                "affectedRecordType" => "User",
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => 1,
            ],
        ]);

        // Automation Rule 3: dateLastRun = 2022-06-08 17:00:00
        $automationRule3 = $this->createAutomationRule($trigger, $action, true, [
            "dateLastRun" => "2020-06-07 17:00:00",
        ]);
        $this->createAutomationDispatches([
            "automationRuleID" => $automationRule3["automationRuleID"],
            "automationRuleRevisionID" => $automationRule3["automationRuleRevisionID"],
            "attributes" => [
                "affectedRecordType" => "User",
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => 1,
            ],
        ]);

        // We have 2 automation rules with dateLastRun values that are on or after 2020-06-07 & updated today..
        $records = $this->api()
            ->get("automation-rules/dispatches", ["dateLastRun" => ">=2020-06-07", "dateUpdated" => date("Y-m-d")])
            ->getBody();
        $this->assertCount(2, $records);
        // We have 1 automation rule that was last run on 2020-06-06.
        $records = $this->api()
            ->get("automation-rules/dispatches", ["dateLastRun" => "2020-06-06"])
            ->getBody();
        $this->assertCount(1, $records);
        // We have 0 automation rules that were updated after today.
        $records = $this->api()
            ->get("automation-rules/dispatches", ["dateUpdated" => ">" . date("Y-m-d")])
            ->getBody();
        $this->assertCount(0, $records);
    }

    /**
     * Test that a dispatch record entry is created for timed based trigger on its initial run.
     *
     * @return void
     * @throws Exception
     */
    public function testDispatchRecordGetsCreatedOnInitialRun()
    {
        $trigger = [
            "type" => LastActiveDiscussionTrigger::getType(),
            "value" => [
                "applyToNewContentOnly" => false,
                "triggerTimeLookBackLimit" => [
                    "length" => 5,
                    "unit" => "day",
                ],
                "triggerTimeDelay" => [
                    "length" => 4,
                    "unit" => "day",
                ],
                "postType" => ["discussion"],
            ],
        ];
        $action = [
            "type" => CloseDiscussionAction::getType(),
            "value" => [],
        ];

        $automationRule = $this->createAutomationRule($trigger, $action, false);

        $this->api()->put("automation-rules/{$automationRule["automationRuleID"]}/status", [
            "status" => AutomationRuleModel::STATUS_ACTIVE,
        ]);

        $dispatchedRecords = $this->automationRuleDispatchesModel->select([
            "automationRuleID" => $automationRule["automationRuleID"],
        ]);
        $this->assertCount(1, $dispatchedRecords);
        $dispatchedRecords = array_shift($dispatchedRecords);
        $this->assertEquals(AutomationRuleDispatchesModel::STATUS_WARNING, $dispatchedRecords["status"]);
        $this->assertEquals("initial", $dispatchedRecords["dispatchType"]);
        $this->assertSame(
            [
                "affectedRecordType" => "Discussion",
                "estimatedRecordCount" => 0,
                "affectedRecordCount" => 0,
            ],
            $dispatchedRecords["attributes"]
        );
    }
}
