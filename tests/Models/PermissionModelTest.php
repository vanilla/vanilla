<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Vanilla\Forum\Widgets\CategoriesWidgetTrait;
use VanillaTests\Bootstrap;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the `PermissionModel` class.
 */
class PermissionModelTest extends SiteTestCase
{
    use TestCategoryModelTrait;
    use CommunityApiTestTrait;
    use CategoriesWidgetTrait;

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
    public function setUp(): void
    {
        $this->enableCaching();
        parent::setUp();

        $this->permissionModel = $this->container()->get(\PermissionModel::class);
        $this->structure = $this->container()->get(\Gdn_DatabaseStructure::class);
        $this->createUserFixtures();
        $this->categoryModel->Validation->setResetOnValidate(true);
        $this->category = $this->insertCategories(1, ["CustomPermissions" => true])[0];
        $this->categoryModel->Schema = null; // kludge for validation corruption.
    }

    /**
     * Test get permissions.
     */
    public function testGetPermissions(): void
    {
        $roleData = [
            "RoleID" => 3,
        ];

        $globalPermissions = $this->permissionModel->getGlobalPermissions($roleData);
        $junctionPermissions = $this->permissionModel->getJunctionPermissions($roleData);
        $rolePermission = $this->permissionModel->getPermissions($roleData);
        $resultMerge = array_merge($globalPermissions, $junctionPermissions);

        $this->assertSame($resultMerge, $rolePermission);
    }

    /**
     * Test get All permissions.
     */
    public function testGetAllPermissions(): void
    {
        $roleData = [
            "Garden.Email.View",
            "Garden.Settings.Manage",
            "Garden.Settings.View",
            "Garden.SignIn.Allow",
            "Garden.Users.Add",
            "Garden.Users.Edit",
            "Garden.Users.Delete",
            "Garden.Users.Approve",
            "Garden.Activity.Delete",
            "Garden.Activity.View",
            "Garden.Profiles.View",
            "Garden.Profiles.Edit",
            "Garden.ProfilePicture.Edit",
            "Garden.Username.Edit",
            "Garden.Curation.Manage",
            "Garden.Moderation.Manage",
            "Garden.PersonalInfo.View",
            "Garden.InternalInfo.View",
            "Garden.AdvancedNotifications.Allow",
            "Garden.Community.Manage",
        ];

        $allPermissions = $this->permissionModel->getAllPermissions();

        $this->assertSame($roleData, array_slice($allPermissions, 0, count($roleData)));
    }

    /**
     * Test Junction Permissions
     * Permissions for a specific table, like Category, should not be overwritten by
     * permissions for another type of table.
     */
    public function testGetJunctionPermissions()
    {
        $addCustomPermsToCategory = [
            "CategoryID" => 1,
            "CustomPermissions" => 1,
        ];
        $this->categoryModel->save($addCustomPermsToCategory);
        if (count($this->categoryModel->Validation->results()) > 0) {
            $this->fail($this->categoryModel->Validation->resultsText());
        }

        $categoryJunctionPermissions = [
            "CategoryID" => 1,
            "CustomPermissions" => 1,
            "JunctionTable" => "Category",
            "JunctionColumn" => "PermissionCategoryID",
            "JunctionID" => 1,
            "RoleID" => 8,
            "Vanilla.Comments.Delete" => 1,
        ];
        $this->permissionModel->save($categoryJunctionPermissions);

        $altDefaultPermissions = [
            "JunctionTable" => "Discussion",
            "JunctionColumn" => "CategoryID",
            "JunctionID" => null,
            "RoleID" => 0,
            "Vanilla.Discussions.View" => 3,
            "Vanilla.Discussions.Add" => 3,
            "Vanilla.Discussions.Edit" => 2,
            "Vanilla.Discussions.Announce" => 2,
            "Vanilla.Discussions.Sink" => 2,
            "Vanilla.Discussions.Close" => 2,
            "Vanilla.Discussions.Delete" => 2,
            "Vanilla.Comments.Add" => 3,
            "Vanilla.Comments.Edit" => 2,
            "Vanilla.Comments.Delete" => 2,
        ];
        $this->permissionModel->save($altDefaultPermissions);

        $altJunctionPermissions = [
            "JunctionTable" => "Discussion",
            "JunctionColumn" => "CategoryID",
            "JunctionID" => 1,
            "RoleID" => 8,
            "Vanilla.Comments.Delete" => 0,
        ];
        $this->permissionModel->save($altJunctionPermissions);

        // Add a Sort column to the Discussions table to spoof it being a Category Table.
        $this->structure
            ->table("Discussion")
            ->column("Sort", "TinyInt", 1)
            ->set();

        $categoryPermissions = array_column(
            $this->permissionModel->getJunctionPermissions(["RoleID" => 8], "Category"),
            null,
            "JunctionID"
        );
        $discussionPermissions = array_column(
            $this->permissionModel->getJunctionPermissions(["RoleID" => 8], "Discussion"),
            null,
            "JunctionID"
        );
        $this->assertEquals(1, $categoryPermissions[1]["Vanilla.Comments.Delete"]);
        $this->assertEquals(0, $discussionPermissions[1]["Vanilla.Comments.Delete"]);
    }

