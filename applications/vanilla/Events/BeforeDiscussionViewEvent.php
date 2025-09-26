<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Community\Events;

/**
 * Generic event fired before we check view permission on a discussion.
 */
class BeforeDiscussionViewEvent
{
    public function __construct(public array $discussion)
    {
    }
}
