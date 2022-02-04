<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
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
class LayoutViewModelTest extends ClassLocatorTest {

    /**
     * @var LayoutViewModel
     */
    private $layoutViewModel;
    private $layoutModel;

    /**
     * Get a new model for each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->container()->call(function (
            \Gdn_DatabaseStructure $st,
            \Gdn_SQLDriver $sql
        ) {
            $Database = Gdn::database();
            if (!$st->tableExists("layout")) {
                LayoutModel::structure($Database);
            }
            if (!$st->tableExists("layoutView")) {
                LayoutViewModel::structure($Database);
            }
        });

        $this->resetTable('layout');
        $this->resetTable('layoutView');
        $this->layoutViewModel = $this->container()->get(LayoutViewModel::class);
        $this->layoutModel = $this->container()->get(LayoutModel::class);
    }

    /**
    * Test LayoutView model getViewLayout method
    */
    public function testGetViewLayout() {
        $layout = ['layoutID' => 1, 'layoutViewType' => 'home', 'name' => 'Home Test', 'layout' => 'test'];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = ['layoutID' => $layoutID, 'recordID' => 1, 'recordType' => 'home'];
        $id = $this->layoutViewModel->insert($layoutView);

        $results = $this->layoutViewModel->getViewsByLayoutID(1);

        $this->assertSame(1, count($results));
        $result = $results[0];
        $this->assertSame($id, $result['layoutViewID']);
        $this->assertSame($layoutView['layoutID'], $result['layoutID']);
    }

    /**
     * Test LayoutView model getViewLayout method
     */
    public function testGetLayoutView() {
        $layout = ['layoutID' => 1, 'layoutViewType' => 'home', 'name' => 'Home Test', 'layout' => 'test'];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = ['layoutID' => $layoutID, 'recordID' => 1, 'recordType' => 'home'];
        $id = $this->layoutViewModel->insert($layoutView);

        $result = $this->layoutViewModel->getLayoutViews('home', 'home', 1);

        $this->assertSame($id, $result['layoutViewID']);
        $this->assertSame($layoutView['layoutID'], $result['layoutID']);
    }
}
