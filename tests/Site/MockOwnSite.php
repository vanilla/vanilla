<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Site;

use Vanilla\Contracts\Site\Site;
use Vanilla\Site\OwnSite;

/**
 * Mockable own site.
 */
class MockOwnSite extends OwnSite {

    /**
     * Apply properties from a site to the ownsite instance.r
     *
     * @param Site $site
     */
    public function applyFrom(Site $site) {
        $this->siteID = $site->getSiteID();
        $this->accountID = $site->getAccountID();
        $this->name = $site->getName();
        $this->webUrl = $site->getWebUrl();
        $this->httpClient = $site->getHttpClient();
    }
}
