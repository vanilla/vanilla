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
    protected Gdn_SQLDriver $commentSQL;

    /**
     * Constructor.
     *
     * @param Gdn_SQLDriver $commentSQL
     */
    public function __construct(\Gdn_SQLDriver &$commentSQL)
    {
        $this->commentSQL = &$commentSQL;
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
}
