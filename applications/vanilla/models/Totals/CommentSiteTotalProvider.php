<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\Totals;

use Vanilla\Contracts\Models\SiteSectionTotalProviderInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;

/**
 * Provide site totals for comments.
 */
class CommentSiteTotalProvider implements SiteSectionTotalProviderInterface
{
    /** @var \Gdn_Database */
    private $database;

    /** @var \CategoryModel */
    private $categoryModel;

    /**
     * DI.
     *
     * @param \Gdn_Database $database
     */
    public function __construct(\Gdn_Database $database, \CategoryModel $categoryModel)
    {
        $this->database = $database;
        $this->categoryModel = $categoryModel;
    }

    /**
     * Get the total number of comments on a site (or site section).
     *
     * @param SiteSectionInterface|null $siteSection
     * @return int
     */
    public function calculateSiteTotalCount(?SiteSectionInterface $siteSection = null): int
    {
        $rootCategoryID = $siteSection === null ? \CategoryModel::ROOT_ID : $siteSection->getCategoryID();
        $sql = $this->database
            ->createSql()
            ->from("Comment c")
            ->join("Discussion d", "c.DiscussionID = d.DiscussionID");
        if ($rootCategoryID != \CategoryModel::ROOT_ID) {
            $childCategoryIDs = $this->categoryModel->getCategoriesDescendantIDs([$rootCategoryID]);
            $sql->where("d.CategoryID", $childCategoryIDs);
        }
        return $sql->getCount();
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
