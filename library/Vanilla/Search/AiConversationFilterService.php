<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Exception;
use Vanilla\Logging\ErrorLogger;

/**
 * Service for managing and applying AI conversation filters.
 */
class AiConversationFilterService
{
    /**
     * @var AiConversatonFilterInterface[] Array of registered filters
     */
    private array $filters = [];

    /**
     * Register an AI conversation filter.
     *
     * @param AiConversatonFilterInterface $filter
     * @return void
     */
    public function registerFilter(AiConversatonFilterInterface $filter): void
    {
        $this->filters[$filter::class] = $filter;
    }

    /**
     * Apply all registered filters to the query.
     *
     * @param array &$query The search query to modify
     * @return void
     */
    public function applyFilters(array &$query): void
    {
        foreach ($this->filters as $filter) {
            $filter->applyFilter($query);
        }
    }
}
