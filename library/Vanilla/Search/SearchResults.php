<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

/**
 * Item to handle search results.
 */
class SearchResults implements \IteratorAggregate, \JsonSerializable {

    /** @var SearchResultItem[] */
    private $resultItems;

    /** @var int */
    private $totalCount;

    /** @var int */
    private $offset;

    /** @var int */
    private $limit;

    /**
     * Constructor.
     *
     * @param SearchResultItem[] $resultItems
     * @param int $totalCount
     * @param int $offset
     * @param int $limit
     */
    public function __construct(array $resultItems, int $totalCount, int $offset, int $limit) {
        $this->resultItems = $resultItems;
        $this->totalCount = $totalCount;
        $this->offset = $offset;
        $this->limit = $limit;
    }

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
     * @return SearchResultItem[]
     */
    public function getResultItems(): array {
        return $this->resultItems;
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
}
