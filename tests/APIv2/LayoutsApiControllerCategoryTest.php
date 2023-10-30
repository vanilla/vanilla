<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use CategoryModel;
use Garden\Container\Container;
use Garden\Http\HttpResponse;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Widgets\DiscussionListAsset;
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Layout\LayoutViewModel;
use Vanilla\Layout\Providers\FileBasedLayoutProvider;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Fixtures\IterableArray;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Layout\LayoutTestTrait;

/**
 * Tests for api/v2/layouts endpoints
 */
class LayoutsApiControllerCategoryTest extends AbstractResourceTest
{
    use CommunityApiTestTrait;
    use ExpectExceptionTrait;
    use LayoutTestTrait;

    /* @var LayoutViewModel */
    private $layoutViewModel;

    //region Properties
    protected $testPagingOnIndex = false;

    /** {@inheritdoc} */
    protected $baseUrl = "/layouts";

    /** {@inheritdoc} */
    protected $pk = "layoutID";

    /** {@inheritdoc} */
    protected $editFields = ["name", "layout"];

    /** {@inheritdoc} */
    protected $patchFields = ["name", "layout"];

    /** {@inheritdoc} */
    protected $record = [
        "name" => "Layout",
        "layout" => [["foo" => "bar"], ["fizz" => "buzz", "flip" => ["flap", "flop"], "drip" => ["drop" => "derp"]]],
        "layoutViewType" => "categoryList",
    ];

    /** local version of the record */
    protected $localRecord = [
        "name" => "Layout",
        "layout" => [["foo" => "bar"], ["fizz" => "buzz", "flip" => ["flap", "flop"], "drip" => ["drop" => "derp"]]],
    ];

    /** @var CategoryModel */
    protected $categoryModel;

    /** @var array */
    protected $categoryList;

    //endregion

    //region Setup / Teardown

    /**
     * @inheritDoc
     */
    public static function configureContainerBeforeStartup(Container $container)
    {
        // Kludge into the breadcrumb asset which is used in the tests.
        $container->rule(LayoutHydrator::class)->addCall("addReactResolver", [DiscussionListAsset::class]);
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        Gdn::sql()->truncate("layout");
        Gdn::sql()->truncate("layoutView");
        Gdn::sql()->truncate("Category");
        $category = $this->createCategory(["name" => "My Category", "displayAs" => "categories"]);
        $this->categoryList = ["Category" => $category["categoryID"]];
        $category2 = $this->createCategory([
            "name" => "Child1",
            "displayAs" => "discussions",
            "parentCategoryID" => $category["categoryID"],
        ]);
        $this->categoryList["categoryDisc1"] = $category2["categoryID"];
        $category3 = $this->createCategory([
            "name" => "Child2",
            "displayAs" => "discussions",
            "parentCategoryID" => $category["categoryID"],
        ]);
        $this->categoryList["categoryDisc2"] = $category3["categoryID"];
        $flat = $this->createCategory(["name" => "My Flat Category", "displayAs" => "flat"]);
        $this->categoryList["flat"] = $flat["categoryID"];
        $flat2 = $this->createCategory([
            "name" => "flat Child1",
            "displayAs" => "discussions",
            "parentCategoryID" => $flat["categoryID"],
        ]);
        $this->categoryList["flatDisc1"] = $flat2["categoryID"];
        $flat3 = $this->createCategory([
            "name" => "flat Child2",
            "displayAs" => "discussions",
            "parentCategoryID" => $flat["categoryID"],
        ]);
        $this->categoryList["flatDisc2"] = $flat2["categoryID"];
        $heading = $this->createCategory([
            "name" => "Heading",
            "displayAs" => "heading",
            "parentCategoryID" => $flat["categoryID"],
        ]);
        $this->categoryList["heading"] = $heading["categoryID"];

        $fileBasedLayoutProvider = $this->container()->get(FileBasedLayoutProvider::class);
        $fileBasedLayoutProvider->setCacheBasePath(PATH_TEST_CACHE);

        $this->categoryModel = $this->container()->get(CategoryModel::class);
        $this->layoutViewModel = $this->container()->get(LayoutViewModel::class);
    }
    //endregion

    //region Test Methods / Data Providers
    /**
     * Test that GET /api/v2/layouts/:id returns the expected static layout definition
     *
     * @param string $id
     * @dataProvider staticLayoutDataProvider
     */
    public function testGetStaticLayout(string $id): void
    {
        $response = $this->api()->get("{$this->baseUrl}/{$id}");
        $this->assertTrue($response->isSuccessful());
        $layout = $response->getBody();
        $this->assertEquals($id, $layout["layoutID"]);
        $this->assertTrue($layout["isDefault"]);
    }

    /**
     * Test that GET /api/v2/layouts includes the static layout definition provided.
     *
     * @param string $id
     * @dataProvider staticLayoutDataProvider
     */
    public function testIndexIncludesStaticLayout(string $id): void
    {
        $response = $this->api()->get("{$this->baseUrl}");
        $this->assertTrue($response->isSuccessful());
        $layouts = $response->getBody();
        $this->assertIsArray($layouts);
        /** @var array $layouts */
        $this->assertGreaterThanOrEqual(1, count($layouts));
        $this->assertCount(
            1,
            array_filter($layouts, function (array $layout) use ($id) {
                return $layout["layoutID"] === $id;
            })
        );
    }

    /**
     * Data Provider for tests retrieving static layouts
     *
     * @return iterable
     */
    public function staticLayoutDataProvider(): iterable
    {
        yield "categoryList" => ["categoryList"];
        yield "nestedCategoryList" => ["nestedCategoryList"];
        yield "discussionCategoryPage" => ["discussionCategoryPage"];
    }

