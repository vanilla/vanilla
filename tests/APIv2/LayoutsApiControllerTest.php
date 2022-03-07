<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use CategoryModel;
use Garden\Http\HttpResponse;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Vanilla\Layout\LayoutViewModel;
use Vanilla\Layout\Providers\FileBasedLayoutProvider;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Tests for api/v2/layouts endpoints
 */
class LayoutsApiControllerTest extends AbstractResourceTest {

    use CommunityApiTestTrait;
    use ExpectExceptionTrait;

    /* @var LayoutViewModel */
    private $layoutViewModel;

    //region Properties
    protected $testPagingOnIndex = false;

    /** {@inheritdoc} */
    protected $baseUrl = '/layouts';

    /** {@inheritdoc} */
    protected $pk = 'layoutID';

    /** {@inheritdoc} */
    protected $editFields = ['name', 'layout'];

    /** {@inheritdoc} */
    protected $patchFields = ['name', 'layout'];

    /** {@inheritdoc} */
    protected $record = [
        'name' => 'Layout',
        'layout' => [
            ['foo' => 'bar'],
            ['fizz' => 'buzz', 'flip' => ['flap', 'flop'], 'drip' => ['drop' => 'derp']]
        ],
        'layoutViewType' => 'home'
    ];

    /** @var CategoryModel */
    protected $categoryModel;

    //endregion

