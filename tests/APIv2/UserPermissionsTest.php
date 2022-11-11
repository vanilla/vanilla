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

    /**
     * Test how we handled our consolidated permissions.
     *
     * @param array $inputPermissions The permissions inputted to the API.
     * @param array $outputPermissions The permissions outputted from the API.
     *
     * @dataProvider provideConsolidatedPermissions
     */
    public function testConsolidatedPermissions(array $inputPermissions, array $outputPermissions)
    {
        $role = $this->createRole(
            [],
            [],
            [
                "type" => "category",
                "id" => 0,
                "permissions" => $inputPermissions,
            ]
        );

        $user = $this->createUser([
            "roleID" => [\RoleModel::MEMBER_ID, $role["roleID"]],
        ]);

        $permissions = $this->api()
            ->get("/users/{$user["userID"]}/permissions")
            ->getBody()["permissions"][0]["permissions"];

        foreach ($outputPermissions as $permName => $expectedValue) {
            $actualValue = $permissions[$permName] ?? false;
            $this->assertEquals(
                $expectedValue,
                $actualValue,
                "Expected user to have {$expectedValue} for the {$permName} permission."
            );
        }
    }

    /**
     * @return \Generator
     */
    public function provideConsolidatedPermissions()
    {
        yield "have all consolidated" => [
            [
                "discussions.delete" => true,
                "discussions.edit" => true,
                "discussions.announce" => true,
                "discussions.close" => true,
                "discussions.sink" => true,
            ],
            [
                "discussions.delete" => true,
                "discussions.edit" => true,
                "discussions.announce" => true,
                "discussions.close" => true,
                "discussions.sink" => true,
                // Consolidated
                "discussions.moderate" => true,
                "discussions.manage" => true,
            ],
        ];

        yield "have partial consolidated" => [
            [
                "discussions.delete" => true,
                "discussions.edit" => false,
                "discussions.announce" => false,
                "discussions.close" => false,
                "discussions.sink" => true,
            ],
            [
                "discussions.delete" => true,
                "discussions.edit" => false,
                "discussions.announce" => false,
                "discussions.close" => false,
                "discussions.sink" => true,
                // Consolidated
                "discussions.moderate" => false,
                "discussions.manage" => false,
            ],
        ];

        yield "have no consolidated" => [
            [
                "discussions.delete" => false,
                "discussions.edit" => false,
                "discussions.announce" => false,
                "discussions.close" => false,
                "discussions.sink" => false,
            ],
            [
                "discussions.delete" => false,
                "discussions.edit" => false,
                "discussions.announce" => false,
                "discussions.close" => false,
                "discussions.sink" => false,
                // Consolidated
                "discussions.moderate" => false,
                "discussions.manage" => false,
            ],
        ];
    }
}
