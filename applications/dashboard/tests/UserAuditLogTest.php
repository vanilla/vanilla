<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard;

use VanillaTests\AuditLogTestTrait;
use VanillaTests\ExpectedAuditLog;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for core events being tracked in the audit log system.
 */
class UserAuditLogTest extends SiteTestCase
{
    use AuditLogTestTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->api()->setUserID(2);
    }

    /**
     * Test CRUD audit logs on users.
     */
    public function testUserAuditLogs(): void
    {
        $this->createUser(["name" => "user1"]);

        $this->assertAuditLogged(
            ExpectedAuditLog::create("user_add")->withMessage("User `user1` was added by `circleci`.")
        );

        $this->api()->patch("/users/{$this->lastUserID}", ["name" => "user1-modified"]);
        $this->assertAuditLogged(
            ExpectedAuditLog::create("user_update")
                ->withMessage("User `user1` was updated by `circleci`.")
                ->withModification("name", "user1", "user1-modified")
        );

        $this->api()->deleteWithBody("/users/{$this->lastUserID}");
        $this->assertAuditLogged(
            ExpectedAuditLog::create("user_delete")->withMessage("User `user1-modified` was deleted by `circleci`.")
        );

        // Registration
        $this->runWithUser(function () {
            $fields = $this->dummyUser(["Name" => "registration_user"]) + ["Password" => md5(__FUNCTION__)];
            $this->userModel->register($fields);
        }, \UserModel::GUEST_USER_ID);

        $this->assertAuditLogged(
            ExpectedAuditLog::create("user_register")->withMessage("User `registration_user` registered.")
        );
    }

    /**
     * Test that profile field changes also show up in the audit log.
     */
    public function testUserProfileFields(): void
    {
        $this->createProfileField(["name" => "Special Name", "apiName" => "special-name"]);
        $this->createUser(["name" => "user2"]);
        $this->api()->patch("/users/{$this->lastUserID}", [
            "profileFields" => [
                "special-name" => "hello",
            ],
        ]);
        $this->assertAuditLogged(
            ExpectedAuditLog::create("user_update")
                ->withMessage("User `user2` was updated by `circleci`.")
                ->withModification("profileFields.special-name", null, "hello")
        );

        // We also track them being removed.
        $this->api()->patch("/users/{$this->lastUserID}", [
            "profileFields" => [
                "special-name" => null,
            ],
        ]);
        $this->assertAuditLogged(
            ExpectedAuditLog::create("user_update")
                ->withMessage("User `user2` was updated by `circleci`.")
                ->withModification("profileFields.special-name", "hello", null)
        );
    }

    /**
     * Keep this test last because it mucks up the session for the test case.
     */
    public function testSignIn(): void
    {
        $this->createUser(["name" => "signinUser"]);
        \Gdn::session()->start($this->lastUserID, true);
        $this->assertAuditLogged(ExpectedAuditLog::create("user_signin")->withMessage("User signed in."));
    }

    /**
     * Test spoofing as another user.
     */
    public function testSpoof(): void
    {
        $originalUserID = $this->api()->getUserID();
        // We have a spoof event
        $spoofedUser = $this->createUser(["name" => "spoofedUser", "roleID" => [\RoleModel::ADMIN_ID]]);

        $this->bessy()->post("user/autospoof/{$spoofedUser["userID"]}", [], ["deliveryType" => DELIVERY_TYPE_ALL]);

        // We are spoofed.
        $this->assertEquals($spoofedUser["userID"], $this->getSession()->UserID);

        // We should also have an audit log
        $this->assertAuditLogged(
            ExpectedAuditLog::create("user_spoof")->withMessage("User spoofed in as `spoofedUser`")
        );

        // Now actions by this user should be indicated as "spoofed"
        $this->createUser(["name" => "user3"]);
        $this->assertAuditLogged(
            ExpectedAuditLog::create("user_add")
                ->withMessage("User `user3` was added by `spoofedUser`.")
                ->withSpoofedUserID($originalUserID)
        );
    }

    /**
     * Test that banning and unbanning user is audit logged.
     */
    public function testBanAndUnban(): void
    {
        $banUser = $this->createUser(["name" => "banUser"]);
        $this->api()->put("/users/{$banUser["userID"]}/ban", ["banned" => true]);
        $this->assertAuditLogged(
            ExpectedAuditLog::create("user_ban")->withMessage("User banUser was banned by circleci.")
        );
        $this->api()->put("/users/{$banUser["userID"]}/ban", ["banned" => false]);
        $this->assertAuditLogged(
            ExpectedAuditLog::create("user_unban")->withMessage("User banUser was unbanned by circleci.")
        );
    }

    /**
     * Test that we log role changes.
     */
    public function testChangeRoles(): void
    {
        $modifyUser = $this->createUser(["name" => "modifyUser"]);
        $this->api()->patch("/users/{$modifyUser["userID"]}", [
            "roleID" => [\RoleModel::ADMIN_ID],
        ]);

        $this->assertAuditLogged(
            ExpectedAuditLog::create("user_roleModification")
                ->withMessage("User's roles were modified: `modifyUser`")
                ->withContext([
                    "rolesAdded" => ["Administrator"],
                    "rolesRemoved" => ["Member"],
                ])
        );
    }
}
