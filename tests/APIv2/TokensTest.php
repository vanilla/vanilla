<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use AccessTokenModel;
use Garden\Web\Exception\ForbiddenException;
use Vanilla\CurrentTimeStamp;
use Vanilla\Web\CacheControlConstantsInterface;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/tokens endpoints.
 */
class TokensTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;

    /**
     * The number of rows create when testing index endpoints.
     */
    const INDEX_ROWS = 4;

    /** @var AccessTokenModel */
    private $accessTokenModel;

    /** {@inheritdoc} */
    protected $baseUrl = "/tokens";

    /** {@inheritdoc} */
    protected $pk = "accessTokenID";

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpUsersAndRolesApiTestTrait();
        $this->accessTokenModel = static::container()->get(AccessTokenModel::class);
    }

    /**
     * Test DELETE /tokens/<id>.
     */
    public function testDelete()
    {
        $row = $this->testPost();

        $r = $this->api()->delete("{$this->baseUrl}/{$row[$this->pk]}");

        $this->assertEquals(204, $r->getStatusCode());

        try {
            $this->api()->getWithTransientKey("{$this->baseUrl}/{$row[$this->pk]}");
            $this->fail("The token was not deleted.");
        } catch (\Exception $ex) {
            // A revoked (deleted) token should return a 410 (gone).
            $this->assertEquals(410, $ex->getCode());
            return;
        }
    }

    /**
     * Test GET /tokens/<id>.
     *
     * @return array
     */
    public function testGet()
    {
        $row = $this->testPost();

        $r = $this->api()->getWithTransientKey("{$this->baseUrl}/{$row[$this->pk]}");

        $this->assertEquals(200, $r->getStatusCode());
        $this->assertEquals(CacheControlConstantsInterface::NO_CACHE, $r->getHeader("cache-control"));

        $body = $r->getBody();
        $this->assertCamelCase($body);
        $accessToken = $body["accessToken"];
        $this->assertEquals(
            $this->accessTokenModel->trim($row["accessToken"]),
            $this->accessTokenModel->trim($body["accessToken"])
        );
        unset($row["accessToken"], $body["accessToken"]);
        $this->assertRowsEqual($row, $r->getBody());

        $this->accessTokenModel->verify($accessToken, true);

        return $body;
    }

    /**
     * Test GET /tokens.
     *
     * @return array Returns the fetched data.
     */
    public function testIndex()
    {
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
    public function testPost()
    {
        $row = ["name" => "phpUnit"];
        $result = $this->api()->postWithTransientKey($this->baseUrl, $row);

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();
        $this->assertCamelCase($body);
        $this->assertTrue(is_int($body[$this->pk]));
        $this->assertTrue($body[$this->pk] > 0);
        $this->assertEquals($row["name"], $body["name"]);
        $this->assertArrayHasKey("dateInserted", $body);
        $this->assertIsInt(strtotime($body["dateInserted"]));

        $this->accessTokenModel->verify($body["accessToken"], true);

        return $body;
    }

    /**
     * Test that a POST /api/v2/tokens/roles responds with 403 Forbidden
     * as this endpoint is only available to authenticated users.
     */
    public function testPostRolesNoUser()
    {
        $this->runWithUser(function () {
            $this->expectException(ForbiddenException::class);
            $_ = $this->api()->post("tokens/roles");
        }, \UserModel::GUEST_USER_ID);
    }

    /**
     * Test that a POST /api/v2/tokens/roles responds with a success status code and
     * returns a signed JWT containing a set of role IDs that match the current user.
     * Also test that role tokens requested from different users that share the same role set
     * and requested within the same time window are identical when encoded,
     * while role tokens requested from different users that do not share the same role set
     * and requested within the same time window differ when encoded, but have a common expiration datetime.
     */
    public function testPostRoles()
    {
        $fooRole = $this->createRole([
            "name" => "foo",
            "permissions" => [
                [
                    "type" => "global",
                    "permissions" => [
                        "session.valid" => true,
                    ],
                ],
            ],
        ]);
        $barRole = $this->createRole([
            "name" => "bar",
            "permissions" => [
                [
                    "type" => "global",
                    "permissions" => [
                        "session.valid" => true,
                    ],
                ],
            ],
        ]);

        $user1 = $this->createUser(["roleID" => [$fooRole["roleID"], $barRole["roleID"]]]);
        $user2 = $this->createUser(["roleID" => [$fooRole["roleID"], $barRole["roleID"]]]);
        $user3 = $this->createUser(["roleID" => [$barRole["roleID"]]]);

        $tokenGenerator = function (): array {
            $response = $this->api()->post("tokens/roles");
            $this->assertEquals(201, $response->getStatusCode());
            $responseBody = $response->getBody();
            $this->assertArrayHasKey("roleToken", $responseBody);
            $this->assertArrayHasKey("expires", $responseBody);
            return array_values($responseBody);
        };

        $now = \DateTimeImmutable::createFromMutable(new \DateTime("2021-10-12T10:09:07Z"));
        CurrentTimeStamp::mockTime($now);

        [$user1Token, $user1TokenExpires] = (array) $this->runWithUser($tokenGenerator, $user1);

        CurrentTimeStamp::mockTime($now->add(new \DateInterval("PT10S")));
        [$user2Token, $user2TokenExpires] = (array) $this->runWithUser($tokenGenerator, $user2);

        CurrentTimeStamp::mockTime($now->add(new \DateInterval("PT20S")));
        [$user3Token, $user3TokenExpires] = (array) $this->runWithUser($tokenGenerator, $user3);

        $this->assertEquals($user1Token, $user2Token);
        $this->assertNotEquals($user1Token, $user3Token);
        $this->assertNotEquals($user2Token, $user3Token);

        $this->assertEquals($user1TokenExpires, $user2TokenExpires);
        $this->assertEquals($user2TokenExpires, $user3TokenExpires);
    }

    /**
     * Test that the `/tokens` header cache-control is set to no-cache.
     */
    public function testNoCache()
    {
        $r = $this->api()->get($this->baseUrl);
        $this->assertEquals(CacheControlConstantsInterface::NO_CACHE, $r->getHeader("cache-control"));
    }
}
