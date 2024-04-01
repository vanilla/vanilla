<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\Totals;

use Vanilla\Contracts\Models\AlreadyCachedSiteSectionTotalProviderInterface;
use Vanilla\Contracts\Models\SiteSectionTotalProviderInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;

/**
 * Provide site totals for comments.
 */
class CommentSiteTotalProvider implements
    SiteSectionTotalProviderInterface,
    AlreadyCachedSiteSectionTotalProviderInterface
{
    /**
     * Get the total number of comments on a site (or site section).
     *
     * @param SiteSectionInterface|null $siteSection
     * @return int
     */
    public function calculateSiteTotalCount(?SiteSectionInterface $siteSection = null): int
    {
        $rootCategoryID = $siteSection === null ? \CategoryModel::ROOT_ID : $siteSection->getCategoryID();
        $category = \CategoryModel::categories($rootCategoryID) ?: [];
        $postCount = $category["CountAllComments"] ?? 0;
        return $postCount;
    }

    /**
     * @inheritdoc
     */
    public function getSiteTotalRecordType(): string
    {
        return "comment";
    }

    /**
     * @inheritdoc
     */
    public function getTableName(): string
    {
        return "Category";
    }
}
