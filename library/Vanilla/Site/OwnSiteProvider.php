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
    private OwnSite $ownSite;

    /**
     * @param OwnSite $ownSite
     */
    public function __construct(OwnSite $ownSite)
    {
        $this->ownSite = $ownSite;
        $this->ownSite->setSiteProvider($this);
        parent::__construct([$ownSite->getCluster()->getRegionID()]);
        $this->setCache(new NullAdapter());
    }

    /**
     * @inheritdoc
     */
    public function getOwnSite(): VanillaSite
    {
        return $this->ownSite;
    }

    /**
     * @inheritdoc
     */
    protected function loadAllSiteRecords(): array
    {
        return [
            $this->ownSite->getSiteID() => $this->ownSite->getSiteRecord(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSite(int $siteID): Site
    {
        if ($siteID !== $this->ownSite->getSiteID()) {
            throw new SiteNotFoundException($siteID);
        }

        return $this->ownSite;
    }

    /**
     * @return array|\Garden\Sites\Cluster[]
     */
    protected function loadAllClusters(): array
    {
        $cluster = $this->ownSite->getCluster();
        return [
            $cluster->getClusterID() => $cluster,
        ];
    }
}
