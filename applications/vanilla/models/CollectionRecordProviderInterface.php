<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic
 * @license Proprietary
 */

namespace Vanilla\Models;

/**
 * Interface for Collection Records.
 */
interface CollectionRecordProviderInterface
{
    /**
     * Get the record type handled by this collection.
     *
     * @return string
     */
    public function getRecordType(): string;

    /**
     * Filter to recordIDs to ones that can be used in the collection.
     *
     * @param array $recordIDs The input IDs.
     *
     * @return array The filtered IDs.
     */
    public function filterValidRecordIDs(array $recordIDs): array;

    /**
     * @param array $recordIDs The input IDs
     * @param string $locale
     * @return array The records
     */
    public function getRecords(array $recordIDs, string $locale): array;
}
