<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Community\Events;

/**
 * Event fired before we edit a comment through the API.
 */
class BeforeCommentEditEvent
{
    public function __construct(public string $parentRecordType, public int $parentRecordID)
    {
    }
}
