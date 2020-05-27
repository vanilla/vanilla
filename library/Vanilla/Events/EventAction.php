<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Events;

/**
 * An enum class for all possible event actions.
 *
 * All resource events should use actions from this list. If you think you need an action that isn't in this list, you
 * can make a pull request against this class. Please try and use existing actions as much as possible.
 */
final class EventAction {
    public const ADD = 'add';
    public const DELETE = 'delete';
    public const UPDATE = 'update';

    public const ENABLE = 'enable';
    public const DISABLE = 'disable';

    public const AUTHENTICATE = 'authenticate';
    public const UNAUTHENTICATE = 'unauthenticate';
    public const BAN = 'ban';
    public const WARN = 'warn';
    public const ACCESS = 'access';
    public const DENY = 'deny';

    public const REQUEST = 'request';
    public const RESPONSE = 'response';

    public const SUCCESS = 'success';
    public const FAILURE = 'failure';

    /**
     * Generate an event name from a resource and an action.
     *
     * @param string $resource The name of the resource.
     * @param string $action The action that is being taken on the resource. Usually one of the constants from this class.
     * @return string Returns a full event name.
     */
    public static function eventName(string $resource, string $action): string {
        return $resource.'_'.$action;
    }
}
