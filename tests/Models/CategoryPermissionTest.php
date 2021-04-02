<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Simple tests for category permissions.
 */
class CategoryPermissionTest extends SiteTestCase {

    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    private const PERM_VIEW = 'Vanilla.Discussions.View';

    /**
     * Test that our permission checks work as expected.
     */
    public function testNestedPermissions() {
        $role = $this->createRole();
        $userWithNoAccess = $this->createUser();
        $userWithAccess = $this->createUser([
            'roleID' => [$this->lastRoleID, \RoleModel::MEMBER_ID],
        ]);
        $permCategory = $this->createPermissionedCategory(['name' => 'Only new role access'], [$role['roleID']]);
        // Permission categoryID points to the perm category.
        $permSubCategory = $this->createCategory(['name' => 'Perm subcategory']);
        $plainCategory = $this->createCategory([
            'parentCategoryID' => -1,
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
    private function assertCategoryPermission(array $category, string $permission, bool $expected) {
        $actual = \CategoryModel::checkPermission($category['categoryID'], $permission);
        $this->assertEquals($actual, $expected);
    }
}
