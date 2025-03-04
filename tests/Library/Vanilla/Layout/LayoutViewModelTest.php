<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Layout;

use Garden\Web\Exception\ClientException;
use Gdn;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\LayoutModel;
use Vanilla\Layout\LayoutViewModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestCategoryModelTrait;
use VanillaTests\SiteTestCase;

/**
 * Unit test for LayoutViewModel
 */
class LayoutViewModelTest extends SiteTestCase
{
    use TestCategoryModelTrait;
    use CommunityApiTestTrait;

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
    public function testInsertMultipleViewsQuery()
    {
        $layout = ["layoutID" => 1, "layoutViewType" => "home", "name" => "Home Test", "layout" => "test"];
        $layoutID1 = $this->layoutModel->insert($layout);
        $layout = [
            "layoutID" => 2,
            "layoutViewType" => "discussionCategoryPage",
            "name" => "Discussion Category Page Test",
            "layout" => "test",
        ];
        $layoutID2 = $this->layoutModel->insert($layout);

        $layoutViews = [["recordID" => -1, "recordType" => "global"]];
        $this->layoutViewModel->saveLayoutViews($layoutViews, "home", $layoutID1);
        $this->layoutViewModel->saveLayoutViews(
            [["recordID" => 1, "recordType" => "category"]],
            "discussionCategoryPage",
            $layoutID2
        );

        [$resultLayoutID] = $this->layoutViewModel->queryLayout(new LayoutQuery("home", "global", -1));
        [$resultFileLayoutID] = $this->layoutViewModel->queryLayout(
            new LayoutQuery("discussionCategoryPage", "category", 1)
        );
        [$resultFileLayoutID2] = $this->layoutViewModel->queryLayout(new LayoutQuery("categoryList", "category", 1));

        $this->assertEquals($layoutID1, $resultLayoutID);
        $this->assertEquals($layoutID2, $resultFileLayoutID);
        $this->assertEquals($layoutID2, $resultFileLayoutID2);
    }

    /**
     * Test LayoutView model getViewLayout method with NestedCategorylist
     */
    public function testNestedCategoryQuery()
    {
        $nestedCategory = $this->createCategory(["displayAs" => strtolower(\CategoryModel::DISPLAY_NESTED)]);
        $headingCategory = $this->createCategory(["displayAs" => strtolower(\CategoryModel::DISPLAY_HEADING)]);
        $layout = [
            "layoutID" => 2,
            "layoutViewType" => "nestedCategoryList",
            "name" => "Discussion Category Page Test",
            "layout" => "test",
        ];
        $nestedLayoutID = $this->layoutModel->insert($layout);

        $this->layoutViewModel->saveLayoutViews(
            [["recordID" => $nestedCategory["categoryID"], "recordType" => "category"]],
            "nestedCategoryList",
            $nestedLayoutID
        );
        //Test Default
        [$resultFileLayoutID] = $this->layoutViewModel->queryLayout(new LayoutQuery("categoryList", "category", 1));

        $this->assertEquals("discussionCategoryPage", $resultFileLayoutID);

        [$resultFileLayoutID] = $this->layoutViewModel->queryLayout(
            new LayoutQuery("categoryList", "category", $nestedCategory["categoryID"])
        );

        $this->assertEquals($nestedLayoutID, $resultFileLayoutID);

        $this->expectExceptionMessage("Heading categories cannot be viewed directly.");
        [$resultFileLayoutID] = $this->layoutViewModel->queryLayout(
            new LayoutQuery("categoryList", "category", $headingCategory["categoryID"])
        );
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

        $this->assertSame($idFile, $resultFile["layoutViewID"]);
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

        // The old row should be removed.
        $viewRows = $this->layoutViewModel->select();
        $this->assertCount(0, $viewRows);

        $this->assertLayoutQueryID("home", new LayoutQuery("home", "global", 0));
    }

    /**
     * Test lookups of discussion thread layouts.
     */
    public function testResolveDiscussionLayoutView()
    {
        $cat1 = $this->createCategory();
        $disc1 = $this->createDiscussion();
        $cat2 = $this->createCategory();
        $disc2 = $this->createDiscussion();

        $categoryLayoutID = $this->layoutModel->insert([
            "layoutViewType" => "discussion",
            "name" => "discussionTest",
            "layout" => "test",
        ]);
        $this->layoutViewModel->insert([
            "layoutID" => $categoryLayoutID,
            "recordID" => $cat1["categoryID"],
            "recordType" => "category",
            "layoutViewType" => "discussion",
        ]);

        $this->assertLayoutQueryID(
            "discussion",
            new LayoutQuery("post", "discussion", $disc2["discussionID"]),
            "Failed to lookup template fallback"
        );

        $this->assertLayoutQueryID(
            $categoryLayoutID,
            new LayoutQuery("post", "discussion", $disc1["discussionID"]),
            "Failed category discussion lookup"
        );

        $globalLayoutID = $this->layoutModel->insert([
            "layoutViewType" => "discussion",
            "name" => "discussionTest",
            "layout" => "test",
        ]);
        $this->layoutViewModel->insert([
            "layoutID" => $globalLayoutID,
            "recordID" => 1,
            "recordType" => "global",
            "layoutViewType" => "discussion",
        ]);
    }

    /**
     * Utility to assert the layoutID returned by a query.
     *
     * @param $expectedID
     * @param LayoutQuery $query
     * @param string|null $message
     */
    private function assertLayoutQueryID($expectedID, LayoutQuery $query, ?string $message = ""): void
    {
        [$actual] = $this->layoutViewModel->queryLayout($query);
        $this->assertEquals($expectedID, $actual, $message);
    }
}
