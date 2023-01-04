<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use RolesApiController;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/roles endpoints.
 */
class RolesTest extends AbstractResourceTest
{
    use UsersAndRolesApiTestTrait;

    protected $editFields = ["canSession", "deletable", "description", "name", "personalInfo", "type"];

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = "")
    {
        $this->baseUrl = "/roles";
        $this->record = [
            "name" => "Tester",
            "description" => "Diligent QA workers.",
            "type" => "member",
            "deletable" => true,
            "canSession" => true,
            "personalInfo" => false,
        ];
        $this->testPagingOnIndex = false;

        parent::__construct($name, $data, $dataName);
    }

    /**
     * Test expand=assignable
     *
     * @return void
     */
    public function testIndexWithAssignableExpand()
    {
        $callApi = function () {
            return $this->api()
                ->get($this->baseUrl, ["expand" => "assignable"])
                ->getBody();
        };

        // Test response has assignable property if user has users.edit
        $roles = $this->runWithPermissions($callApi, ["users.edit" => true]);
        foreach ($roles as $role) {
            $this->assertArrayHasKey("assignable", $role);
            $this->assertIsBool($role["assignable"]);
        }

        // Test response does not have assignable property if user does not have users.edit
        $roles = $this->runWithPermissions($callApi, []);
        foreach ($roles as $role) {
            $this->assertArrayNotHasKey("assignable", $role);
        }
    }

    /**
     * Given a role ID, get its full list of permissions.
     *
     * @param $roleID
     * @return array
     */
    private function getPermissions($roleID)
    {
        $role = $this->api()
            ->get("{$this->baseUrl}/{$roleID}", ["expand" => "permissions"])
            ->getBody();
        return $role["permissions"];
    }

    /**
     * Create and return a new role for testing permission setting.
     *
     * @param array $permissions
     * @return array
     */
    private function getPermissionsRole(array $permissions = [])
    {
        if (empty($permissions)) {
            $permissions = [
                [
                    "type" => "global",
                    "permissions" => [
                        "tokens.add" => true,
                    ],
                ],
                [
                    "type" => "category",
                    "id" => 1,
                    "permissions" => [
                        "comments.add" => true,
                        "discussions.view" => true,
                    ],
                ],
            ];
        }

        $result = $this->testPost(null, ["permissions" => $permissions]);
        return $result;
    }

    /**
     * Check if a particular permission exists in the permissions array.
     *
     * @param string $name The name of the permission.
     * @param string $type Permission type (e.g. global, category)
     * @param array $permissions An array of permission rows.
     * @param int|bool $id A resource ID (e.g. a category ID)
     * @return bool
     */
    private function hasPermission($name, $type, array $permissions, $id = false)
    {
        $result = false;
        foreach ($permissions as $perm) {
            if ($type !== $perm["type"]) {
                continue;
            } elseif ($id !== false && (!array_key_exists("id", $perm) || $perm["id"] != $id)) {
                continue;
            } else {
                $result = array_key_exists($name, $perm["permissions"]) && $perm["permissions"][$name];
                break;
            }
        }
        return $result;
    }

    /**
     * Test setting permissions with POST /roles
     */
    public function testPostPermission()
    {
        $role = $this->getPermissionsRole();
        $permissions = $this->getPermissions($role["roleID"]);

        $this->assertTrue($this->hasPermission("tokens.add", "global", $permissions));
        $this->assertTrue($this->hasPermission("comments.add", "category", $permissions, 1));
        $this->assertTrue($this->hasPermission("discussions.view", "category", $permissions, 1));

        $this->assertFalse($this->hasPermission("site.manage", "global", $permissions));
        $this->assertFalse($this->hasPermission("discussions.add", "category", $permissions, 1));
    }

    /**
     * Test updating permissions with PATCH /roles
     */
    public function testPatchPermission()
    {
        $role = $this->getPermissionsRole();

        $this->api()->patch("{$this->baseUrl}/{$role[$this->pk]}", [
            "permissions" => [
                [
                    "type" => "global",
                    "permissions" => [
                        "email.view" => true,
                    ],
                ],
                [
                    "type" => "category",
                    "id" => 1,
                    "permissions" => [
                        "discussions.add" => true,
                        "comments.add" => false,
                    ],
                ],
            ],
        ]);

        $permissions = $this->getPermissions($role["roleID"]);

        $this->assertTrue($this->hasPermission("tokens.add", "global", $permissions));
        $this->assertTrue($this->hasPermission("email.view", "global", $permissions));
        $this->assertTrue($this->hasPermission("discussions.add", "category", $permissions, 1));
        $this->assertTrue($this->hasPermission("discussions.view", "category", $permissions, 1));

        $this->assertFalse($this->hasPermission("site.manage", "global", $permissions));
        $this->assertFalse($this->hasPermission("comments.add", "category", $permissions, 1));
    }

    /**
     * Test updating permissions with PATCH /roles/:id/permissions
     */
    public function testPatchPermissionEndpoint()
    {
        $role = $this->getPermissionsRole();

        $this->api()->patch("{$this->baseUrl}/{$role[$this->pk]}/permissions", [
            [
                "type" => "global",
                "permissions" => [
                    "email.view" => true,
                ],
            ],
            [
                "type" => "category",
                "id" => 1,
                "permissions" => [
                    "discussions.add" => true,
                    "comments.add" => false,
                ],
            ],
        ]);

        $permissions = $this->getPermissions($role["roleID"]);

        $this->assertTrue($this->hasPermission("tokens.add", "global", $permissions));
        $this->assertTrue($this->hasPermission("email.view", "global", $permissions));
        $this->assertTrue($this->hasPermission("discussions.add", "category", $permissions, 1));
        $this->assertTrue($this->hasPermission("discussions.view", "category", $permissions, 1));

        $this->assertFalse($this->hasPermission("site.manage", "global", $permissions));
        $this->assertFalse($this->hasPermission("comments.add", "category", $permissions, 1));
    }

    /**
     * Test updating permissions with PATCH /roles/:id/permissions
     */
    public function testPatchPermissionOverWrite()
    {
        $role = $this->getPermissionsRole([
            [
                "type" => "category",
                "id" => 1,
                "permissions" => [
                    "discussions.view" => true,
                    "discussions.add" => true,
                    "comments.add" => true,
                ],
            ],
        ]);

        $role2 = $this->getPermissionsRole([
            [
                "type" => "category",
                "id" => 1,
                "permissions" => [
                    "discussions.view" => true,
                    "discussions.add" => true,
                    "comments.add" => true,
                ],
            ],
        ]);

        $this->api()->patch("{$this->baseUrl}/{$role["roleID"]}/permissions", [
            [
                "type" => "category",
                "id" => 1,
                "permissions" => [
                    "discussions.add" => true,
                    "comments.add" => false,
                ],
            ],
        ]);

        $permissions1 = $this->getPermissions($role["roleID"]);
        $permissions2 = $this->getPermissions($role2["roleID"]);

        $this->assertTrue($this->hasPermission("discussions.add", "category", $permissions1, 1));
        $this->assertFalse($this->hasPermission("comments.add", "category", $permissions1, 1));

        $this->assertTrue($this->hasPermission("discussions.add", "category", $permissions2, 1));
        $this->assertTrue($this->hasPermission("comments.add", "category", $permissions2, 1));
    }

    /**
     * Test empty body for PATCH /roles/:id/permissions
     */
    public function testPatchPermissionFailBody()
    {
        $role = $this->getPermissionsRole();
        $this->expectExceptionMessage("Body must be formatted as follows : [null, null, ...]");
        $this->api()
            ->patch("{$this->baseUrl}/{$role[$this->pk]}/permissions", [])
            ->getBody();
    }

    /**
     * Test permission error for PATCH /roles/:id/permissions
     */
    public function testPatchPermissionFailPermission()
    {
        $user = $this->createUser();

        $this->expectExceptionMessage("Permission Problem");
        $this->runWithUser(function () {
            $role = $this->getPermissionsRole();
            $this->api()->patch("{$this->baseUrl}/{$role[$this->pk]}/permissions", []);
        }, $user);
    }

    public function testPutPermissionsEndpoint()
    {
        $role = $this->getPermissionsRole();

        $this->api()->put("{$this->baseUrl}/{$role[$this->pk]}/permissions", [
            [
                "type" => "global",
                "permissions" => [
                    "email.view" => true,
                ],
            ],
            [
                "type" => "category",
                "id" => 1,
                "permissions" => [
                    "discussions.add" => true,
                ],
            ],
        ]);

        $permissions = $this->getPermissions($role["roleID"]);

        $this->assertTrue($this->hasPermission("email.view", "global", $permissions));
        $this->assertTrue($this->hasPermission("discussions.add", "category", $permissions, 1));

        // Make sure all the original permissions have been removed.
        $this->assertFalse($this->hasPermission("comments.add", "category", $permissions, 1));
        $this->assertFalse($this->hasPermission("discussions.view", "category", $permissions, 1));
        $this->assertFalse($this->hasPermission("tokens.add", "global", $permissions));
    }

    /**
     * Assert that we can set global category permissions.
     */
    public function testRootCategoryPermissions()
    {
        $role = $this->getPermissionsRole();

        $this->api()->put("{$this->baseUrl}/{$role[$this->pk]}/permissions", [
            [
                "type" => "global",
                "permissions" => [
                    "email.view" => true,
                ],
            ],
            [
                "type" => "category",
                "id" => 0,
                "permissions" => [
                    "discussions.add" => true,
                ],
            ],
        ]);

        $permissions = $this->getPermissions($role["roleID"]);

        $this->assertTrue($this->hasPermission("email.view", "global", $permissions));
        $this->assertTrue($this->hasPermission("discussions.add", "category", $permissions, 0));
    }

    /**
     * Test GET /Roles with a user that doesn't have Garden.Settings.Manage'
     */
    public function testGetRolesWithMember()
    {
        $member = $this->createUser();
        $this->api()->setUserID($member["userID"]);

        $roles = $this->api()
            ->get($this->baseUrl)
            ->getBody();

        /** @var RolesApiController $rolesApiController */
        $rolesApiController = \Gdn::getContainer()->get(RolesApiController::class);
        $minimalSchema = $rolesApiController->minimalRolesSchema();

        foreach ($roles as $role) {
            $minimalSchema->validate($role);
            $this->assertArrayHasKey("roleID", $role);
            $this->assertArrayHasKey("name", $role);
            $this->assertArrayHasKey("description", $role);

            $this->assertArrayNotHasKey("type", $role);
            $this->assertArrayNotHasKey("deletable", $role);
            $this->assertArrayNotHasKey("canSession", $role);
            $this->assertArrayNotHasKey("personalInfo", $role);
        }
    }

    /**
     * Test that a user without the Garden.PersonalInfo.View permission cannot view roles that are flagged as personal info.
     */
    public function testFilterPersonalInfoRoles()
    {
        // Make a role that is personal Info.
        $record = $this->testPost(["name" => "personalInfo", "personalInfo" => true]);

        // And admin has the Garden.PersonalInfo.View permission, so the role should be returned.
        $allRoles = $this->api()
            ->get($this->baseUrl)
            ->getBody();
        $allRoleIDs = array_column($allRoles, "roleID");
        $this->assertContains($record["roleID"], $allRoleIDs);

        // A regular old member doesn't have the Garden.PersonInfo.View permission, so the role should be filtered out.
        $member = $this->createUser();
        $this->api()->setUserID($member["userID"]);
        $filteredRoles = $this->api()
            ->get($this->baseUrl)
            ->getBody();
        $filteredRoleIDs = array_column($filteredRoles, "roleID");
        $this->assertNotContains($record["roleID"], $filteredRoleIDs);
    }
}
