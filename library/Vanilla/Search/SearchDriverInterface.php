<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

/**
 *
 */
abstract class SearchDriverInterface {

    /** @var SearchTypeInterface[] */
    private $searchTypes = [];

    /**
     * Build a query object.
     *
     * @param array $queryData
     * @return SearchQuery
     */
    public function buildQuery(array $queryData): SearchQuery {
        $typeSchemas = array_map(function (SearchTypeInterface $type) {
            return $type->getQuerySchema();
        }, $this->searchTypes);

        $query = new SearchQuery($typeSchemas, $queryData);

        foreach ($this->searchTypes as $searchType) {
            $query = $searchType->validateQuery($query);
        }

        return $query;
    }

    /**
     * Perform a query.
     *
     * @param SearchQuery $query
     *
     * @return SearchResults
     */
    abstract public function search(SearchQuery $query): SearchResults;

    /**
     * @return SearchTypeInterface[]
     */
    public function getSearchTypes(): array {
        return $this->searchTypes;
    }

    /**
     * Register a search type.
     *
     * @param SearchTypeInterface $searchType
     */
    public function registerSearchType(SearchTypeInterface $searchType) {
        $this->searchTypes[] = $searchType;
    }
}
