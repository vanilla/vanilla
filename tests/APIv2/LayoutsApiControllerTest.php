<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use CategoryModel;
use DiscussionModel;
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
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Layout\LayoutTestTrait;

/**
 * Tests for api/v2/layouts endpoints
 */
class LayoutsApiControllerTest extends AbstractResourceTest
{
    public static $addons = ["stubcontent"];

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
        "layoutViewType" => "home",
    ];

    /** @var CategoryModel */
    protected $categoryModel;

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
        yield "home" => ["home"];
    }

    /**
     * Test that the GET /api/v2/layouts/:id endpoint throws ClientException on invalid ID formats
     *
     * @param mixed $id
     * @dataProvider getThrowsClientExceptionDataProvider
     */
    public function testGetThrowsClientException($id): void
    {
        $this->expectException(ClientException::class);
        $_ = $this->api()->get("{$this->baseUrl}/{$id}");
    }

    /**
     * @return iterable
     */
    public function getThrowsClientExceptionDataProvider(): iterable
    {
        yield "floating point number" => [pi()];
        yield "boolean" => [false];
    }

    /**
     * Test that GET /api/v2/layouts/:id endpoint throws NotFoundException when endpoint not found
     *
     * @param mixed $id
     * @dataProvider idempotentThrowsNotFoundExceptionDataProvider
     */
    public function testGetThrowsNotFoundException($id): void
    {
        $this->expectException(NotFoundException::class);
        $_ = $this->api()->get("{$this->baseUrl}/{$id}");
    }

    /**
     * Test that the DELETE /api/v2/layouts/:id endpoint throws ClientException on invalid ID formats
     *
     * @param mixed $id
     * @dataProvider mutableEndpointThrowsClientExceptionOnInvalidIdFormatDataProvider
     */
    public function testDeleteThrowsClientException($id): void
    {
        $this->expectException(ClientException::class);
        $_ = $this->api()->delete("{$this->baseUrl}/{$id}");
    }

    /**
     * Test that the DELETE /api/v2/layouts/:id endpoint throws NotFoundException when ID provided
     * doesn't correspond to a layout definition.
     *
     * @param mixed $id
     * @dataProvider nonIdempotentThrowsNotFoundExceptionDataProvider
     */
    public function testDeleteThrowsNotFoundException($id): void
    {
        $this->expectException(NotFoundException::class);
        $_ = $this->api()->delete("{$this->baseUrl}/{$id}");
    }

    /**
     * Test that the PATCH /api/v2/layouts/:id endpoint throws ClientException on invalid ID formats
     *
     * @param mixed $id
     * @dataProvider mutableEndpointThrowsClientExceptionOnInvalidIdFormatDataProvider
     */
    public function testPatchThrowsClientException($id): void
    {
        $this->expectException(ClientException::class);
        $body = ["name" => "Norman"];
        $_ = $this->api()->patch("{$this->baseUrl}/{$id}", $body);
    }

    /**
     * Test that the PATCH /api/v2/layouts/:id endpoint throws NotFoundException when ID provided
     * doesn't correspond to a layout definition.
     *
     * @param mixed $id
     * @dataProvider nonIdempotentThrowsNotFoundExceptionDataProvider
     */
    public function testPatchThrowsNotFoundException($id): void
    {
        $this->expectException(NotFoundException::class);
        $body = ["name" => "Norman"];
        $_ = $this->api()->patch("{$this->baseUrl}/{$id}", $body);
    }

    /**
     * Test that the GET /api/v2/layouts/:id/edit endpoint throws ClientException on invalid ID formats
     *
     * @param mixed $id
     * @dataProvider mutableEndpointThrowsClientExceptionOnInvalidIdFormatDataProvider
     */
    public function testGetEditThrowsClientException($id): void
    {
        $this->expectException(ClientException::class);
        $_ = $this->api()->get("{$this->baseUrl}/{$id}/edit");
    }

    /**
     * Test that the GET /api/v2/layouts/:id/edit endpoint throws NotFoundException when ID provided
     * doesn't correspond to a layout definition.
     *
     * @param mixed $id
     * @dataProvider nonIdempotentThrowsNotFoundExceptionDataProvider
     */
    public function testGetEditThrowsNotFoundException($id): void
    {
        $this->expectException(NotFoundException::class);
        $_ = $this->api()->get("{$this->baseUrl}/{$id}/edit");
    }

    /**
     * @return iterable
     */
    public function idempotentThrowsNotFoundExceptionDataProvider(): iterable
    {
        yield "mutable not found" => [987654321];
        yield "immutable not found" => ["Norman"];
    }

    /**
     * @return iterable
     */
    public function nonIdempotentThrowsNotFoundExceptionDataProvider(): iterable
    {
        yield "mutable not found" => [987654321];
    }

    /**
     * @return iterable
     */
    public function mutableEndpointThrowsClientExceptionOnInvalidIdFormatDataProvider(): iterable
    {
        yield "floating point number" => [pi()];
        yield "boolean" => [false];
    }

    /**
     * Test that we can hydrate arbitrary and saved layouts.
     *
     * @return void
     */
    public function testHydrate()
    {
        $category = $this->createCategory(["name" => "My Category"]);

        $layoutDefinition = [
            [
                '$hydrate' => "react.section.1-column",
                "children" => [
                    [
                        // Assets should be available.
                        '$hydrate' => "react.asset.discussionList",
                        "apiParams" => [
                            "sort" => "-dateLastComment",
                        ],
                    ],
                ],
            ],
        ];

        $expected = $this->getExpectedDiscussionListLayout();

        $params = [
            "categoryID" => $category["categoryID"],
        ];

        // Posting to the main hydrate endpoints will have the correct result.
        $response = $this->api()->post("/layouts/hydrate", [
            "layout" => $layoutDefinition,
            "params" => $params,
            "layoutViewType" => "home",
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $layout = $this->getDiscussionListLayoutMinusDiscussions($response->getBody()["layout"]);
        $this->assertSame($expected, $layout);

        // We can save it as a layout and render it by the ID.

        $layout = $this->api()->post("/layouts", [
            "name" => "My Layout",
            "layout" => $layoutDefinition,
            "layoutViewType" => "home",
        ]);
        $response = $this->api()->get("/layouts/{$layout["layoutID"]}/hydrate", [
            "params" => $params,
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $layout = $this->getDiscussionListLayoutMinusDiscussions($response->getBody()["layout"]);
        $this->assertSame($expected, $layout);
    }

    /**
     * Test that we can use dynamic hydrate lookup.
     *
     * @return void
     */
    public function testLookupHydrate()
    {
        $category = $this->createCategory(["name" => "My Category"]);

        $layoutDefinition = [
            [
                '$hydrate' => "react.section.1-column",
                "children" => [
                    [
                        // Assets should be available.
                        '$hydrate' => "react.asset.discussionList",
                        "apiParams" => [
                            "sort" => "-dateLastComment",
                        ],
                    ],
                ],
            ],
        ];

        $expected = $this->getExpectedDiscussionListLayout();

        $params = [
            "categoryID" => $category["categoryID"],
        ];

        // Posting to the main hydrate endpoints will have the correct result.
        $response = $this->api()->post("/layouts/hydrate", [
            "layout" => $layoutDefinition,
            "params" => $params,
            "layoutViewType" => "discussionList",
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $layout = $this->getDiscussionListLayoutMinusDiscussions($response->getBody()["layout"]);
        $this->assertSame($expected, $layout);

        // We can save it as a layout and render it by the ID.

        $layout = $this->api()->post("/layouts", [
            "name" => "My Layout",
            "layout" => $layoutDefinition,
            "layoutViewType" => "discussionList",
        ]);

        $response = $this->api()->put("/layouts/{$layout["layoutID"]}/views", [
            [
                "recordID" => $category["categoryID"],
                "recordType" => "category",
            ],
        ]);

        $response = $this->api()->get("/layouts/lookup-hydrate", [
            "layoutViewType" => "discussionList",
            "recordID" => $category["categoryID"],
            "recordType" => "category",
            "params" => $params,
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $layout = $this->getDiscussionListLayoutMinusDiscussions($response->getBody()["layout"]);
        $this->assertSame($expected, $layout);
    }

    /**
     * Test that we can hydrate arbitrary and saved layouts asset.
     *
     * @return void
     */
    public function testHydrateAsset()
    {
        $layoutDefinition = [
            [
                '$hydrate' => "react.section.1-column",
                "children" => [
                    [
                        // Assets should be available.
                        '$hydrate' => "react.asset.discussionList",
                        "apiParams" => [
                            "sort" => "-dateLastComment",
                        ],
                    ],
                ],
            ],
        ];

        $params = [];

        $expected = $this->getExpectedDiscussionListLayout();

        // Posting to the main hydrate endpoints will have the correct result.
        $response = $this->api()->post("/layouts/hydrate", [
            "layout" => $layoutDefinition,
            "params" => $params,
            "layoutViewType" => "discussionList",
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $layout = $response->getBody()["layout"];
        // Let's remove the discussions before comparing.
        $layout[0]['$reactProps']["children"][0]['$reactProps']["discussions"] = [];
        $this->assertSame($expected, $layout);

        // We can save it as a layout and render it by the ID.

        $layout = $this->api()->post("/layouts", [
            "name" => "My Layout",
            "layout" => $layoutDefinition,
            "layoutViewType" => "discussionList",
        ]);
        $response = $this->api()->get("/layouts/{$layout["layoutID"]}/hydrate-assets", [
            "params" => $params,
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertArrayHasKey("js", $body);
        $this->assertArrayHasKey("css", $body);
    }

    /**
     * Test that we can use dynamic hydrate lookup assets.
     *
     * @return void
     */
    public function testLookupHydrateAsset()
    {
        $category = $this->createCategory(["name" => "My Category"]);

        $layoutDefinition = [
            [
                '$hydrate' => "react.section.1-column",
                "children" => [
                    [
                        // Assets should be available.
                        '$hydrate' => "react.asset.discussionList",
                        "apiParams" => [
                            "sort" => "-dateLastComment",
                        ],
                    ],
                ],
            ],
        ];

        $params = [
            "categoryID" => $category["categoryID"],
        ];

        // Posting to the main hydrate endpoints will have the correct result.
        $response = $this->api()->post("/layouts/hydrate", [
            "layout" => $layoutDefinition,
            "params" => $params,
            "layoutViewType" => "discussionList",
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        // We can save it as a layout and render it by the ID.

        $layout = $this->api()->post("/layouts", [
            "name" => "My Layout",
            "layout" => $layoutDefinition,
            "layoutViewType" => "discussionList",
        ]);

        $response = $this->api()->put("/layouts/{$layout["layoutID"]}/views", [
            [
                "recordID" => $category["categoryID"],
                "recordType" => "category",
            ],
        ]);

        $response = $this->api()->get("/layouts/lookup-hydrate-assets", [
            "layoutViewType" => "discussionList",
            "recordID" => $category["categoryID"],
            "recordType" => "category",
            "params" => $params,
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertArrayHasKey("js", $body);
        $this->assertArrayHasKey("css", $body);
    }

    /**
     * Test that we can generate a hydrateable schema.
     */
    public function testGetSchema()
    {
        // The actual schema generation is pretty well tested over in vanilla/garden-hydrate
        // so this is just requesting the endpoint to make sure it gets parameters applied properly.

        $response = $this->api()->get("/layouts/schema", ["layoutViewType" => "discussionList"]);
        $this->assertEquals(200, $response->getStatusCode());

        // Home asset was applied.
        $this->assertStringContainsString("react.asset.discussionList", $response->getRawBody());
    }

    /**
     * Test our out when fetching a catalogue.
     */
    public function testGetCatalogue()
    {
        $response = $this->api()->get("/layouts/catalog", ["layoutViewType" => "discussionList"]);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertSame("discussionList", $response["layoutViewType"]);
        $this->assertSchemaExists($response, "assets", "react.asset.discussionList");
        $this->assertSchemaExists($response, "sections", "react.section.2-columns");
        $this->assertSchemaExists($response, "widgets", "react.html");
        $this->assertSchemaExists($response, "layoutParams", "category/categoryID");
        $this->assertSchemaExists($response, "middlewares", "role-filter");
        $this->assertWidgetsHaveAllowedSections($response);
    }

    /**
     * Test the assets available to the `categoryList` layoutViewType.
     */
    public function testCategoryListViewAssets()
    {
        $layoutViewType = "categoryList";

        $response = $this->api()->get("/layouts/catalog", ["layoutViewType" => $layoutViewType]);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertSame($layoutViewType, $response["layoutViewType"]);
        $this->assertSchemaExists($response, "assets", "react.asset.categoryList");
    }

    /**
     * Assert that catalog recommendations all exist.
     *
     * @param array $catalogData
     */
    private function assertWidgetsHaveAllowedSections(HttpResponse $catalogData)
    {
        $allAllowedIDs = [];
        foreach ($catalogData["sections"] as $widgetDefinition) {
            $allowedIDs = $widgetDefinition["allowedWidgetIDs"] ?? [];
            $allAllowedIDs = array_merge($allAllowedIDs, $allowedIDs);
            $this->assertNotCount(0, $allowedIDs, "Each section should have allowed widgets.");
        }

        foreach ($allAllowedIDs as $allowedID) {
            $catalog = str_contains($allowedID, "asset") ? $catalogData["assets"] : $catalogData["widgets"];
            //this conditioning is a temporary kludge until we figure out if sections should have different
            //allowedWidgetIDs depending on layoutViewType, as discussionList catalog don't have asset breadcrumbs in it
            if (!in_array($allowedID, ["react.asset.discussionTagsAsset", "react.asset.breadcrumbs"])) {
                $this->assertArrayHasKey(
                    $allowedID,
                    $catalog,
                    "Allowed widget was not present in the catalog: $allowedID"
                );
            }
        }
    }

    /**
     * Assert that some value exists in a catalogue response and it is a schema.
     *
     * @param HttpResponse $catalogueResponse
     * @param string $collectionName
     * @param string $propertyName
     */
    private function assertSchemaExists(HttpResponse $catalogueResponse, string $collectionName, string $propertyName)
    {
        $collection = $catalogueResponse[$collectionName] ?? null;
        $value = $collection[$propertyName]["schema"] ?? null;
        $this->assertArrayHasKey(
            "type",
            $value ?? [],
            "Could not find expected schema in catalogue collection '{$collectionName}': {$propertyName}\n" .
                json_encode($collection, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Test posting new views, retrieving those views, deleting those views and verifying those views were deleted
     */
    public function testViewLifecycle()
    {
        $layout = $this->testPost();
        $layoutID = $layout["layoutID"];

        $expected1 = ["recordID" => -1, "recordType" => "global", "layoutViewType" => "home"];

        // Posting to the layout view post endpoints will have the correct result.
        $response = $this->api()->put("/layouts/" . $layoutID . "/views", [$expected1]);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($layoutID, $response->getBody()[0]["layoutID"]);
        $this->assertRowsEqual($expected1, $response->getBody()[0]);

        $layoutViewIDs = [$response->getBody()[0]["layoutViewID"]];

        // Getting to the main layout view endpoints will have the correct result.
        $response = $this->api()->get("/layouts/" . $layoutID . "/views");
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertGreaterThanOrEqual(1, count($response->getBody())); // account for global default views

        $response = $this->api()->delete("/layouts/" . $layoutID . "/views", ["layoutViewIDs" => $layoutViewIDs]);
        $this->assertEquals(204, $response->getStatusCode());

        $response = $this->api()->get("/layouts/" . $layoutID . "/views");
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(0, count($response->getBody()));
    }

    /**
     * Test layout deletion for layouts associated to view.
     */
    public function testDeleteLayoutAssociatedToView()
    {
        // Create a layout
        $layout = $this->testPost();
        $layoutID = $layout["layoutID"];

        // Create a view associated to the new layout.
        $expected = [["recordID" => -1, "recordType" => "global"]];
        $createViewResponse = $this->api()->put("/layouts/" . $layoutID . "/views", $expected);
        $layoutViewID = [$createViewResponse->getBody()[0]["layoutViewID"]];

        // Try to delete a layout associate to the view (This should fail).
        $this->runWithExpectedException(ClientException::class, function () use ($layoutID) {
            $this->api()->delete("/layouts/" . $layoutID);
        });

        $deleteViewResponse = $this->api()->delete("/layouts/" . $layoutID . "/views", [
            "layoutViewIDs" => $layoutViewID,
        ]);
        $this->assertEquals(204, $deleteViewResponse->getStatusCode());
        $deleteLayoutResponse = $this->api()->delete("/layouts/" . $layoutID);
        $this->assertEquals(204, $deleteLayoutResponse->getStatusCode());
    }

    /**
     * Test assigning a LayoutView to a Nonexistent Category.
     */
    public function testAssignLayoutViewToNonexistentCategory()
    {
        $fileBasedLayoutProvider = $this->container()->get(FileBasedLayoutProvider::class);
        $fileBasedLayoutProvider->setCacheBasePath(PATH_TEST_CACHE);
        // Create a layout
        $layout = $this->testPost();
        $layoutID = $layout["layoutID"];

        $nonExistentCategoryID = 100;
        $categoryExists = $this->categoryModel->getID($nonExistentCategoryID);
        $this->assertFalse($categoryExists);

        // Create a view associated to a nonexistent category. We are expecting a NotFoundException to be thrown.
        $expected = [["recordType" => "category", "recordID" => $nonExistentCategoryID]];
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("category not found");

        $this->api()->put("/layouts/" . $layoutID . "/views", $expected);
    }

    /**
     * Test assigning a LayoutView to an existing Category.
     * Test deletion of layoutView when the associated category is deleted.
     */
    public function testLayoutViewDeletionUponCategoryDeletion()
    {
        // Create a layout
        $layout = $this->testPost();
        $layoutID = $layout["layoutID"];
        // We create a new category.
        $newCategoryID = $this->createCategory()["categoryID"];
        // We create a new layoutView assigned to the new category.
        $expected = [["recordType" => "category", "recordID" => $newCategoryID]];
        $this->api()->put("/layouts/" . $layoutID . "/views", $expected);
        // Verify that the associated layoutView exists.
        $responseBody = $this->api()
            ->get("/layouts/" . $layoutID . "/views")
            ->getBody();
        $this->assertEquals(1, count($responseBody));

        // We test that the CategoryRecordProvider getRecords() function returns properly formatted data.
        $providedRecords = $this->layoutViewModel->getRecords("category", [$responseBody[0]["layoutViewID"]]);
        $providedRecord = reset($providedRecords);
        $this->assertArrayHasKey("name", $providedRecord);
        $this->assertArrayHasKey("url", $providedRecord);

        // We delete the category.
        $this->categoryModel->deleteID($newCategoryID);
        // Verify that the associated layoutView has also been deleted.
        $response = $this->api()->get("/layouts/" . $layoutID . "/views");
        $this->assertEquals(0, count($response->getBody()));
    }

    /**
     * Test that attempting to pass invalid parameters to layout/view throws a ClientException
     */
    public function testPostInvalidLayoutViewReturnsClientException(): void
    {
        $layout = $this->testPost();
        $layoutID = $layout["layoutID"];

        $discussionView = [
            ["recordid" => 1, "recordTypes" => "category"],
            ["recordid" => 1, "recordTypes" => "category"],
            ["record123" => 1, "RecordType" => "category"],
        ];

        $this->expectException(ClientException::class);
        $_ = $this->api()->put("/layouts/" . $layoutID . "/views", $discussionView);
    }

    /**
     * Test that attempting to create a duplicate layout view throws a ClientException
     */
    public function testPostDuplicateLayoutViewInOneCallReturnsClientException(): void
    {
        $layout = $this->testPost();
        $layoutID = $layout["layoutID"];

        $discussionView = [
            ["recordID" => 1, "recordType" => "category"],
            ["recordID" => 1, "recordType" => "category"],
        ];

        // Duplicate post
        $this->expectException(ClientException::class);
        $response = $this->api()->put("/layouts/" . $layoutID . "/views", $discussionView);
    }

    /**
     * Test that attempting to create a new layoutView with same recordID, recordType, layoutType, layoutViewType, but different layoutID
     * Should delete old layoutView, and add new one.
     */
    public function testPostLayoutViewDifferentLayoutID(): void
    {
        $layout = $this->testPost();
        $layoutID = $layout["layoutID"];

        $discussionView = [["recordID" => -1, "recordType" => "global"]];

        // Duplicate post
        $this->api()->put("/layouts/" . $layoutID . "/views", $discussionView);
        $layout1 = $this->testPost();
        $layoutID1 = $layout1["layoutID"];
        $response = $this->api()->put("/layouts/" . $layoutID1 . "/views", $discussionView);
        $body = $response->getBody();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertCount(1, $body);
        $this->assertEquals($layoutID1, $body[0]["layoutID"]);
    }

    /**
     * Test that attempting to create a multiple layout views with one request.
     */
    public function testMultiplePutLayoutView(): void
    {
        $layout = $this->testPost();
        $layoutID = $layout["layoutID"];
        $newCategoryID = $this->createCategory()["categoryID"];
        $newCategoryID2 = $this->createCategory()["categoryID"];
        $discussionView = [
            ["recordID" => $newCategoryID, "recordType" => "category"],
            ["recordID" => $newCategoryID2, "recordType" => "category"],
            ["recordID" => -1, "recordType" => "global"],
        ];

        // First post will succeed
        $response = $this->api()->put("/layouts/" . $layoutID . "/views", $discussionView);
        $body = $response->getBody();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertCount(3, $body);
        $this->assertEquals($layoutID, $body[0]["layoutID"]);
        $this->assertRowsEqual($discussionView[1], $body[1]);
        $this->assertEquals($layoutID, $body[2]["layoutID"]);
    }

    /**
     * Test excludeHiddenCategories not hiding discussions on discussionCategoryPage
     */
    public function testGetLayoutIdDiscussionForExcludeHiddenCategories()
    {
        // Mark categories discussions as hidden on global pages.
        $category = $this->createCategory(["HideAllDiscussions" => true, "DisplayAs" => "Discussions"]);

        $layoutDefinition = [
            [
                '$hydrate' => "react.section.1-column",
                "children" => [
                    [
                        // Assets should be available.
                        '$hydrate' => "react.asset.discussionList",
                        "apiParams" => [
                            "sort" => "-dateLastComment",
                        ],
                    ],
                ],
            ],
        ];
        $layout = $this->api()->post("/layouts", [
            "name" => "Discussion Category Layout",
            "layout" => $layoutDefinition,
            "layoutViewType" => "discussionCategoryPage",
        ]);
        $layoutViews = [["recordID" => 1, "recordType" => "category"]];
        $views = $this->api()
            ->put("/layouts/" . $layout["layoutID"] . "/views", [
                ["recordType" => "global", "recordID" => -1],
                ["recordType" => "category", "recordID" => $category["categoryID"]],
            ])
            ->getBody();
        $this->layoutViewModel->saveLayoutViews($layoutViews, "home", "discussionCategoryPage");
        // Creating Categories
        $this->createDiscussion(["categoryID" => $category["categoryID"]]);
        $this->createDiscussion(["categoryID" => $category["categoryID"]]);
        $this->createDiscussion(["categoryID" => $category["categoryID"]]);

        $discussionModel = $this->container()->get(DiscussionModel::class);
        $discussionCount = $discussionModel->getCountForCategory($category["categoryID"]);
        $categoryLookup = $this->api()->get("/layouts/lookup-hydrate", [
            "layoutViewType" => "categoryList",
            "recordType" => "category",
            "recordID" => 1,
            "params" => [
                "categoryID" => $category["categoryID"],
            ],
        ]);
        $this->assertEquals(200, $categoryLookup->getStatusCode());
        $discussions = $categoryLookup->getBody()["layout"][0]['$reactProps']["children"][0]['$reactProps'][
            "discussions"
        ];
        $this->assertCount($discussionCount, $discussions);
    }

    /**
     * Test that we can delete all views for a layout.
     */
    public function testDeleteAllViewsForLayout(): void
    {
        $category = $this->createCategory();

        // Create a layout with multiple views.
        $layout = $this->testPost();
        $views = $this->api()
            ->put("/layouts/" . $layout["layoutID"] . "/views", [
                ["recordType" => "global", "recordID" => -1],
                ["recordType" => "category", "recordID" => $category["categoryID"]],
            ])
            ->getBody();

        $this->assertCount(2, $views);

        // We can delete all views from the layout.
        $this->api()->delete("/layouts/{$layout["layoutID"]}/views");
        $views = $this->api()
            ->get("/layouts/{$layout["layoutID"]}/views")
            ->getBody();
        $this->assertCount(0, $views);
    }

    //endregion

    //region Non-Public methods
    /**
     * {@inheritdoc}
     */
    protected function modifyGetExpected(array $row): array
    {
        // GET endpoint doesn't return 'layout' property
        return array_diff_key($row, ["layout" => 0]);
    }
    //endregion
}
