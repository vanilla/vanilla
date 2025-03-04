<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\ValidationException;
use Traversable;
use Vanilla\Utility\UrlUtils;

/**
 * Item to handle search results.
 */
class SearchResults implements \IteratorAggregate, \JsonSerializable, \Countable
{
    /**
     * Constructor.
     *
     * @param array<SearchResultItem|SearchTypeaheadResult> $resultItems
     * @param int $totalCount
     * @param int $offset
     * @param int $limit
     * @param string[] $terms
     * @param string|null $cursor
     * @param array $aggregations
     */
    public function __construct(
        private array $resultItems,
        private int $totalCount,
        private int $offset,
        private int $limit,
        private array $terms = [],
        private ?string $cursor = null,
        private array $aggregations = []
    ) {
    }

    /**
     * @return SearchResultItem[]
     */
    public function getResultItems(): array
    {
        return $this->resultItems;
    }

    /**
     * @param array $resultItems
     * @return void
     */
    public function setResultItems(array $resultItems): void
    {
        $this->resultItems = $resultItems;
    }

    /**
     * Remove a resultItem based on it's key.
     *
     * @param int $key
     */
    public function removeResultItem(int $key): void
    {
        unset($this->resultItems[$key]);
        $this->resultItems = array_values($this->resultItems);
        $this->totalCount--;
    }

    /**
     * @return int
     */
    public function getResultCount(): int
    {
        return count($this->resultItems);
    }

    /**
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return string[]
     */
    public function getTerms(): array
    {
        return $this->terms;
    }

    /**
     * Token which may be used to fetch next page of results.
     *
     * @return string|null
     */
    public function getCursor(): ?string
    {
        return $this->cursor;
    }

    ///
    /// PHP interfaces
    ///

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return $this->resultItems;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->resultItems);
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->resultItems);
    }

    /**
     * Convert some search results into legacy results.
     *
     * @return array
     */
    public function asLegacyResults(): array
    {
        $results = [];
        $schema = SearchResultItem::legacySchema();
        foreach ($this->resultItems as $serviceResult) {
            if ($serviceResult instanceof SearchResultItem) {
                try {
                    $legacyResult = $schema->validate($serviceResult->asLegacyArray());
                    $results[] = $legacyResult;
                } catch (ValidationException $e) {
                    $formatted = formatException($e);
                    trigger_error("Validation of result failed.\n$formatted");
                }
            }
        }
        return $results;
    }

    /**
     * @return array
     */
    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    /**
     * Add UTM parameters to the search results' URLs.
     *
     * @param string $searchTerm
     * @return void
     */
    public function applyUtmParams(string $searchTerm = "")
    {
        $utmParameters = [
            "utm_source" => "community-search",
            "utm_medium" => "organic-search",
            "utm_term" => $searchTerm,
        ];

        $resultItems = $this->getResultItems();
        foreach ($resultItems as &$resultItem) {
            if ($resultItem instanceof SearchResultItem) {
                $itemUrl = $resultItem->getUrl();
                $itemUrl = UrlUtils::concatQuery($itemUrl, $utmParameters);

                $resultItem->setUrl($itemUrl);
            }
        }
        $this->setResultItems($resultItems);
    }
}
