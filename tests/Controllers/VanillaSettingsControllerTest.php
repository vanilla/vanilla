<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use CategoryModel;
use PermissionModel;
use RoleModel;
use VanillaTests\SiteTestCase;

/**
 * Test various capabilities of VanillaSettingsController.
 */
class VanillaSettingsControllerTest extends SiteTestCase {

    /** @inheritDoc */
    protected static $addons = ["vanilla"];

    /** @var CategoryModel */
    private $categoryModel;

    /** @var PermissionModel */
    private $permissionModel;

    /**
     * Grab an array of category permissions, indexed by role ID.
     *
     * @param int $categoryID
     * @param bool $addDefaults
     * @return array
     */
    private function getCategoryPermissions(int $categoryID, bool $addDefaults): array {
        $permissions = $this->permissionModel->getJunctionPermissions(
            ["JunctionID" => $categoryID],
            "Category",
            "",
            ["AddDefaults" => $addDefaults]
        );
        $permissions = array_column($permissions, null, "RoleID");
        return $permissions;
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->container()->call(function (CategoryModel $categoryModel, PermissionModel $permissionModel) {
            $this->categoryModel = $categoryModel;
            $this->permissionModel = $permissionModel;
        });
    }

    /**
     * Verify ability to update category permissions by providing a permission config array.
     */
    public function testEditCategoryCustomPermissions(): void {
        $id = $this->categoryModel->save([
            "Name" => __FUNCTION__,
            "UrlCode" => strtolower(__FUNCTION__),
        ]);

        $permissions = $this->getCategoryPermissions($id, true);
        $discussionsView = !$permissions[RoleModel::MEMBER_ID]["Vanilla.Discussions.View"];

        $this->bessy()->postHtml("vanilla/settings/editcategory.json", [
            "CategoryID" => $id,
            "Permissions" => [
                [
                    "RoleID" => RoleModel::MEMBER_ID,
                    "Vanilla.Discussions.View" => $discussionsView,
                ],
            ],
        ]);

        $row = $this->categoryModel->getID($id, \DATASET_TYPE_ARRAY);
        $this->assertSame((int)$id, $row["PermissionCategoryID"]);

        $result = $this->getCategoryPermissions($id, false);

        $this->assertSame($discussionsView, (bool)$result[RoleModel::MEMBER_ID]["Vanilla.Discussions.View"]);
    }

    /**
     * Verify ability to reset custom permissions on a category.
     *
     * @param array $request
     * @dataProvider provideEditCategoryResetCustomPermissionsData
     */
    public function testEditCategoryResetCustomPermissions(array $request): void {
        $name = __FUNCTION__ . md5(serialize($request));
        $id = $this->categoryModel->save([
            "Name" => $name,
            "UrlCode" => strtolower($name),
        ]);

        $this->bessy()->postHtml("vanilla/settings/editcategory.json", [
            "CategoryID" => $id,
            "Permissions" => [],
        ]);
        $row = $this->categoryModel->getID($id, \DATASET_TYPE_ARRAY);
        $this->assertSame((int)$id, $row["PermissionCategoryID"]);

        $this->bessy()->postHtml("vanilla/settings/editcategory.json", ["CategoryID" => $id] + $request);
        $row = $this->categoryModel->getID($id, \DATASET_TYPE_ARRAY);
        $this->assertSame(CategoryModel::ROOT_ID, $row["PermissionCategoryID"]);

        $this->assertTrue(true);
    }

    /**
     * Provide valid request parameters for resetting a category's permissions via the Edit Category page.
     *
     * @return array
     */
    public function provideEditCategoryResetCustomPermissionsData(): array {
        return [
            "CustomPermissions: false" => [["CustomPermissions" => false]],
            "Permissions: null" => [["Permissions" => null]],
        ];
    }

    /**
     * Verify custom category permissions persist during a sparse update.
     */
    public function testSparseEditCategoryWithCustomPermissions(): void {
        $id = $this->categoryModel->save([
            "Name" => __FUNCTION__,
            "UrlCode" => strtolower(__FUNCTION__),
        ]);

        $permissions = $this->getCategoryPermissions($id, true);
        $discussionsView = !$permissions[RoleModel::MEMBER_ID]["Vanilla.Discussions.View"];

        $this->bessy()->postHtml("vanilla/settings/editcategory.json", [
            "CategoryID" => $id,
            "Permissions" => [
                [
                    "RoleID" => RoleModel::MEMBER_ID,
                    "Vanilla.Discussions.View" => $discussionsView,
                ],
            ],
        ]);

        $updatedRow = $this->categoryModel->getID($id, \DATASET_TYPE_ARRAY);
        $this->assertSame((int)$id, $updatedRow["PermissionCategoryID"]);

        $updatedPermissions = $this->getCategoryPermissions($id, false);
        $this->assertSame(
            $discussionsView,
            (bool)$updatedPermissions[RoleModel::MEMBER_ID]["Vanilla.Discussions.View"]
        );

        $updatedName = md5(time());
        $this->bessy()->postHtml("vanilla/settings/editcategory.json", [
            "CategoryID" => $id,
            "Name" => $updatedName,
        ]);
        $resultRow = $this->categoryModel->getID($id, \DATASET_TYPE_ARRAY);
        $resultPermissions = $this->getCategoryPermissions($id, false);

        $this->assertSame($updatedName, $resultRow["Name"]);
        $this->assertSame((int)$id, $resultRow["PermissionCategoryID"]);
        $this->assertSame(
            $discussionsView,
            (bool)$resultPermissions[RoleModel::MEMBER_ID]["Vanilla.Discussions.View"]
        );
    }
}
