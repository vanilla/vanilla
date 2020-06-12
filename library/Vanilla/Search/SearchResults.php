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
    private $resultCount;

    /** @var int */
    private $totalCount;

    /** @var int */
    private $currentPage;

    /** @var int */
    private $limit;

    /**
     * Constructor.
     *
     * @param SearchResultItem[] $resultItems
     * @param int $resultCount
     * @param int $totalCount
     * @param int $currentPage
     * @param int $limit
     */
    public function __construct(array $resultItems, int $resultCount, int $totalCount, int $currentPage, int $limit) {
        $this->resultItems = $resultItems;
        $this->resultCount = $resultCount;
        $this->totalCount = $totalCount;
        $this->currentPage = $currentPage;
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
        return $this->resultCount;
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
    public function getCurrentPage(): int {
        return $this->currentPage;
    }

    /**
     * @return int
     */
    public function getLimit(): int {
        return $this->limit;
    }
}
