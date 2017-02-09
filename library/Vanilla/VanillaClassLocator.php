<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla;


use Garden\EventManager;

/**
 * A subclass of ClassLocator with consideration for overrides registered in an instance of EventManager.
 */
class VanillaClassLocator extends ClassLocator {
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * VanillaClassLocator constructor.
     *
     * @param EventManager $eventManager
     */
    public function __construct(EventManager $eventManager) {
        $this->eventManager = $eventManager;
    }

    /**
     * Find a method on an object, allowing for overrides registered through EventManager.
     *
     * @param object $object An object to search.
     * @param string $method The name of the method to look up.
     * @return callable|null Returns a callback to the method or null if it does not exist.
     */
    public function findMethod($object, $method) {
        $class = get_class($object);

        // If we have a namespace, shave it off.
        if (strpos($class, '\\')) {
            $class = substr($class, strrpos($class, '\\') + 1);
        }

        $event = "{$class}_{$method}_method";

        // Check for an overriding event.
        if ($this->eventManager->hasHandler($event)) {
            $handlers = $this->eventManager->getHandlers($event);
            return reset($handlers);
        } else {
            return parent::findMethod($object, $method);
        }
    }
}
