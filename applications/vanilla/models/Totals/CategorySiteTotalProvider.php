<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\Totals;

use Vanilla\Contracts\Models\SiteTotalProviderInterface;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;

/**
 * Provide site totals for categories.
 */
class CategorySiteTotalProvider implements SiteTotalProviderInterface {

    /** @var \Gdn_Database */
    private $database;

    /**
     * DI.
     *
     * @param \Gdn_Database $database
     */
    public function __construct(\Gdn_Database $database) {
        $this->database = $database;
    }

    /**
     * @inheritdoc
     */
    public function calculateSiteTotalCount(): int {
        $dbResult = $this->database
            ->createSql()
            ->from("Category")
            ->getCount()
        ;
        // Subtract the root category.
        return $dbResult - 1;
    }

    /**
     * @inheritdoc
     */
    public function getSiteTotalRecordType(): string {
        return "category";
    }

    /**
     * @inheritdoc
     */
    public function getTableName(): string {
        return 'Category';
    }
}
