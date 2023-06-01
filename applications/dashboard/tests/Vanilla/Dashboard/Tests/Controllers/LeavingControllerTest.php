<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Tests\Controllers;

use VanillaTests\SiteTestCase;

/**
 * LeavingController Test
 */
class LeavingControllerTest extends SiteTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->api()->setBaseUrl("");
    }

    /**
     * Test leaving platform with trusted domain.
     */
    public function testLeavingWithTrustedDomain(): void
    {
        $destinationUrl = "example.com";

        $this->runWithConfig(["Garden.TrustedDomains" => $destinationUrl], function () use ($destinationUrl) {
            // As example.com is trusted, reaching for /home/leaving should trigger a 302 redirection.
            $this->assertRedirectsTo("http://" . $destinationUrl, 302, function () use ($destinationUrl) {
                $this->api()->get("/home/leaving", [
                    "target" => "http://" . $destinationUrl,
                    "allowTrusted" => true,
                ]);
            });
        });
    }

    /**
     * Test leaving platform with trusted domain.
     */
    public function testLeavingWithUntrustedDomain(): void
    {
        $this->runWithConfig(["Garden.TrustedDomains" => ""], function () {
            // As example.com is trusted, reaching for /home/leaving should trigger a 302 redirection.
            $response = $this->api()->get("/home/leaving", [
                "target" => "http://not-trusted.com",
                "allowTrusted" => true,
            ]);
            $this->assertEquals(200, $response->getStatusCode());
        });
    }

    /**
     * Test untrusted domain with Garden.Format.WarnLeaving off.
     */
    public function testLeavingUntrustedDomainWithoutWarnLeaving(): void
    {
        $this->runWithConfig(["Garden.Format.WarnLeaving" => false], function () {
            $destinationUrl = "http://domain.com";
            $this->expectExceptionCode(404);
            // With this feature off untrusted domain should redirect to 404 error page.
            $this->api()->get("/home/leaving", ["target" => $destinationUrl, "allowTrusted" => true]);
        });
    }
}
