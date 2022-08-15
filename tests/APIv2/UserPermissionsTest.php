<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\Models\PermissionFragmentSchema;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the /api/v2/users/:id/permissions endpoints.
 */
class UserPermissionsTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    /**
     * Test the admin and sysamdin flags for a user.
     */
    public function testUserAdminPermissions()
    {
        $this->createUser([], ["Admin" => 1]);
        $permissions = $this->api()
            ->get("/users/{$this->lastUserID}/permissions")
            ->getBody();
        $this->assertDataLike(
            [
                "isAdmin" => true,
                "isSysAdmin" => false,
            ],
            $permissions
        );

        $this->createUser([], ["Admin" => 2]);
        $permissions = $this->api()
            ->get("/users/{$this->lastUserID}/permissions")
            ->getBody();
        $this->assertDataLike(
            [
                "isAdmin" => true,
                "isSysAdmin" => true,
            ],
            $permissions
        );
    }

    /**
     * To catch this regression.
     * @see https://github.com/vanilla/support/issues/4039
     */
    public function testNoJunctionsPermissions()
    {
        // There should not be an error.
        $userID = $this->api()->getUserID();
        $this->api()->get("/users/$userID/permissions", ["expand" => "junctions"]);
        $this->assertTrue(true);
    }

    /**
     * Test the users me endpoint with some custom roles.
     */
    public function testPermissions()
    {
        $customCategory = $this->api()
            ->post("/categories", [
                "name" => "Custom Perms",
                "urlCode" => "test-permissions-api",
            ])
            ->getBody();

        $customRole = $this->api()
            ->post("/roles", [
                "name" => "Custom Role",
                "type" => "member",
                "permissions" => [
                    [
                        "type" => PermissionFragmentSchema::TYPE_GLOBAL,
                        "permissions" => [
                            "community.manage" => true,
                        ],
                    ],
                    // I would add some root category permissions here, but it's not possible to insert them through the API.
                    // https://github.com/vanilla/vanilla/issues/10184
                    [
                        "type" => "category",
                        "id" => $customCategory["categoryID"],
                        "permissions" => [
                            "comments.add" => true,
                            "comments.delete" => true,
                            "comments.edit" => true,
                            "discussions.add" => true,
                            "discussions.manage" => false,
                            "discussions.moderate" => false,
                        ],
                    ],
                ],
            ])
            ->getBody();

        $user = $this->api()
            ->post("/users", [
                "email" => "testy@test.com",
                "emailConfirmed" => true,
                "name" => "TestTest",
                "password" => randomString(\Gdn::config("Garden.Password.MinLength")),
                "roleID" => [$customRole["roleID"]],
            ])
            ->getBody();

        $permissions = $this->api()
            ->get("/users/" . $user["userID"] . "/permissions")
            ->getBody();

        $this->assertEquals(
            [
                "isAdmin" => false,
                "isSysAdmin" => false,
                "permissions" => [
                    [
                        "type" => PermissionFragmentSchema::TYPE_GLOBAL,
                        "permissions" => [
                            "community.manage" => true,
                        ],
                    ],
                    [
                        "type" => "category",
                        "id" => $customCategory["categoryID"],
                        "permissions" => [
                            "comments.add" => true,
                            "comments.delete" => true,
                            "comments.edit" => true,
                            "discussions.add" => true,
                        ],
                    ],
                ],
            ],
            $permissions
        );
    }
}
