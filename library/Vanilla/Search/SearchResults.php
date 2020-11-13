<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\ValidationException;
use Vanilla\Utility\SchemaUtils;

/**
 * Item to handle search results.
 */
class SearchResults implements \IteratorAggregate, \JsonSerializable, \Countable {

    /** @var SearchResultItem[] */
    private $resultItems;

    /** @var int */
    private $totalCount;

    /** @var int */
    private $offset;

    /** @var int */
    private $limit;

    /** @var string[] $terms */
    private $terms;

    /**
     * Constructor.
     *
     * @param SearchResultItem[] $resultItems
     * @param int $totalCount
     * @param int $offset
     * @param int $limit
     */
    public function __construct(array $resultItems, int $totalCount, int $offset, int $limit, array $terms = []) {
        $this->resultItems = $resultItems;
        $this->totalCount = $totalCount;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->terms = $terms;
    }

    /**
     * @return SearchResultItem[]
     */
    public function getResultItems(): array {
        return $this->resultItems;
    }

    /**
     * Remove a resultItem based on it's key.
     *
     * @param int $key
     */
    public function removeResultItem(int $key) {
         unset($this->resultItems[$key]);
         $this->totalCount--;
    }

    /**
     * @return int
     */
    public function getResultCount(): int {
        return count($this->resultItems);
    }

    /**
     * @return int
     */
    public function getTotalCount(): int {
        return $this->totalCount;
    }

    /**
     * @return int
     */
    public function getOffset(): int {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getLimit(): int {
        return $this->limit;
    }

    /**
     * @return string[]
     */
    public function getTerms(): array {
        return $this->terms;
    }

    ///
    /// PHP interfaces
    ///

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return $this->resultItems;
    }

    /**
     * @inheritdoc
     */
    public function getIterator() {
        return new \ArrayIterator($this->resultItems);
    }

    /**
     * @inheritdoc
     */
    public function count() {
        return count($this->resultItems);
    }

    /**
     * Convert some search results into legacy results.
     *
     * @return array
     */
    public function asLegacyResults(): array {
        $results = [];
        $schema = SearchResultItem::legacySchema();
        foreach ($this->resultItems as $serviceResult) {
            try {
                $legacyResult = $schema->validate($serviceResult->asLegacyArray());
                $results[] = $legacyResult;
            } catch (ValidationException $e) {
                $formatted = formatException($e);
                trigger_error("Validation of result failed.\n$formatted");
            }
        }
        return $results;
    }
}
