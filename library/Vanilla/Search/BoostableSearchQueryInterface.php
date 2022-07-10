<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

/**
 * Interface to apply on a query type that supports boosting various fields at query time.
 */
interface BoostableSearchQueryInterface {

    /**
     * Boost a date field by it's recency.
     *
     * @param string $fieldName
     *
     * @return mixed
     */
    public function boostFieldRecency(string $fieldName);

    /**
     * Boost ranking of a specific type.
     *
     * Boost values are relative to the default value of 1.0.
     * A boost value between 0 and 1.0 decreases the type relevance. A value greater than 1.0 increases the relevance score.
     *
     * @param AbstractSearchType $type The type to boost.
     * @param float|null $amount The amount to boost by.
     */
    public function boostType(AbstractSearchType $type, ?float $amount);

    /**
     * Start a boosting query.
     *
     * All query calls made before calling endBoostQuery() will apply to the boost.
     */
    public function startBoostQuery(): void;

    /**
     * End a boosting query.
     */
    public function endBoostQuery(): void;
}
