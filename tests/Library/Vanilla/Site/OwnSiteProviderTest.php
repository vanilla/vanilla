<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Site;

use Garden\Sites\Exceptions\SiteNotFoundException;
use Vanilla\Contracts\Site\VanillaSiteProvider;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Site\OwnSite;
use Vanilla\Site\OwnSiteProvider;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Tests for the builtin own site provider.
 */
class OwnSiteProviderTest extends AbstractAPIv2Test
{
    /**
     * @return OwnSiteProvider
     */
    protected function getProvider(): VanillaSiteProvider
    {
        return $this->container()->get(VanillaSiteProvider::class);
    }

    /**
     * Test our OwnSite instance.
     */
    public function testOwnSite()
    {
        \Gdn::config()->saveToConfig([
            "Garden.Title" => "Hello Title",
            "Vanilla.SiteID" => 105,
            "Vanilla.AccountID" => 100,
        ]);
        self::container()->setInstance(OwnSite::class, null);

        $provider = $this->getProvider();

        $ownSite = $provider->getOwnSite();
        $this->assertInstanceOf(OwnSite::class, $ownSite);
        $this->assertEquals(105, $ownSite->getSiteID());
        $this->assertEquals(100, $ownSite->getAccountID());

        $crumbs = $ownSite->toBreadcrumbs();
        $this->assertEquals(
            [new Breadcrumb("Hello Title", "https://vanilla.test/" . static::getBootstrapFolderName())],
            $crumbs
        );

        // Make sure our local http client works.
        $response = $ownSite->getHttpClient()->get("/api/v2/discussions");
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test adding and clearing sites.
     */
    public function testGettingSites()
    {
        \Gdn::config()->saveToConfig([
            "Vanilla.SiteID" => 105,
            "Vanilla.AccountID" => 100,
        ]);
        self::container()->setInstance(OwnSite::class, null);
        self::container()->setInstance(VanillaSiteProvider::class, null);

        $provider = $this->getProvider();
        $accountSites = $provider->getByAccountID(100);
        $this->assertCount(1, $accountSites);
        $this->assertInstanceOf(OwnSite::class, $accountSites[0]);

        $siteIDSite = $provider->getSite(105);
        $this->assertInstanceOf(OwnSite::class, $siteIDSite);
    }

    /**
     * Test the http client on an unknown site.
     */
    public function testUnknownSiteHttp()
    {
        $provider = $this->getProvider();
        $this->expectException(SiteNotFoundException::class);
        $unknown = $provider->getSite(314124);
    }
}
