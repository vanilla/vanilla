<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\Totals;

use CategoryModel;
use Vanilla\Contracts\Models\AlreadyCachedSiteSectionTotalProviderInterface;
use Vanilla\Contracts\Models\SiteSectionTotalProviderInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;

/**
 * Provide site totals for categories.
 */
class CategorySiteTotalProvider implements
    SiteSectionTotalProviderInterface,
    AlreadyCachedSiteSectionTotalProviderInterface
{
    private CategoryModel $categoryModel;

    /**
     * DI.
     *
     * @param \CategoryModel $categoryModel
     */
    public function __construct(CategoryModel $categoryModel)
    {
        $this->categoryModel = $categoryModel;
    }

    /**
     * @inheritdoc
     */
    public function calculateSiteTotalCount(SiteSectionInterface $siteSection = null): int
    {
        $rootCategoryID = $siteSection === null ? \CategoryModel::ROOT_ID : $siteSection->getCategoryID();
        // Use collection not filter by permissions.
        $descendantIDs = $this->categoryModel->getCollection()->getDescendantIDs($rootCategoryID);
        $total = count($descendantIDs);
        if ($rootCategoryID === \CategoryModel::ROOT_ID) {
            return $total;
        } else {
            return $total + 1;
        }
    }

    /**
     * @inheritdoc
     */
    public function getSiteTotalRecordType(): string
    {
        return "category";
    }

    /**
     * @inheritdoc
     */
    public function getTableName(): string
    {
        return "Category";
    }
}
