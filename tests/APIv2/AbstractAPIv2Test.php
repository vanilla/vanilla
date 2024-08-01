<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Web\PrivateCommunityMiddleware;
use Vanilla\Web\RoleTokenAuthTrait;
use VanillaTests\SiteTestCase;

/**
 * Base API test case.
 */
abstract class AbstractAPIv2Test extends SiteTestCase
{
    use RoleTokenAuthTrait;

    /**
     * @var bool Set to false before setUp() to skip the session->start();
     */
    private $startSessionOnSetup = true;

    /**
     * @var array Fields that are getting formatted using the format column.
     */
    protected $formattedFields = ["body"];

    /**
     * Whether to start a session on setUp() or not.
     *
     * @param bool $enabled
     */
    protected function startSessionOnSetup($enabled)
    {
        $this->startSessionOnSetup = $enabled;
    }

    /**
     * Create a fresh API client for the test.
     */
    public function setUp(): void
    {
        parent::setUp();
        if ($this->startSessionOnSetup) {
            $this->setAdminApiUser();
            $this->api->setTransientKey(md5(now()));
        }
    }

    /**
     * Destroy the API client that was just used for the test.
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->api = null;
    }

    public function assertRowsEqual(array $expected, array $actual)
    {
        // Fix formatted fields.
        foreach ([&$expected, &$actual] as &$row) {
            if (array_intersect(array_keys($row), $this->formattedFields) && array_key_exists("format", $row)) {
                foreach ($this->formattedFields as $field) {
                    if (array_key_exists($field, $row)) {
                        $row[$field] = \Gdn::formatService()->renderHTML(
                            $row[$field] ?? "",
                            $row["format"] ?? TextFormat::FORMAT_KEY
                        );
                    }
                }
                unset($row["format"]);
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
    public function assertCamelCase(array $array, $path = "")
    {
        $camel = new CamelCaseScheme();

        foreach ($array as $key => $value) {
            $fullKey = trim($path . "/" . $key, "/");
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
    protected function pagingTest($resourceUrl)
    {
        $pagingTestUrl = $resourceUrl . (strpos($resourceUrl, "?") === false ? "?" : "&") . "limit=1";
        $resourcePath = parse_url($resourceUrl, PHP_URL_PATH);

        $result = $this->api()->get($pagingTestUrl, ["pinOrder" => "mixed"]);
        $link = $result->getHeader("Link");
        $this->assertNotEmpty($link);
        $this->assertTrue(
            preg_match("/<([^;]*?" . preg_quote($resourcePath, "/") . '[^>]+)>; rel="first"/', $link) === 1
        );
        $this->assertTrue(
            preg_match("/<([^;]*?" . preg_quote($resourcePath, "/") . '[^>]+)>; rel="next"/', $link, $matches) === 1
        );

        // Ensure we are getting full url
        $parsedMatch = parse_url($matches[1]);
        $this->assertTrue($parsedMatch["scheme"] === "http" || $parsedMatch["scheme"] === "https");

        $result = $this->api()->get($resourcePath . "?" . $parsedMatch["query"]);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals(1, count($result->getBody()));
    }

    /**
     * Generate a random valid username.
     *
     * @param string $prefix
     * @return string
     */
    protected static function randomUsername(string $prefix = ""): string
    {
        $uniqueID = preg_replace("/[^0-9A-Z_]/i", "_", uniqid($prefix, true));
        $result = substr($uniqueID, 0, 20);
        return $result;
    }

    /**
     * Set the API to the admin user.
     */
    protected function setAdminApiUser(): void
    {
        $this->api->setUserID(self::$siteInfo["adminUserID"]);
    }

    /**
     * Assert that there is no session.
     *
     * @param bool $force End the session if there is one.
     * @return int Returns the old user ID of the session.
     */
    protected function assertNoSession(bool $force = false): int
    {
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
    protected function runWithPrivateCommunity(callable $test)
    {
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
    protected function runWithAdminUser(callable $callback)
    {
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

    /**
     * Execute api request and check results.
     *
     * @param string $api API endpoint to call
     * @param array $params API params to pass
     * @param array $expectedFields Mapping of expectedField => expectedValues.
     * @param bool $strictOrder Whether or not the fields should be returned in a strict order.
     * @param int|null $count Expected count of result items
     */
    public function assertApiResults(
        string $api,
        array $params,
        array $expectedFields,
        bool $strictOrder = false,
        int $count = null
    ) {
        $response = $this->api()->get($api, $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        foreach ($expectedFields as $expectedField => $expectedValues) {
            if ($expectedValues === null) {
                foreach ($results as $result) {
                    $this->assertArrayNotHasKey($expectedField, $result);
                }
            } else {
                // In case value of type 'field.subfield' is passed, explode the string, and drill down the array tree for values to compare.
                if (str_contains($expectedField, ".")) {
                    $parts = explode(".", $expectedField);
                    $actualResults = $results;
                    //Dig down to the value in the array of arrays
                    foreach ($parts as $part) {
                        $actualResults = array_column($actualResults, $part);
                    }
                    $actualValues = $actualResults;
                } else {
                    $actualValues = array_column($results, $expectedField);
                }
                if (!$strictOrder) {
                    sort($actualValues);
                    sort($expectedValues);
                }

                $this->assertEquals($expectedValues, $actualValues);
            }
        }

        if (is_int($count)) {
            $this->assertEquals($count, count($results));
        }
    }

    /**
     * Get the response from the role token endpoint associated to the current user. Note that the role token issued
     * is time-constrained so the tests that utilize this token must pass this token prior to its expiration,
     * i.e. within two minutes or so, otherwise the test will fail due to token expiration.
     *
     * @return array Associative single element array containing the role token's query param name as the key
     * and the encoded role token JWT as the value.
     * @throws \Garden\Container\ContainerException Container Exception.
     * @throws \Garden\Container\NotFoundException Not Found Exception.
     */
    public function getRoleTokenResponseBody(): array
    {
        /* @var \Gdn_Session $session */
        $session = static::container()->get(\Gdn_Session::class);
        $this->assertTrue($session->isValid(), "Cannot obtain a role token without a user specified in the session");
        $tokenResponse = $this->api()->post("/tokens/roles");
        $this->assertTrue($tokenResponse->isSuccessful());
        return $tokenResponse->getBody();
    }
}
