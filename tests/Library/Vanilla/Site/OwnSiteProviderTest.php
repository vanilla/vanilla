<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Site;

use Garden\Web\Exception\NotFoundException;
use Vanilla\Contracts\Site\AbstractSiteProvider;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Site\OwnSite;
use Vanilla\Site\OwnSiteProvider;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Tests for the builtin own site provider.
 */
class OwnSiteProviderTest extends AbstractAPIv2Test {

    /**
     * @return OwnSiteProvider
     */
    protected function getProvider(): AbstractSiteProvider {
        return $this->container()->get(AbstractSiteProvider::class);
    }

    /**
     * Test our OwnSite instance.
     */
    public function testOwnSite() {
        \Gdn::config()->saveToConfig([
            'Garden.Title' => 'Hello Title',
            'Vanilla.SiteID' => 105,
            'Vanilla.AccountID' => 100,
        ]);

        $provider = $this->getProvider();

        $ownSite = $provider->getOwnSite();
        $this->assertInstanceOf(OwnSite::class, $ownSite);
        $this->assertEquals(105, $ownSite->getSiteID());
        $this->assertEquals(100, $ownSite->getAccountID());

        $crumbs = $ownSite->toBreadcrumbs();
        $this->assertEquals([
            new Breadcrumb(
                'Hello Title',
                'http://vanilla.test/' . static::getBootstrapFolderName()
            ),
        ], $crumbs);

            // Make sure our local http client works.
        $response = $ownSite->getHttpClient()->get('/api/v2/discussions');
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test adding and clearing sites.
     */
    public function testGettingSites() {
        \Gdn::config()->saveToConfig([
            'Vanilla.SiteID' => 105,
            'Vanilla.AccountID' => 100,
        ]);

        $provider = $this->getProvider();
        $accountSites = $provider->getByAccountID(100);
        $this->assertCount(1, $accountSites);
        $this->assertInstanceOf(OwnSite::class, $accountSites[0]);

        $siteIDSite = $provider->getBySiteID(105);
        $this->assertInstanceOf(OwnSite::class, $siteIDSite);

        $provider = $this->getProvider();
        $siteIDsSites = $provider->getBySiteIDs([105, 100000]);
        $this->assertCount(2, $siteIDsSites);
        $this->assertInstanceOf(OwnSite::class, $siteIDsSites[0]);
        $this->assertEquals('Unknown Site', $siteIDsSites[1]->getName());
        $this->assertEquals(-1, $siteIDsSites[1]->getSiteID());
    }

    /**
     * Test the http client on an unknown site.
     */
    public function testUnknownSiteHttp() {
        $provider = $this->getProvider();
        $unknown = $provider->getBySiteID(314124);

        $this->expectException(NotFoundException::class);
        $unknown->getHttpClient()->get('/api/v2/discussions');
    }
}
