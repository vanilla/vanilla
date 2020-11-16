<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Site;

use Vanilla\Contracts\Site\Site;
use Vanilla\Site\OwnSiteProvider;

/**
 * Mock site provider for tests.
 */
class MockSiteProvider extends OwnSiteProvider {

    /** @var Site */
    private $mockSites = [];

    /**
     * Apply a mock site.
     *
     * @param Site $site
     */
    public function addMockSite(Site $site): void {
        $this->mockSites[] = $site;
    }

    /**
     * @return array
     */
    public function getAllSites(): array {
        return array_merge([$this->ownSite], $this->mockSites);
    }

    /**
     * @param Site $ownSite
     */
    public function setOwnSite(Site $ownSite): void {
        $this->ownSite = $ownSite;
    }

    /**
     * @param Site $unknownSite
     */
    public function setUnknownSite(Site $unknownSite): void {
        $this->unknownSite = $unknownSite;
    }
}
