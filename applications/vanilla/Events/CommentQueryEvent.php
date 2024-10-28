<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Gdn_DataSet;

/**
 * Event for handling comment query checks.
 */
class CommentQueryEvent
{
    /**
     * Constructor.
     *
     * @param Gdn_DataSet $comment
     */
    public function __construct(private Gdn_DataSet &$comment)
    {
    }

    /**
     * Get the Comment.
     *
     * @return Gdn_DataSet
     */
    public function &getComment(): Gdn_DataSet
    {
        return $this->comment;
    }

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