    /**
     * Test the basic admin user permissions after an installation.
     */
    public function testGetPermissionsByUserBasic()
    {
        $permissions = $this->permissionModel->getPermissionsByUser(self::$siteInfo["adminUserID"]);
        $expected = [
            0 => "Conversations.Conversations.Add",
            1 => "Garden.Activity.Delete",
            2 => "Garden.Activity.View",
            3 => "Garden.AdvancedNotifications.Allow",
            4 => "Garden.Community.Manage",
            5 => "Garden.Curation.Manage",
            6 => "Garden.Email.View",
            7 => "Garden.InternalInfo.View",
            8 => "Garden.Moderation.Manage",
            9 => "Garden.PersonalInfo.View",
            10 => "Garden.Profiles.Edit",
            11 => "Garden.Profiles.View",
            12 => "Garden.Settings.Manage",
            13 => "Garden.Settings.View",
            14 => "Garden.SignIn.Allow",
            15 => "Garden.Uploads.Add",
            16 => "Garden.Username.Edit",
            17 => "Garden.Users.Add",
            18 => "Garden.Users.Approve",
            19 => "Garden.Users.Delete",
            20 => "Garden.Users.Edit",
            21 => "Vanilla.Comments.Add",
            22 => "Vanilla.Comments.Delete",
            23 => "Vanilla.Comments.Edit",
            24 => "Vanilla.Discussions.Add",
            25 => "Vanilla.Discussions.Announce",
            26 => "Vanilla.Discussions.Close",
            27 => "Vanilla.Discussions.Delete",
            28 => "Vanilla.Discussions.Edit",
            29 => "Vanilla.Discussions.Sink",
            30 => "Vanilla.Discussions.View",
        ];

        $this->assertEqualsCanonicalizing($expected, $permissions);
    }

    /**
     * Test the basic install permissions for the guest user.
     */
    public function testGetPermissionsByUserGuest()
    {
        $permissions = $this->permissionModel->getPermissionsByUser(0);
        $expected = [
            0 => "Garden.Activity.View",
            1 => "Garden.Profiles.View",
            2 => "Vanilla.Discussions.View",
        ];

        $this->assertSame($expected, $permissions);
    }

    /**
     * Test the basic permission namespace.
     */
    public function testPermissionNamespace(): void
    {
        $this->assertSame("Vanilla", \PermissionModel::permissionNamespace("Vanilla.Discussions.Add"));
        $this->assertSame("", \PermissionModel::permissionNamespace("Foo"));
    }

