<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Layout;

use Garden\Web\Exception\ClientException;
use Gdn;
use Vanilla\Layout\LayoutModel;
use Vanilla\Layout\LayoutViewModel;
use VanillaTests\Models\TestCategoryModelTrait;
use VanillaTests\SiteTestCase;

/**
 * Unit test for LayoutViewModel
 */
class LayoutViewModelTest extends SiteTestCase
{
    use TestCategoryModelTrait;

    /* @var LayoutViewModel */
    private $layoutViewModel;
    /* @var LayoutModel */
    private $layoutModel;

    /**
     * Get a new model for each test.
     */
    public function setUp(): void
    {
        $this->enableCaching();
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
    public function testInsertMultipleViewsAndGetLayoutIdLookup()
    {
        $layout = ["layoutID" => 1, "layoutViewType" => "home", "name" => "Home Test", "layout" => "test"];
        $layoutID = $this->layoutModel->insert($layout);

        $layoutViews = [["recordID" => -1, "recordType" => "global"], ["recordID" => 1, "recordType" => "category"]];
        $this->layoutViewModel->saveLayoutViews($layoutViews, "home", $layoutID);

        $resultLayoutID = $this->layoutViewModel->getLayoutIdLookup("home", "global", -1);
        $resultFileLayoutID = $this->layoutViewModel->getLayoutIdLookup("home", "category", 1);

        $this->assertEquals($layoutID, $resultLayoutID);
        $this->assertEquals($layoutID, $resultFileLayoutID);
    }

    /**
     * Test LayoutView model getViewLayout method
     */
    public function testInsertMultipleViewsAndThrowException()
    {
        $layout = ["layoutID" => 1, "layoutViewType" => "home", "name" => "Home Test", "layout" => "test"];
        $layoutID = $this->layoutModel->insert($layout);

        $layoutViews = [["recordID" => 1, "recordType" => "global"]];
        $this->expectException(ClientException::class);
        $this->layoutViewModel->saveLayoutViews($layoutViews, "home", $layoutID);
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

    /**
     * Test correcting the default layoutView row.
     */
    public function testCorrectDefaultView(): void
    {
        $this->resetTable("layoutView");

        $database = Gdn::database();

        // Insert the old (bad) default row.
        $database->sql()->insert("layoutView", [
            "layoutViewID" => 1,
            "layoutID" => 1,
            "recordID" => 0,
            "recordType" => "global",
            "layoutViewType" => "global",
            "insertUserID" => 2,
            "dateInserted" => date("Y-m-d H:i:s"),
            "updateUserID" => 2,
            "dateUpdated" => date("Y-m-d H:i:s"),
        ]);

        // Run structure
        LayoutViewModel::structure($database);

        // The default row's layoutID should now be "home"
        $viewRow = $this->layoutViewModel->selectSingle(["layoutViewID" => 1]);
        $this->assertSame("home", $viewRow["layoutID"]);
    }
}