    //region Setup / Teardown
    /**
     * @inheritdoc
     */
    public function setUp(): void {
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
    public function testGetStaticLayout(string $id): void {
        $response = $this->api()->get("{$this->baseUrl}/{$id}");
        $this->assertTrue($response->isSuccessful());
        $layout = $response->getBody();
        $this->assertEquals($id, $layout['layoutID']);
        $this->assertTrue($layout['isDefault']);
    }

    /**
     * Test that GET /api/v2/layouts includes the static layout definition provided.
     *
     * @param string $id
     * @dataProvider staticLayoutDataProvider
     */
    public function testIndexIncludesStaticLayout(string $id): void {
        $response = $this->api()->get("{$this->baseUrl}");
        $this->assertTrue($response->isSuccessful());
        $layouts = $response->getBody();
        $this->assertIsArray($layouts);
        /** @var array $layouts */
        $this->assertGreaterThanOrEqual(1, count($layouts));
        $this->assertCount(
            1,
            array_filter(
                $layouts,
                function (array $layout) use ($id) {
                    return $layout['layoutID'] === $id;
                }
            )
        );
    }

    /**
     * Data Provider for tests retrieving static layouts
     *
     * @return iterable
     */
    public function staticLayoutDataProvider(): iterable {
        yield "home" => ['home'];
    }

    /**
     * Test that the GET /api/v2/layouts/:id endpoint throws ClientException on invalid ID formats
     *
     * @param mixed $id
     * @dataProvider getThrowsClientExceptionDataProvider
     */
    public function testGetThrowsClientException($id): void {
        $this->expectException(ClientException::class);
        $_ = $this->api()->get("{$this->baseUrl}/{$id}");
    }

    /**
     * @return iterable
     */
    public function getThrowsClientExceptionDataProvider(): iterable {
        yield "floating point number" => [pi()];
        yield "boolean" => [false];
    }

    /**
     * Test that GET /api/v2/layouts/:id endpoint throws NotFoundException when endpoint not found
     *
     * @param mixed $id
     * @dataProvider idempotentThrowsNotFoundExceptionDataProvider
     */
    public function testGetThrowsNotFoundException($id): void {
        $this->expectException(NotFoundException::class);
        $_ = $this->api()->get("{$this->baseUrl}/{$id}");
    }

    /**
     * Test that the DELETE /api/v2/layouts/:id endpoint throws ClientException on invalid ID formats
     *
     * @param mixed $id
     * @dataProvider mutableEndpointThrowsClientExceptionOnInvalidIdFormatDataProvider
     */
    public function testDeleteThrowsClientException($id): void {
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
    public function testDeleteThrowsNotFoundException($id): void {
        $this->expectException(NotFoundException::class);
        $_ = $this->api()->delete("{$this->baseUrl}/{$id}");
    }

    /**
     * Test that the PATCH /api/v2/layouts/:id endpoint throws ClientException on invalid ID formats
     *
     * @param mixed $id
     * @dataProvider mutableEndpointThrowsClientExceptionOnInvalidIdFormatDataProvider
     */
    public function testPatchThrowsClientException($id): void {
        $this->expectException(ClientException::class);
        $body = ['name' => 'Norman'];
        $_ = $this->api()->patch("{$this->baseUrl}/{$id}", $body);
    }

    /**
     * Test that the PATCH /api/v2/layouts/:id endpoint throws NotFoundException when ID provided
     * doesn't correspond to a layout definition.
     *
     * @param mixed $id
     * @dataProvider nonIdempotentThrowsNotFoundExceptionDataProvider
     */
    public function testPatchThrowsNotFoundException($id): void {
        $this->expectException(NotFoundException::class);
        $body = ['name' => 'Norman'];
        $_ = $this->api()->patch("{$this->baseUrl}/{$id}", $body);
    }

    /**
     * Test that the GET /api/v2/layouts/:id/edit endpoint throws ClientException on invalid ID formats
     *
     * @param mixed $id
     * @dataProvider mutableEndpointThrowsClientExceptionOnInvalidIdFormatDataProvider
     */
    public function testGetEditThrowsClientException($id): void {
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
    public function testGetEditThrowsNotFoundException($id): void {
        $this->expectException(NotFoundException::class);
        $_ = $this->api()->get("{$this->baseUrl}/{$id}/edit");
    }

    /**
     * @return iterable
     */
    public function idempotentThrowsNotFoundExceptionDataProvider(): iterable {
        yield "mutable not found" => [987654321];
        yield "immutable not found" => ["Norman"];
    }

    /**
     * @return iterable
     */
    public function nonIdempotentThrowsNotFoundExceptionDataProvider(): iterable {
        yield "mutable not found" => [987654321];
    }

    /**
     * @return iterable
     */
    public function mutableEndpointThrowsClientExceptionOnInvalidIdFormatDataProvider(): iterable {
        yield "floating point number" => [pi()];
        yield "boolean" => [false];
    }


    /**
     * Test that we can hydrate arbitrary and saved layouts.
     *
     * @return void
     */
    public function testHydrate() {
        $category = $this->createCategory(['name' => 'My Category']);

        $layoutDefinition = [
            [
                '$hydrate' => 'react.section.1-column',
                'contents' => [
                    [
                        // Assets should be available.
                        '$hydrate' => 'react.asset.breadcrumbs',
                        'recordType' => 'category',
                        'recordID' => [
                            // Param definitions should be available.
                            '$hydrate' => 'param',
                            'ref' => 'category/categoryID',
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            [
                '$reactComponent' => 'SectionOneColumn',
                '$reactProps' => [
                    'contents' => [
                        [
                            '$reactComponent' => 'Breadcrumbs',
                            '$reactProps' => [
                                'crumbs' => [
                                    ['name' => 'Home', 'url' => url('', true)],
                                    ['name' => 'My Category', 'url' => $category['url']],
                                ],
                            ],
                        ],
                    ],
                    'isNarrow' => false,
                    'autoWrap' => true,
                ],
            ],
        ];

        $params = [
            'categoryID' => $category['categoryID'],
        ];

        // Posting to the main hydrate endpoints will have the correct result.
        $response = $this->api()->post('/layouts/hydrate', [
            'layout' => $layoutDefinition,
            'params' => $params,
            'layoutViewType' => 'home',
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, $response->getBody()['layout']);

        // We can save it as a layout and render it by the ID.

        $layout = $this->api()->post('/layouts', [
            'name' => 'My Layout',
            'layout' => $layoutDefinition,
            'layoutViewType' => 'home',
        ]);
        $response = $this->api()->get("/layouts/{$layout['layoutID']}/hydrate", [
            'params' => $params,
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, $response->getBody()['layout']);
    }

    /**
     * Test that we can use dynamic hydrate lookup.
     *
     * @return void
     */
    public function testLookupHydrate() {
        $category = $this->createCategory(['name' => 'My Category']);

        $layoutDefinition = [
            [
                '$hydrate' => 'react.section.1-column',
                'contents' => [
                    [
                        // Assets should be available.
                        '$hydrate' => 'react.asset.breadcrumbs',
                        'recordType' => 'category',
                        'recordID' => [
                            // Param definitions should be available.
                            '$hydrate' => 'param',
                            'ref' => 'category/categoryID',
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            [
                '$reactComponent' => 'SectionOneColumn',
                '$reactProps' => [
                    'contents' => [
                        [
                            '$reactComponent' => 'Breadcrumbs',
                            '$reactProps' => [
                                'crumbs' => [
                                    ['name' => 'Home', 'url' => url('', true)],
                                    ['name' => 'My Category', 'url' => $category['url']],
                                ],
                            ],
                        ],
                    ],
                    'isNarrow' => false,
                    'autoWrap' => true,
                ],
            ],
        ];

        $params = [
            'categoryID' => $category['categoryID'],
        ];

        // Posting to the main hydrate endpoints will have the correct result.
        $response = $this->api()->post('/layouts/hydrate', [
            'layout' => $layoutDefinition,
            'params' => $params,
            'layoutViewType' => 'home',
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, $response->getBody()['layout']);

        // We can save it as a layout and render it by the ID.

        $layout = $this->api()->post('/layouts', [
            'name' => 'My Layout',
            'layout' => $layoutDefinition,
            'layoutViewType' => 'home',
        ]);

        $response = $this->api()->put("/layouts/{$layout['layoutID']}/views", [
            'recordID' => $category['categoryID'],
            'recordType' => 'category'
        ]);

        $response = $this->api()->get("/layouts/lookup-hydrate", [
            'layoutViewType' => 'home',
            'recordID' => $category['categoryID'],
            'recordType' => 'category',
            'params' => $params,
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, $response->getBody()['layout']);
    }


    /**
     * Test that we can hydrate arbitrary and saved layouts asset.
     *
     * @return void
     */
    public function testHydrateAsset() {
        $category = $this->createCategory(['name' => 'My Category']);

        $layoutDefinition = [
            [
                '$hydrate' => 'react.section.1-column',
                'contents' => [
                    [
                        // Assets should be available.
                        '$hydrate' => 'react.asset.breadcrumbs',
                        'recordType' => 'category',
                        'recordID' => [
                            // Param definitions should be available.
                            '$hydrate' => 'param',
                            'ref' => 'category/categoryID',
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            [
                '$reactComponent' => 'SectionOneColumn',
                '$reactProps' => [
                    'contents' => [
                        [
                            '$reactComponent' => 'Breadcrumbs',
                            '$reactProps' => [
                                'crumbs' => [
                                    ['name' => 'Home', 'url' => url('', true)],
                                    ['name' => 'My Category', 'url' => $category['url']],
                                ],
                            ],
                        ],
                    ],
                    'isNarrow' => false,
                    'autoWrap' => true,
                ],
            ],
        ];

        $params = [
            'categoryID' => $category['categoryID'],
        ];

        // Posting to the main hydrate endpoints will have the correct result.
        $response = $this->api()->post('/layouts/hydrate', [
            'layout' => $layoutDefinition,
            'params' => $params,
            'layoutViewType' => 'home',
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, $response->getBody()['layout']);

        // We can save it as a layout and render it by the ID.

        $layout = $this->api()->post('/layouts', [
            'name' => 'My Layout',
            'layout' => $layoutDefinition,
            'layoutViewType' => 'home',
        ]);
        $response = $this->api()->get("/layouts/{$layout['layoutID']}/hydrate-assets", [
            'params' => $params,
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
    public function testLookupHydrateAsset() {
        $category = $this->createCategory(['name' => 'My Category']);

        $layoutDefinition = [
            [
                '$hydrate' => 'react.section.1-column',
                'contents' => [
                    [
                        // Assets should be available.
                        '$hydrate' => 'react.asset.breadcrumbs',
                        'recordType' => 'category',
                        'recordID' => [
                            // Param definitions should be available.
                            '$hydrate' => 'param',
                            'ref' => 'category/categoryID',
                        ],
                    ],
                ],
            ],
        ];

        $params = [
            'categoryID' => $category['categoryID'],
        ];

        // Posting to the main hydrate endpoints will have the correct result.
        $response = $this->api()->post('/layouts/hydrate', [
            'layout' => $layoutDefinition,
            'params' => $params,
            'layoutViewType' => 'home',
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        // We can save it as a layout and render it by the ID.

        $layout = $this->api()->post('/layouts', ['name' => 'My Layout',
            'layout' => $layoutDefinition,
            'layoutViewType' => 'home',
        ]);

        $response = $this->api()->put("/layouts/{$layout['layoutID']}/views", [
            'recordID' => $category['categoryID'],
            'recordType' => 'category'
        ]);

        $response = $this->api()->get("/layouts/lookup-hydrate-assets", [
            'layoutViewType' => 'home',
            'recordID' => $category['categoryID'],
            'recordType' => 'category',
            'params' => $params,
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertArrayHasKey("js", $body);
        $this->assertArrayHasKey("css", $body);
    }

    /**
     * Test that we can generate a hydrateable schema.
     */
    public function testGetSchema() {
        // The actual schema generation is pretty well tested over in vanilla/garden-hydrate
        // so this is just requesting the endpoint to make sure it gets parameters applied properly.

        $response = $this->api()->get('/layouts/schema', ['layoutViewType' => 'home']);
        $this->assertEquals(200, $response->getStatusCode());

        // Home asset was applied.
        $this->assertStringContainsString("react.asset.breadcrumbs", $response->getRawBody());
    }

    /**
     * Test our out when fetching a catalogue.
     */
    public function testGetCatalogue() {
        $response = $this->api()->get('/layouts/catalogue', ['layoutViewType' => 'home']);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertSame('home', $response['layoutViewType']);
        $this->assertSchemaExists($response, 'assets', 'react.asset.breadcrumbs');
        $this->assertSchemaExists($response, 'sections', 'react.section.2-columns');
        $this->assertSchemaExists($response, 'widgets', 'react.html');
        $this->assertSchemaExists($response, 'layoutParams', 'category/categoryID');
        $this->assertSchemaExists($response, 'middlewares', 'role-filter');
    }

    /**
     * Assert that some value exists in a catalogue response and it is a schema.
     *
     * @param HttpResponse $catalogueResponse
     * @param string $collectionName
     * @param string $propertyName
     */
    private function assertSchemaExists(HttpResponse $catalogueResponse, string $collectionName, string $propertyName) {
        $collection = $catalogueResponse[$collectionName] ?? null;
        $value = $collection[$propertyName] ?? null;
        $this->assertArrayHasKey(
            "type",
            $value ?? [],
            "Could not find expected schema in catalogue collection '{$collectionName}': {$propertyName}\n"
            . json_encode($collection, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Test posting new views, retrieving those views, deleting those views and verifying those views were deleted
     */
    public function testViewLifecycle() {
        $layout = $this->testPost();
        $layoutID = $layout['layoutID'];

        $expected1 = ['recordID' => -1, 'recordType' => 'global', 'layoutViewType' => 'home'];

        // Posting to the layout view post endpoints will have the correct result.
        $response = $this->api()->put('/layouts/'.$layoutID.'/views', $expected1);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($layoutID, $response->getBody()['layoutID']);
        $this->assertRowsEqual($expected1, $response->getBody());

        $layoutViewIDs = [$response->getBody()['layoutViewID']];

        // Getting to the main layout view endpoints will have the correct result.
        $response = $this->api()->get('/layouts/'.$layoutID.'/views');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertGreaterThanOrEqual(1, count($response->getBody())); // account for global default views

        $response = $this->api()->delete('/layouts/'.$layoutID.'/views', ['layoutViewIDs' => $layoutViewIDs]);
        $this->assertEquals(204, $response->getStatusCode());

        $response = $this->api()->get('/layouts/'.$layoutID.'/views');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(0, count($response->getBody()));
    }

    /**
     * Test layout deletion for layouts associated to view.
     */
    public function testDeleteLayoutAssociatedToView() {
        // Create a layout
        $layout = $this->testPost();
        $layoutID = $layout['layoutID'];

        // Create a view associated to the new layout.
        $expected = ['recordID' => -1, 'recordType' => 'global'];
        $createViewResponse = $this->api()->put('/layouts/'.$layoutID.'/views', $expected);
        $layoutViewID = [$createViewResponse->getBody()['layoutViewID']];

        // Try to delete a layout associate to the view (This should fail).
        $this->runWithExpectedException(ClientException::class, function () use ($layoutID) {
            $this->api()->delete('/layouts/'.$layoutID);
        });

        $deleteViewResponse = $this->api()->delete('/layouts/'.$layoutID.'/views', ['layoutViewIDs' => $layoutViewID]);
        $this->assertEquals(204, $deleteViewResponse->getStatusCode());
        $deleteLayoutResponse = $this->api()->delete('/layouts/'.$layoutID);
        $this->assertEquals(204, $deleteLayoutResponse->getStatusCode());
    }

    /**
     * Test assigning a LayoutView to a Nonexistent Category.
     */
    public function testAssignLayoutViewToNonexistentCategory() {
        $fileBasedLayoutProvider = $this->container()->get(FileBasedLayoutProvider::class);
        $fileBasedLayoutProvider->setCacheBasePath(PATH_TEST_CACHE);
        // Create a layout
        $layout = $this->testPost();
        $layoutID = $layout['layoutID'];

        $nonExistentCategoryID = 100;
        $categoryExists = $this->categoryModel->getID($nonExistentCategoryID);
        $this->assertFalse($categoryExists);

        // Create a view associated to a nonexistent category. We are expecting a NotFoundException to be thrown.
        $expected = ['recordType' => 'category', 'recordID' => $nonExistentCategoryID];
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("category not found");

        $this->api()->put('/layouts/'.$layoutID.'/views', $expected);
    }

    /**
     * Test assigning a LayoutView to an existing Category.
     * Test deletion of layoutView when the associated category is deleted.
     */
    public function testLayoutViewDeletionUponCategoryDeletion() {
        // Create a layout
        $layout = $this->testPost();
        $layoutID = $layout['layoutID'];
        // We create a new category.
        $newCategoryID = $this->createCategory()['categoryID'];
        // We create a new layoutView assigned to the new category.
        $expected = ['recordType' => 'category', 'recordID' => $newCategoryID];
        $this->api()->put('/layouts/'.$layoutID.'/views', $expected);
        // Verify that the associated layoutView exists.
        $responseBody = $this->api()->get('/layouts/'.$layoutID.'/views')->getBody();
        $this->assertEquals(1, count($responseBody));

        // We test that the CategoryRecordProvider getRecords() function returns properly formatted data.
        $providedRecords = $this->layoutViewModel->getRecords('category', [$responseBody[0]['layoutViewID']]);
        $providedRecord = reset($providedRecords);
        $this->assertArrayHasKey('name', $providedRecord);
        $this->assertArrayHasKey('url', $providedRecord);

        // We delete the category.
        $this->categoryModel->deleteID($newCategoryID);
        // Verify that the associated layoutView has also been deleted.
        $response = $this->api()->get('/layouts/'.$layoutID.'/views');
        $this->assertEquals(0, count($response->getBody()));
    }

    /**
     * Test that attempting to create a duplicate layout view throws a ClientException
     */
    public function testPostDuplicateLayoutViewReturnsClientException(): void {
        $layout = $this->testPost();
        $layoutID = $layout['layoutID'];

        $discussionView = ['recordID' => 1, 'recordType' => 'category'];

        // First post will succeed
        $response = $this->api()->put('/layouts/'.$layoutID.'/views', $discussionView);
        $this->assertEquals(201, $response->getStatusCode());

        // Second post, not so much
        $this->expectException(ClientException::class);
        $_ = $this->api()->put('/layouts/'.$layoutID.'/views', $discussionView);
    }

    /**
     * Test that invoking the DELETE /api/v2/layouts/:layoutID/views endpoint fails if no layout view IDs specified
     */
    public function testDeleteLayoutViewWithoutViewIDsFails(): void {
        $layout = $this->testPost();
        $layoutID = $layout['layoutID'];
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("layoutViewIDs is required");
        $_ = $this->api()->delete('/layouts/'.$layoutID.'/views');
    }
    //endregion

    //region Non-Public methods
    /**
     * {@inheritdoc}
     */
    protected function modifyGetExpected(array $row): array {
        // GET endpoint doesn't return 'layout' property
        return array_diff_key($row, ['layout' => 0]);
    }
    //endregion
}
