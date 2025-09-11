<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use CategoryModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Search\AiConversatonFilterInterface;

/**
 * Filter to control post inclusion in AI conversation results based on:
 * - A list of postTypeIDs to include
 * - A list of statusIDs to include
 * - A list of Categories to exclude
 */
class PostAiConversationFilter implements AiConversatonFilterInterface
{
    const CONFIG_INCLUDED_POST_TYPE_IDS = "Search.RAG.IncludedPostTypeIDs";
    const CONFIG_INCLUDED_STATUS_IDS = "Search.RAG.IncludedStatusIDs";
    const CONFIG_EXCLUDED_CATEGORY_IDS = "Search.RAG.ExcludedCategoryIDs";

    /**
     * @param ConfigurationInterface $config
     * @param CategoryModel $categoryModel
     */
    public function __construct(private ConfigurationInterface $config, private CategoryModel $categoryModel)
    {
    }

    /**
     * Apply filter logic to include/exclude posts based on configured criteria.
     *
     * @param array &$query The search query to modify
     * @return void
     */
    public function applyFilter(array &$query): void
    {
        // Apply postTypeID filtering (include only specified post types)
        $this->applyPostTypeFilter($query);

        // Apply statusID filtering (include only specified statuses)
        $this->applyStatusFilter($query);

        // Apply category exclusion filtering
        $this->applyCategoryExclusionFilter($query);
    }

    /**
     * Apply postTypeID inclusion filter.
     *
     * @param array &$query
     * @return void
     */
    private function applyPostTypeFilter(array &$query): void
    {
        $includedPostTypeIDs = $this->config->get(self::CONFIG_INCLUDED_POST_TYPE_IDS, []);

        if (empty($includedPostTypeIDs)) {
            return;
        }

        // If there are already postTypeIDs in the query, intersect them
        if (isset($query["postTypeID"])) {
            $query["postTypeID"] = array_intersect($query["postTypeID"], $includedPostTypeIDs);
        } else {
            $query["postTypeID"] = $includedPostTypeIDs;
        }
    }

    /**
     * Apply statusID inclusion filter.
     *
     * @param array &$query
     * @return void
     */
    private function applyStatusFilter(array &$query): void
    {
        $includedStatusIDs = $this->config->get(self::CONFIG_INCLUDED_STATUS_IDS, []);

        if (empty($includedStatusIDs)) {
            return;
        }

        // If there are already statusIDs in the query, intersect them
        if (isset($query["statusID"])) {
            $query["statusID"] = array_intersect($query["statusID"], $includedStatusIDs);
        } else {
            $query["statusID"] = $includedStatusIDs;
        }
    }

    /**
     * Apply category exclusion filter.
     *
     * @param array &$query
     * @return void
     */
    private function applyCategoryExclusionFilter(array &$query): void
    {
        $excludedCategoryIDs = $this->config->get(self::CONFIG_EXCLUDED_CATEGORY_IDS, []);

        if (empty($excludedCategoryIDs)) {
            return;
        }

        if (!isset($query["categoryIDs"])) {
            $allVisibleCategories = $this->categoryModel->getVisibleCategoryIDs(["forceArrayReturn" => true]);
            $allowedCategories = array_diff($allVisibleCategories, $excludedCategoryIDs);
            if (!empty($allowedCategories)) {
                $query["categoryIDs"] = $allowedCategories;
            }
        } else {
            // If categoryIDs are already specified, remove the excluded ones
            $query["categoryIDs"] = array_diff($query["categoryIDs"], $excludedCategoryIDs);
        }
    }
}
