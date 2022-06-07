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

/**
 * Unit test for LayoutViewModel
 */
class LayoutViewModelTest extends ClassLocatorTest
{
    /* @var LayoutViewModel */
    private $layoutViewModel;
    /* @var LayoutModel */
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
     * Test LayoutView model getViewLayout method
     */
    public function testGetViewLayout()
    {
        $layout = ["layoutID" => 1, "layoutViewType" => "home", "name" => "Home Test", "layout" => "test"];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = ["layoutID" => $layoutID, "recordID" => 1, "recordType" => "home", "layoutViewType" => "home"];
        $id = $this->layoutViewModel->insert($layoutView);

        $results = $this->layoutViewModel->getViewsByLayoutID(1);

        $this->assertSame(1, count($results));
        $result = $results[0];
        $this->assertSame($id, $result["layoutViewID"]);
        $this->assertEquals($layoutView["layoutID"], $result["layoutID"]);
    }

    /**
     * Test LayoutView model getViewLayout method
     */
    public function testGetLayoutIdLookup()
    {
        $layout = ["layoutID" => 1, "layoutViewType" => "home", "name" => "Home Test", "layout" => "test"];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = ["layoutID" => $layoutID, "recordID" => 1, "recordType" => "global", "layoutViewType" => "home"];
        $id = $this->layoutViewModel->insert($layoutView);

        $layoutViewFile = ["layoutID" => "file", "recordID" => 2, "recordType" => "global", "layoutViewType" => "home"];
        $this->layoutViewModel->saveLayoutView([
            "layoutID" => $layoutID,
            "recordID" => 2,
            "recordType" => "global",
            "layoutViewType" => "home",
        ]);
        $this->layoutViewModel->saveLayoutView($layoutViewFile);

        $resultLayoutID = $this->layoutViewModel->getLayoutIdLookup("home", "global", 1);
        $resultFileLayoutID = $this->layoutViewModel->getLayoutIdLookup("home", "global", 2);

        $this->assertEquals($layoutView["layoutID"], $resultLayoutID);
        $this->assertEquals($layoutViewFile["layoutID"], $resultFileLayoutID);
    }

    /**
     * Test LayoutView model getViewLayout method
     */
    public function testGetLayoutView()
    {
        $layout = ["layoutID" => 1, "layoutViewType" => "home", "name" => "Home Test", "layout" => "test"];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = ["layoutID" => $layoutID, "recordID" => 1, "recordType" => "global", "layoutViewType" => "home"];
        $id = $this->layoutViewModel->insert($layoutView);

        $layoutViewFile = ["layoutID" => "file", "recordID" => 2, "recordType" => "global", "layoutViewType" => "home"];
        $idFile = $this->layoutViewModel->insert($layoutViewFile);

        $result = $this->layoutViewModel->getLayoutViews(true, "home", "global", 1);
        $resultFile = $this->layoutViewModel->getLayoutViews(true, "home", "global", 2);
        $resultRecursive = $this->layoutViewModel->getLayoutViews(true, "home", "global1", 3);

        $this->assertSame($id, $result["layoutViewID"]);
        $this->assertEquals($layoutView["layoutID"], $result["layoutID"]);
        $this->assertEquals($layoutView["layoutID"], $result["layoutID"]);

        $this->assertSame($idFile, $resultFile["layoutViewID"]);
        $this->assertEquals($layoutViewFile["layoutID"], $resultFile["layoutID"]);
        $this->assertEquals($layoutViewFile["layoutID"], $resultFile["layoutID"]);

        $this->assertEquals($layoutView["layoutID"], $resultRecursive["layoutID"]);
    }

    /**
     * Test model layout View model normalize Rows with string layoutID.
     *
     * @throws \Exception Throws exception when something goes wrong.
     */
    public function testNormalizeRows()
    {
        $layoutID = "home";
        $layoutView = ["layoutID" => $layoutID, "recordID" => 1, "recordType" => "global", "layoutViewType" => "home"];
        $id = $this->layoutViewModel->insert($layoutView);
        $rows = $this->layoutViewModel->getViewsByLayoutID($layoutID);
        $result = $this->layoutViewModel->normalizeRows($rows, ["record"]);

        $this->assertSame(1, count($result));
        $this->assertSame(2, count($result[0]["record"]));
    }
}