    /**
     * Getting the edit permissions should return global keys and one key for each custom permission.
     */
    public function testGetEditForRole(): void
    {
        $perms = $this->permissionModel->getPermissionsEdit($this->roleID(Bootstrap::ROLE_ADMIN));

        $expectedKeys = [
            "_Garden",
            "_Vanilla",
            "_Conversations",
            "Category/PermissionCategoryID/-1",
            "Category/PermissionCategoryID/" . $this->category["CategoryID"],
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $perms);
        }
    }

    /**
     * Test getting the permissions for a specific category.
     */
    public function testGetEditForCategory(): void
    {
        $categoryID = $this->category["CategoryID"];
        $permissions = $this->permissionModel->getJunctionPermissions(["JunctionID" => $categoryID], "Category");
        $editPerms = $this->permissionModel->unpivotPermissions($permissions, true);

        $roles = [
            $this->roleID(Bootstrap::ROLE_ADMIN),
            $this->roleID(Bootstrap::ROLE_MOD),
            $this->roleID(Bootstrap::ROLE_MEMBER),
        ];
        foreach ($roles as $roleID) {
            $key = "Category/PermissionCategoryID/$categoryID/$roleID";
            $this->assertArrayHasKey($key, $editPerms);
        }
    }

    /**
     * Test save all permissions
     * Permissions for a specific table, like Category, should not be overwritten by
     * permissions for another type of table.
     */
    public function testSaveAllPermissions()
    {
        $cat1 = $this->createCategory();
        $this->categoryModel->save([
            "PermissionCategoryID" => $cat1["categoryID"],
            "CategoryID" => $cat1["categoryID"],
        ]);
        $cat2 = $this->createCategory();
        $this->categoryModel->save([
            "PermissionCategoryID" => $cat2["categoryID"],
            "CategoryID" => $cat2["categoryID"],
        ]);
        $cat3 = $this->createCategory();
        $this->categoryModel->save([
            "PermissionCategoryID" => $cat3["categoryID"],
            "CategoryID" => $cat3["categoryID"],
        ]);

        if (count($this->categoryModel->Validation->results()) > 0) {
            $this->fail($this->categoryModel->Validation->resultsText());
        }
        $roleID = 8;
        $categoryJunctionPermissions = [
            [
                "JunctionID" => "",
                "JunctionTable" => "",
                "JunctionColumn" => "",
                "RoleID" => $roleID,
                "Vanilla.Discussions.View" => 1,
                "Vanilla.Discussions.Add" => 1,
                "Vanilla.Discussions.Edit" => 1,
                "Vanilla.Discussions.Announce" => 1,
                "Vanilla.Discussions.Sink" => 1,
                "Vanilla.Discussions.Close" => 1,
                "Vanilla.Discussions.Delete" => 1,
                "Vanilla.Comments.Add" => 1,
                "Vanilla.Comments.Edit" => 1,
                "Vanilla.Comments.Delete" => 1,
            ],
            [
                "JunctionID" => $cat1["categoryID"],
                "JunctionTable" => "Category",
                "JunctionColumn" => "PermissionCategoryID",
                "RoleID" => $roleID,
                "Vanilla.Discussions.View" => 1,
                "Vanilla.Discussions.Add" => 1,
                "Vanilla.Discussions.Edit" => 0,
                "Vanilla.Discussions.Announce" => 1,
                "Vanilla.Discussions.Sink" => 0,
                "Vanilla.Discussions.Close" => 0,
                "Vanilla.Discussions.Delete" => 0,
                "Vanilla.Comments.Add" => 1,
                "Vanilla.Comments.Edit" => 0,
                "Vanilla.Comments.Delete" => 1,
            ],
            [
                "JunctionID" => $cat2["categoryID"],
                "JunctionTable" => "Category",
                "JunctionColumn" => "PermissionCategoryID",
                "RoleID" => $roleID,
                "Vanilla.Discussions.View" => 0,
                "Vanilla.Discussions.Add" => 1,
                "Vanilla.Discussions.Edit" => 1,
                "Vanilla.Discussions.Announce" => 1,
                "Vanilla.Discussions.Sink" => 0,
                "Vanilla.Discussions.Close" => 0,
                "Vanilla.Discussions.Delete" => 1,
                "Vanilla.Comments.Add" => 1,
                "Vanilla.Comments.Edit" => 0,
                "Vanilla.Comments.Delete" => 1,
            ],
            [
                "JunctionID" => $cat3["categoryID"],
                "JunctionTable" => "Category",
                "JunctionColumn" => "PermissionCategoryID",
                "RoleID" => $roleID,
                "Vanilla.Discussions.View" => 0,
                "Vanilla.Discussions.Add" => 1,
                "Vanilla.Discussions.Edit" => 1,
                "Vanilla.Discussions.Announce" => 1,
                "Vanilla.Discussions.Sink" => 1,
                "Vanilla.Discussions.Close" => 1,
                "Vanilla.Discussions.Delete" => 1,
                "Vanilla.Comments.Add" => 1,
                "Vanilla.Comments.Edit" => 1,
                "Vanilla.Comments.Delete" => 0,
            ],
        ];
        $this->permissionModel->saveAll($categoryJunctionPermissions, ["RoleID" => 8]);

        $cat3permissionData = $this->permissionModel->getJunctionPermissions(
            ["RoleID" => $roleID, "JunctionID" => $cat3["categoryID"]],
            "Category"
        );

        $this->assertEquals(1, $cat3permissionData[1]["Vanilla.Discussions.Announce"]);
    }
}
