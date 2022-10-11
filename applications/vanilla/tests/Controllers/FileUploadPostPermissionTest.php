<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Controllers;

use Gdn;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test file upload permissions.
 */
class FileUploadPostPermissionTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait, CommunityApiTestTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test that uploads.add permission is respected when setting the post form's "FileUpload" attribute when a user
     * has the permission.
     */
    public function testFileUploadPermissionAllow()
    {
        $this->runWithPermissions(
            function () {
                $r = $this->bessy()->get("/post/discussion");
                $uploadAllowed = $r->Form->EventArguments["Attributes"]["FileUpload"];
                $this->assertTrue($uploadAllowed);
            },
            ["uploads.add" => true],
            $this->categoryPermission(-1, ["discussions.add" => true])
        );
    }

    /**
     * Test that uploads.add permission is respected when setting the post form's "FileUpload" attribute when a user
     * doesn't have the permission.
     */
    public function testFileUploadPermissionDisallow()
    {
        $this->runWithPermissions(
            function () {
                $r = $this->bessy()->get("/post/discussion");
                $uploadAllowed = $r->Form->EventArguments["Attributes"]["FileUpload"];
                $this->assertFalse($uploadAllowed);
            },
            ["uploads.add" => false],
            $this->categoryPermission(-1, ["discussions.add" => true])
        );
    }

    /**
     * Test deleting the old "Plugins.Attachments.Upload.Allow" permission and updating the value of "Garden.Uploads.Allow"
     * to true if the old permission is true.
     */
    public function testUploadPermissionMigration()
    {
        $oldPermission = "Plugins.Attachments.Upload.Allow";

        // Create a role without the new upload permission.
        $role = $this->createRole(["Garden.Uploads.Add" => 0]);

        // Define the old permission, defaulted to true, so it's in the db.
        $permissionModel = Gdn::getContainer()->get(\PermissionModel::class);
        $permissionModel->define([$oldPermission => 1]);

        $structure = Gdn::structure();

        // Confirm we have the old permission column in the db.
        $oldPermissionExists = $structure->table("Permission")->columnExists("Plugins.Attachments.Upload.Allow");
        $this->assertTrue($oldPermissionExists);

        // Run structure.
        include PATH_APPLICATIONS . "/dashboard/settings/structure.php";

        // Confirm the old permission column has been deleted.
        $oldPermissionExists = $structure->table("Permission")->columnExists("Plugins.Attachments.Upload.Allow");
        $this->assertFalse($oldPermissionExists);

        // Confirm that the new permission has been updated to true.
        $updatedRolePermissions = $this->roleModel->getPermissions($role["roleID"]);
        $globalRolePermissions = $updatedRolePermissions[0];
        $this->assertSame($globalRolePermissions["Garden.Uploads.Add"], 1);
    }
}
