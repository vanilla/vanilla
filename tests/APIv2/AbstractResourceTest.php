<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Vanilla\Formatting\FormatCompatibilityService;

abstract class AbstractResourceTest extends AbstractAPIv2Test {

    /**
     * The number of rows create when testing index endpoints.
     */
    const INDEX_ROWS = 4;

    /**
     * @var string The resource route.
     */
    protected $baseUrl = '/resources';

    /** @var array Fields to be checked with get/<id>/edit */
    protected $editFields = ['name', 'body', 'format'];

    /** @var bool Whether to check if paging works or not in the index. */
    protected $testPagingOnIndex = true;

    /**
     * @var string The singular name of the resource.
     */
    protected $singular = '';

    /**
     * @var array A record that can be posted to the endpoint.
     */
    protected $record = ['body' => 'Hello world!', 'format' => 'markdown'];

    /**
     * @var string[] An array of field names that are okay to send to patch endpoints.
     */
    protected $patchFields;

    /**
     * @var string The name of the primary key of the resource.
     */
    protected $pk = '';

    /**
     * AbstractResourceTest constructor.
     *
     * Subclasses can override properties and then call this constructor to set defaults.
     *
     * @param null $name Required by PHPUnit.
     * @param array $data Required by PHPUnit.
     * @param string $dataName Required by PHPUnit.
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);

        if (empty($this->singluar)) {
            $this->singular = rtrim(ltrim($this->baseUrl, '/'), 's');
        }
        if (empty($this->pk)) {
            $this->pk = $this->singular.'ID';
        }

        if ($this->patchFields === null) {
            $this->patchFields = array_keys($this->record());
        }
    }

    /**
     * Test GET /resource/<id>.
     */
    public function testGet() {
        $row = $this->testPost();

        $r = $this->api()->get(
            "{$this->baseUrl}/{$row[$this->pk]}"
        );

        $this->assertEquals(200, $r->getStatusCode());
        $this->assertRowsEqual($row, $r->getBody());
        $this->assertCamelCase($r->getBody());

        return $r->getBody();
    }

    /**
     * Test POST /resource.
     *
     * @param array|null $record Fields for a new record.
     * @param array $extra Additional fields to send along with the POST request.
     * @return array Returns the new record.
     */
    public function testPost($record = null, array $extra = []) {
        $record = $record === null ? $this->record() : $record;
        $post = $record + $extra;
        $result = $this->api()->post(
            $this->baseUrl,
            $post
        );

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();
        $this->assertTrue(is_int($body[$this->pk]));
        $this->assertTrue($body[$this->pk] > 0);

        $this->assertRowsEqual($record, $body);

        return $body;
    }

    /**
     * You shouldn't be allowed to post with a bad format.
     */
    public function testPostBadFormat(): void {
        $record = $this->record();
        if (!array_key_exists('format', $record)) {
            // The test doesn't apply to this resource so just arbitrarily pass.
            $this->assertTrue(true);
            return;
        }
        $record['format'] = 'invalid';

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(422);
        $result = $this->api()->post($this->baseUrl, $record);
    }

    /**
     * Test PATCH /resource/<id> with a full record overwrite.
     */
    public function testPatchFull() {
        $row = $this->testGetEdit();
        $newRow = $this->modifyRow($row);

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            $newRow
        );

        $this->assertEquals(200, $r->getStatusCode());

        $this->assertRowsEqual($newRow, $r->getBody());

