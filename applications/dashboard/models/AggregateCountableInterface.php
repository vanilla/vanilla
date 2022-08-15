<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

/**
 * Implement this on models that can calculate aggregate counts.
 */
interface AggregateCountableInterface
{
    /**
     * Calculate an aggregate for the model.
     *
     * @param string $aggregateName The name of the aggregate.
     * @param int $from The starting ID to calculate for.
     * @param int $to The ending ID to calculate for.
     */
    public function calculateAggregates(string $aggregateName, int $from, int $to);
}
