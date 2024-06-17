<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Models;

/**
 * Interface DiscussionInterface
 */
interface DiscussionInterface
{
    /**
     * Get the discussion id
     *
     * @return int
     */
    public function getDiscussionID(): int;

    /**
     * Set the discussion id
     */
    public function setDiscussionID(int $discussionID): void;
}
