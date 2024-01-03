<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Site;

use Garden\Sites\SiteRecord;
use Vanilla\Contracts\Site\VanillaSite;
use Vanilla\Site\OwnSite;

/**
 * Mockable own site.
 */
class MockOwnSite extends OwnSite
{
    /**
     * Apply properties from a site to the ownsite instance.r
     *
     * @param VanillaSite|SiteRecord $site
     */
    public function applyFrom($site)
    {
        if ($site instanceof SiteRecord) {
            $this->siteRecord = $site;
        } else {
            $this->siteRecord = $site->siteRecord;
            $this->name = $site->getName();
            $this->setHttpClient($site->httpClient());
        }
    }
}
