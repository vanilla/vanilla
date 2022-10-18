<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Web\Exception\ClientException;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Tests for the jsconnect post and patch endpoints
 */
class JsConnectApiTest extends AbstractAPIv2Test
{
    protected static $addons = ["jsconnect"];

    public function setUp(): void
    {
        parent::setUp();
        \Gdn::sql()->truncate("UserAuthenticationProvider");
    }

    /**
     * Test that the /authenticator-types endpoint contains the jsconnect authenticator type
     *
     * @return void
     */
    public function testAuthenticatorTypesEndpointReturnsJsConnect()
    {
        $response = $this->api()->get("authenticator-types");
        $status = $response->getStatusCode();
        $body = $response->getBody();
        $this->assertEquals(200, $status);
        $this->assertCount(1, $body);
        $this->assertSame("JsConnect", $body[0]["authenticatorType"]);
    }

    /**
     * Tests the patch endpoint for creating a jsconnect authenticator
     *
     * @param array $data
     * @param string|null $expectedException Expected exception class
     * @param string[]|null $expectedExceptionMessages Expected exception messages as regex strings
     * @return void
     * @dataProvider provideJsConnectData
     */
    public function testPost(array $data, ?string $expectedException = null, ?array $expectedExceptionMessages = null)
    {
        if (isset($expectedException)) {
            $this->expectException($expectedException);
        }
        if (isset($expectedExceptionMessages)) {
            array_map([$this, "expectExceptionMessageMatches"], $expectedExceptionMessages);
        }
        $response = $this->api()->post("authenticators", $data);
        $status = $response->getStatusCode();
        $body = $response->getBody();
        $this->assertEquals(201, $status);
        $this->assertEquals($data["name"], $body["name"]);
        $this->assertEquals($data["type"], $body["type"]);
    }

    /**
     * Tests the patch endpoint for updating a jsconnect authenticator
     *
     * @param array $patchData
     * @param string|null $expectedException Expected exception class
     * @param string[]|null $expectedExceptionMessages Expected exception messages as regex strings
     * @return void
     * @dataProvider provideJsConnectData
     */
    public function testPatch(
        array $patchData,
        ?string $expectedException = null,
        ?array $expectedExceptionMessages = null
    ) {
        $postData = $this->validJsConnectData("patch");
        $response = $this->api()->post("authenticators", $postData);
        $status = $response->getStatusCode();
        $body = $response->getBody();
        $this->assertEquals(201, $status);

        if (isset($expectedException)) {
            $this->expectException($expectedException);
        }
        if (isset($expectedExceptionMessages)) {
            array_map([$this, "expectExceptionMessageMatches"], $expectedExceptionMessages);
        }

        $patchResponse = $this->api()->patch("authenticators/" . $body["authenticatorID"], $patchData);
        $this->assertEquals(200, $patchResponse->getStatusCode());
    }

    /**
     * Provides test data for post and patch endpoints
     *
     * @return array
     */
    public function provideJsConnectData(): array
    {
        return [
            "Test valid data #1" => [$this->validJsConnectData("1")],
            "Test valid data #2" => [
                [
                    "name" => "jsConnect Test",
                    "type" => "jsconnect",
                    "clientID" => "jsconnect_client_2",
                    "default" => false,
                    "active" => true,
                    "visible" => false,
                    "secret" => "jsconnect_secret",
                    "urls" => [
                        "signInUrl" => "https://example.com/signin",
                        "signOutUrl" => "https://example.com/signout",
                        "authenticateUrl" => "https://example.com/sso",
                        "registerUrl" => "https://example.com/register",
                        "passwordUrl" => "https://example.com/password",
                        "profileUrl" => "https://example.com/profile",
                    ],
                    "authenticatorConfig" => [
                        "Protocol" => "v2",
                        "HashType" => "sha256",
                        "Trusted" => true,
                        "TestMode" => true,
                    ],
                ],
            ],
            "Test invalid data" => [
                [
                    "name" => "jsConnect Test",
                    "type" => "jsconnect",
                    "clientID" => "jsconnect_client_3",
                    "default" => false,
                    "active" => true,
                    "visible" => false,
                    "secret" => "jsconnect_secret",
                    "urls" => [
                        "signInUrl" => "https://example.com/signin",
                        "signOutUrl" => "https://example.com/signout",
                        "authenticateUrl" => "https://example.com/sso",
                        "registerUrl" => "https://example.com/register",
                        "passwordUrl" => "https://example.com/password",
                        "profileUrl" => "https://example.com/profile",
                    ],
                    "authenticatorConfig" => [
                        "Protocol" => "v9000",
                        "HashType" => "!doesntexist",
                        "Trusted" => "abc",
                        "TestMode" => "def",
                    ],
                ],
                ClientException::class,
                [
                    "/Protocol must be one of/i",
                    "/HashType must be one of/i",
                    "/Trusted is not a valid boolean/i",
                    "/Testmode is not a valid boolean/i",
                ],
            ],
        ];
    }

    /**
     * A reusable function which provides a set of valid JsConnect data
     *
     * @param string $suffix
     * @return array
     */
    private function validJsConnectData(string $suffix): array
    {
        return [
            "name" => "jsConnect Test",
            "type" => "jsconnect",
            "clientID" => "jsconnect_client_$suffix",
            "default" => false,
            "active" => true,
            "visible" => false,
            "secret" => "jsconnect_secret",
            "urls" => [
                "signInUrl" => "https://example.com/signin",
                "signOutUrl" => "https://example.com/signout",
                "authenticateUrl" => "https://example.com/sso",
                "registerUrl" => "https://example.com/register",
                "passwordUrl" => "https://example.com/password",
                "profileUrl" => "https://example.com/profile",
            ],
            "authenticatorConfig" => [
                "Protocol" => "v3",
                "HashType" => "md5",
                "Trusted" => false,
                "TestMode" => false,
            ],
        ];
    }
}
