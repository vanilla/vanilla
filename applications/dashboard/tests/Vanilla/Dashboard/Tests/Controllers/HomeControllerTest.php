<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Tests\Controllers;

use Garden\Web\Exception\ResponseException;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\FormatService;
use VanillaTests\SiteTestCase;

/**
 * HomeController Test
 */
class HomeControllerTest extends SiteTestCase
{
    /**
     * Test leaving platform with trusted domain.
     */
    public function testLeavingWithTrustedDomain(): void
    {
        $destinationUrl = "example.com";

        $this->runWithConfig(["Garden.TrustedDomains" => $destinationUrl], function () use ($destinationUrl) {
            // As example.com is trusted, reaching for /home/leaving should trigger a 302 redirection.
            try {
                $this->bessy()->get("/home/leaving", ["target" => url("http://example.com"), "allowTrusted" => true]);
            } catch (\Throwable $exception) {
                $exResponse = $exception->getResponse();
                $this->assertEquals(302, $exResponse->getStatus());
                $this->assertEquals("http://example.com", $exResponse->getMeta("HTTP_LOCATION"));
            }
        });
    }

    /**
     * Test leaving platform with untrusted domain.
     */
    public function testLeavingWithUntrustedDomain(): void
    {
        $destinationUrl = "http://domain.com";
        // As domain.com is not trusted, reaching for /home/leaving should display a link to the url.
        $leavingPage = $this->bessy()->getHtml("/home/leaving", [
            "target" => url($destinationUrl),
            "allowTrusted" => true,
        ]);
        $leavingPageLinkUrl = $leavingPage->assertCssSelectorExists("a")->getAttribute("href");

        // The link's href should be the same as the one provided in the target.
        $this->assertEquals($leavingPageLinkUrl, $destinationUrl);
    }

    /**
     * Test leaving platform with trusted domain while not allowing trusted redirections.
     */
    public function testLeavingWithDisallowedTrustedDomain(): void
    {
        $destinationUrl = "http://domain.com";

        $this->runWithConfig(["Garden.TrustedDomains" => $destinationUrl], function () use ($destinationUrl) {
            // As domain.com is trusted but we did not allow automatic redirection on trusted domains, there is a link.
            $leavingPage = $this->bessy()->getHtml("/home/leaving", [
                "target" => url($destinationUrl),
                "allowTrusted" => false,
            ]);
            $leavingPageLinkUrl = $leavingPage->assertCssSelectorExists("a")->getAttribute("href");

            // The link's href should be the same as the one provided in the target.
            $this->assertEquals($leavingPageLinkUrl, $destinationUrl);
        });
    }

    /**
     * Test leaving platform to a relative internal url.
     */
    public function testRelativeInternalUrl(): void
    {
        $destinationUrl = "/discussions";
        // Trying to reach a relative internal url should trigger an error.
        $this->expectException(\Gdn_UserException::class);
        $this->bessy()->getHtml("/home/leaving", ["target" => $destinationUrl]);
    }

    /**
     * Test leaving platform with trusted domain.
     */
    public function testLeavingWithoutWarnLeaving(): void
    {
        $destinationUrl = "http://example.com";

        $this->runWithConfig(["Garden.Format.WarnLeaving" => false], function () use ($destinationUrl) {
            // As example.com is trusted, reaching for /home/leaving should trigger a 302 redirection.
            try {
                $this->bessy()->get("/home/leaving", ["target" => url("http://example.com"), "allowTrusted" => true]);
            } catch (\Throwable $exception) {
                $exResponse = $exception->getResponse();
                $this->assertEquals(302, $exResponse->getStatus());
                $this->assertEquals("http://example.com", $exResponse->getMeta("HTTP_LOCATION"));
            }
        });
    }
}
