<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @var AddonManager
     */
    private $addonManager;

    /**
     * VanillaClassLocator constructor.
     *
     * @param EventManager $eventManager The event manager used to find methods added via event handlers.
     * @param AddonManager $addonManager The addon manager used to find classes with wildcard matches.
     */
    public function __construct(EventManager $eventManager, AddonManager $addonManager) {
        $this->eventManager = $eventManager;
        $this->addonManager = $addonManager;
    }

    /**
     * @inheritdoc
     *
     * This version of **findClass** accepts glob style wildcards.
     */
    public function findClass($name) {
        $classes = $this->addonManager->findClasses($name);

        if (empty($classes)) {
            return parent::findClass($name);
        } elseif (count($classes) > 1) {
            trigger_error(sprintf("There were %s classes found in %s for search: %s", count($classes), __CLASS__, $name));
        }
        return reset($classes);

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

        $event = "{$class}_{$method}";

        // Check for an overriding event.
        if ($this->eventManager->hasHandler($event)) {
            $handlers = $this->eventManager->getHandlers($event);
            return reset($handlers);
        } else {
            return parent::findMethod($object, $method);
        }
    }
}
