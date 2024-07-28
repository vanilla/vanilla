<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Events;

/**
 * Event fired before we create a comment through the API.
 */
class BeforeCommentPostEvent
{
    public function __construct(public string $parentRecordType, public int $parentRecordID)
    {
    }
}
