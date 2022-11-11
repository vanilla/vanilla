<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Analytics;

/**
 * Interface to be implemented by classes that can create an event corresponding to a request payload
 */
interface EventProviderInterface
{
    /**
     * Returns the event object corresponding to the request payload
     *
     * @param array $body
     * @return object
     */
    public function getEvent(array $body): object;

    /**
     * Returns true if the provider can return an event for the passed in request payload
     *
     * @param array $body
     * @return bool
     */
    public function canHandleRequest(array $body): bool;
}
