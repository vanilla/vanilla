<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Garden\Sites\Exceptions\SiteNotFoundException;
use Garden\Sites\Site;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Vanilla\Contracts\Site\VanillaSiteProvider;
use Vanilla\Contracts\Site\VanillaSite;

/**
 * Default site provider for when no other one is registered.
 *
 * Only knows about its own site and the IDs don't mean anything.
 */
class OwnSiteProvider extends VanillaSiteProvider
{
    /**
     * @param OwnSite $ownSite
     */
    public function __construct(OwnSite $ownSite)
    {
        parent::__construct([$ownSite->getCluster()->getRegionID()]);
        $this->setCache(new NullAdapter());
    }

    /**
     * @inheritdoc
     */
    public function getOwnSite(): VanillaSite
    {
        $ownSite = \Gdn::getContainer()->get(OwnSite::class);
        $ownSite->setSiteProvider($this);
        return $ownSite;
    }

    /**
     * @inheritdoc
     */
    protected function loadAllSiteRecords(): array
    {
        return [
            $this->getOwnSite()->getSiteID() => $this->getOwnSite()->getSiteRecord(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSite(int $siteID): Site
    {
        if ($siteID !== $this->getOwnSite()->getSiteID()) {
            throw new SiteNotFoundException($siteID);
        }

        return $this->getOwnSite();
    }

    /**
     * @return array|\Garden\Sites\Cluster[]
     */
    protected function loadAllClusters(): array
    {
        $cluster = $this->getOwnSite()->getCluster();
        return [
            $cluster->getClusterID() => $cluster,
        ];
    }
}
