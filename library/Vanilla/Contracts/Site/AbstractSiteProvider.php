<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

/**
 * Interface representing information about the current or related sites.
 */
abstract class AbstractSiteProvider {

    /**
     * Get all availaible sites.
     *
     * @return Site[]
     */
    abstract public function getAllSites(): array;

    /**
     * Get the current site.
     *
     * @return Site
     */
    abstract public function getOwnSite(): Site;

    /**
     * Get a site to use in place of a site that could not be found.
     *
     * @return Site
     */
    abstract public function getUnknownSite(): Site;

    /**
     * Clear any cached sites.
     */
    public function clearCache(): void {
    }

    /**
     * Get a site by it's siteID.
     *
     * @param int $siteID
     *
     * @return Site
     */
    public function getBySiteID(int $siteID): Site {
        foreach ($this->getAllSites() as $site) {
            if ($site->getSiteID() === $siteID) {
                return $site;
            }
        }

        return $this->getUnknownSite();
    }

    /**
     * Get all sites contained within an accountID.
     *
     * @param int $accountID
     *
     * @return Site[]
     */
    public function getByAccountID(int $accountID): array {
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
     * @return Site[]
     */
    public function getBySiteIDs(array $siteIDs): array {
        $result = [];
        foreach ($siteIDs as $siteID) {
            $result[] = $this->getBySiteID($siteID);
        }

        return $result;
    }
}
