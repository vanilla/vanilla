<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Layout;

use Gdn;
use Vanilla\Layout\LayoutModel;
use Vanilla\Layout\LayoutViewModel;
use VanillaTests\Library\Garden\ClassLocatorTest;
use VanillaTests\SiteTestTrait;

/**
 * Unit test for LayoutModel
 */
class LayoutModelTest extends ClassLocatorTest
{
    use SiteTestTrait;

    /**
     * @var LayoutViewModel
     */
    private $layoutViewModel;
    /**
     * @var LayoutModel
     */
    private $layoutModel;

    /**
     * Get a new model for each test.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container()->call(function (\Gdn_DatabaseStructure $st, \Gdn_SQLDriver $sql) {
            $Database = Gdn::database();
            if (!$st->tableExists("layout")) {
                LayoutModel::structure($Database);
            }
            if (!$st->tableExists("layoutView")) {
                LayoutViewModel::structure($Database);
            }
        });

        $this->resetTable("layout");
        $this->resetTable("layoutView");
        $this->layoutViewModel = $this->container()->get(LayoutViewModel::class);
        $this->layoutModel = $this->container()->get(LayoutModel::class);
    }

    /**
     * Test model layout model normalize Rows.
     *
     * @throws \Exception Throws exception when something goes wrong.
     */
    public function testNormalizeRows()
    {
        $layout = ["layoutID" => 1, "layoutViewType" => "home", "name" => "Home Test", "layout" => "test"];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = ["layoutID" => $layoutID, "recordID" => 1, "recordType" => "global", "layoutViewType" => "home"];
        $this->layoutViewModel->insert($layoutView);
        $layoutView = ["layoutID" => $layoutID, "recordID" => 2, "recordType" => "global", "layoutViewType" => "home"];
        $this->layoutViewModel->insert($layoutView);
        $rows = $this->layoutModel->getAll();
        $result = $this->layoutModel->normalizeRows($rows, ["layoutViews"]);

        $this->assertSame(1, count($result));
        $this->assertSame(2, count($result[0]["layoutViews"]));
    }

    /**
     * Test Layout model getByID method
     */
    public function testGetLayout()
    {
        $layout = ["layoutID" => 1, "layoutViewType" => "home", "name" => "Home Test", "layout" => "test"];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = ["layoutID" => $layoutID, "recordID" => 1, "recordType" => "home", "layoutViewType" => "home"];
        $this->layoutViewModel->insert($layoutView);

        $result = $this->layoutModel->getByID($layoutID);

        $this->assertSame($layoutID, $result["layoutID"]);
    }

    /**
     * Test GetLayoutFromLayoutType.
     * @throws \Exception Excpetion.
     */
    public function testGetLayoutFromLayoutType()
    {
        $layout = ["layoutID" => 1, "layoutViewType" => "home", "name" => "Home Test", "layout" => "test"];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = ["layoutID" => $layoutID, "recordID" => 1, "recordType" => "home", "layoutViewType" => "home"];
        $this->layoutViewModel->insert($layoutView);

        $result = $this->layoutModel->getLayoutFromLayoutType("home", null);

        $this->assertSame($layout["name"], $result["name"]);
    }

    /**
     * Test removing the "layoutType" column from the GDN_layout table.
     */
    public function testStructureCleanup(): void
    {
        $construct = Gdn::getContainer()->get(\Gdn_DatabaseStructure::class);
        $construct
            ->table("layout")
            ->column("layoutType", "varchar(20)", "test")
            ->set();
        $layoutTypeColumnExists = $construct->table("layout")->columnExists("layoutType");
        $this->assertTrue($layoutTypeColumnExists);
        LayoutModel::structure(Gdn::database());
        $layoutTypeColumnExists = $construct->table("layout")->columnExists("layoutType");
        $this->assertFalse($layoutTypeColumnExists);
    }
}
