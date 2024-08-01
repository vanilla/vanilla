<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Site;

use Garden\Sites\Site;
use Vanilla\Contracts\Site\VanillaSite;
use Vanilla\Site\OwnSiteProvider;

/**
 * Mock site provider for tests.
 */
class MockSiteProvider extends OwnSiteProvider
{
    /** @var array<int, VanillaSite> */
    private array $mockSites = [];

    /**
     * Apply a mock site.
     *
     * @param VanillaSite $site
     */
    public function addMockSite(VanillaSite $site): void
    {
        $this->mockSites[$site->getSiteID()] = $site;
    }

    /**
     * @return array
     */
    public function loadAllSiteRecords(): array
    {
        $result = [
            $this->getOwnSite()->getSiteID() => $this->getOwnSite()->getSiteRecord(),
        ];

        foreach ($this->mockSites as $mockSite) {
            $result[$mockSite->getSiteID()] = $mockSite->getSiteRecord();
        }
        return $result;
    }

    public function getSite(int $siteID): Site
    {
        if ($siteID === $this->getOwnSite()->getSiteID()) {
            return parent::getSite($siteID);
        }

        $this->getSiteRecord($siteID);
        return $this->mockSites[$siteID];
    }
}
