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
class RolesTest extends AbstractResourceTest {
    use UsersAndRolesApiTestTrait;

    protected $editFields = ['canSession', 'deletable', 'description', 'name', 'personalInfo', 'type'];

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/roles';
        $this->record = [
            'name' => 'Tester',
            'description' => 'Diligent QA workers.',
            'type' => 'member',
            'deletable' => true,
            'canSession' => true,
            'personalInfo' => false
        ];
        $this->testPagingOnIndex = false;

        parent::__construct($name, $data, $dataName);
    }

    /**
     * Given a role ID, get its full list of permissions.
     *
     * @param $roleID
     * @return array
     */
    private function getPermissions($roleID) {
        $role = $this->api()->get("{$this->baseUrl}/{$roleID}", ['expand' => 'permissions'])->getBody();
        return $role['permissions'];
    }

    /**
     * Create and return a new role for testing permission setting.
     *
     * @param array $permissions
     * @return array
     */
    private function getPermissionsRole(array $permissions = []) {
        if (empty($permissions)) {
            $permissions = [
                [
                    'type' => 'global',
                    'permissions' => [
                        'tokens.add' => true
                    ]
                ],
                [
                    'type' => 'category',
                    'id' => 1,
                    'permissions' => [
                        'comments.add' => true,
                        'discussions.view' => true
                    ]
                ],
            ];
        }

        $result = $this->testPost(null, ['permissions' => $permissions]);
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
    private function hasPermission($name, $type, array $permissions, $id = false) {
        $result = false;
        foreach ($permissions as $perm) {
            if ($type !== $perm['type']) {
                continue;
            } elseif ($id !== false && (!array_key_exists('id', $perm) || $perm['id'] != $id)) {
                continue;
            } else {
                $result = array_key_exists($name, $perm['permissions']) && $perm['permissions'][$name];
                break;
            }
        }
        return $result;
    }

    /**
     * Test setting permissions with POST /roles
     */
    public function testPostPermission() {
        $role = $this->getPermissionsRole();
        $permissions = $this->getPermissions($role['roleID']);

        $this->assertTrue($this->hasPermission('tokens.add', 'global', $permissions));
        $this->assertTrue($this->hasPermission('comments.add', 'category', $permissions, 1));
        $this->assertTrue($this->hasPermission('discussions.view', 'category', $permissions, 1));

        $this->assertFalse($this->hasPermission('site.manage', 'global', $permissions));
        $this->assertFalse($this->hasPermission('discussions.add', 'category', $permissions, 1));
    }

    /**
     * Test updating permissions with PATCH /roles
     */
    public function testPatchPermission() {
        $role = $this->getPermissionsRole();

        $this->api()->patch(
            "{$this->baseUrl}/{$role[$this->pk]}",
            [
                'permissions' => [
                    [
                        'type' => 'global',
                        'permissions' => [
                            'email.view' => true
                        ]
                    ],
                    [
                        'type' => 'category',
                        'id' => 1,
                        'permissions' => [
                            'discussions.add' => true,
                            'comments.add' => false
                        ]
                    ]
                ]
            ]
        );

        $permissions = $this->getPermissions($role['roleID']);

        $this->assertTrue($this->hasPermission('tokens.add', 'global', $permissions));
        $this->assertTrue($this->hasPermission('email.view', 'global', $permissions));
        $this->assertTrue($this->hasPermission('discussions.add', 'category', $permissions, 1));
        $this->assertTrue($this->hasPermission('discussions.view', 'category', $permissions, 1));

        $this->assertFalse($this->hasPermission('site.manage', 'global', $permissions));
        $this->assertFalse($this->hasPermission('comments.add', 'category', $permissions, 1));

    }

    /**
     * Test updating permissions with PATCH /roles/:id/permissions
     */
    public function testPatchPermissionEndpoint() {
        $role = $this->getPermissionsRole();

        $this->api()->patch(
            "{$this->baseUrl}/{$role[$this->pk]}/permissions",
            [
                [
                    'type' => 'global',
                    'permissions' => [
                        'email.view' => true
                    ]
                ],
                [
                    'type' => 'category',
                    'id' => 1,
                    'permissions' => [
                        'discussions.add' => true,
                        'comments.add' => false
                    ]
                ]
            ]
        );

        $permissions = $this->getPermissions($role['roleID']);

        $this->assertTrue($this->hasPermission('tokens.add', 'global', $permissions));
        $this->assertTrue($this->hasPermission('email.view', 'global', $permissions));
        $this->assertTrue($this->hasPermission('discussions.add', 'category', $permissions, 1));
        $this->assertTrue($this->hasPermission('discussions.view', 'category', $permissions, 1));

        $this->assertFalse($this->hasPermission('site.manage', 'global', $permissions));
        $this->assertFalse($this->hasPermission('comments.add', 'category', $permissions, 1));
    }
    /**
     * Test updating permissions with PATCH /roles/:id/permissions
     */
    public function testPatchPermissionOverWrite() {
        $role = $this->getPermissionsRole([[
            'type' => 'category',
            'id' =>  1,
            'permissions' => [
                'discussions.view' => true,
                'discussions.add' => true,
                'comments.add' => true
            ]
           ]]);

        $role2 = $this->getPermissionsRole([[
            'type' => 'category',
            'id' => 1,
            'permissions' => [
                'discussions.view' => true,
                'discussions.add' => true,
                'comments.add' => true
            ]
        ]]);

        $this->api()->patch(
            "{$this->baseUrl}/{$role['roleID']}/permissions",
            [
                [
                    'type' => 'category',
                    'id' => 1,
                    'permissions' => [
                        'discussions.add' => true,
                        'comments.add' => false
                    ]
                ]
            ]
        );

        $permissions1 = $this->getPermissions($role['roleID']);
        $permissions2 = $this->getPermissions($role2['roleID']);

        $this->assertTrue($this->hasPermission('discussions.add', 'category', $permissions1, 1));
        $this->assertFalse($this->hasPermission('comments.add', 'category', $permissions1, 1));

        $this->assertTrue($this->hasPermission('discussions.add', 'category', $permissions2, 1));
        $this->assertTrue($this->hasPermission('comments.add', 'category', $permissions2, 1));
    }

    public function testPutPermissionsEndpoint() {
        $role = $this->getPermissionsRole();

        $this->api()->put(
            "{$this->baseUrl}/{$role[$this->pk]}/permissions",
            [
                [
                    'type' => 'global',
                    'permissions' => [
                        'email.view' => true
                    ]
                ],
                [
                    'type' => 'category',
                    'id' => 1,
                    'permissions' => [
                        'discussions.add' => true,
                    ]
                ]
            ]
        );

        $permissions = $this->getPermissions($role['roleID']);

        $this->assertTrue($this->hasPermission('email.view', 'global', $permissions));
        $this->assertTrue($this->hasPermission('discussions.add', 'category', $permissions, 1));

        // Make sure all the original permissions have been removed.
        $this->assertFalse($this->hasPermission('comments.add', 'category', $permissions, 1));
        $this->assertFalse($this->hasPermission('discussions.view', 'category', $permissions, 1));
        $this->assertFalse($this->hasPermission('tokens.add', 'global', $permissions));
    }

    /**
     * Test GET /Roles with a user that doesn't have Garden.Settings.Manage'
     */
    public function testGetRolesWithMember() {
        $member = $this->createUser();
        $this->api()->setUserID($member['userID']);

        $roles = $this->api()->get($this->baseUrl)->getBody();

        /** @var RolesApiController $rolesApiController */
        $rolesApiController = \Gdn::getContainer()->get(RolesApiController::class);
        $minimalSchema = $rolesApiController->minimalRolesSchema();

        foreach ($roles as $role) {
            $minimalSchema->validate($role);
            $this->assertArrayHasKey('roleID', $role);
            $this->assertArrayHasKey('name', $role);
            $this->assertArrayHasKey('description', $role);

            $this->assertArrayNotHasKey('type', $role);
            $this->assertArrayNotHasKey('deletable', $role);
            $this->assertArrayNotHasKey('canSession', $role);
            $this->assertArrayNotHasKey('personalInfo', $role);
        }
    }
}
