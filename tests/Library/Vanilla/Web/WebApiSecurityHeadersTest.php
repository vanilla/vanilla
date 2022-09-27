<?php
/**
 * @author Gary Pomerant <gpomerant@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Container\Container;
use Vanilla\AddonManager;
use Vanilla\Models\AddonModel;
use VanillaTests\Fixtures\TestAddonManager;
use VanillaTests\SiteTestCase;

/**
 * Tests for the response security headers controller.
 */
class WebApiSecurityHeadersTest extends SiteTestCase
{
    /**
     * Test that all headers added to Web response by default.
     */
    public function testDefaultSecurityHeadersWebResponse()
    {
        $response = $this->bessy()->getJsonData("/");
        $this->assertEquals("max-age=15768000", $response->getHeader("Strict-Transport-Security"));
        $this->assertEquals("nosniff", $response->getHeader("X-Content-Type-Options"));
        $this->assertEquals("master-only", $response->getHeader("X-Permitted-Cross-Domain-Policies"));
        $this->assertEquals("0", $response->getHeader("X-XSS-Protection"));
    }

    /**
     * Test that all headers added to Api response by default.
     */
    public function testDefaultSecurityHeadersApiResponse()
    {
        $response = $this->api()->get("/resources");
        $this->assertEquals("max-age=15768000", $response->getHeader("Strict-Transport-Security"));
        $this->assertEquals("nosniff", $response->getHeader("X-Content-Type-Options"));
        $this->assertEquals("master-only", $response->getHeader("X-Permitted-Cross-Domain-Policies"));
        $this->assertEquals("0", $response->getHeader("X-XSS-Protection"));
    }

    /**
     * Test that modified TTL and configs on API strict transport header applies.
     */
    public function testConfigApiStrictTransportMaxAgeResponse()
    {
        $securityHeaderConfig["Garden.Security.Hsts.MaxAge"] = 999999;
        $securityHeaderConfig["Garden.Security.Hsts.IncludeSubDomains"] = true;
        $this->runWithConfig($securityHeaderConfig, function () {
            $response = $this->api()->get("/resources");
            $this->assertEquals("max-age=999999; includeSubDomains", $response->getHeader("Strict-Transport-Security"));
        });
    }

    /**
     * Test that modified TTL on Web strict transport header applies.
     */
    public function testConfigWebStrictTransportMaxAgeResponse()
    {
        $securityHeaderConfig["Garden.Security.Hsts.MaxAge"] = 999999;
        $this->runWithConfig($securityHeaderConfig, function () {
            $response = $this->bessy()->getJsonData("/");
            $this->assertEquals("max-age=999999", $response->getHeader("Strict-Transport-Security"));
        });
    }
}
