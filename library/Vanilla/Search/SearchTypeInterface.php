<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;

/**
 * Interface for a search item.
 */
interface SearchTypeInterface {

    /**
     * Get a unique key for the search type.
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Maps to how the record lives in the database.
     *
     * Eg. discussion, article, category.
     *
     * @return string
     */
    public function getSearchGroup(): string;

    /**
     * Get the type of the record.
     *
     * Eg. Discussion, Comment, Article, Category.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Get records data by their IDs
     *
     * @param array $recordIDs
     *
     * @return SearchResultItem[]
     */
    public function getResultItems(array $recordIDs): array;

    /**
     * Get supported filters for the type.
     */
    public function getFilters(): array;


    /**
     * Get supported sorts for the type.
     */
    public function getSorts(): array;

    /**
     * Get the schema for supported query parameters.
     *
     * @return Schema
     */
    public function getQuerySchema(): Schema;

    /**
     * Validate a query.
     *
     * @param SearchQuery $query
     *
     * @return SearchQuery
     */
    public function validateQuery(SearchQuery $query): SearchQuery;

//    /**
//     * Get a global identifier for across multiple sites.
//     *
//     *
//     *
//     * @return string
//     */
//    public function getGlobalID(): string;
}
