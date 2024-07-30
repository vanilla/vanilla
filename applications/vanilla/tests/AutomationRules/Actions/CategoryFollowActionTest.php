<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace AutomationRules\Actions;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Vanilla\AutomationRules\Actions\UserFollowCategoryAction;
use Vanilla\Dashboard\AutomationRules\Triggers\UserEmailDomainTrigger;
use Vanilla\Exception\Database\NoResultsException;
use VanillaTests\AutomationRules\AutomationRulesTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the CategoryFollowAction
 */
class CategoryFollowActionTest extends SiteTestCase
{
    use AutomationRulesTestTrait, CommunityApiTestTrait, UsersAndRolesApiTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->initialize();
    }

    /**
     * Test instantiating the CategoryFollowAction class with an invalid automation ID throws an exception
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws NoResultsException
     */
    public function testPassingInvalidAutomationIDThrowsInValidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid automation rule ID");
        new UserFollowCategoryAction(1);
    }

    /**
     * Test getUserData throws an exception when invalid user ID is set
     */
    public function testGetUserDataThrowsExceptionWhenUserIDIsNotSet()
    {
        $trigger = ["type" => UserEmailDomainTrigger::getType(), "value" => ["emailDomain" => "test.com"]];
        $action = [
            "type" => UserFollowCategoryAction::getType(),
            "value" => ["categoryID" => [1]],
        ];
        $automationRule = $this->createAutomationRule($trigger, $action);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("User not found");
        $categoryFollowAction = new UserFollowCategoryAction($automationRule["automationRuleID"]);
        $categoryFollowAction->setUserID(99);
        $categoryFollowAction->getUserData();
    }

    /**
     * Test CategoryFollow Action assigns user's default notification preference to the followed category
     *
     * @return void
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function testUSerGetTheirNotificationPreferenceOnCategoryFollowAction(): void
    {
        $this->createCategory();
        $categoryID = $this->lastInsertedCategoryID;
        $this->createUser();
        $userID = $this->lastUserID;
        //set user notification preference
        $result = $this->api()->patch("notification-preferences/" . $userID, [
            "NewDiscussion" => [
                "email" => true,
                "popup" => false,
            ],
            "NewComment" => [
                "email" => false,
                "popup" => true,
            ],
        ]);
        $expectedPreferences = [
            "Preferences.Follow" => true,
            "Preferences.Popup.NewDiscussion" => false,
            "Preferences.Email.NewDiscussion" => true,
            "Preferences.Popup.NewComment" => true,
            "Preferences.Email.NewComment" => false,
        ];
        $trigger = ["type" => UserEmailDomainTrigger::getType(), "value" => ["emailDomain" => "test.com"]];
        $action = [
            "type" => UserFollowCategoryAction::getType(),
            "value" => ["categoryID" => [$categoryID]],
        ];
        $automationRule = $this->createAutomationRule($trigger, $action);
        $automationRuleID = $automationRule["automationRuleID"];

        $categoryFollowAction = new UserFollowCategoryAction($automationRuleID);
        $categoryFollowAction->setUserID($userID);
        $categoryFollowAction->execute();

        //Now get users category preferences
        $categoryModel = \CategoryModel::instance();
        $categoryPreferences = $categoryModel->getPreferencesByCategoryID($userID, $categoryID);
        $this->assertNotEmpty($categoryPreferences);
        $this->assertEquals($expectedPreferences, $categoryPreferences);
    }
}
