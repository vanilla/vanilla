<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Models;

/**
 * Interface for record types with custom logic for crawlable counts.
 */
interface SiteTotalProviderInterface
{
    /**
     * Calculate the actual count of crawlable records for the model.
     *
     * WARNING: This may be very slow.
     *
     * @return int
     */
    public function calculateSiteTotalCount(): int;

    /**
     * @return string
     */
    public function getTableName(): string;

    /**
     * @return string
     */
    public function getSiteTotalRecordType(): string;
}
