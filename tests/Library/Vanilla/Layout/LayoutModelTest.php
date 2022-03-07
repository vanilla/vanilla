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
 * Unit test for LayoutModel
 */
class LayoutModelTest extends ClassLocatorTest {

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
     * Test model layout model normalize Rows.
     *
     * @throws \Exception Throws exception when something goes wrong.
     */
    public function testNormalizeRows() {
        $layout = ['layoutID' => 1, 'layoutViewType' => 'home', 'name' => 'Home Test', 'layout' => 'test'];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = ['layoutID' => $layoutID, 'recordID' => 1, 'recordType' => 'global', 'layoutViewType' => 'home'];
        $this->layoutViewModel->insert($layoutView);
        $layoutView = ['layoutID' => $layoutID, 'recordID' => 2, 'recordType' => 'global', 'layoutViewType' => 'home'];
        $this->layoutViewModel->insert($layoutView);
        $rows = $this->layoutModel->getAll();
        $result = $this->layoutModel->normalizeRows($rows, ['layoutViews']);

        $this->assertSame(1, count($result));
        $this->assertSame(2, count($result[0]['layoutViews']));
    }

    /**
    * Test Layout model getByID method
    */
    public function testGetLayout() {
        $layout = ['layoutID' => 1, 'layoutViewType' => 'home', 'name' => 'Home Test', 'layout' => 'test'];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = ['layoutID' => $layoutID, 'recordID' => 1, 'recordType' => 'home', 'layoutViewType' => 'home'];
        $this->layoutViewModel->insert($layoutView);

        $result = $this->layoutModel->getByID($layoutID);

        $this->assertSame($layoutID, $result['layoutID']);
    }
}
