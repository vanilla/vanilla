<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ForbiddenException;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test for banning users with the users API.
 */
class UsersBanTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    /**
     * Test filtering the users API by banned.
     *
     * @return void
     */
    public function testFilterBanned()
    {
        $role = $this->createRole();
        $userA = $this->createUser(["roleID" => [$role["roleID"]]]);
        $userB = $this->createUser(["roleID" => [$role["roleID"]]]);
        // Now let's ban user A
        $this->api()->put("/users/{$userA["userID"]}/ban", [
            "banned" => true,
        ]);

        $this->api()
            ->get("/users", ["roleIDs" => [$role["roleID"]], "isBanned" => true])
            ->assertSuccess()
            ->assertCount(1, "Expected only 1 user to be returned.")
            ->assertJsonArrayValues([
                "userID" => [$userA["userID"]],
            ]);

        // Now try the inverse.
        $this->api()
            ->get("/users", ["roleIDs" => [$role["roleID"]], "isBanned" => false])
            ->assertSuccess()
            ->assertCount(1, "Expected only 1 user to be returned.")
            ->assertJsonArrayValues([
                "userID" => [$userB["userID"]],
            ]);
    }

    /**
     * A moderator should be able to ban a member through the PUT /users/{id}/ban endpoint.
     */
    public function testPutBanWithPermission()
    {
        $this->createUserFixtures("testPutBanWithPermission");
        $this->api()->setUserID($this->moderatorID);
        $r = $this->api()->put("/users/{$this->memberID}/ban", ["banned" => true]);
        $this->assertTrue($r["banned"]);

        // Make sure the user has the banned photo.
        $user = $this->api()
            ->get("/users/{$this->memberID}")
            ->assertSuccess()
            ->assertJsonObjectLike(["banned" => 1])
            ->getBody();
        $this->assertStringEndsWith(\UserModel::PATH_BANNED_AVATAR, $user["photoUrl"]);
        $this->assertSame($user["photoUrl"], $user["profilePhotoUrl"]);
    }

    /**
     * A user with the right permission should be able to ban a user with lower permissions through the PATCH /users/{id} endpoint.
     */
    public function testPatchBanWithPermission(): void
    {
        $this->createUserFixtures("testPatchBanWithPermission");
        $this->api()->setUserID($this->adminID);
        $r = $this->api()->patch("/users/{$this->memberID}", ["banned" => true]);
        $this->assertSame(1, $r["banned"]);

        // Make sure the user has the banned photo.
        $user = $this->api()
            ->get("/users/{$this->memberID}")
            ->getBody();
        $this->assertStringEndsWith(\UserModel::PATH_BANNED_AVATAR, $user["photoUrl"]);
        $this->assertSame($user["photoUrl"], $user["profilePhotoUrl"]);
    }

    /**
     * A moderator should not be able to ban an administrator through the PUT /users/{id}/ban endpoint.
     */
    public function testPutBanWithoutPermission()
    {
        $this->createUserFixtures("testPutBanWithoutPermission");
        $this->api()->setUserID($this->moderatorID);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You are not allowed to ban a user that has higher permissions than you.");
        $r = $this->api()->put("/users/{$this->adminID}/ban", ["banned" => true]);
    }

    /**
     * A user with the "users.edit" permission should not be allowed to ban an admin through the PATCH /users/{id} endpoint.
     */
    public function testPatchBanWithoutPermission(): void
    {
        $this->createUserFixtures("testPatchBanWithoutPermission");
        $this->runWithPermissions(
            function () {
                $this->expectException(ForbiddenException::class);
                $this->expectExceptionMessage(\UsersApiController::ERROR_PATCH_HIGHER_PERMISSION_USER);
                $r = $this->api()->patch("/users/{$this->adminID}", ["banned" => true]);
            },
            ["users.edit" => true]
        );
    }

    /**
     * A moderator should not be able to ban another moderator through the PUT /users/{id}/ban endpoint.
     */
    public function testPutBanSamePermissionRank()
    {
        $this->createUserFixtures("testBanSamePermissionRank");
        $moderatorID = $this->moderatorID;
        $this->createUserFixtures("testBanSamePermissionRank2");
        $this->api()->setUserID($this->moderatorID);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You are not allowed to ban a user with the same permission level as you.");
        $r = $this->api()->put("/users/{$moderatorID}/ban", ["banned" => true]);
    }

    /**
     * A user should not be able to ban another user with identical permissions through the PATCH /users/{id} endpoint.
     */
    public function testPatchBanSamePermissionRank(): void
    {
        $usersEditRole = $this->createRole([], ["session.valid" => true, "users.edit" => true]);
        $user1ID = $this->createUserFixture($usersEditRole["name"]);
        $user2ID = $this->createUserFixture($usersEditRole["name"]);
        $this->api()->setUserID($user1ID);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You are not allowed to ban a user with the same permission level as you.");
        $r = $this->api()->patch("/users/{$user2ID}", ["banned" => true]);
    }

    /**
     * Test that we can patch a ban with the same value that already exists.
     */
    public function testPatchBanSameBanValue()
    {
        $this->createUserFixtures(__FUNCTION__);
        $this->api()->setUserID($this->adminID);
        $r = $this->api()->patch("/users/{$this->memberID}", ["banned" => true]);
        $this->assertSame(1, $r["banned"]);

        // And we can do it again but nothing changes.
        $r = $this->api()->patch("/users/{$this->memberID}", ["banned" => true]);
        $this->assertSame(1, $r["banned"]);

        // And we can do the inverse.
        $r = $this->api()->patch("/users/{$this->memberID}", ["banned" => false]);
        $this->assertSame(0, $r["banned"]);

        // And again no change.
        $r = $this->api()->patch("/users/{$this->memberID}", ["banned" => false]);
        $this->assertSame(0, $r["banned"]);
    }
}
