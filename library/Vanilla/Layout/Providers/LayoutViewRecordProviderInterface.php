<?php
/**
 * @author Pavel Gonharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Providers;

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
     * Get Parent recordType/ID for layout lookup.
     *
     * @param string $recordType
     * @param string $recordID
     * @return array
     */
    public function getParentRecordTypeAndID(string $recordType, string $recordID): array;
}
