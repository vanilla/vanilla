<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

use Vanilla\Site\SiteSectionModel;

/**
 * A counter that contributes to the aggregated counts for a site section.
 */
interface SiteSectionCounterInterface {

    /**
     * Trigger a recalculation of counts for a site section.
     *
     * @param SiteSectionModel $siteSectionModel
     * @param SiteSectionInterface $siteSection
     *
     * @return array Return an array of keyed counts [$key => [ 'labelCount' => '', 'count' => 424 ]].
     */
    public function calculateCountsForSiteSection(SiteSectionModel $siteSectionModel, SiteSectionInterface $siteSection): array;
}
