<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\QnA\Models\Totals;

use CategoryModel;
use Vanilla\Contracts\Models\SiteSectionTotalProviderInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;

/**
 * Provide site totals for questions.
 */
class QuestionSiteTotalProvider implements SiteSectionTotalProviderInterface
{
    /** @var \Gdn_Database  */
    private $database;

    /** @var CategoryModel */
    private $categoryModel;

    /**
     * DI.
     *
     * @param \Gdn_Database $database
     * @param CategoryModel $categoryModel
     */
    public function __construct(\Gdn_Database $database, CategoryModel $categoryModel)
    {
        $this->database = $database;
        $this->categoryModel = $categoryModel;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateSiteTotalCount(?SiteSectionInterface $siteSection = null): int
    {
        $sql = $this->database
            ->createSql()
            ->from($this->getTableName())
            ->where("Type", "Question");

        if ($siteSection !== null) {
            $rootCategoryID = $siteSection->getCategoryID();
            $categories = array_merge(
                [$rootCategoryID],
                $this->categoryModel->getCategoryDescendantIDs($rootCategoryID)
            );
            $sql->where("CategoryID", $categories);
        }

        return $sql->getCount();
    }

    /**
     * {@inheritDoc}
     */
    public function getTableName(): string
    {
        return "Discussion";
    }

    /**
     * {@inheritDoc}
     */
    public function getSiteTotalRecordType(): string
    {
        return "question";
    }
}
