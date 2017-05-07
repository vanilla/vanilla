<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use VanillaTests\InternalClient;
use VanillaTests\SiteTestTrait;

abstract class AbstractAPIv2Test extends \PHPUnit_Framework_TestCase {
    use SiteTestTrait;

    /**
     * @var InternalClient
     */
    private $api;

    /**
     * Create a fresh API client for the test.
     */
    public function setUp() {
        parent::setUp();

        $this->api = static::container()->getArgs(InternalClient::class, [static::container()->get('@baseUrl').'/api/v2']);
        $this->api->setUserID(self::$siteInfo['adminUserID']);
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

    public function assertRowsEqual(array $expected, array $actual, $format = false) {
        // Fix the body and format.
        if ($format && array_key_exists('body', $expected)) {
            $expected['body'] = \Gdn_Format::to($expected['body'], $expected['format']);
            unset($expected['format']);
        }

        $actualSparse = [];
        foreach ($expected as $key => $value) {
            $actualSparse[$key] = isset($actual[$key]) ? $actual[$key] : null;
        }

        $this->assertEquals($expected, $actualSparse);
    }
}
