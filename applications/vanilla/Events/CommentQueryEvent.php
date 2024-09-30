<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Gdn_SQLDriver;

/**
 * Event for handling comment query checks.
 */
class CommentQueryEvent
{
    /**
     * Constructor.
     *
     * @param Gdn_SQLDriver $commentSQL
     * @param array $parentRecordTypes
     * @param array $whereGroups
     */
    public function __construct(
        private \Gdn_SQLDriver &$commentSQL,
        private array $parentRecordTypes,
        private array $whereGroups
    ) {
    }

    /**
     * Add an additional where clause to the query.
     *
     * @param array $where
     * @return void
     */
    public function addWhereGroup(array $where): void
    {
        $this->whereGroups[] = $where;
    }

    /**
     * Get the CommentSQL.
     *
     * @return Gdn_SQLDriver
     */
    public function &getCommentSQL(): Gdn_SQLDriver
    {
        return $this->commentSQL;
    }

    /**
     * @return array
     */
    public function getParentRecordTypes(): array
    {
        return $this->parentRecordTypes;
    }

    /**
     * Apply the collected where's to the query.
     *
     * @return void
     */
    public function applyWheresToQuery(): void
    {
        $sql = $this->getCommentSQL();
        $whereGroups = $this->whereGroups;

        if (count($whereGroups) === 0) {
            // There are none.
            return;
        }

        $sql->beginWhereGroup();
        foreach ($whereGroups as $where) {
            $sql->orOp();
            $sql->beginWhereGroup();
            $sql->where($where);
            $sql->endWhereGroup();
        }
        $sql->endWhereGroup();
    }
}
