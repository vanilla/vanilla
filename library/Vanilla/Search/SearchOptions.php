<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

/**
 * Options for a search. Includes information like pagination.
 */
class SearchOptions
{
    const DEFAULT_LIMIT = 10;

    private array $aggregations = [];

    /**
     * Constructor.
     *
     * @param int $offset
     * @param int $limit
     * @param bool $skipConversion
     */
    public function __construct(
        public int $offset = 0,
        public int $limit = self::DEFAULT_LIMIT,
        public bool $skipConversion = false,
        public bool $includeTypeaheads = false,
        public bool $includeResults = true
    ) {
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
     * Whether we should skip AbstractSearchDriver::convertRecordsToResultItems().
     *
     * @return bool
     */
    public function skipConversion(): bool
    {
        return $this->skipConversion;
    }

    /**
     * Returns all aggregation options.
     *
     * @return SearchAggregation[]
     */
    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    /**
     * Add an aggregation option.
     *
     * @param SearchAggregation $aggregation
     * @return void
     */
    public function addAggregation(SearchAggregation $aggregation): void
    {
        $this->aggregations[$aggregation->getName()] = $aggregation;
    }
}
