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
interface BoostableSearchQueryInterface
{
    /**
     * Boost a date field by it's recency.
     *
     * @param string $fieldName
     *
     * @return mixed
     */
    public function boostFieldRecency(string $fieldName);

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
