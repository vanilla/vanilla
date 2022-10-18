<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\Totals;

use CategoryModel;
use Vanilla\Contracts\Models\SiteSectionTotalProviderInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;

/**
 * Provide site totals for categories.
 */
class CategorySiteTotalProvider implements SiteSectionTotalProviderInterface
{
    /** @var \Gdn_Database */
    private $database;

    /** @var CategoryModel */
    private $categoryModel;

    /**
     * DI.
     *
     * @param \Gdn_Database $database
     * @param \CategoryModel $categoryModel
     */
    public function __construct(\Gdn_Database $database, CategoryModel $categoryModel)
    {
        $this->database = $database;
        $this->categoryModel = $categoryModel;
    }

    /**
     * @inheritdoc
     */
    public function calculateSiteTotalCount(SiteSectionInterface $siteSection = null): int
    {
        $rootCategoryID = $siteSection === null ? \CategoryModel::ROOT_ID : $siteSection->getCategoryID();
        $cats = array_merge([$rootCategoryID], $this->categoryModel->getCategoryDescendantIDs($rootCategoryID));

        $dbResult = $this->database
            ->createSql()
            ->select("CountCategories")
            ->from("Category")
            ->where("CategoryID", $cats)
            ->get()
            ->resultArray();

        $count = array_sum(array_column($dbResult, "CountCategories"));

        return $count;
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
