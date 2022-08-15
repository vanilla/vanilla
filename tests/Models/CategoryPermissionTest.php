<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Vanilla\Utility\ModelUtils;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Simple tests for category permissions.
 */
class CategoryPermissionTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    private const PERM_VIEW = "Vanilla.Discussions.View";

    /**
     * Test that our permission checks work as expected.
     */
    public function testNestedPermissions()
    {
        $role = $this->createRole();
        $userWithNoAccess = $this->createUser();
        $userWithAccess = $this->createUser([
            "roleID" => [$this->lastRoleID, \RoleModel::MEMBER_ID],
        ]);
        $permCategory = $this->createPermissionedCategory(["name" => "Only new role access"], [$role["roleID"]]);
        // Permission categoryID points to the perm category.
        $permSubCategory = $this->createCategory(["name" => "Perm subcategory"]);
        $plainCategory = $this->createCategory([
            "parentCategoryID" => -1,
        ]);

        $this->runWithUser(function () use ($permCategory, $plainCategory, $permSubCategory) {
            $this->assertCategoryPermission($permCategory, self::PERM_VIEW, false);
            $this->assertCategoryPermission($permSubCategory, self::PERM_VIEW, false);
            $this->assertCategoryPermission($plainCategory, self::PERM_VIEW, true);
        }, $userWithNoAccess);

        $this->runWithUser(function () use ($permCategory, $plainCategory, $permSubCategory) {
            $this->assertCategoryPermission($permCategory, self::PERM_VIEW, true);
            $this->assertCategoryPermission($permSubCategory, self::PERM_VIEW, true);
            $this->assertCategoryPermission($plainCategory, self::PERM_VIEW, true);
        }, $userWithAccess);
    }

    /**
     * Assert a permission value of the current user.
     *
     * @param array $category
     * @param string $permission
     * @param bool $expected
     */
    private function assertCategoryPermission(array $category, string $permission, bool $expected)
    {
        $actual = \CategoryModel::checkPermission($category["categoryID"], $permission);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test situations when permission rows are left behind and orphaned in the permission table.
     *
     * @see https://github.com/vanilla/support/issues/4070
     */
    public function testOrphanedPermissionRow()
    {
        $role = $this->createRole();
        $member = $this->createUser([
            "roleID" => [\RoleModel::MEMBER_ID],
        ]);
        $category = $this->createPermissionedCategory(["name" => "Oprhaned Role Access"], [$role["roleID"]]);

        // No-one should have access to this now.
        $this->runWithUser(function () use ($category) {
            $this->assertCategoryPermission($category, "Vanilla.Discussions.View", false);
        }, $member);

        // Remove custom permissions (but don't clear permission table).
        $categoryModel = \CategoryModel::instance();
        $categoryModel->setField($category["categoryID"], "PermissionCategoryID", -1);
        \Gdn::userModel()->clearPermissions();
        \CategoryModel::clearCache();

        // User should have permissions now.
        $this->runWithUser(function () use ($category) {
            $this->assertCategoryPermission($category, "Vanilla.Discussions.View", true);
        }, $member);
    }
}
