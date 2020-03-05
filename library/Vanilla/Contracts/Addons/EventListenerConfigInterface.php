<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Addons;

/**
 * A collection of event listeners that can be configured by addons.
 */
interface EventListenerConfigInterface {
    /**
     * Add a class method as an event listener.
     *
     * The class must be defined as a string class name rather than an object to enable caching optimization. The class
     * instance will usually be retrieved from a container.
     *
     * Reflection is used to determine the event being subscribed to so make sure the method has the appropriate type hint.
     *
     * @param string $class The name of the class that contains the listener.
     * @param string $method The name of the method within the class.
     * @return $this
     */
    public function addListenerMethod(string $class, string $method): self;
}
