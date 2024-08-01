<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Models;

use Vanilla\Contracts\Site\SiteSectionInterface;

/**
 * Interface for record types with custom logic for crawlable counts for a specified site section.
 */
interface SiteSectionTotalProviderInterface extends SiteTotalProviderInterface
{
    /**
     * Calculate the actual count of crawlable records for the model.
     *
     * WARNING: This may be very slow.
     *
     * @param SiteSectionInterface|null $siteSection
     *
     * @return int
     */
    public function calculateSiteTotalCount(SiteSectionInterface $siteSection = null): int;
}
