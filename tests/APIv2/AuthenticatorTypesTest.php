<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

/**
 * Class AuthenticatorTypesTest
 */
class AuthenticatorTypesTest extends AbstractAPIv2Test
{
    protected static $addons = ["OAuth2"];

    /**
     * Verify basic behavior of the authenticators index.
     */
    public function testIndex(): void
    {
        // If the OAuth2 plugin is enabled there should be a single authenticator type returned.
        $apiResponse = $this->api()->get("authenticator-types");
        $apiResponseStatusCode = $apiResponse->getStatusCode();
        $apiResponseBody = $apiResponse->getBody();
        $this->assertEquals(200, $apiResponseStatusCode);
        $this->assertCount(1, $apiResponseBody);
        $this->assertArraySubsetRecursive([["authenticatorType" => "OAuth2"]], $apiResponseBody);
    }
}
