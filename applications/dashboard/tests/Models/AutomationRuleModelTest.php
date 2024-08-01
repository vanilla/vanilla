<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Models;

use Vanilla\AutomationRules\Actions\UserFollowCategoryAction;
use Vanilla\Dashboard\AutomationRules\Actions\AddRemoveUserRoleAction;
use Vanilla\Dashboard\AutomationRules\Triggers\UserEmailDomainTrigger;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Exception\Database\NoResultsException;
use VanillaTests\AutomationRules\AutomationRulesTestTrait;
use VanillaTests\AutomationRules\Models\MockAutomationRuleModel;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * AutomationRuleModelTest
 */
class AutomationRuleModelTest extends SiteTestCase
{
    use CommunityApiTestTrait, AutomationRulesTestTrait, ExpectExceptionTrait;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $mockAutomationRuleModel = $this->container()->get(MockAutomationRuleModel::class);
        $this->container()->setInstance(AutomationRuleModel::class, $mockAutomationRuleModel);
        $this->initialize();
    }

    /**
     * Test get automation recipes.
     *
     * @return void
     */
    public function testAutomationGetRecipe(): void
    {
        $this->assertEmpty($this->automationRuleModel->getAutomationRules());
        $category = $this->createCategory();
        $trigger = ["type" => UserEmailDomainTrigger::getType(), "value" => ["emailDomain" => "test.com"]];
        $action = [
            "type" => UserFollowCategoryAction::getType(),
            "value" => ["categoryID" => [$category["categoryID"]]],
        ];
        $this->createAutomationRule($trigger, $action);
        $automationRules = $this->automationRuleModel->getAutomationRules();
        $automationRuleSchema = $this->automationRuleModel->getAutomationRuleSchema();
        $automationRule = $automationRules[0];
        $this->assertTrue($automationRuleSchema->isValid($automationRule));
        $this->assertCount(1, $automationRules);

        // test expand for insert user
        $this->assertArrayNotHasKey("insertUser", $automationRule);

        $automationRules = $this->automationRuleModel->getAutomationRules(["expand" => ["insertUser"]]);
        $automationRule = $automationRules[0];

        $this->assertArrayHasKey("insertUser", $automationRule);
        $trigger["value"]["emailDomain"] = "recipe.com";
        $newCategory = $this->createCategory(["parentCategoryID" => -1]);
        $action["value"]["followedCategory"]["categoryID"] = [$newCategory["categoryID"]];
        $this->createAutomationRule($trigger, $action, false);

        // Test for query by status
        $automationRules = $this->automationRuleModel->getAutomationRules([
            "status" => [AutomationRuleModel::STATUS_ACTIVE],
        ]);
        $this->assertCount(1, $automationRules);
        $this->assertEquals("active", $automationRules[0]["status"]);

        $automationRules = $this->automationRuleModel->getAutomationRules([
            "status" => [AutomationRuleModel::STATUS_INACTIVE],
        ]);
        $this->assertCount(1, $automationRules);
        $this->assertEquals("inactive", $automationRules[0]["status"]);

        // Filter by automationRuleID
        $automationRules = $this->automationRuleModel->getAutomationRules([
            "automationRuleID" => [2],
        ]);
        $this->assertCount(1, $automationRules);
        $this->assertEquals(2, $automationRules[0]["automationRuleID"]);
    }

    /**
     * Test max automation rule id.
     *
     * @return void
     */
    public function testGetMaxAutomationRuleID()
    {
        $this->assertEquals(
            0,
            $this->automationRuleModel->getMaxAutomationRuleID(),
            "Max Automation Rule ID should be 0 when there are no automation rules"
        );
        $trigger = ["type" => "testTrigger", "value" => ["trigger-key" => "trigger-value"]];
        $action = ["type" => "testAction", "value" => ["action-key" => "action-value"]];
        $this->createAutomationRule($trigger, $action);
        $this->assertEquals(1, $this->automationRuleModel->getMaxAutomationRuleID());
    }

    /**
     * Test get automation recipes by trigger action or values.
     *
     * @return void
     */
    public function testGetAutomationRulesByTriggerActionOrValues(): void
    {
        $this->assertEmpty($this->automationRuleModel->getAutomationRulesByTriggerActionOrValues());
        $category = $this->createCategory();
        $trigger = ["type" => UserEmailDomainTrigger::getType(), "value" => ["emailDomain" => "test.com"]];
        $action = [
            "type" => UserFollowCategoryAction::getType(),
            "value" => ["categoryID" => [$category["categoryID"]]],
        ];
        $this->createAutomationRule($trigger, $action);
        $lastID = $this->automationRuleModel->getMaxAutomationRuleID();
        $automationRules = $this->automationRuleModel->getAutomationRulesByTriggerActionOrValues(
            UserEmailDomainTrigger::getType(),
            UserFollowCategoryAction::getType(),
            ["emailDomain" => "test.com"],
            ["categoryID" => [$category["categoryID"]]]
        );
        $this->assertCount(1, $automationRules);
        $this->assertEquals($lastID, $automationRules[0]["automationRuleID"]);
    }

    /**
     * Test total automation recipes.
     */
    public function testTotalAutomationRecipes(): void
    {
        $this->assertEquals(0, $this->automationRuleModel->getSiteTotalRecipes());
        $trigger = ["type" => UserEmailDomainTrigger::getType(), "value" => ["emailDomain" => "test.com"]];
        $action = [
            "type" => UserFollowCategoryAction::getType(),
            "value" => ["categoryID" => [1]],
        ];
        $this->createAutomationRule($trigger, $action);
        $this->assertEquals(1, $this->automationRuleModel->getSiteTotalRecipes());
        $trigger["value"] = ["emailDomain" => "vanilla.com"];
        $this->createAutomationRule($trigger, $action, false);

        $this->assertEquals(2, $this->automationRuleModel->getSiteTotalRecipes());
        $this->automationRuleModel->update(
            ["status" => AutomationRuleModel::STATUS_DELETED],
            ["automationRuleID" => 1]
        );

        $this->assertEquals(1, $this->automationRuleModel->getSiteTotalRecipes());
    }

    /**
     * Test get automation recipe by status.
     *
     * @return void
     */
    public function testGetTotalRecipesByStatus(): void
    {
        $trigger = ["type" => UserEmailDomainTrigger::getType(), "value" => ["emailDomain" => "test.com"]];
        $action = [
            "type" => UserFollowCategoryAction::getType(),
            "value" => ["categoryID" => [1]],
        ];
        $this->createAutomationRule($trigger, $action, false);
        $this->assertEquals(0, $this->automationRuleModel->getTotalRecipesByStatus(AutomationRuleModel::STATUS_ACTIVE));
        $this->assertEquals(
            1,
            $this->automationRuleModel->getTotalRecipesByStatus(AutomationRuleModel::STATUS_INACTIVE)
        );
        $this->automationRuleModel->update(
            ["status" => AutomationRuleModel::STATUS_DELETED],
            ["automationRuleID" => 1]
        );
        $this->assertEquals(
            0,
            $this->automationRuleModel->getTotalRecipesByStatus(AutomationRuleModel::STATUS_INACTIVE)
        );
        $this->assertEquals(
            1,
            $this->automationRuleModel->getTotalRecipesByStatus(AutomationRuleModel::STATUS_DELETED)
        );
    }

    /**
     * Test get automation recipe by ID.
     */
    public function testGetAutomationRuleByID(): void
    {
        $trigger = ["type" => UserEmailDomainTrigger::getType(), "value" => ["emailDomain" => "test.com"]];
        $action = [
            "type" => UserFollowCategoryAction::getType(),
            "value" => ["categoryID" => [1]],
        ];
        $this->createAutomationRule($trigger, $action);
        $automationRule = $this->automationRuleModel->getAutomationRuleByID(1);
        $this->assertNotEmpty($automationRule, "Should return an automation rule");
        $this->assertEquals(1, $automationRule["automationRuleID"]);
        $this->runWithExpectedException(NoResultsException::class, function () {
            $this->automationRuleModel->getAutomationRuleByID(2);
        });
    }

    /**
     * Test save and update recipe.
     *
     * @return void
     */
    public function testSaveRecipe(): void
    {
        $recipe = [
            "name" => "testRecipe",
            "triggerType" => UserEmailDomainTrigger::getType(),
            "triggerValue" => ["emailDomain" => "test.com"],
            "actionType" => UserFollowCategoryAction::getType(),
            "actionValue" => ["categoryID" => [1]],
        ];
        $automationRuleID = $this->automationRuleModel->saveAutomationRule($recipe);
        $this->assertIsInt($automationRuleID);
        $automationRule = $this->automationRuleModel->getAutomationRuleByID($automationRuleID);
        $this->assertEquals($recipe["name"], $automationRule["name"]);
        $this->assertEquals($recipe["triggerType"], $automationRule["trigger"]["triggerType"]);
        $this->assertEquals($recipe["actionType"], $automationRule["action"]["actionType"]);
        $this->assertEquals(1, $automationRule["automationRuleRevisionID"]);
        $this->assertEquals(AutomationRuleModel::STATUS_INACTIVE, $automationRule["status"]);

        // Now let's update the recipe
        $recipe["triggerValue"]["emailDomain"] = "vanilla.com";
        $recipe["status"] = AutomationRuleModel::STATUS_ACTIVE;
        $newAutomationRuleID = $this->automationRuleModel->saveAutomationRule($recipe, $automationRuleID);
        $automationRule = $this->automationRuleModel->getAutomationRuleByID($newAutomationRuleID);
        $this->assertEquals(
            $recipe["triggerValue"]["emailDomain"],
            $automationRule["trigger"]["triggerValue"]["emailDomain"]
        );
        $this->assertEquals(2, $automationRule["automationRuleRevisionID"]);
        $this->assertEquals(AutomationRuleModel::STATUS_ACTIVE, $automationRule["status"]);
    }

    /**
     * Test delete recipe.
     *
     * @return void
     */
    public function testDeleteRecipe(): void
    {
        $recipe = [
            "triggerType" => UserEmailDomainTrigger::getType(),
            "triggerValue" => ["emailDomain" => "test.com"],
            "actionType" => UserFollowCategoryAction::getType(),
            "actionValue" => ["categoryID" => [1]],
        ];
        $automationRuleID = $this->automationRuleModel->saveAutomationRule($recipe);
        $this->assertIsInt($automationRuleID);
        $this->automationRuleModel->deleteAutomationRule($automationRuleID);
        $this->assertLog([
            "level" => "info",
            "message" => "Deleted recipe.",
            "tags" => ["automation rules", "recipe"],
        ]);
    }

    /**
     * Test updating status of a recipe
     *
     * @return void
     * @throws NoResultsException
     */
    public function testUpdateRecipeStatus()
    {
        $recipe = [
            "triggerType" => UserEmailDomainTrigger::getType(),
            "triggerValue" => ["emailDomain" => "test.com"],
            "actionType" => UserFollowCategoryAction::getType(),
            "actionValue" => ["categoryID" => [1]],
        ];
        $automationRuleID = $this->automationRuleModel->saveAutomationRule($recipe);

        $this->runWithExpectedException(\InvalidArgumentException::class, function () use ($automationRuleID) {
            $this->automationRuleModel->updateAutomationRuleStatus($automationRuleID, "invalid");
        });

        $this->automationRuleModel->updateAutomationRuleStatus($automationRuleID, AutomationRuleModel::STATUS_ACTIVE);
        $automationRule = $this->automationRuleModel->getAutomationRuleByID($automationRuleID);
        $this->assertEquals(AutomationRuleModel::STATUS_ACTIVE, $automationRule["status"]);

        $this->automationRuleModel->updateAutomationRuleStatus($automationRuleID, AutomationRuleModel::STATUS_DELETED);
        $automationRule = $this->automationRuleModel->getAutomationRuleByID($automationRuleID);
        $this->assertEquals(AutomationRuleModel::STATUS_DELETED, $automationRule["status"]);

        $this->runWithExpectedException(NoResultsException::class, function () use ($automationRuleID) {
            $this->automationRuleModel->updateAutomationRuleStatus(
                $automationRuleID,
                AutomationRuleModel::STATUS_INACTIVE
            );
        });
    }

    /**
     * Test get total automation rules by trigger action status.
     *
     * @return array
     */
    public function testGetTotalAutomationRulesByTriggerActionStatus(): array
    {
        $automationRules = [];
        $trigger = ["type" => UserEmailDomainTrigger::getType(), "value" => ["emailDomain" => "test.com"]];
        $action = [
            "type" => UserFollowCategoryAction::getType(),
            "value" => ["categoryID" => [1]],
        ];
        $this->createAutomationRule($trigger, $action);
        $automationRules[] = [
            "trigger" => $trigger,
            "action" => $action,
        ];

        $trigger = ["type" => UserEmailDomainTrigger::getType(), "value" => ["emailDomain" => "example.com"]];
        $action = [
            "type" => AddRemoveUserRoleAction::getType(),
            "value" => ["addRoleID" => [16]],
        ];
        $this->createAutomationRule($trigger, $action);
        $automationRules[] = [
            "trigger" => $trigger,
            "action" => $action,
        ];
        $this->assertEquals(
            1,
            $this->automationRuleModel->getTotalAutomationRulesByTriggerActionStatus(
                UserEmailDomainTrigger::getType(),
                UserFollowCategoryAction::getType(),
                AutomationRuleModel::STATUS_ACTIVE
            )
        );
        $this->assertEquals(
            1,
            $this->automationRuleModel->getTotalAutomationRulesByTriggerActionStatus(
                UserEmailDomainTrigger::getType(),
                AddRemoveUserRoleAction::getType(),
                AutomationRuleModel::STATUS_ACTIVE
            )
        );

        $this->assertEquals(
            0,
            $this->automationRuleModel->getTotalAutomationRulesByTriggerActionStatus(
                UserEmailDomainTrigger::getType(),
                AddRemoveUserRoleAction::getType(),
                AutomationRuleModel::STATUS_INACTIVE
            )
        );
        return $automationRules;
    }

    /**
     * Test get active automation rules by trigger action.
     *
     * @return void
     * @depends testGetTotalAutomationRulesByTriggerActionStatus
     */
    public function testGetActiveAutomationRulesByTriggerAction(array $automationRules): void
    {
        $this->createAutomationRule($automationRules[0]["trigger"], $automationRules[0]["action"]);
        $ruleID = $this->lastRuleID;
        $activeAutomationRules = $this->automationRuleModel->getActiveAutomationRules(
            UserEmailDomainTrigger::getType(),
            UserFollowCategoryAction::getType()
        );
        $this->assertCount(1, $activeAutomationRules);
        $this->assertEquals($ruleID, $activeAutomationRules[0]["automationRuleID"]);
        $this->assertEquals($automationRules[0]["trigger"]["type"], $activeAutomationRules[0]["triggerType"]);
        $this->assertIsArray($activeAutomationRules[0]["triggerValue"]);
        $this->assertArrayHasKey("emailDomain", $activeAutomationRules[0]["triggerValue"]);
        $this->assertEquals($automationRules[0]["action"]["type"], $activeAutomationRules[0]["actionType"]);

        $this->createAutomationRule($automationRules[1]["trigger"], $automationRules[1]["action"]);
        $ruleID = $this->lastRuleID;
        $activeAutomationRules = $this->automationRuleModel->getActiveAutomationRules(
            UserEmailDomainTrigger::getType(),
            AddRemoveUserRoleAction::getType()
        );
        $this->assertCount(1, $activeAutomationRules);
        $this->assertEquals($ruleID, $activeAutomationRules[0]["automationRuleID"]);
        $this->assertEquals($automationRules[1]["trigger"]["type"], $activeAutomationRules[0]["triggerType"]);
        $this->assertEquals($automationRules[1]["action"]["type"], $activeAutomationRules[0]["actionType"]);

        $this->automationRuleModel->updateAutomationRuleStatus($ruleID, AutomationRuleModel::STATUS_INACTIVE);
        $activeAutomationRules = $this->automationRuleModel->getActiveAutomationRules(
            UserEmailDomainTrigger::getType(),
            AddRemoveUserRoleAction::getType()
        );
        $this->assertCount(0, $activeAutomationRules);
    }

    /**
     * Test that on updating the date last run, the date updated is not modified
     *
     * @return void
     * @throws NoResultsException
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Schema\ValidationException
     */
    public function testUpdateRuleDateLastRunDoesNotModifyUpdatedTime(): void
    {
        $recipe = [
            "name" => "testRecipe",
            "triggerType" => UserEmailDomainTrigger::getType(),
            "triggerValue" => ["emailDomain" => "test.com"],
            "actionType" => UserFollowCategoryAction::getType(),
            "actionValue" => ["categoryID" => [1]],
        ];
        $automationRuleID = $this->automationRuleModel->saveAutomationRule($recipe);
        $automationRule = $this->automationRuleModel->selectSingle(["automationRuleID" => $automationRuleID]);
        $this->assertEmpty($automationRule["dateLastRun"]);
        $dateUpdated = $automationRule["dateUpdated"];
        $lastRunDate = $dateUpdated->add(new \DateInterval("PT20H"));
        // Now update the date last run
        $result = $this->automationRuleModel->updateRuleDateLastRun(
            $automationRuleID,
            $lastRunDate->format("Y-m-d H:i:s")
        );
        $this->assertTrue($result);

        $updatedRule = $this->automationRuleModel->selectSingle(["automationRuleID" => $automationRuleID]);
        // Assert that the date updated is not changed
        $this->assertEquals($dateUpdated, $updatedRule["dateUpdated"]);

        // Assert that the date last run is updated
        $this->assertEquals($lastRunDate, $updatedRule["dateLastRun"]);
    }
}
