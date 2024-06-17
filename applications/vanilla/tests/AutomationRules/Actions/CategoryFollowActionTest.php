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
use VanillaTests\SiteTestCase;

/**
 * Test the CategoryFollowAction
 */
class CategoryFollowActionTest extends SiteTestCase
{
    use AutomationRulesTestTrait;

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
}
