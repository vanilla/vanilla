<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

/**
 * Interface for stashing counts related to a site section.
 */
interface SiteSectionCountStasherInterface {

    /**
     * Save counts for a site section somewhere.
     *
     * @param SiteSectionInterface $siteSection The site section.
     * @param array $counts The counts.
     */
    public function stashCountsForSiteSection(SiteSectionInterface $siteSection, array $counts): void;
}
