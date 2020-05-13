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
 * Represent a user resource event.
 */
class UserEvent extends ResourceEvent implements LoggableEventInterface {
    use LoggableEventTrait;
}
