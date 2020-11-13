<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use VanillaTests\Bootstrap;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `PermissionModel` class.
 */
class PermissionModelTest extends SiteTestCase {
    use TestCategoryModelTrait;

    /**
     * @var \PermissionModel
     */
    private $permissionModel;

    /**
     * @var mixed \Gdn_DatabaseStructure
     */
    private $structure;

    /**
     * @var array
     */
    private $category;

    /**
     *  Test setup.
     */
    public function setUp(): void {
        parent::setUp();

        $this->permissionModel = $this->container()->get(\PermissionModel::class);
        $this->structure = $this->container()->get(\Gdn_DatabaseStructure::class);
        $this->createUserFixtures();
        $this->categoryModel->Validation->setResetOnValidate(true);
        $this->category = $this->insertCategories(1, ['CustomPermissions' => true])[0];
        $this->categoryModel->Schema = null; // kludge for validation corruption.
    }

    /**
     * Test get permissions.
     */
    public function testGetPermissions(): void {
        $roleData = [
            'RoleID' => 3
        ];
        $globalPermissions = $this->permissionModel->getGlobalPermissions($roleData);
        $junctionPermissions = $this->permissionModel->getJunctionPermissions($roleData);
        $rolePermission = $this->permissionModel->getPermissions($roleData);
        $resultMerge = array_merge($globalPermissions, $junctionPermissions);

        $this->assertSame($resultMerge, $rolePermission);
    }

    /**
     * Test Junction Permissions
     * Permissions for a specific table, like Category, should not be overwritten by
     * permissions for another type of table.
     */
    public function testGetJunctionPermissions() {
        $addCustomPermsToCategory = [
            'CategoryID' => 1,
            'CustomPermissions' => 1
        ];
        $this->categoryModel->save($addCustomPermsToCategory);
        if (count($this->categoryModel->Validation->results()) > 0) {
            $this->fail($this->categoryModel->Validation->resultsText());
        }

        $categoryJunctionPermissions = [
            'CategoryID' => 1,
            'CustomPermissions' => 1,
            'JunctionTable' => 'Category',
            'JunctionColumn' => 'PermissionCategoryID',
            'JunctionID' => 1,
            'RoleID' => 8,
            'Vanilla.Comments.Delete' => 1
        ];
        $this->permissionModel->save($categoryJunctionPermissions);

        $altDefaultPermissions = [
            'JunctionTable' => 'Discussion',
            'JunctionColumn' => 'CategoryID',
            'JunctionID' => null,
            'RoleID' => 0,
            'Vanilla.Discussions.View' => 3,
            'Vanilla.Discussions.Add' => 3,
            'Vanilla.Discussions.Edit' => 2,
            'Vanilla.Discussions.Announce' => 2,
            'Vanilla.Discussions.Sink' => 2,
            'Vanilla.Discussions.Close' => 2,
            'Vanilla.Discussions.Delete' => 2,
            'Vanilla.Comments.Add' => 3,
            'Vanilla.Comments.Edit' => 2,
            'Vanilla.Comments.Delete' => 2
        ];
        $this->permissionModel->save($altDefaultPermissions);

        $altJunctionPermissions = [
            'JunctionTable' => 'Discussion',
            'JunctionColumn' => 'CategoryID',
            'JunctionID' => 1,
            'RoleID' => 8,
            'Vanilla.Comments.Delete' => 0
        ];
        $this->permissionModel->save($altJunctionPermissions);

        // Add a Sort column to the Discussions table to spoof it being a Category Table.
        $this->structure->table('Discussion')->column('Sort', 'TinyInt', 1)->set();

        $categoryPermissions = array_column(
            $this->permissionModel->getJunctionPermissions(['RoleID' => 8], 'Category'),
            null,
            'JunctionID'
        );
        $discussionPermissions = array_column(
            $this->permissionModel->getJunctionPermissions(['RoleID' => 8], 'Discussion'),
            null,
            'JunctionID'
        );
        $this->assertEquals(1, $categoryPermissions[1]['Vanilla.Comments.Delete']);
        $this->assertEquals(0, $discussionPermissions[1]['Vanilla.Comments.Delete']);
    }

