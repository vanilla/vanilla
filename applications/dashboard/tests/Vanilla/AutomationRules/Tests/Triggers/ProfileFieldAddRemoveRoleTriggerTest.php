<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Tests\Triggers;

use AutomationRules\Triggers\ProfileFieldUserFollowCategoryTriggerTest;
use Vanilla\Dashboard\AutomationRules\Actions\AddRemoveUserRoleAction;
use Vanilla\Dashboard\AutomationRules\Triggers\ProfileFieldSelectionTrigger;
use VanillaTests\AutomationRules\AutomationRulesTestTrait;
use VanillaTests\AutomationRules\ProfileFieldTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Class to test the add and remove profile field trigger
 */
class ProfileFieldAddRemoveRoleTriggerTest extends SiteTestCase
{
    use AutomationRulesTestTrait,
        CommunityApiTestTrait,
        UsersAndRolesApiTestTrait,
        ProfileFieldTrait,
        ExpectExceptionTrait;

    private \LogModel $logModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->initialize();
        $this->logModel = $this->container()->get(\LogModel::class);

        $this->resetTable("Log");
        $this->resetTable("profileField");
    }

    /**
     * Get a test automation record
     *
     * @param array $profileFieldValues
     * @param int $addRoleID
     * @param int|null $removeRoleID
     * @return array[]
     */
    private function getAutomationRecord(array $profileFieldValues, int $addRoleID, ?int $removeRoleID = null): array
    {
        $actionValue = [
            "addRoleID" => $addRoleID,
        ];
        if (!empty($removeRoleID)) {
            $actionValue["removeRoleID"] = $removeRoleID;
        }
        return [
            "trigger" => [
                "type" => ProfileFieldSelectionTrigger::getType(),
                "value" => ["profileField" => $profileFieldValues],
            ],
            "action" => [
                "type" => AddRemoveUserRoleAction::getType(),
                "value" => $actionValue,
            ],
        ];
    }

    /**
     * Get a test registration record
     *
     * @param array $registerFields
     * @param array $profileFields
     * @return array
     * @throws \Exception
     */
    public function getRegistrationRecord(array $registerFields, array $profileFields): array
    {
        return ProfileFieldUserFollowCategoryTriggerTest::getRegistrationRecord($registerFields, $profileFields);
    }

    /**
     * Test the action is not executed when the automation rule is not active.
     */
    public function testActionNotExecutedWhenRuleIsNotActive(): void
    {
        $this->generateProfileField(["apiName" => "field-1", "label" => "Field 1"]);
        $record = $this->getAutomationRecord(["field-1" => "testValue"], 3);

        $this->createAutomationRule($record["trigger"], $record["action"], false);

        $formFields = $this->getRegistrationRecord(
            ["Email" => "testUser1@example.com", "Name" => "testUser_1"],
            ["field-1" => "testValue"]
        );

        $registrationResults = $this->registerNewUser($formFields);
        $this->assertNotEmpty($registrationResults->Form->_FormValues["Profile"]["field-1"]);
        $this->assertNotEmpty($registrationResults->Data["UserID"]);

        $this->assertLog([
            "level" => "debug",
            "message" => "No active automation rules found for profileFieldTrigger and categoryFollowAction",
        ]);

        // Profile fields should be successfully saved in userMeta
        $userMetaData = \Gdn::userMetaModel()->getUserMeta($registrationResults->Data["UserID"]);
        $this->assertEquals("testValue", $userMetaData["Profile.field-1"]);
    }

    /**
     * Test that the action execution is skipped when the user selected profile field value don't match with the trigger value.
     *
     * @return void
     */
    public function testActionNotExecutedWhenTheUserSelectedProfileFieldValueDontMatch(): void
    {
        $this->generateProfileField(["apiName" => "field-1", "label" => "Field 1"]);
        $record = $this->getAutomationRecord(["field-1" => "testValue"], 3);
        $automationRule = $this->createAutomationRule($record["trigger"], $record["action"]);
        $formFields = $this->getRegistrationRecord(
            ["Email" => "testUser2@example.com", "Name" => "testUser_2"],
            ["field-1" => "someValue"]
        );
        $registrationResults = $this->registerNewUser($formFields);
        $userID = $registrationResults->Data["UserID"];
        $message = "Automation rule {$automationRule["automationRuleID"]} skipped for user $userID";
        $this->assertNotEmpty($registrationResults->Form->_FormValues["Profile"]["field-1"]);
        $this->assertLogMessage($message);
    }

    /**
     * Test single automation rule execution.
     *
     * @return void
     */
    public function testSingleRuleExecution()
    {
        // Create some new roles
        $testRole = $this->createRole([
            "name" => "TestRole",
            "description" => "Test Role Description",
            "type" => "moderator",
        ]);
        $testRoleID = $testRole["roleID"];

        // Create a profile field
        $this->generateDropDownField([
            "apiName" => "favourite-sport",
            "label" => "Favourite Sport",
            "dropdownOptions" => ["Cricket", "Football", "Tennis"],
        ]);
        $record = $this->getAutomationRecord(["favourite-sport" => ["Cricket", "Tennis"]], $testRoleID);
        $automationRule = $this->createAutomationRule($record["trigger"], $record["action"]);

        $this->generateProfileField(["apiName" => "favourite-food", "label" => "Favourite Food"]);
        $foodRole = $this->createRole(["name" => "pizza", "description" => "pizza role", "type" => "moderator"]);
        $foodRoleID = $foodRole["roleID"];
        $record = $this->getAutomationRecord(["favourite-food" => "Pizza"], $foodRoleID, $testRoleID);
        $secondAutomationRule = $this->createAutomationRule($record["trigger"], $record["action"]);

        $formFields = $this->getRegistrationRecord(
            ["Email" => "JohnDoe@example.com", "Name" => "JohnDoe"],
            ["favourite-sport" => "Tennis"]
        );

        $registrationResults = $this->registerNewUser($formFields);
        $userID = $registrationResults->Data["UserID"];
        $userRoles = $this->userModel->getRoles($userID)->resultArray();
        $this->assertTrue(in_array($testRoleID, array_column($userRoles, "RoleID")));

        $dispatched = $this->getDispatchedRules(null, ["success"]);
        $this->assertCount(1, $dispatched);
        $this->assertEquals($automationRule["automationRuleID"], $dispatched[0]["automationRuleID"]);
        $this->assertEquals($automationRule["automationRuleRevisionID"], $dispatched[0]["automationRuleRevisionID"]);
        $this->assertNotEmpty($dispatched[0]["dateFinished"]);

        $logged = $this->getLogs([
            "Operation" => "Automation",
            "RecordType" => "UserRole",
            "RecordUserID" => $userID,
        ]);
        $this->assertCount(1, $logged);
        $this->assertEquals($dispatched[0]["automationRuleDispatchUUID"], $logged[0]["DispatchUUID"]);
        $this->assertEquals($testRoleID, $logged[0]["Data"]["RoleAdded"]);

        // Update user profile fields
        $this->updateProfileFields(["favourite-sport" => "Tennis", "favourite-food" => "Pizza"], $userID);
        $userRoles = $this->userModel->getRoles($userID)->resultArray();

        $this->assertTrue(in_array($foodRoleID, array_column($userRoles, "RoleID")));
        $logged = $this->getLogs([
            "Operation" => "Automation",
            "RecordType" => "UserRole",
            "RecordUserID" => $userID,
            "AutomationRuleRevisionID" => $secondAutomationRule["automationRuleRevisionID"],
        ]);

        $this->assertCount(1, $logged);
        $this->assertEquals([3, 8, 33], $logged[0]["Data"]["CurrentRoles"]);
        $this->assertEquals($foodRoleID, $logged[0]["Data"]["RoleAdded"]);
        $this->assertEquals($testRoleID, $logged[0]["Data"]["RoleRemoved"]);
    }

    /**
     * Simultaneously run multiple rules on a one user
     *
     * @return void
     */
    public function testMultiRuleAutomation()
    {
        $data = $this->prepareMultiRuleTestData();
        $defaultRoleIds = [3, 8];
        $roles = $data["roleIDs"];
        $formFields = $this->getRegistrationRecord(
            ["Email" => "JohnyWalker@example.com", "Name" => "JohnWalker"],
            ["favourite-sport" => "Tennis", "favourite-food" => "Pizza", "favourite-color" => "Red"]
        );

        $registrationResults = $this->registerNewUser($formFields);
        $userID = $registrationResults->Data["UserID"];
        $userRoles = $this->userModel->getRoles($userID)->resultArray();
        $this->assertEquals(
            array_merge($defaultRoleIds, [$roles["foodRole"], $roles["colorRole"]]),
            array_column($userRoles, "RoleID")
        );
        $dispatches = $this->getDispatchedRules(null, ["success"]);
        $this->assertCount(3, $dispatches);

        $logs = $this->getLogs([
            "Operation" => "Automation",
            "RecordType" => "UserRole",
            "RecordUserID" => $userID,
        ]);
        $this->assertCount(3, $logs);
        $expected = [];
        $expected[0] = ["currentRoles" => $defaultRoleIds, "roleAdded" => $roles["sportRole"], "roleRemoved" => null];
        $expected[1] = [
            "currentRoles" => array_merge($defaultRoleIds, [$roles["sportRole"]]),
            "roleAdded" => $roles["foodRole"],
            "roleRemoved" => null,
        ];
        $expected[2] = [
            "currentRoles" => array_merge($defaultRoleIds, [$roles["sportRole"], $roles["foodRole"]]),
            "roleAdded" => $roles["colorRole"],
            "roleRemoved" => $roles["sportRole"],
        ];
        foreach ($logs as $key => $log) {
            $data = $log["Data"];
            $this->assertEquals($expected[$key]["currentRoles"], $data["CurrentRoles"]);
            $this->assertEquals($expected[$key]["roleAdded"], $data["RoleAdded"]);
            if (!empty($expected[$key]["roleRemoved"])) {
                $this->assertEquals($expected[$key]["roleRemoved"], $data["RoleRemoved"]);
            }
        }
    }

    /**
     * provide data for multi rule test
     *
     * @return array[]
     */
    public function prepareMultiRuleTestData(): array
    {
        $this->generateProfileField(["apiName" => "favourite-sport", "label" => "Favourite Sport"]);
        $this->generateProfileField(["apiName" => "favourite-food", "label" => "Favourite Food"]);
        $this->generateProfileField(["apiName" => "favourite-color", "label" => "Favourite Color"]);

        $sportRole = $this->createRole([
            "name" => "SportRole",
            "description" => "Sport Role",
            "type" => "",
        ]);
        $sportRoleID = $sportRole["roleID"];

        $foodRole = $this->createRole(["name" => "FoodRole", "description" => "Food Role", "type" => ""]);
        $foodRoleID = $foodRole["roleID"];

        $colorRole = $this->createRole(["name" => "ColorRole", "description" => "color role", "type" => ""]);
        $colorRoleID = $colorRole["roleID"];

        $record1 = $this->getAutomationRecord(["favourite-sport" => "Tennis"], $sportRoleID);
        $sportRule = $this->createAutomationRule($record1["trigger"], $record1["action"]);

        $record2 = $this->getAutomationRecord(["favourite-food" => "Pizza"], $foodRoleID);
        $foodRule = $this->createAutomationRule($record2["trigger"], $record2["action"]);

        $record3 = $this->getAutomationRecord(["favourite-color" => "Red"], $colorRoleID, $sportRoleID);
        $colorRule = $this->createAutomationRule($record3["trigger"], $record3["action"]);

        return [
            "automationRule" => [
                "sportRule" => $sportRule,
                "foodRule" => $foodRule,
                "colorRule" => $colorRule,
            ],
            "roleIDs" => [
                "sportRole" => $sportRoleID,
                "foodRole" => $foodRoleID,
                "colorRole" => $colorRoleID,
            ],
        ];
    }
}
