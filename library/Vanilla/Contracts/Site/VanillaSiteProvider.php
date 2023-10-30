<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

use Garden\Sites\Cluster;
use Garden\Sites\SiteProvider;

/**
 * Interface representing information about the current or related sites.
 *
 * @extends SiteProvider<VanillaSite, Cluster>
 */
abstract class VanillaSiteProvider extends SiteProvider
{
    /**
     * Get all availaible sites.
     *
     * @return VanillaSite[]
     */
    public function getAllSites(): array
    {
        return array_values($this->getSites());
    }

    /**
     * Get the current site.
     *
     * @return VanillaSite
     */
    abstract public function getOwnSite(): VanillaSite;

    /**
     * Clear any cached sites.
     */
    public function clearCache(): void
    {
    }

    /**
     * Get a site by it's siteID.
     *
     * @param int $siteID
     *
     * @return VanillaSite
     *
     * @deprecated Use {@link VanillaSiteProvider::getSite()}
     */
    public function getBySiteID(int $siteID): VanillaSite
    {
        return $this->getSite($siteID);
    }

    /**
     * Get all sites contained within an accountID.
     *
     * @param int $accountID
     *
     * @return VanillaSite[]
     */
    public function getByAccountID(int $accountID): array
    {
        $result = [];
        foreach ($this->getAllSites() as $site) {
            if ($site->getAccountID() === $accountID) {
                $result[] = $site;
            }
        }

        return $result;
    }

    /**
     * Get multiple sites by multiple siteIDs.
     *
     * Unknown sites will be replaced by the unknown site.
     *
     * @param string[] $siteIDs
     *
     * @return VanillaSite[]
     */
    public function getBySiteIDs(array $siteIDs): array
    {
        $result = [];
        foreach ($siteIDs as $siteID) {
            $result[] = $this->getSite($siteID);
        }

        return $result;
    }
}