        return $r->getBody();
    }

    /**
     * A PATCH with an invalid format should have validation problems.
     *
     * @depends testGetEdit
     */
    public function testPatchInvalidFormat(): void {
        $row = $this->testGetEdit();
        if (!array_key_exists('format', $row)) {
            $this->assertTrue(true);
            return;
        }
        $row['format'] = 'invalid';

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(422);
        $r = $this->api()->patch("{$this->baseUrl}/{$row[$this->pk]}", $row);
    }

    /**
     * Test GET /resource/<id>/edit.
     *
     * @param array|null $record A record to use for comparison.
     * @return array
     */
    public function testGetEdit($record = null) {
        if ($record === null) {
            $record = $this->record();
            $row = $this->testPost($record);
        } else {
            $row = $record;
        }

        $r = $this->api()->get(
            "{$this->baseUrl}/{$row[$this->pk]}/edit"
        );

        $this->assertEquals(200, $r->getStatusCode());
        $this->assertRowsEqual(arrayTranslate($record, $this->editFields), $r->getBody());
        $this->assertCamelCase($r->getBody());

        return $r->getBody();
    }

    /**
     * Test that the edit endpoints apply format compatibility where possible.
     *
     * @param string $editSuffix Set this to change the GET request suffix.
     */
    public function testEditFormatCompat(string $editSuffix = "/edit") {
        $record = $this->record();

        // Only check anything if we've got a format and body field.
        if (!isset($record['body']) || !isset($record['format'])) {
            $this->assertTrue(true);
            return;
        }

        $row = $this->testPost($record);

        $expectedBody = 'Converted!!';

        // Create a stub for the compatService class.
        $actualCompatService = static::container()->get(FormatCompatibilityService::class);
        $mockCompatService = $this->createMock(FormatCompatibilityService::class);
        $mockCompatService->method('convert')
            ->willReturn($expectedBody);
        static::container()
            ->setInstance(FormatCompatibilityService::class, $mockCompatService);


        // Get the actual record and assert our value was set.
        $r = $this->api()->get(
            "{$this->baseUrl}/{$row[$this->pk]}$editSuffix"
        );

        $actualBody = $r['body'];
        $this->assertEquals(
            $expectedBody,
            $actualBody,
            "FormatCompatibilityServer::convert() should be run on edit endpoints."
        );

        // Restore back the actual format compat service.
        static::container()
            ->setInstance(FormatCompatibilityService::class, $actualCompatService);
    }

    /**
     * Modify the row for update requests.
     *
     * @param array $row The row to modify.
     * @return array Returns the modified row.
     */
    protected function modifyRow(array $row) {
        $newRow = [];

        $dt = new \DateTimeImmutable();
        foreach ($this->patchFields as $key) {
            $value = $row[$key];
            if (in_array($key, ['name', 'body', 'description'])) {
                $value .= ' '.$dt->format(\DateTime::RSS);
            } elseif (stripos($key, 'id') === strlen($key) - 2) {
                $value++;
            } else {
                switch ($key) {
                    case 'format':
                        $value = $value === 'markdown' ? 'text' : 'markdown';
                        break;
                }
            }

            $newRow[$key] = $value;
        }

        return $newRow;
    }

    /**
     * Grab values for inserting a new record.
     *
     * @return array
     */
    public function record() {
        return $this->record;
    }

    /**
     * The GET /resource/<id>/edit endpoint should have the same fields as that patch fields.
     *
     * This test helps to ensure that fields are added to the test as the endpoint is updated.
     */
    public function testGetEditFields() {
        $row = $this->testGetEdit();

        unset($row[$this->pk]);
        $rowFields = array_keys($row);
        sort($rowFields);

        $patchFields = $this->patchFields;
        sort($patchFields);

        $this->assertEquals($patchFields, $rowFields);
    }

    /**
     * Test PATCH /resource/<id> with a a single field update.
     *
     * Patch endpoints should be able to update every field on its own.
     *
     * @param string $field The name of the field to patch.
     * @dataProvider providePatchFields
     */
    public function testPatchSparse($field) {
        $row = $this->testGetEdit();
        $patchRow = $this->modifyRow($row);

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            [$field => $patchRow[$field]]
        );

        $this->assertEquals(200, $r->getStatusCode());

        $newRow = $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}/edit");
        $this->assertSame($patchRow[$field], $newRow[$field]);
    }

    /**
     * Test DELETE /resource/<id>.
     */
    public function testDelete() {
        $row = $this->testPost();

        // GardenHTTP does not allow a call to its delete method with a body. This long form is required for delete requests with a body.
        $r = $this->api()->request(\Garden\Http\HttpRequest::METHOD_DELETE, "{$this->baseUrl}/{$row[$this->pk]}", []);

        $this->assertEquals(204, $r->getStatusCode());

        try {
            $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}");
            $this->fail("The {$this->singular} did not get deleted.");
        } catch (\Exception $ex) {
            $this->assertEquals(404, $ex->getCode());
            return;
        }
        $this->fail("Something odd happened while deleting a {$this->singular}.");
    }

    /**
     * Test GET /resource.
     *
     * The base class test can only test a minimum of functionality. Subclasses can make additional assertions on the
     * return value of this method.
     *
     * @return array Returns the fetched data.
     */
    public function testIndex() {
        $indexUrl = $this->indexUrl();
        $originalIndex = $this->api()->get($indexUrl);
        $this->assertEquals(200, $originalIndex->getStatusCode());

        $originalRows = $originalIndex->getBody();
        $rows = $this->generateIndexRows();
        $newIndex = $this->api()->get($indexUrl, ['limit' => count($originalRows) + count($rows) + 1]);

        $newRows = $newIndex->getBody();
        $this->assertEquals(count($originalRows) + count($rows), count($newRows));
        // The index should be a proper indexed array.
        $count = 0;
        foreach ($newRows as $i => $row) {
            $this->assertSame($count, $i);
            $count++;
        }

        if ($this->testPagingOnIndex) {
            $this->pagingTest($indexUrl);
        }

        // There's not much we can really test here so just return and let subclasses do some more assertions.
        return [$rows, $newRows];
    }

    /**
     * Generate rows for the index test.
     *
     * @return array
     */
    protected function generateIndexRows() {
        $rows = [];

        // Insert a few rows.
        for ($i = 0; $i < static::INDEX_ROWS; $i++) {
            $rows[] = $this->testPost();
        }

        return $rows;
    }

    /**
     * The endpoint's index URL.
     *
     * @return string
     */
    public function indexUrl() {
        return $this->baseUrl;
    }

    /**
     * Provide the patch fields in a way that can be consumed as a data provider.
     *
     * @return array Returns a data provider array.
     */
    public function providePatchFields() {
        $r = [];
        foreach ($this->patchFields as $field) {
            $r[$field] = [$field];
        }
        return $r;
    }
}
