<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Layout;

use Gdn;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\LayoutModel;
use Vanilla\Layout\LayoutPage;
use Vanilla\Layout\LayoutViewModel;
use Vanilla\Layout\Providers\FileBasedLayoutProvider;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;

/**
 * Unit test for LayoutModel
 */
class LayoutPageTest extends SiteTestCase
{
    use LayoutTestTrait;
    use CommunityApiTestTrait;

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
        $page = $layoutPage->preloadLayout(new LayoutQuery("home")); //, 'home', 1, $params);

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
        $page = $layoutPage->preloadLayout(new LayoutQuery("home", "global", 1, []));

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

    /**
     * Test that the proper seo data is included in custom layouts.
     *
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider provideTestSeoLayoutData
     */
    public function testLayoutSeoTags($input, $expected): void
    {
        static $layoutID = 2;

        $layout = [
            "layoutID" => $layoutID,
            "layoutViewType" => $input["layoutViewType"],
            "name" => "Test",
            "layout" => $input["layout"],
        ];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = [
            "layoutID" => $layoutID,
            "recordID" => $input["recordID"],
            "recordType" => $input["recordType"],
            "layoutViewType" => $input["layoutViewType"],
        ];
        $this->layoutViewModel->insert($layoutView);

        $layoutPage = $this->container()->get(LayoutPage::class);
        $page = $layoutPage->preloadLayout(
            new LayoutQuery($input["layoutViewType"], $input["recordType"], $input["recordID"], [])
        );
        $this->assertSame($expected["title"], $page->getSeoTitle());
        $this->assertSame($expected["description"], $page->getSeoDescription());
        $this->assertSame($expected["canonicalUrl"], $page->getCanonicalUrl());
        $jsonLD = $page->getJsonLdItems();
        $crumbs = array_column($jsonLD["@graph"][0]["itemListElement"], "name");
        foreach ($crumbs as $crumb) {
            $this->assertTrue(in_array($crumb, $expected["breadcrumbs"]));
        }
        $layoutID++;
    }

    /**
     * Provide data for testLayoutSeoTags.
     *
     * @return array[]
     */
    public function provideTestSeoLayoutData(): array
    {
        $siteUrl = "https://vanilla.test/layoutpagetest";

        $r = [
            "Home Layout View" => [
                [
                    "layoutViewType" => "home",
                    "layout" => [
                        [
                            '$hydrate' => "react.section.1-column",
                            "children" => [],
                        ],
                    ],
                    "recordType" => "global",
                    "recordID" => -1,
                ],
                [
                    "title" => "Home - LayoutPageTest",
                    "description" => "",
                    "canonicalUrl" => $siteUrl,
                    "breadcrumbs" => ["Home"],
                ],
            ],
            "CategoryList Layout View" => [
                [
                    "layoutViewType" => "categoryList",
                    "layout" => [
                        [
                            '$hydrate' => "react.section.1-column",
                            "children" => [],
                        ],
                    ],
                    "recordType" => "global",
                    "recordID" => -1,
                ],
                [
                    "title" => "Categories - LayoutPageTest",
                    "description" => "",
                    "canonicalUrl" => $siteUrl . "/categories",
                    "breadcrumbs" => ["Home", "Categories"],
                ],
            ],
            "DiscussionList Layout View" => [
                [
                    "layoutViewType" => "discussionList",
                    "layout" => [
                        [
                            '$hydrate' => "react.section.1-column",
                            "children" => [],
                        ],
                    ],
                    "recordType" => "global",
                    "recordID" => -1,
                ],
                [
                    "title" => "Discussions - LayoutPageTest",
                    "description" => "",
                    "canonicalUrl" => $siteUrl . "/discussions",
                    "breadcrumbs" => ["Home", "Discussions"],
                ],
            ],
        ];

        return $r;
    }

