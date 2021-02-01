<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

/**
 * Interface to extend on a query type.
 */
interface SearchTypeQueryExtenderInterface {

    /**
     * Extend crawlable record
     *
     * @param array $record
     * @param string $recordType
     * @return mixed
     */
    public function extendRecord(array &$record, string $recordType);

    /**
     * Extend search query
     *
     * @param SearchQuery $query
     * @return SearchQuery
     */
    public function extendQuery(SearchQuery &$query): SearchQuery;

    /**
     * Extend list of category IDs
     *
     * @param array $categories
     * @return array
     */
    public function extendCategories(array $categories): array;

    /**
     * Extend user permissions for some search records
     */
    public function extendPermissions();
}
