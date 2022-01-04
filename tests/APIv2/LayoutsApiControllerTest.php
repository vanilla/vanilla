<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Layout\Providers\FileBasedLayoutProvider;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Tests for api/v2/layouts endpoints
 */
class LayoutsApiControllerTest extends AbstractResourceTest {

    use CommunityApiTestTrait;

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

    //endregion

    //region Setup / Teardown
    /**
     * @inheritdoc
     */
    public function setUp(): void {
        parent::setUp();
        $fileBasedLayoutProvider = $this->container()->get(FileBasedLayoutProvider::class);
        $fileBasedLayoutProvider->setCacheBasePath(PATH_TEST_CACHE);
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
        yield "string" => ["home"];
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
     * Test posting new views, retrieving those views, deleting those views and verifying those views were deleted
     */
    public function testViewLifecycle() {
        $layout = $this->testPost();
        $layoutID = $layout['layoutID'];

        $expected1 = ['recordID' => 1, 'recordType' => 'widget'];
        $expected2 = ['recordID' => 2, 'recordType' => 'widget2'];

        // Posting to the layout view post endpoints will have the correct result.
        $response = $this->api()->post('/layouts/'.$layoutID.'/views', $expected1);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertSame($layoutID, $response->getBody()['layoutID']);
        $this->assertRowsEqual($expected1, $response->getBody());

        $layoutViewIDs = [$response->getBody()['layoutViewID']];

        $response = $this->api()->post('/layouts/'.$layoutID.'/views', $expected2);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertSame($layoutID, $response->getBody()['layoutID']);
        $this->assertRowsEqual($expected2, $response->getBody());

        $layoutViewIDs[] = $response->getBody()['layoutViewID'];

        // Getting to the main layout view endpoints will have the correct result.
        $response = $this->api()->get('/layouts/'.$layoutID.'/views');
        $this->assertEquals(200, $response->getStatusCode());
        $numLayoutViews = count($response->getBody());
        $this->assertGreaterThanOrEqual(2, $numLayoutViews); // account for global default views

        $response = $this->api()->delete('/layouts/'.$layoutID.'/views', ['layoutViewIDs' => $layoutViewIDs]);
        $this->assertEquals(204, $response->getStatusCode());

        $response = $this->api()->get('/layouts/'.$layoutID.'/views');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($numLayoutViews - 2, count($response->getBody()));
    }

    /**
     * Test that attempting to create a duplicate layout view throws a ClientException
     */
    public function testPostDuplicateLayoutViewReturnsClientException(): void {
        $layout = $this->testPost();
        $layoutID = $layout['layoutID'];

        $discussionView = ['recordID' => 1, 'recordType' => 'discussion'];

        // First post will succeed
        $response = $this->api()->post('/layouts/'.$layoutID.'/views', $discussionView);
        $this->assertEquals(201, $response->getStatusCode());

        // Second post, not so much
        $this->expectException(ClientException::class);
        $_ = $this->api()->post('/layouts/'.$layoutID.'/views', $discussionView);
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
