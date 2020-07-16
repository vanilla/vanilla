<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;

/**
 * A search query object.
 */
abstract class SearchQuery {

    const FILTER_OP_OR = 'or';
    const FILTER_OP_AND = 'and';

    const SORT_RELEVANCE = 'relevance';

    /** @var Schema */
    private $querySchema;

    /** @var array */
    private $queryData;

    /** @var array $indexes */
    protected $indexes;

    /**
     * Create a query.
     *
     * @param AbstractSearchType[] $searchTypes The registered search types contributing to the query.
     * @param array $queryData The data making up the query.
     */
    public function __construct(array $searchTypes, array $queryData) {
        $querySchema = new Schema();
        foreach ($searchTypes as $searchType) {
            $querySchema = $querySchema->merge($searchType->getQuerySchema());
        }
        $this->querySchema = $querySchema;
        $this->queryData = $this->querySchema->validate($queryData);

        // Give each of the search types a chance to validate the query object.
        foreach ($searchTypes as $searchType) {
            $searchType->validateQuery($this);
        }

        // Give each of the search types a chance to validate the query object.
        foreach ($searchTypes as $searchType) {
            $searchType->applyToQuery($this);
        }
    }

    /**
     * Get a specific query parameter.
     *
     * @param string $queryParam
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function getQueryParameter(string $queryParam, $default = null) {
        return $this->queryData[$queryParam] ?? $default;
    }

    ///
    /// Abstract Query Functions
    ///

    /**
     * Apply a query where some text is matched.
     *
     * @param string $text The text to search.
     * @param string[] $fieldNames The fields to perform the search against. If empty, all fields will be searched.
     *
     * @return $this
     */
    abstract public function whereText(string $text, array $fieldNames = []);

    /**
     * Add index to scan to the search query
     *
     * @param string $index
     * @return mixed
     */
    public function addIndex(string $index) {
        $this->indexes[$index] = true;
    }

    /**
     * Get all indexes to scan
     *
     * @return array|null
     */
    public function getIndexes(): ?array {
        return array_keys($this->indexes);
    }


    /**
     * Set filter values for some attribute.
     *
     * @param string $attribute
     * @param array $values Values should be numeric
     * @param bool $exclude Whether or not the values should be excluded.
     * @param string $filterOp One of the AbstractSearchQuery::FILTER_OP_* constants.
     *
     * @return $this
     */
    abstract public function setFilter(
        string $attribute,
        array $values,
        bool $exclude = false,
        string $filterOp = SearchQuery::FILTER_OP_OR
    );

    /**
     * Set int range filter
     *
     * @param string $attribute
     * @param int $min
     * @param int $max
     * @param bool $exclude
     *
     * @return $this
     */
    public function setFilterRange(string $attribute, int $min, int $max, bool $exclude = false) {
        return $this;
    }

    /**
     * Return driver compatibility
     *
     * @return string
     */
    public function driver(): string {
        return '';
    }
}