    /**
     * Test that we can use dynamic hydrate lookup.
     *
     * @return void
     */
    public function testLookupHydrateCategoryList()
    {
        $layoutDefinition = [
            [
                '$hydrate' => "react.section.1-column",
                "children" => [
                    [
                        // Assets should be available.
                        '$hydrate' => "react.asset.categoryList",
                        "apiParams" => [
                            "sort" => "-dateLastComment",
                        ],
                    ],
                ],
            ],
        ];

        $expected = $this->getExpectedCategoryListLayout();

        $params = [
            "categoryID" => $this->categoryList["Category"],
        ];

        // Posting to the main hydrate endpoints will have the correct result.
        $response = $this->api()->post("/layouts/hydrate", [
            "layout" => $layoutDefinition,
            "params" => $params,
            "layoutViewType" => "categoryList",
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $layout = $this->getCategoryListLayoutMinusCategories($response->getBody()["layout"]);
        $this->assertSame($expected, $layout);

        // We can save it as a layout and render it by the ID.

        $layout = $this->api()->post("/layouts", [
            "name" => "My Layout",
            "layout" => $layoutDefinition,
            "layoutViewType" => "categoryList",
        ]);

        $response = $this->api()->put("/layouts/{$layout["layoutID"]}/views", [
            [
                "recordID" => -1,
                "recordType" => "global",
            ],
        ]);

        $response = $this->api()->get("/layouts/lookup-hydrate", [
            "layoutViewType" => "categoryList",
            "recordID" => -1,
            "recordType" => "global",
            "params" => $params,
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $layout = $this->getCategoryListLayoutMinusCategories($response->getBody()["layout"]);
        $this->assertSame($expected, $layout);
    }

    /**
     * Test that we can use dynamic hydrate lookup assets, nestedCategoryList.
     *
     * @return void
     */
    public function testNestedCategoryListLookupHydrateAsset()
    {
        $layoutDefinition = [
            [
                '$hydrate' => "react.section.1-column",
                "children" => [
                    [
                        // Assets should be available.
                        '$hydrate' => "react.asset.categoryList",
                        "apiParams" => [
                            "sort" => "-dateLastComment",
                        ],
                    ],
                ],
            ],
        ];

        $params = [
            "categoryID" => $this->categoryList["Category"],
        ];
        // We can save it as a layout and render it by the ID.

        $layout = $this->api()->post("/layouts", [
            "name" => "My Layout",
            "layout" => $layoutDefinition,
            "layoutViewType" => "nestedCategoryList",
        ]);

        $response = $this->api()->put("/layouts/{$layout["layoutID"]}/views", [
            [
                "recordID" => $this->categoryList["Category"],
                "recordType" => "category",
            ],
        ]);

        $response = $this->api()->get("/layouts/lookup-hydrate-assets", [
            "layoutViewType" => "categoryList",
            "recordID" => $this->categoryList["Category"],
            "recordType" => "category",
            "params" => $params,
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertArrayHasKey("js", $body);
        $this->assertArrayHasKey("css", $body);
    }

    /**
     * Test that we can use dynamic hydrate lookup assets, nestedCategoryList.
     *
     * @return void
     */
    public function testDiscussionCategoryListLookupHydrateAsset()
    {
        $layoutDefinition = [
            [
                '$hydrate' => "react.section.1-column",
                "children" => [
                    [
                        // Assets should be available.
                        '$hydrate' => "react.asset.categoryList",
                        "apiParams" => [
                            "sort" => "-dateLastComment",
                        ],
                    ],
                ],
            ],
        ];

        $params = [
            "categoryID" => $this->categoryList["Category"],
        ];
        // We can save it as a layout and render it by the ID.

        $layout = $this->api()->post("/layouts", [
            "name" => "My Layout",
            "layout" => $layoutDefinition,
            "layoutViewType" => "discussionCategoryPage",
        ]);

        $response = $this->api()->put("/layouts/{$layout["layoutID"]}/views", [
            [
                "recordID" => $this->categoryList["Category"],
                "recordType" => "category",
            ],
        ]);

        $response = $this->api()->get("/layouts/lookup-hydrate-assets", [
            "layoutViewType" => "categoryList",
            "recordID" => $this->categoryList["Category"],
            "recordType" => "category",
            "params" => $params,
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertArrayHasKey("js", $body);
        $this->assertArrayHasKey("css", $body);
    }

    /**
     * Test the lifecycle of the feature flag configuration.
     * @dataProvider staticLayoutDataProvider
     *
     * @param string $flagName
     */
    public function testFeatureFlagConfigLifecycle(string $flagName)
    {
        $feature = "Feature.customLayout.{$flagName}.Enabled";
        $this->runWithConfig([$feature => false], function () use ($feature, $flagName) {
            $layout = $this->testPost($this->localRecord, ["layoutViewType" => $flagName]);
            $this->api()->put("/layouts/{$layout["layoutID"]}/views", [
                [
                    "recordID" => -1,
                    "recordType" => "global",
                ],
            ]);
            // Was enabled automatically.
            $this->assertConfigValue($feature, true);
            $this->api()->put("/layouts/views-legacy", [
                "layoutViewType" => $flagName,
                "legacyViewValue" => "foundation",
            ]);
            $this->assertConfigValue($feature, false);
            // Our views were deleted.
            $views = $this->api()
                ->get("/layouts/{$layout["layoutID"]}/views")
                ->getBody();
            $this->assertEmpty($views);
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyGetExpected(array $row): array
    {
        // GET endpoint doesn't return 'layout' property
        return array_diff_key($row, ["layout" => 0]);
    }
}
