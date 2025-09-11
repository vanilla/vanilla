<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\Totals;

use Vanilla\Contracts\Models\AlreadyCachedSiteSectionTotalProviderInterface;
use Vanilla\Contracts\Models\SiteSectionTotalProviderInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;

/**
 * Provide site totals for posts.
 */
class PostSiteTotalProvider implements SiteSectionTotalProviderInterface, AlreadyCachedSiteSectionTotalProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function calculateSiteTotalCount(SiteSectionInterface $siteSection = null): int
    {
        $rootCategoryID = $siteSection === null ? \CategoryModel::ROOT_ID : $siteSection->getCategoryID();
        $category = \CategoryModel::categories($rootCategoryID) ?: [];
        $postCount = ($category["CountAllDiscussions"] ?? 0) + ($category["CountAllComments"] ?? 0);
        return $postCount;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName(): string
    {
        return "Category";
    }

    /**
     * {@inheritdoc}
     */
    public function getSiteTotalRecordType(): string
    {
        return "post";
    }
}
