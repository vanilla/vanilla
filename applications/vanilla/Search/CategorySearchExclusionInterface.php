<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Search;

/**
 * Interface for excluded categories from category search.
 */
interface CategorySearchExclusionInterface
{
    /**
     * Get the categoryIDs to exclude from search.
     *
     * @return int[]
     */
    public function getExcludedCategorySearchIDs(): array;
}
