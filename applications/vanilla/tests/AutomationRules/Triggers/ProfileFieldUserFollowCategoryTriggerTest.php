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
use Vanilla\Dashboard\AutomationRules\Triggers\ProfileFieldSelectionTrigger;
use VanillaTests\AutomationRules\AutomationRulesTestTrait;
use VanillaTests\AutomationRules\ProfileFieldTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the ProfileFieldUserFollowCategoryTrigger
 */
class ProfileFieldUserFollowCategoryTriggerTest extends SiteTestCase
{
    use AutomationRulesTestTrait,
        CommunityApiTestTrait,
        UsersAndRolesApiTestTrait,
        ProfileFieldTrait,
        ExpectExceptionTrait;

    private LogModel $logModel;
    private CategoryModel $categoryModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->initialize();
        $this->logModel = $this->container()->get(\LogModel::class);
        $this->categoryModel = $this->container()->get(\CategoryModel::class);

        $this->resetTable("Log");
        $this->resetTable("profileField");
    }

    /**
     * Get a test automation record
     *
     * @param array $profileFieldValues
     * @param array $categoryList
     * @return array[]
     */
    private function getAutomationRecord(array $profileFieldValues, array $categoryList): array
    {
        return [
            "trigger" => [
                "type" => ProfileFieldSelectionTrigger::getType(),
                "value" => ["profileField" => $profileFieldValues],
            ],
            "action" => [
                "type" => UserFollowCategoryAction::getType(),
                "value" => ["categoryID" => $categoryList],
            ],
        ];
    }

    /**
     * Get a test registration record
     *
     * @param array $overrides
     * @param array $profileFields
     * @return array
     * @throws \Exception
     */
    public static function getRegistrationRecord(array $overrides = [], array $profileFields = [])
    {
        $password = bin2Hex(random_bytes(6));
        $slug = randomString(10, "abcdefghijklmnopqrstuvwxyz1234567890");
        $record = $overrides + [
            "Email" => $slug . "@test.com",
            "Name" => $slug,
            "Password" => $password,
            "PasswordMatch" => $password,
            "TermsOfService" => "1",
            "Save" => "Save",
        ];

        if (!empty($profileFields)) {
            $record["Profile"] = $profileFields;
        }
        return $record;
    }

    /**
     * Test that the action is not executed when the automation rule is not active.
     */
    public function testActionNotExecutedWhenRuleIsNotActive(): void
    {
        $this->generateProfileField(["apiName" => "field-1", "label" => "Field 1"]);
        $record = $this->getAutomationRecord(["field-1" => "testValue"], [1]);

        $this->createAutomationRule($record["trigger"], $record["action"], false);

        $formFields = self::getRegistrationRecord(
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

        //profile fields should be successfully saved in userMeta
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
        $record = $this->getAutomationRecord(["field-1" => "testValue"], [1]);
        $automationRule = $this->createAutomationRule($record["trigger"], $record["action"]);
        $formFields = self::getRegistrationRecord(
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
     * Test partial success of the action execution.
     *
     * @return void
     */
    public function testCategoryFollowActionOnRegistrationWithPartialSuccess()
    {
        // Create some categories to follow
        $currentUserID = $this->getSession()->UserID;
        $categories = [];
        $this->createCategory(["name" => "Sports", "urlCode" => "sports", "parentCategoryID" => -1]);
        $categories[] = $this->lastInsertedCategoryID;
        $this->createCategory(["name" => "General", "urlCode" => "music", "parentCategoryID" => -1]);
        $categories[] = $this->lastInsertedCategoryID;

        // Add an invalid category to the list
        $categories[] = 999;

        // Create a profile field
        $this->generateDropDownField([
            "apiName" => "favourite-sport",
            "label" => "Favourite Sport",
            "dropdownOptions" => ["Cricket", "Football", "Tennis"],
        ]);
        $record = $this->getAutomationRecord(["favourite-sport" => ["Cricket", "Tennis"]], $categories);

        $automationRule = $this->createAutomationRule($record["trigger"], $record["action"]);
        $formFields = self::getRegistrationRecord(
            ["Email" => "cricket-fan@test.com", "Name" => "CricketFan"],
            ["favourite-sport" => "Cricket"]
        );
        $registrationResults = $this->registerNewUser($formFields);

        $userID = $registrationResults->Data["UserID"];

        // Make sure the user is following the categories
        $userCategories = $this->categoryModel->getFollowed($userID);
        $this->assertNotEmpty($userCategories);
        $followedCategoryIDs = array_column($userCategories, "CategoryID");

        // Remove the invalid Category ID
        array_pop($categories);
        $this->assertEquals($categories, $followedCategoryIDs);

        // make sure we have made a successful dispatch
        $dispatched = $this->automationRuleDispatchesModel->select([
            "automationRuleID" => $automationRule["automationRuleID"],
            "dispatchType" => "triggered",
        ]);
        // Assert that there is only a single dispatch
        $this->assertCount(1, $dispatched);

        $dispatchedRecord = $dispatched[0];

        //Make sure an error has been logged for the invalid category
        $this->assertLog([
            "level" => "error",
            "message" => "Error occurred trying to follow category",
            "data" => [
                "automationRuleID" => $automationRule["automationRuleID"],
                "automationRuleDispatchUUID" => $dispatchedRecord["automationRuleDispatchUUID"],
                "categoryID" => 999,
                "error" => "Failed setting users notification preference., Category not found.",
            ],
        ]);
        $this->assertEquals(
            [
                "affectedRecordType" => "User",
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => 1,
            ],
            $dispatchedRecord["attributes"]
        );

        //Make sure we have a successful log entry for the dispatch
        $dispatchLog = $this->logModel->getWhere([
            "DispatchUUID" => $dispatchedRecord["automationRuleDispatchUUID"],
            "AutomationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
        ]);

        $this->assertCount(1, $dispatchLog);

        $this->assertEquals("triggered", $dispatchedRecord["dispatchType"]);
        $this->assertEquals("warning", $dispatchedRecord["status"]);
        $this->assertEquals(
            "CategoryID: 999 - Failed setting users notification preference., Category not found.",
            $dispatchedRecord["errorMessage"]
        );
        $this->assertEquals($currentUserID, $dispatchedRecord["dispatchUserID"]);

        $dispatchLogRecord = $dispatchLog[0];
        $this->assertEquals("UserCategory", $dispatchLogRecord["RecordType"]);
        $this->assertEquals($userID, $dispatchLogRecord["RecordUserID"]);
        $this->assertEquals(
            $automationRule["automationRuleRevisionID"],
            $dispatchLogRecord["AutomationRuleRevisionID"]
        );
        $this->assertEquals(
            [
                "newFollowedCategories" => $categories,
            ],
            $dispatchLogRecord["Data"]
        );
        $this->assertEquals($dispatchedRecord["automationRuleDispatchUUID"], $dispatchLogRecord["DispatchUUID"]);
    }

    /**
     * Test the action execution when the user selected profile fields value matches with the multiple trigger values.
     *
     * @return void
     */
    public function testMultiTriggerExecutions(): void
    {
        $data = $this->prepareDataForMultiTriggerExecution();

        //create a new user with the profession "Engineering"
        $formFields = self::getRegistrationRecord(
            ["Email" => "engineer@test.com", "Name" => "JaneDoe"],
            [
                "profession" => "Engineering",
                "ice-cream" => '[{"value":"Vanilla","label":"Vanilla"}, {"value":"Chocolate","label":"Chocolate"}]',
                "adult-age" => true,
            ]
        );
        $registrationResults = $this->registerNewUser($formFields);

        //User should be assigned with all the 3 profile-fields and the corresponding categories should be followed

        $userID = $registrationResults->Data["UserID"];
        $userMetaData = \Gdn::userMetaModel()->getUserMeta($userID);

        //Make sure all the profile fields are saved prorperly
        $this->assertEquals("Engineering", $userMetaData["Profile.profession"]);
        $this->assertEquals(["Vanilla", "Chocolate"], $userMetaData["Profile.ice-cream"]);
        $this->assertEquals(1, $userMetaData["Profile.adult-age"]);

        //Now make sure the user is following to the categories based on rules
        $userCategories = $this->categoryModel->getFollowed($userID);
        $userCategoryIDS = array_column($userCategories, "CategoryID");
        $this->assertCount(4, $userCategories);
        $expectedCategories = [
            $data["categories"]["professional"]["engineering"],
            $data["categories"]["iceCream"]["v"],
            $data["categories"]["iceCream"]["o"],
            $data["categories"]["event"]["Adult"],
        ];
        $this->assertEquals($expectedCategories, $userCategoryIDS);

        $dispatched = $this->getDispatchedRules(null, ["success"]);
        // Now we have to make sure 4 dispatches are made for the 4 rules
        $this->assertCount(4, $dispatched);
        $this->assertEquals(
            [
                "affectedRecordType" => "User",
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => 1,
            ],
            $dispatched[0]["attributes"]
        );
        $this->assertNotEmpty($dispatched[0]["dateFinished"]);
        $dispatchedRuleIDs = array_column($dispatched, "automationRuleID");
        $keys = ["engineering-rule", "vanilla-rule", "other-rule", "Adult-rule"];
        $expectedRuleIDs = [];
        foreach ($keys as $key) {
            $expectedRuleIDs[] = $data["automationRules"][$key]["automationRuleID"];
        }
        $this->assertEqualsCanonicalizing($expectedRuleIDs, $dispatchedRuleIDs);

        // Now we have to check the logs were made for the dispatches

        $dispatchLog = $this->logModel->getWhere(["Operation" => "Automation", "RecordType" => "UserCategory"]);
        $this->assertCount(4, $dispatchLog);
        $this->assertEqualsCanonicalizing(
            array_column($dispatched, "automationRuleDispatchUUID"),
            array_column($dispatchLog, "DispatchUUID")
        );
        foreach ($dispatchLog as $key => $log) {
            if ($key != 0) {
                $this->assertEquals(
                    array_slice($expectedCategories, 0, $key),
                    $log["Data"]["currentlyFollowedCategories"]
                );
            }
            $this->assertEquals([$expectedCategories[$key]], $log["Data"]["newFollowedCategories"]);
        }

        // Now we have to update the user profile fields and make sure the action is executed again only when the data is different
        $this->updateProfileFields(
            [
                "profession" => "Education",
                "ice-cream" => ["Vanilla", "Butterscotch"],
                "adult-age" => true,
            ],
            $userID
        );

        // Now the user should follow a new category and the currently followed categories should remain same
        $userCategories = $this->categoryModel->getFollowed($userID);
        $userCategoryIDS = array_column($userCategories, "CategoryID");
        $this->assertCount(5, $userCategories);
        $this->assertEqualsCanonicalizing(
            array_merge($expectedCategories, [$data["categories"]["professional"]["social"]]),
            $userCategoryIDS
        );

        // Now we have to make sure we have only a total of 5 dispatches. Only 1 rule should be dispatched
        $dispatched = $this->getDispatchedRules(null, ["success"]);
        $this->assertCount(5, $dispatched);

        // check for one more registration with inactive rule
        $formFields = self::getRegistrationRecord(
            ["Email" => "jackDaniels@test.com", "Name" => "JackDaniels"],
            [
                "profession" => "Artist",
                "ice-cream" => '[{"value":"Vanilla","label":"Vanilla"}]',
                "senior-age" => true,
            ]
        );
        $registrationResults = $this->registerNewUser($formFields);

        $newUserID = $registrationResults->Data["UserID"];
        $userCategories = $this->categoryModel->getFollowed($newUserID);
        $userCategoryIDS = array_column($userCategories, "CategoryID");
        $this->assertCount(2, $userCategories);
        $this->assertEqualsCanonicalizing(
            [$data["categories"]["professional"]["social"], $data["categories"]["iceCream"]["v"]],
            $userCategoryIDS
        );

        // Now we have to make sure we have only a total of 7 dispatches. Only 2 rules should be dispatched
        $totalDispatched = $this->automationRuleDispatchesModel
            ->getSql()
            ->getCount($this->automationRuleDispatchesModel->getTable(), [
                "dispatchType" => "triggered",
                "status" => "success",
            ]);
        $this->assertEquals(7, $totalDispatched);

        // Now we have to make sure the logs were made for the dispatches
        $dispatchLog = $this->getLogs([
            "Operation" => "Automation",
            "RecordType" => "UserCategory",
            "RecordUserID" => $newUserID,
        ]);
        $this->assertCount(2, $dispatchLog);
    }

    /**
     *  Prepare initial data for multi trigger execution
     *
     * @return array
     */
    private function prepareDataForMultiTriggerExecution(): array
    {
        $data = [];
        // Create some categories to follow
        $professionCategory = [];
        $this->createCategory(["name" => "Social", "urlCode" => "social", "parentCategoryID" => -1]);
        $professionCategory["social"] = $this->lastInsertedCategoryID;
        $this->createCategory(["name" => "Engineering", "urlCode" => "eng", "parentCategoryID" => -1]);
        $professionCategory["engineering"] = $this->lastInsertedCategoryID;
        $data["categories"]["professional"] = $professionCategory;

        $iceCreamCategory = [];
        $this->createCategory(["name" => "Vanilla", "urlCode" => "vanilla", "parentCategoryID" => -1]);
        $iceCreamCategory["v"] = $this->lastInsertedCategoryID;
        $this->createCategory(["name" => "IceCream", "urlCode" => "ice-cream", "parentCategoryID" => -1]);
        $iceCreamCategory["o"] = $this->lastInsertedCategoryID;
        $data["categories"]["iceCream"] = $iceCreamCategory;

        //Event Category
        $eventCategory = [];
        $this->createCategory(["name" => "Youth Event", "urlCode" => "y-event", "parentCategoryID" => -1]);
        $eventCategory["Youth"] = $this->lastInsertedCategoryID;
        $this->createCategory(["name" => "Adult Event", "urlCode" => "a-event", "parentCategoryID" => -1]);
        $eventCategory["Adult"] = $this->lastInsertedCategoryID;
        $this->createCategory(["name" => "Senior Event", "urlCode" => "s-event", "parentCategoryID" => -1]);
        $eventCategory["Senior"] = $this->lastInsertedCategoryID;
        $data["categories"]["event"] = $eventCategory;

        // Create multiple ProfileFields

        $this->generateDropDownField([
            "apiName" => "profession",
            "label" => "Profession",
            "dropdownOptions" => ["Engineering", "Artist", "Education", "Transportation", "Health Care", "Other"],
        ]);

        $this->generateStringTokenInputField([
            "apiName" => "ice-cream",
            "label" => "Ice Cream",
            "dropdownOptions" => ["Vanilla", "Strawberry", "Chocolate", "Butterscotch", "None"],
        ]);

        $this->generateCheckboxField(["apiName" => "child-age", "label" => "Check if your age is below 18"]);
        $this->generateCheckboxField(["apiName" => "youth-age", "label" => "Check if your age range between 18-25"]);
        $this->generateCheckboxField(["apiName" => "adult-age", "label" => "Check if your age range between 26-55"]);
        $this->generateCheckboxField(["apiName" => "senior-age", "label" => "Check if your age is above 56"]);

        $automationRule = [];
        // create automation rules
        $record = $this->getAutomationRecord(["profession" => ["Engineering"]], [$professionCategory["engineering"]]);
        $automationRule["engineering-rule"] = $this->createAutomationRule($record["trigger"], $record["action"]);

        $record = $this->getAutomationRecord(
            ["profession" => ["Artist", "Education", "Transportation", "Health Care"]],
            [$professionCategory["social"]]
        );
        $automationRule["social-rule"] = $this->createAutomationRule($record["trigger"], $record["action"]);

        $record = $this->getAutomationRecord(["ice-cream" => ["Vanilla"]], [$iceCreamCategory["v"]]);
        $automationRule["vanilla-rule"] = $this->createAutomationRule($record["trigger"], $record["action"]);

        $record = $this->getAutomationRecord(
            ["ice-cream" => ["Strawberry", "Chocolate", "Butterscotch"]],
            [$iceCreamCategory["o"]]
        );
        $automationRule["other-rule"] = $this->createAutomationRule($record["trigger"], $record["action"]);

        $record = $this->getAutomationRecord(["youth-age" => true], [$eventCategory["Youth"]]);
        $automationRule["Youth-rule"] = $this->createAutomationRule($record["trigger"], $record["action"]);
        $record = $this->getAutomationRecord(["adult-age" => true], [$eventCategory["Adult"]]);
        $automationRule["Adult-rule"] = $this->createAutomationRule($record["trigger"], $record["action"]);
        $record = $this->getAutomationRecord(["senior-age" => true], [$eventCategory["Senior"]]);
        $automationRule["Senior-rule"] = $this->createAutomationRule($record["trigger"], $record["action"], false);

        $data["automationRules"] = $automationRule;
        return $data;
    }
}
