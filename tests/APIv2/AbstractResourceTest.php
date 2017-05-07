<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;


abstract class AbstractResourceTest extends AbstractAPIv2Test {
    const INDEX_ROWS = 4;

    protected $folder = '/resources';
    protected $singular = '';
    protected $record = ['body' => 'Hello world!', 'format' => 'markdown'];
    protected $patchFields = ['body', 'format'];
    protected $pk = '';

    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);

        if (empty($this->singluar)) {
            $this->singular = rtrim(ltrim($this->folder, '/'), 's');
        }
        if (empty($this->pk)) {
            $this->pk = $this->singular.'ID';
        }
    }

    /**
     * @param array $row
     * @depends testPost
     */
    public function testGet(array $row) {
        $r = $this->api()->get(
            "{$this->folder}/{$row[$this->pk]}"
        );

        $this->assertEquals(200, $r->getStatusCode());
        $this->assertRowsEqual($row, $r->getBody());
    }

    /**
     * @param array $row
     * @depends testPost
     */
    public function testGetEdit(array $row) {
        $r = $this->api()->get(
            "{$this->folder}/{$row[$this->pk]}/edit"
        );

        $this->assertEquals(200, $r->getStatusCode());
        $this->assertRowsEqual(arrayTranslate($this->record, ['name', 'body', 'format']), $r->getBody());

        return $r->getBody();
    }

    /**
     * Test record updating.
     *
     * @param array $row The row to update.
     * @depends testGetEdit
     */
    public function testPatchFull(array $row) {
        $newRow = $this->modifyRow($row);

        $r = $this->api()->patch(
            "{$this->folder}/{$row[$this->pk]}",
            $newRow
        );

        $this->assertEquals(200, $r->getStatusCode());

        $this->assertRowsEqual($newRow, $r->getBody(), true);

        return $r->getBody();
    }

    /**
     * Modify the row for update requests.
     *
     * @param array $row The row to modify.
     * @return array
     */
    protected function modifyRow(array $row) {
        $newRow = [];

        $dt = new \DateTimeImmutable();
        foreach ($this->patchFields as $key) {
            $value = $row[$key];
            if (in_array($key, ['name', 'body'])) {
                $value .= ' '.$dt->format(\DateTime::RSS);
            } elseif ($key === 'format') {
                $value = $value === 'markdown' ? 'text' : 'markdown';
            } elseif (stripos($key, 'id') === strlen($key) - 2) {
                $value++;
            }

            $newRow[$key] = $value;
        }

        return $newRow;
    }

    /**
     * Test sparse record updating.
     *
     * @param array $row The row to update.
     * @depends testGetEdit
     */
    public function testPatchSparse(array $row) {
        $newRow = $this->modifyRow($row);

        foreach ($this->patchFields as $field) {
            $r = $this->api()->patch(
                "{$this->folder}/{$row[$this->pk]}",
                [$field => $newRow[$field]]
            );

            $this->assertEquals(200, $r->getStatusCode());

            if ($field === 'body') {
                $this->assertSame(\Gdn_Format::to($newRow['body'], $newRow['format']), $r['body']);
            } elseif ($field !== 'format') {
                $this->assertSame($newRow[$field], $r[$field]);
            }
        }
    }

    /**
     * A record should be able to be deleted (by the admin).
     */
    public function testDelete() {
        $row = $this->testPost();

        $r = $this->api()->delete(
            "{$this->folder}/{$row[$this->pk]}"
        );

        $this->assertEquals(204, $r->getStatusCode());

        try {
            $this->api()->get("{$this->folder}/{$row[$this->pk]}");
            $this->fail("The {$this->singular} did not get deleted.");
        } catch (\Exception $ex) {
            $this->assertEquals(404, $ex->getCode());
            return;
        }
        $this->fail("Something odd happened while deleting a {$this->singular}.");
    }

    /**
     * Test record creation.
     *
     * @return array Returns the new record.
     */
    public function testPost() {
        $result = $this->api()->post(
            $this->folder,
            $this->record
        );

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();
        $this->assertTrue(is_int($body[$this->pk]));
        $this->assertTrue($body[$this->pk] > 0);

        $this->assertRowsEqual($this->record, $body, true);

        return $body;
    }

    public function testIndex() {
        // Insert a few rows.
        $rows = [];
        for ($i = 0; $i < static::INDEX_ROWS; $i++) {
            $rows[] = $this->testPost();
        }

        $r = $this->api()->get($this->folder);
        $this->assertEquals(200, $r->getStatusCode());

        $dbRows = $r->getBody();
        $this->assertGreaterThan(self::INDEX_ROWS, count($dbRows));
        // The index should be a proper indexed array.
        for ($i = 0; $i < count($dbRows); $i++) {
            $this->assertArrayHasKey($i, $dbRows);
        }

        // There's not much we can really test here so just return and let subclasses do some more assertions.
        return [$rows, $dbRows];
    }
}
