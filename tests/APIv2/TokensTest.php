<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use AccessTokenModel;

/**
 * Test the /api/v2/tokens endpoints.
 */
class TokensTest extends AbstractAPIv2Test {

    /**
     * The number of rows create when testing index endpoints.
     */
    const INDEX_ROWS = 4;

    /** @var AccessTokenModel */
    private $accessTokenModel;

    /** {@inheritdoc} */
    protected $baseUrl = '/tokens';

    /** {@inheritdoc} */
    protected $pk = 'accessTokenID';

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
        parent::setUp();
        $this->accessTokenModel = static::container()->get(AccessTokenModel::class);
    }

    /**
     * Test DELETE /tokens/<id>.
     */
    public function testDelete() {
        $row = $this->testPost();

        $r = $this->api()->delete(
            "{$this->baseUrl}/{$row[$this->pk]}"
        );

        $this->assertEquals(204, $r->getStatusCode());

        try {
            $this->api()->getWithTransientKey("{$this->baseUrl}/{$row[$this->pk]}");
            $this->fail('The token was not deleted.');
        } catch (\Exception $ex) {
            // A revoked (deleted) token should return a 410 (gone).
            $this->assertEquals(410, $ex->getCode());
            return;
        }
        $this->fail('Something odd happened while deleting a token');
    }

    /**
     * Test GET /tokens/<id>.
     *
     * @return array
     */
    public function testGet() {
        $row = $this->testPost();

        $r = $this->api()->getWithTransientKey(
            "{$this->baseUrl}/{$row[$this->pk]}"
        );

        $this->assertEquals(200, $r->getStatusCode());

        $body = $r->getBody();
        $this->assertCamelCase($body);
        $accessToken = $body['accessToken'];
        $this->assertEquals(
            $this->accessTokenModel->trim($row['accessToken']),
            $this->accessTokenModel->trim($body['accessToken'])
        );
        unset($row['accessToken'], $body['accessToken']);
        $this->assertRowsEqual($row, $r->getBody());

        $this->accessTokenModel->verify($accessToken, true);

        return $body;
    }

    /**
     * Test GET /tokens.
     *
     * @return array Returns the fetched data.
     */
    public function testIndex() {
        // Insert a few rows.
        $rows = [];
        for ($i = 0; $i < static::INDEX_ROWS; $i++) {
            $rows[] = $this->testPost();
        }

        $r = $this->api()->get($this->baseUrl);
        $this->assertEquals(200, $r->getStatusCode());

        $dbRows = $r->getBody();
        $this->assertGreaterThan(self::INDEX_ROWS, count($dbRows));

        // The index should be a proper indexed array.
        for ($i = 0; $i < count($dbRows); $i++) {
            $this->assertArrayHasKey($i, $dbRows);
        }

        return [$rows, $dbRows];
    }

    /**
     * Test POST /tokens.
     *
     * @return array
     */
    public function testPost() {
        $row = ['name' => 'phpUnit'];
        $result = $this->api()->postWithTransientKey(
            $this->baseUrl,
            $row
        );

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();
        $this->assertCamelCase($body);
        $this->assertTrue(is_int($body[$this->pk]));
        $this->assertTrue($body[$this->pk] > 0);
        $this->assertEquals($row['name'], $body['name']);
        $this->assertArrayHasKey('dateInserted', $body);
        $this->assertInternalType('int', strtotime($body['dateInserted']));

        $this->accessTokenModel->verify($body['accessToken'], true);

        return $body;
    }
}
