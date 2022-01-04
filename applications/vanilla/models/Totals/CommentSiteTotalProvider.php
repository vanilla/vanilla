<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\Totals;

use Vanilla\Contracts\Models\SiteTotalProviderInterface;

/**
 * Provide site totals for comments.
 */
class CommentSiteTotalProvider implements SiteTotalProviderInterface {

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
        return $this->database
            ->createSql()
            ->from("Comment")
            ->getCount()
        ;
    }

    /**
     * @inheritdoc
     */
    public function getSiteTotalRecordType(): string {
        return "comment";
    }

    /**
     * @inheritdoc
     */
    public function getTableName(): string {
        return 'Comment';
    }
}
