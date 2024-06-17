<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Gdn_SQLDriver;

/**
 * Event for handling discussion query checks.
 */
class DiscussionQueryEvent
{
    protected Gdn_SQLDriver $discussionSQL;

    /**
     * Constructor.
     *
     * @param Gdn_SQLDriver $discussionSQL
     */
    public function __construct(Gdn_SQLDriver &$discussionSQL)
    {
        $this->discussionSQL = &$discussionSQL;
    }

    /**
     * Get the discussionModel.
     *
     * @return Gdn_SQLDriver
     */
    public function &getDiscussionSQL(): Gdn_SQLDriver
    {
        return $this->discussionSQL;
    }
}
