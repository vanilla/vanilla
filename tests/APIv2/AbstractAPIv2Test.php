<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use PHPUnit\Framework\TestCase;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Web\PrivateCommunityMiddleware;
use VanillaTests\InternalClient;
use VanillaTests\SiteTestTrait;
use VanillaTests\TestLogger;

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
     * @var TestLogger
     */
    protected $logger;

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
    public function setUp(): void {
        parent::setUp();

        $this->api = static::container()->getArgs(InternalClient::class, [static::container()->get('@baseUrl').'/api/v2']);

        if ($this->startSessionOnSetup) {
            $this->setAdminApiUser();
            $this->api->setTransientKey(md5(now()));
        }

        $this->logger = new TestLogger();
        \Logger::addLogger($this->logger);
    }

    /**
     * Destroy the API client that was just used for the test.
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->api = null;
        \Logger::removeLogger($this->logger);
        $this->logger = null;
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
                        $row[$field] = \Gdn::formatService()->renderHTML($row[$field] ?? '', $row['format'] ?? TextFormat::FORMAT_KEY);
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
        $resourcePath = parse_url($resourceUrl, PHP_URL_PATH);

        $result = $this->api()->get($pagingTestUrl);
        $link = $result->getHeader('Link');
        $this->assertNotEmpty($link);
        $this->assertTrue(preg_match('/<([^;]*?'.preg_quote($resourcePath, '/').'[^>]+)>; rel="first"/', $link) === 1);
        $this->assertTrue(preg_match('/<([^;]*?'.preg_quote($resourcePath, '/').'[^>]+)>; rel="next"/', $link, $matches) === 1);

        $result = $this->api()->get(str_replace('/api/v2', '', $matches[1]));
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals(1, count($result->getBody()));
    }

    /**
     * Assert that something was logged.
     *
     * @param array $filter The log filter.
     */
    public function assertLog($filter = []) {
        $item = $this->logger->search($filter);
        $this->assertNotNull($item, "Could not find expected log: ".json_encode($filter));
    }

    /**
     * Generate a random valid username.
     *
     * @param string $prefix
     * @return string
     */
    protected static function randomUsername(string $prefix = ''): string {
        $uniqueID = preg_replace('/[^0-9A-Z_]/i', '_', uniqid($prefix, true));
        $result = substr($uniqueID, 0, 20);
        return $result;
    }

    /**
     * Set the API to the admin user.
     */
    protected function setAdminApiUser(): void {
        $this->api->setUserID(self::$siteInfo['adminUserID']);
    }

    /**
     * Assert that there is no session.
     *
     * @param bool $force End the session if there is one.
     * @return int Returns the old user ID of the session.
     */
    protected function assertNoSession(bool $force = false): int {
        $userID = $this->api()->getUserID();
        if ($force) {
            $this->api()->setUserID(0);
        }

        /* @var \Gdn_Session $session */
        $session = static::container()->get(\Gdn_Session::class);
        $this->assertFalse($session->isValid());
        return $userID;
    }

    /**
     * Run another test with private community enabled.
     *
     * @param callable $test
     * @return mixed Returns whatever the callback returns.
     */
    protected function runWithPrivateCommunity(callable $test) {
        /* @var PrivateCommunityMiddleware $middleware */
        $middleware = static::container()->get(PrivateCommunityMiddleware::class);
        $private = $middleware->isPrivate();

        try {
            $userID = $this->api()->getUserID();
            $middleware->setIsPrivate(true);
            $this->assertNoSession(true);

            return $test();
        } finally {
            $middleware->setIsPrivate($private);
            $this->api()->setUserID($userID);
        }
    }

    /**
     * Run some code with the admin user then restore the session.
     *
     * @param callable $callback The code to run.
     * @return mixed Returns whatever the callback returns.
     */
    protected function runWithAdminUser(callable $callback) {
        // Ensure there is a permission to get the user.
        $userID = $this->api()->getUserID();
        try {
            $this->setAdminApiUser();
            $r = $callback();
            return $r;
        } finally {
            $this->api()->setUserID($userID);
        }
    }
}
