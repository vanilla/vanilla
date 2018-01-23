<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use PHPUnit\Framework\TestCase;
use Vanilla\Utility\CamelCaseScheme;
use VanillaTests\InternalClient;
use VanillaTests\SiteTestTrait;

abstract class AbstractAPIv2Test extends TestCase {
    use SiteTestTrait;

    /**
     * @var InternalClient
     */
    private $api;

    /**
     * @var bool Set to false before setUp() to skip the session->start();
     */
    private $startSessionOnSetup = true;

    /**
     * @var array Fields that are getting formatted using the format column.
     */
    protected $formattedFields = ['body'];

    /**
     * Whether to start a session on setUp() or not.
     *
     * @param bool $enabled
     */
    protected function startSessionOnSetup($enabled) {
        $this->startSessionOnSetup = $enabled;
    }

    /**
     * Create a fresh API client for the test.
     */
    public function setUp() {
        parent::setUp();

        $this->api = static::container()->getArgs(InternalClient::class, [static::container()->get('@baseUrl').'/api/v2']);

        if ($this->startSessionOnSetup) {
            $this->api->setUserID(self::$siteInfo['adminUserID']);
            $this->api->setTransientKey(md5(now()));
        }

    }

    /**
     * Destroy the API client that was just used for the test.
     */
    public function tearDown() {
        parent::tearDown();
        $this->api = null;
    }

    /**
     * Get the API client for internal requests.
     *
     * @return InternalClient Returns the API client.
     */
    public function api() {
        return $this->api;
    }

    public function assertRowsEqual(array $expected, array $actual) {
        // Fix formatted fields.
        foreach([&$expected, &$actual] as &$row) {
            if (array_intersect(array_keys($row), $this->formattedFields) && array_key_exists('format', $row)) {
                foreach ($this->formattedFields as $field) {
                    if (array_key_exists($field, $row)) {
                        $row[$field] = \Gdn_Format::to($row[$field], $row['format']);
                    }
                }
                unset($row['format']);
            }
        }

        $actualSparse = [];
        foreach ($expected as $key => $value) {
            $actualSparse[$key] = isset($actual[$key]) ? $actual[$key] : null;
        }

        $this->assertEquals($expected, $actualSparse);
    }

    /**
     * Assert that an array has camel case keys which is required for API  v2.
     *
     * @param array $array The array to check.
     * @param string $path The current path for recursive calls.
     */
    public function assertCamelCase(array $array, $path = '') {
        $camel = new CamelCaseScheme();

        foreach ($array as $key => $value) {
            $fullKey = trim($path.'/'.$key, '/');
            if (!is_numeric($key) && !$camel->valid($key)) {
                $this->fail("The $fullKey key is not camel case.");
            }

            if (is_array($value)) {
                $this->assertCamelCase($value, $fullKey);
            }
        }
    }

    /**
     * Test paging on a resource. Must have at least 2 records.
     *
     * @param $resourceUrl
     */
    protected function pagingTest($resourceUrl) {
        $pagingTestUrl = $resourceUrl.(strpos($resourceUrl, '?') === false ? '?' : '&').'limit=1';

        $result = $this->api()->get($pagingTestUrl);
        $this->assertNotEmpty($result->getHeader('Paging-First'));
        $this->assertNotEmpty($result->getHeader('Paging-Next'));

        $result = $this->api()->get(str_replace('/api/v2', '', $result->getHeader('Paging-Next')));
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals(1, count($result->getBody()));
    }
}
