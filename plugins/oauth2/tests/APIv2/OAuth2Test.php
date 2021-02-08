<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;

/**
 * Test the /api/v2/authenticators/oauth2 and /api/v2/authenticators/:id/oauth2 endpoints.
 */
class OAuth2Test extends AbstractAPIv2Test {

    protected static $addons = ["oauth2"];

    /** @var string[] */
    private $patchFields = ["clientID", "secret", "urls.authorizeUrl"];

    /**
     * Modify fields in a row.
     *
     * @param array $row
     * @return array
     */
    private function modifyRow(array $row): array {
        $row["clientID"] = md5($row["clientID"]);
        $row["secret"] = md5($row["secret"]);
        $row["urls"]["authorizeUrl"] = "https://example.com/authorize/?clientID=" . $row["clientID"];
        return $row;
    }

    /**
     * Test getting a single authenticator.
     */
    public function testGet(): void {
        $row = $this->testPost();
        $rowID = $row["authenticatorID"];

        $response = $this->api()->get("authenticators/{$rowID}/oauth2");
        $this->assertSame(200, $response->getStatusCode());
        $this->assertCamelCase($response->getBody());
    }

    /**
     * Test getting an invalid authenticator.
     */
    public function testGetInvalid(): void {
        $this->expectExceptionMessage(NotFoundException::class);
        $this->expectExceptionMessage("Authenticator not found.");
        $badID = PHP_INT_MAX;
        $this->api()->get("authenticators/{$badID}/oauth2");
    }

    /**
     * Verify updating a whole record at once.
     */
    public function testPatch(): void {
        $dbRow = $this->testPost();
        $rowID = $dbRow["authenticatorID"];

        $row = $this->api()->get("authenticators/{$rowID}/oauth2")->getBody();
        $fields = $this->modifyRow($row);
        $expected = array_intersect_key($fields, array_flip($this->patchFields));
        $response = $this->api()->patch(
            "authenticators/{$rowID}/oauth2",
            $fields
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArraySubsetRecursive($expected, $response->getBody());
    }

    /**
     * Test patching an invalid authenticator.
     */
    public function testPatchInvalid(): void {
        $this->expectExceptionMessage(NotFoundException::class);
        $this->expectExceptionMessage("Authenticator not found.");
        $badID = PHP_INT_MAX;
        $this->api()->patch("authenticators/{$badID}/oauth2");
    }

    /**
     * Verify ability to create a new OAuth2 authenticator.
     *
     * @param array $body
     * @return array
     */
    public function testPost(array $body = []): array {
        static $id = 0;

        $id++;
        $body += [
            "name" => __FUNCTION__ . " ({$id})",
            "clientID" => "clientID-{$id}",
            "secret" => "secret123",
            "urls" => [
                "authorizeUrl" => "https://example.com/authorize",
                "profileUrl" => "https://example.com/profile",
                "tokenUrl" => "https://example.com/token",
            ],
        ];
        $response = $this->api()->post("authenticators/oauth2", $body);
        $this->assertSame(201, $response->getStatusCode());

        $result = $response->getBody();
        $this->assertArraySubsetRecursive($body, $response->getBody());
        return $result;
    }

    /**
     * Verify inability to create two connections with the same client ID.
     */
    public function testPostDuplicate(): void {
        $body = ["clientID" => __FUNCTION__];
        $this->testPost($body);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("An authenticator with this clientID already exists.");
        $this->testPost($body);
    }
}
