<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use VanillaTests\Bootstrap;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `PermissionModel` class.
 */
class PermissionModelJunctionTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    /**
     * @var \PermissionModel
     */
    private $permissionModel;

    /**
     *  Test setup.
     */
    public function setUp(): void
    {
        self::enableCaching();
        parent::setUp();
        // Clear out the instance.
        $this->permissionModel = $this->container()->get(\PermissionModel::class);
    }

    /**
     * Test fetching of all junctions tables.
     */
    public function testGetAllJunctionTables()
    {
        $model = new \Gdn_Model("Category");
        $model->delete(["CategoryID >" => 0]);
        \CategoryModel::clearCache();
        \PermissionModel::resetAllRoles();

        $permCat1 = $this->createPermissionedCategory([], [\RoleModel::MEMBER_ID]);

        $this->assertEquals(
            [
                "Category" => [$permCat1["categoryID"]],
            ],
            $this->permissionModel->getAllJunctionTablesAndIDs()
        );

        $permCat2 = $this->createPermissionedCategory([], [\RoleModel::GUEST_ID]);
        $nonPermCat = $this->createCategory();

        $this->assertEquals(
            [
                "Category" => [$permCat1["categoryID"], $permCat2["categoryID"]],
            ],
            $this->permissionModel->getAllJunctionTablesAndIDs()
        );

        // Make sure we are actually using cache.
        \Gdn::sql()->truncate("Permission");
        $this->assertEquals(
            [
                "Category" => [$permCat1["categoryID"], $permCat2["categoryID"]],
            ],
            $this->permissionModel->getAllJunctionTablesAndIDs()
        );
    }
}
