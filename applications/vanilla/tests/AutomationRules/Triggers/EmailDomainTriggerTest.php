<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace AutomationRules\Triggers;

use CategoryModel;
use LogModel;
use Vanilla\AutomationRules\Actions\UserFollowCategoryAction;
use Vanilla\Dashboard\AutomationRules\Actions\AddRemoveUserRoleAction;
use Vanilla\Dashboard\AutomationRules\Triggers\UserEmailDomainTrigger;
use VanillaTests\AutomationRules\AutomationRulesTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

class EmailDomainTriggerTest extends SiteTestCase
{
    use AutomationRulesTestTrait, CommunityApiTestTrait, UsersAndRolesApiTestTrait;

    private CategoryModel $categoryModel;
    private LogModel $logModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->initialize();
        $this->categoryModel = $this->container()->get(\CategoryModel::class);
        $this->logModel = $this->container()->get(\LogModel::class);
    }

    /**
     * Get a test automation record
     *
     * @param string $actionType
     * @param array $emailDomains
     * @param array $triggerValue
     * @return array[]
     */
    private function getAutomationRecord(string $actionType, array $emailDomains, array $triggerValue): array
    {
        return [
            "trigger" => [
                "type" => UserEmailDomainTrigger::getType(),
                "value" => ["emailDomain" => implode(", ", $emailDomains)],
            ],
            "action" => [
                "type" => $actionType,
                "value" => $triggerValue,
            ],
        ];
    }

    /**
     * Test users with unconfirmed email are not processed.
     *
     * @return int
     */
    public function testEmailDomainRuleIsNotExecutedWhenUserIsNotConfirmed(): int
    {
        $user = $this->createUser([
            "name" => "Michelle",
            "email" => "Michelle@example.com",
            "emailConfirmed" => false,
        ]);
        $this->assertLogMessage(
            "Skipped processing user record for {$user["email"]}. User is either not confirmed or is not a valid user update."
        );
        return $user["userID"];
    }

    /**
     * Test that users who have a confirmed email are processed.
     *
     * @depends testEmailDomainRuleIsNotExecutedWhenUserIsNotConfirmed
     */
    public function testAutomationRuleIsProcessedForConfirmedUser(int $userID): void
    {
        $this->createCategory([
            "name" => "Test",
            "urlCode" => "test",
            "description" => "Test Category Description",
        ]);
        $categoryID = $this->lastInsertedCategoryID;

        $automationRecord = $this->getAutomationRecord(
            UserFollowCategoryAction::getType(),
            ["example.com"],
            ["categoryID" => [$categoryID]]
        );
        $automationRule = $this->createAutomationRule($automationRecord["trigger"], $automationRecord["action"]);

        $this->updateUser($userID, ["emailConfirmed" => true]);

        $userCategories = $this->categoryModel->getFollowed($userID);
        $followedCategories = array_column($userCategories, "CategoryID");
        $this->assertEquals([$categoryID], $followedCategories);

        //Should create exactly one dispatch and a log
        $dispatches = $this->getDispatchedRules($automationRule["automationRuleID"], ["success"]);
        $this->assertCount(1, $dispatches);
        $this->assertEquals(
            [
                "affectedRecordType" => "User",
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => 1,
            ],
            $dispatches[0]["attributes"]
        );
        // Check DateFinished is updated
        $this->assertNotEmpty($dispatches[0]["dateFinished"]);
        $this->assertEquals(date("Y-m-d H:i", $dispatches[0]["dateFinished"]->getTimestamp()), date("Y-m-d H:i"));
        $log = $this->getLogs([
            "Operation" => "Automation",
            "RecordType" => "UserCategory",
            "AutomationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
        ]);
        $this->assertCount(1, $log);
        $logData = $log[0]["Data"];
        $this->assertEquals([2], $logData["newFollowedCategories"]);
    }

    /**
     * Test automation rule is not processed when rules are not active
     */
    public function testAutomationRuleIsNotProcessedWhenRuleIsNotActive(): void
    {
        $this->createCategory([
            "name" => "Category 1",
            "urlCode" => "cat-1",
            "description" => "Test Category Description",
        ]);
        $categoryID = $this->lastInsertedCategoryID;

        $automationRecord = $this->getAutomationRecord(
            UserFollowCategoryAction::getType(),
            ["example.com"],
            ["categoryID" => [$categoryID]]
        );
        $this->createAutomationRule($automationRecord["trigger"], $automationRecord["action"], false);
        $automationRuleID = $this->lastRuleID;
        $user = $this->createUser([
            "name" => "Jack",
            "email" => "jack.daniels@example.com",
        ]);
        $triggerType = UserEmailDomainTrigger::getType();
        $actionType = UserFollowCategoryAction::getType();
        $this->assertLogMessage("No active automation rules found for $triggerType and $actionType");

        $dispatches = $this->getDispatchedRules($automationRuleID, ["success"]);
        $this->assertCount(0, $dispatches);

        $followedCategories = $this->categoryModel->getFollowed($user["userID"]);
        $this->assertCount(0, $followedCategories);
    }

    /**
     *  Test multiple rules are processed
     */
    public function testMultipleRulesAreProcessed(): void
    {
        $this->createCategory([
            "name" => "Higher Logic Staff",
            "urlCode" => "hl",
            "description" => "Higher Logic Staff Category Description",
        ]);
        $categoryID1 = $this->lastInsertedCategoryID;
        $this->createCategory([
            "name" => "Vanilla Staff",
            "urlCode" => "vnla",
            "description" => "Vanilla Staff Category Description",
        ]);
        $categoryID2 = $this->lastInsertedCategoryID;

        $this->createRole(["name" => "Higher Logic Staff", "description" => "Higher Logic Staff Role", "type" => ""]);
        $roleID1 = $this->lastRoleID;
        $this->createRole(["name" => "Vanilla Staff", "description" => "Vanilla Staff Role", "type" => ""]);
        $roleID2 = $this->lastRoleID;

        $automationRecord = $this->getAutomationRecord(
            UserFollowCategoryAction::getType(),
            ["higherlogic.com"],
            ["categoryID" => [$categoryID1]]
        );
        $higherLogicCategoryRule = $this->createAutomationRule(
            $automationRecord["trigger"],
            $automationRecord["action"]
        );

        $automationRecord = $this->getAutomationRecord(
            AddRemoveUserRoleAction::getType(),
            ["higherlogic.com"],
            ["addRoleID" => $roleID1]
        );
        $higherLogicRoleRule = $this->createAutomationRule($automationRecord["trigger"], $automationRecord["action"]);

        $automationRecord = $this->getAutomationRecord(
            UserFollowCategoryAction::getType(),
            ["vanilla.com"],
            ["categoryID" => [$categoryID2]]
        );
        $this->createAutomationRule($automationRecord["trigger"], $automationRecord["action"]);

        $automationRecord = $this->getAutomationRecord(
            AddRemoveUserRoleAction::getType(),
            ["vanilla.com"],
            ["addRoleID" => $roleID2]
        );
        $this->createAutomationRule($automationRecord["trigger"], $automationRecord["action"]);

        // Create a user with higherlogic.com email
        $user1 = $this->createUser([
            "name" => "John",
            "email" => "John.smith@higherlogic.com",
        ]);
        $userCategories = $this->categoryModel->getFollowed($user1["userID"]);
        $this->assertCount(1, $userCategories);
        $this->assertEquals($categoryID1, key($userCategories));
        $userRoles = $this->userModel->getRoles($user1["userID"])->resultArray();
        $this->assertTrue(in_array($roleID1, array_column($userRoles, "RoleID")));

        $user2 = $this->createUser([
            "name" => "Jane",
            "email" => "Jane.doey@VANILLA.com",
        ]);
        $userCategories = $this->categoryModel->getFollowed($user2["userID"]);
        $this->assertCount(1, $userCategories);
        $this->assertEquals($categoryID2, key($userCategories));
        $userRoles = $this->userModel->getRoles($user2["userID"])->resultArray();
        $this->assertTrue(in_array($roleID2, array_column($userRoles, "RoleID")));

        // Make sure there is one dispatch per rule ID
        $dispatches = $this->getDispatchedRules($higherLogicCategoryRule["automationRuleID"], ["success"]);
        $this->assertCount(1, $dispatches);
        $this->assertEquals(
            [
                "affectedRecordType" => "User",
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => 1,
            ],
            $dispatches[0]["attributes"]
        );
        $this->assertNotEmpty($dispatches[0]["dateFinished"]);
        $dispatches = $this->getDispatchedRules($higherLogicRoleRule["automationRuleID"], ["success"]);
        $this->assertNotEmpty($dispatches[0]["dateFinished"]);
        $this->assertEquals(
            [
                "affectedRecordType" => "User",
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => 1,
            ],
            $dispatches[0]["attributes"]
        );
        $this->assertCount(1, $dispatches);
    }

    /**
     * Update a user
     *
     * @param int $userID
     * @param $data
     * @return array
     */
    private function updateUser(int $userID, $data): array
    {
        $result = $this->api()->patch("/users/{$userID}", $data);
        $this->assertEquals(200, $result->getStatusCode());
        return $result->getBody();
    }
}