    /**
     * Test the basic admin user permissions after an installation.
     */
    public function testGetPermissionsByUserBasic() {
        $permissions = $this->permissionModel->getPermissionsByUser(self::$siteInfo['adminUserID']);
        $expected = [
            0 => 'Garden.Email.View',
            1 => 'Garden.Settings.Manage',
            2 => 'Garden.Settings.View',
            3 => 'Garden.SignIn.Allow',
            4 => 'Garden.Users.Add',
            5 => 'Garden.Users.Edit',
            6 => 'Garden.Users.Delete',
            7 => 'Garden.Users.Approve',
            8 => 'Garden.Activity.Delete',
            9 => 'Garden.Activity.View',
            10 => 'Garden.Profiles.View',
            11 => 'Garden.Profiles.Edit',
            12 => 'Garden.Curation.Manage',
            13 => 'Garden.Moderation.Manage',
            14 => 'Garden.PersonalInfo.View',
            15 => 'Garden.AdvancedNotifications.Allow',
            16 => 'Garden.Community.Manage',
            17 => 'Garden.Uploads.Add',
            18 => 'Vanilla.Discussions.View',
            19 => 'Vanilla.Discussions.Add',
            20 => 'Vanilla.Discussions.Edit',
            21 => 'Vanilla.Discussions.Announce',
            22 => 'Vanilla.Discussions.Sink',
            23 => 'Vanilla.Discussions.Close',
            24 => 'Vanilla.Discussions.Delete',
            25 => 'Vanilla.Comments.Add',
            26 => 'Vanilla.Comments.Edit',
            27 => 'Vanilla.Comments.Delete',
            28 => 'Conversations.Conversations.Add',
            'Vanilla.Discussions.View' =>
                [
                    0 => -1,
                ],
            'Vanilla.Discussions.Add' =>
                [
                    0 => -1,
                ],
            'Vanilla.Discussions.Edit' =>
                [
                    0 => -1,
                ],
            'Vanilla.Discussions.Announce' =>
                [
                    0 => -1,
                ],
            'Vanilla.Discussions.Sink' =>
                [
                    0 => -1,
                ],
            'Vanilla.Discussions.Close' =>
                [
                    0 => -1,
                ],
            'Vanilla.Discussions.Delete' =>
                [
                    0 => -1,
                ],
            'Vanilla.Comments.Add' =>
                [
                    0 => -1,
                ],
            'Vanilla.Comments.Edit' =>
                [
                    0 => -1,
                ],
            'Vanilla.Comments.Delete' =>
                [
                    0 => -1,
                ],
        ];

        $this->assertSame($expected, $permissions);
    }

    /**
     * Test the basic install permissions for the guest user.
     */
    public function testGetPermissionsByUserGuest() {
        $permissions = $this->permissionModel->getPermissionsByUser(0);
        $expected = [
            0 => 'Garden.Activity.View',
            1 => 'Garden.Profiles.View',
            2 => 'Vanilla.Discussions.View',
            'Vanilla.Discussions.View' =>
                [
                    0 => -1,
                ],
        ];

        $this->assertSame($expected, $permissions);
    }

    /**
     * Test the basic permission namespace.
     */
    public function testPermissionNamespace(): void {
        $this->assertSame('Vanilla', \PermissionModel::permissionNamespace('Vanilla.Discussions.Add'));
        $this->assertSame('', \PermissionModel::permissionNamespace('Foo'));
    }

    /**
     * Getting the edit permissions should return global keys and one key for each custom permission.
     */
    public function testGetEditForRole(): void {
        $perms = $this->permissionModel->getPermissionsEdit($this->roleID(Bootstrap::ROLE_ADMIN));

        $expectedKeys = [
            '_Garden',
            '_Vanilla',
            '_Conversations',
            'Category/PermissionCategoryID/-1',
            'Category/PermissionCategoryID/'.$this->category['CategoryID'],
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $perms);
        }
    }

    /**
     * Test getting the permissions for a specific category.
     */
    public function testGetEditForCategory(): void {
        $categoryID = $this->category['CategoryID'];
        $permissions = $this->permissionModel->getJunctionPermissions(
            ['JunctionID' => $categoryID],
            'Category'
        );
        $editPerms = $this->permissionModel->unpivotPermissions($permissions, true);

        $roles = [$this->roleID(Bootstrap::ROLE_ADMIN), $this->roleID(Bootstrap::ROLE_MOD), $this->roleID(Bootstrap::ROLE_MEMBER)];
        foreach ($roles as $roleID) {
            $key = "Category/PermissionCategoryID/$categoryID/$roleID";
            $this->assertArrayHasKey($key, $editPerms);
        }
    }
}
