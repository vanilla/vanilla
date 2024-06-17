<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Tests\Actions;

use Vanilla\Dashboard\AutomationRules\Actions\AddRemoveUserRoleAction;
use Vanilla\Dashboard\AutomationRules\Triggers\UserEmailDomainTrigger;
use VanillaTests\AutomationRules\AutomationRulesTestTrait;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 *  Test the AddRemoveRoleAction class
 */
class AddRemoveRoleActionTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait, AutomationRulesTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->initialize();
    }

    /**
     * Test expand log data
     *
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    public function testExpandLogData()
    {
        // Create some new roles
        $roleA = $this->createRole([
            "name" => "TestRole",
            "description" => "Test Role Description",
            "type" => "",
        ]);
        $roleB = $this->createRole([
            "name" => "TestRole2",
            "description" => "Test Role Description 2",
            "type" => "",
        ]);
        $roleC = $this->createRole([
            "name" => "TestRole2",
            "description" => "Test Role Description 2",
            "type" => "",
        ]);
        $roleD = $this->createRole([
            "name" => "TestRole2",
            "description" => "Test Role Description 2",
            "type" => "",
        ]);
        $logData = [
            "CurrentRoles" => [$roleA["roleID"], $roleB["roleID"], $roleC["roleID"]],
            "RoleAdded" => $roleD["roleID"],
            "RoleRemoved" => $roleC["roleID"],
        ];
        $record = [
            "trigger" => [
                "type" => UserEmailDomainTrigger::getType(),
                "value" => ["emailDomain" => ["example.com"]],
            ],
            "action" => [
                "type" => AddRemoveUserRoleAction::getType(),
                "value" => ["addRoleID" => $roleD["roleID"], "removeRoleID" => $roleC["roleID"]],
            ],
        ];
        $this->createAutomationRule($record["trigger"], $record["action"]);
        $addRemoveRoleAction = new AddRemoveUserRoleAction($this->lastRuleID, "manual", "abc");
        $logSummary = $addRemoveRoleAction->expandLogData($logData);

        $this->assertIsString($logSummary);
        $document = new TestHtmlDocument($logSummary);
        $document->assertContainsString("Log Data:");
        $document->assertContainsString("Current Roles:");
        $document->assertContainsString("Role Added:");
        $document->assertContainsString("Role Removed:");
        $dom = $document->getDom();
        $roleAdded = $dom->getElementById("role-added")->textContent;
        $this->assertStringContainsString($roleD["name"], $roleAdded);
        $roleRemoved = $dom->getElementById("role-removed")->textContent;
        $this->assertStringContainsString($roleC["name"], $roleRemoved);
    }
}
