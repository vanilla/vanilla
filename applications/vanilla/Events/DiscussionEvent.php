<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\ResourceEvent;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggableEventTrait;

/**
 * Represent a discussion resource event.
 */
class DiscussionEvent extends ResourceEvent implements LoggableEventInterface {
    use LoggableEventTrait;
}