    /**
     * Test that the proper seo data is included in the CategoryDiscussionPage layout view.
     *
     * @return void
     */
    public function testCategoryDiscussionLayoutSeoTags(): void
    {
        $category = $this->createCategory();
        $layout = [
            "layoutID" => 1,
            "layoutViewType" => "discussionCategoryPage",
            "name" => "TestDiscussionCategoryPage",
            "layout" => [
                [
                    '$hydrate' => "react.section.1-column",
                    "children" => [],
                ],
            ],
        ];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = [
            "layoutID" => $layoutID,
            "recordID" => -1,
            "recordType" => "global",
            "layoutViewType" => "discussionCategoryPage",
        ];

        $this->layoutViewModel->insert($layoutView);

        $layoutPage = $this->container()->get(LayoutPage::class);
        $page = $layoutPage->preloadLayout(
            new LayoutQuery("discussionCategoryPage", "global", -1, [
                "categoryID" => $category["categoryID"],
            ])
        );
        $this->assertSame("{$category["name"]} - LayoutPageTest", $page->getSeoTitle());
        $this->assertSame($category["description"], $page->getSeoDescription());
        $this->assertSame($category["url"], $page->getCanonicalUrl());
        $jsonLD = $page->getJsonLdItems();
        $crumbs = array_column($jsonLD["@graph"][0]["itemListElement"], "name");
        foreach ($crumbs as $crumb) {
            $this->assertTrue(in_array($crumb, ["Home", $category["name"]]));
        }
    }

    /**
     * Test that the proper seo data is included in the NestedCategoryList layout view.
     *
     * @return void
     */
    public function testNestedCategoryListSeoTags(): void
    {
        $category = $this->createCategory(["displayAs" => strtolower(\CategoryModel::DISPLAY_NESTED)]);
        $layout = [
            "layoutID" => 1,
            "layoutViewType" => "nestedCategoryList",
            "name" => "TestNestedCategoryPage",
            "layout" => [
                [
                    '$hydrate' => "react.section.1-column",
                    "children" => [],
                ],
            ],
        ];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = [
            "layoutID" => $layoutID,
            "recordID" => -1,
            "recordType" => "global",
            "layoutViewType" => "nestedCategoryList",
        ];

        $this->layoutViewModel->insert($layoutView);

        $layoutPage = $this->container()->get(LayoutPage::class);
        $page = $layoutPage->preloadLayout(
            new LayoutQuery("discussionCategoryPage", "global", -1, [
                "categoryID" => $category["categoryID"],
            ])
        );
        $this->assertSame("{$category["name"]} - LayoutPageTest", $page->getSeoTitle());
        $this->assertSame($category["description"], $page->getSeoDescription());
        $this->assertSame($category["url"], $page->getCanonicalUrl());
        $jsonLD = $page->getJsonLdItems();
        $crumbs = array_column($jsonLD["@graph"][0]["itemListElement"], "name");
        foreach ($crumbs as $crumb) {
            $this->assertTrue(in_array($crumb, ["Home", $category["name"]]));
        }
    }

    /**
     * Test that the proper seo data is included in the DiscussionThread layout view.
     *
     * @return void
     */
    public function testDiscussionThreadLayoutViewSeoTags(): void
    {
        $category = $this->createCategory();
        $discussion = $this->createDiscussion(["categoryID" => $category["categoryID"]]);
        $layout = [
            "layoutID" => 1,
            "layoutViewType" => "discussionThread",
            "name" => "TestDiscussionThreadPage",
            "layout" => [
                [
                    '$hydrate' => "react.section.1-column",
                    "children" => [],
                ],
            ],
        ];
        $layoutID = $this->layoutModel->insert($layout);
        $layoutView = [
            "layoutID" => $layoutID,
            "recordID" => -1,
            "recordType" => "global",
            "layoutViewType" => "discussionThread",
        ];

        $this->layoutViewModel->insert($layoutView);

        $layoutPage = $this->container()->get(LayoutPage::class);
        $page = $layoutPage->preloadLayout(
            new LayoutQuery("discussionThread", "global", -1, ["discussionID" => $discussion["discussionID"]])
        );
        $this->assertSame("{$discussion["name"]} - LayoutPageTest", $page->getSeoTitle());
        $this->assertSame(Gdn::formatService()->renderExcerpt($discussion["body"], "html"), $page->getSeoDescription());
        $this->assertSame($discussion["canonicalUrl"] . "/p1", $page->getCanonicalUrl());
        $jsonLD = $page->getJsonLdItems();
        $crumbs = array_column($jsonLD["@graph"][0]["itemListElement"], "name");
        foreach ($crumbs as $crumb) {
            $this->assertTrue(in_array($crumb, ["Home", $category["name"], $discussion["name"]]));
        }
    }
}
