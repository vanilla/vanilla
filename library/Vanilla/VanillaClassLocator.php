<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla;


use Garden\ClassLocator;
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
     * @var EventManager
     */
    private $addonManager;

    /**
     * VanillaClassLocator constructor.
     *
     * @param EventManager $eventManager
     * @param AddonManager $addonManager
     */
    public function __construct(EventManager $eventManager, AddonManager $addonManager) {
        $this->eventManager = $eventManager;
        $this->addonManager = $addonManager;
    }

    /**
     * Find a class with a given name.
     *
     * @param string $name The name to lookup.
     * @return string Returns the name of the class found or **null** if the class isn't found.
     */
    public function findClass($name) {
        $class = parent::findClass($name);

        if ($class === null) {
            if (strpos('\\') !== 0) {
                $name = '*'.$name;
            }

            $classes = $this->addonManager->findClasses($name);
            if ($classes) {
                $class = reset($classes);
            }
        }

        return $class;
    }

    /**
     * Get the name of a class without namespaces.
     *
     * @param string|object $class The name of the class or an instance of the class.
     * @return string Returns the basename of the class.
     */
    private function classBasename($class) {
        if (is_object($class)) {
            $class = get_class($class);
        }

        // If we have a namespace, shave it off.
        if (($i = strrpos($class, '\\')) !== false) {
            $class = substr($class, $i + 1);
        }

        return $class;
    }

    /**
     * Find a method on an object, allowing for overrides registered through EventManager.
     *
     * @param object $object An object to search.
     * @param string $method The name of the method to look up.
     * @return callable|null Returns a callback to the method or null if it does not exist.
     */
    public function findMethod($object, $method) {
        $class = $this->classBasename($object);

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
