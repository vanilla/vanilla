<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Layout;

use Gdn;
use Vanilla\Layout\Asset\LayoutFormAsset;
use Vanilla\Layout\LayoutModel;
use Vanilla\Layout\LayoutPage;
use Vanilla\Layout\LayoutViewModel;
use Vanilla\Layout\Providers\FileBasedLayoutProvider;
use Vanilla\Layout\View\HomeLayoutView;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\Library\Garden\ClassLocatorTest;
use VanillaTests\SiteTestTrait;

/**
 * Unit test for LayoutModel
 */
class LayoutPageTest extends BootstrapTestCase
{
    use LayoutTestTrait;
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
        $fileBasedLayoutProvider = $this->container()->get(FileBasedLayoutProvider::class);
        $fileBasedLayoutProvider->setCacheBasePath(PATH_TEST_CACHE);
        $this->resetTable("layout");
        $this->resetTable("layoutView");
        $this->layoutViewModel = $this->container()->get(LayoutViewModel::class);
        $this->layoutModel = $this->container()->get(LayoutModel::class);
    }

    /**
     * Test Layout model getByID method
     * Test that hydrateLayout layout inputs hydrate into specific outputs.
     *
     */
    public function testPreloadLayout()
    {
        $layoutPage = $this->container()->get(LayoutPage::class);
        $page = $layoutPage->preloadLayout(new LayoutFormAsset("home")); //, 'home', 1, $params);

        $this->assertSame("Home - LayoutPageTest", $page->getSeoTitle());
        $this->assertSame("", $page->getSeoDescription());
    }

    /**
     * Test Layout model getByID method
     * Test that hydrateLayout layout inputs hydrate into specific outputs.
     *
     * @param array $input The input.
     *
     * @dataProvider provideLayoutHydratesTo
     */
    public function testPreloadLayoutWithData(array $input)
    {
        $layout = ["layoutID" => 1, "layoutViewType" => "home", "name" => "Home Test", "layout" => $input];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = ["layoutID" => $layoutID, "recordID" => 1, "recordType" => "home", "layoutViewType" => "home"];
        $this->layoutViewModel->insert($layoutView);

        $layoutPage = $this->container()->get(LayoutPage::class);
        $page = $layoutPage->preloadLayout(new LayoutFormAsset("home", "home", 1, []));

        $this->assertSame("Home - LayoutPageTest", $page->getSeoTitle());
        $this->assertSame("", $page->getSeoDescription());
    }

    /**
     * @return iterable
     */
    public function provideLayoutHydratesTo(): iterable
    {
        $breadcrumbDefinition = [
            '$hydrate' => "react.asset.breadcrumbs",

            /// Invalid value here.
            "recordType" => [],
        ];

        yield "Exceptions propagate up to the nearest react node" => [
            [
                [
                    [
                        '$hydrate' => "react.section.1-column",
                        "children" => [$breadcrumbDefinition],
                    ],
                ],
            ],
        ];

        yield "Component with null props is removed" => [
            [
                [
                    '$hydrate' => "react.section.1-column",
                    "children" => [
                        [
                            '$hydrate' => "react.asset.breadcrumbs",
                            // When we don't have a recordID, breadcrumbs don't render.
                            "recordID" => null,
                            "includeHomeCrumb" => false,
                        ],
                    ],
                ],
            ],
        ];

        yield "Success hydration" => [
            [
                [
                    '$hydrate' => "react.section.1-column",
                    "children" => [
                        [
                            // Assets should be available.
                            '$hydrate' => "react.asset.breadcrumbs",
                            "recordType" => "category",
                            "recordID" => 1,
                        ],
                    ],
                ],
            ],
        ];
    }
}
