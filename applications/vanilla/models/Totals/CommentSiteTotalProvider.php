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

        $countDiscussions = $this->database
            ->createSql()
            ->select("CountAllComments")
            ->from($this->getTableName())
            ->where("CategoryID", $rootCategoryID)
            ->get()
            ->resultArray();

        $countDiscussions = $countDiscussions[0]["CountAllComments"];
        return $countDiscussions;
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
