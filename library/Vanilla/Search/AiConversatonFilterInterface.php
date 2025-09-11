<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

/**
 * Interface for filters that can be applied to AI conversation queries.
 */
interface AiConversatonFilterInterface
{
    /**
     * Apply filter logic to the search query.
     *
     * @param array &$query The search query to modify
     * @return void
     */
    public function applyFilter(array &$query): void;
}
