<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use ReactionModel;

/**
 * Test the /api/v2/reactions endpoints.
 */
class ReactionsTest extends AbstractResourceTest {

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/reactions';
        $this->patchFields = ['active', 'name', 'description', 'class', 'points'];
        $this->pk = 'urlCode';

        parent::__construct($name, $data, $dataName);
    }

    /**
     * Verify a row represents a valid reaction type row from an API response.
     *
     * @param array $row
     * @return bool
     */
    private function isReactionType(array $row) {
        $result = true;

        if (!array_key_exists('urlCode', $row) || !is_string($row['urlCode'])) {
            $result = false;
        } elseif (!array_key_exists('name', $row) || !is_string($row['name'])) {
            $result = false;
        } elseif (!array_key_exists('description', $row) || !is_string($row['description'])) {
            $result = false;
        } elseif (!array_key_exists('points', $row) || !is_int($row['points'])) {
            $result = false;
        } elseif (!array_key_exists('class', $row) || !is_string($row['class'])) {
            $result = false;
        } elseif (!array_key_exists('tagID', $row) || !is_int($row['tagID'])) {
            $result = false;
        } elseif (!array_key_exists('attributes', $row) || (!is_array($row['attributes']) && $row['attributes'] !== null)) {
            $result = false;
        } elseif (!array_key_exists('sort', $row) || !is_int($row['sort'])) {
            $result = false;
        } else if (!array_key_exists('active', $row) || !is_bool($row['active'])) {
            $result = false;
        } else if (!array_key_exists('custom', $row) || !is_bool($row['custom'])) {
            $result = false;
        } elseif (!array_key_exists('hidden', $row) || !is_bool($row['hidden'])) {
            $result = false;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyRow(array $row) {
        $row['active'] = !$row['active'];
        $row['name'] = md5($row['name']);
        $row['description'] = md5($row['name']);
        $row['points']++;

        if ($row['class'] === 'Positive') {
            $row['class'] = 'Negative';
        } else {
            $row['class'] = 'Positive';
        }

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function setup(): void {
        ReactionModel::$ReactionTypes = null;
        parent::setUp();
    }

    /**
     * Setup routine, run before the test class is instantiated.
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['reactions', 'vanilla'];
        parent::setUpBeforeClass();
    }

    /**
     * {@inheritdoc}
     * @requires function ReactionsApiController::delete
     */
    public function testDelete() {
        $this->fail(__METHOD__.' needs to be implemented.');
    }

    /**
     * {@inheritdoc}
     */
    public function testIndex() {
        $response = $this->api()->get($this->indexUrl());
        $this->assertEquals(200, $response->getStatusCode());

        $rows = $response->getBody();
        $this->assertNotEmpty($rows);
        foreach ($rows as $reactionType) {
            $this->assertTrue($this->isReactionType($reactionType), 'Response contains invalid reaction type objects.');
        }

        return $rows;
    }

    /**
     * {@inheritdoc}
     * @depends testIndex
     */
    public function testGet() {
        $index = $this->testIndex();
        $reactionType = reset($index);
        $urlCode = strtolower($reactionType['urlCode']);

        $response = $this->api()->get("{$this->baseUrl}/{$urlCode}");
        $row = $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCamelCase($response->getBody());

        $this->assertTrue($this->isReactionType($row), 'Response is not a valid reaction type object.');

        return $row;
    }

    /**
     * {@inheritdoc}
     * @depends testGet
     */
    public function testGetEdit($record = null) {
        return parent::testGetEdit($this->testGet());
    }

    /**
     * {@inheritdoc}
     * @requires function ReactionsApiController::post
     */
    public function testEditFormatCompat($record = null, array $extra = []) {
        $this->fail(__METHOD__.' needs to be implemented.');
    }

    /**
     * {@inheritdoc}
     * @requires function ReactionsApiController::post
     */
    public function testPost($record = null, array $extra = []) {
        $this->fail(__METHOD__.' needs to be implemented.');
    }

    /**
     * {@inheritdoc}
     * @requires function ReactionsApiController::post
     */
    public function testPostBadFormat(): void {
        parent::testPostBadFormat();
    }
}
