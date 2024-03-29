<?php
/**
 * @author Pavel Gonharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Providers;

use Vanilla\Layout\Asset\LayoutQuery;

/**
 * Some class that can map record IDs to record name/url.
 */
interface LayoutViewRecordProviderInterface
{
    /**
     * Get a Name/URL array for a particular record.
     *
     * @param int[] $recordIDs
     *
     * @return array
     */
    public function getRecords(array $recordIDs): array;

    /**
     * Get the record type that the provider works for.
     *
     * @return string[]
     */
    public static function getValidRecordTypes(): array;

    /**
     * Validate record.
     *
     * @param int[] $recordIDs
     *
     * @return bool
     */
    public function validateRecords(array $recordIDs): bool;

    /**
     * Given a layout query, perform any resolutions that should be done before looking up the layout is looked up.
     *
     * @param LayoutQuery $query
     *
     * @return LayoutQuery
     */
    public function resolveLayoutQuery(LayoutQuery $query): LayoutQuery;

    /**
     * Given a layout query that we couldn't find a layout for, generate a parent layout query.
     *
     * @param LayoutQuery $query
     *
     * @return LayoutQuery
     */
    public function resolveParentLayoutQuery(LayoutQuery $query): LayoutQuery;
}
