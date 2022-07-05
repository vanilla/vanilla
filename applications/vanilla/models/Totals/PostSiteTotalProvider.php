<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\Totals;

use Vanilla\Contracts\Models\SiteSectionTotalProviderInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;

/**
 * Provide site totals for posts.
 */
class PostSiteTotalProvider implements SiteSectionTotalProviderInterface
{
    private $database;

    /**
     * DI.
     *
     * @param \Gdn_Database $database
     */
    public function __construct(\Gdn_Database $database)
    {
        $this->database = $database;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateSiteTotalCount(SiteSectionInterface $siteSection = null): int
    {
        $rootCategoryID = $siteSection === null ? \CategoryModel::ROOT_ID : $siteSection->getCategoryID();

        $countDiscussionsAndComments = $this->database
            ->createSql()
            ->select(["CountAllDiscussions", "CountAllComments"])
            ->from($this->getTableName())
            ->where("CategoryID", $rootCategoryID)
            ->get()
            ->resultArray();

        $postCount =
            $countDiscussionsAndComments[0]["CountAllDiscussions"] +
            $countDiscussionsAndComments[0]["CountAllComments"];

        return $postCount;
    }

    /**
     * {@inheritDoc}
     */
    public function getTableName(): string
    {
        return "Category";
    }

    /**
     * {@inheritDoc}
     */
    public function getSiteTotalRecordType(): string
    {
        return "post";
    }
}
