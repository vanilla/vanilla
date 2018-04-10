<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden;

use Interop\Container\ContainerInterface;

/**
 * Contains methods for binding and firing to events.
 *
 * Addons can create callbacks that bind to events which are called throughout the code to allow extension of the
 * application and framework.
 */
class EventManager {
    const PRIORITY_LOW = 10;
    const PRIORITY_NORMAL = 100;
    const PRIORITY_HIGH = 1000;
    const PRIORITY_MAX = 1000000;

    const EVENT_META = 'meta';

    /**
     * @var ContainerInterface An IOC container to create lazy objects.
     */
    private $container;

    /**
     * @var int The number of events bound. This is for generating keys, and may not be accurate.
     */
    private $count = 0;

    /**
     * @var array An array of event handlers.
     */
    private $handlers;

    /**
     * @var array The events that still need to be sorted by priority.
     */
    private $toSort = [];

    /**
     * Construct a new instance of of the {@link EventManager} class.
     *
     * @param ContainerInterface|null $container The container used to fetch lazy classes.
     */
    public function __construct(ContainerInterface $container = null) {
        $this->container = $container;
    }

    /**
     * Bind an event handler to an event.
     *
     * @param string $event The name of the event to bind to.
     * @param callable $callback The callback of the event.
     * @param int $priority The priority of the event.
     */
    public function bind($event, callable $callback, $priority = EventManager::PRIORITY_NORMAL) {
        $this->bindInternal($event, $callback, $priority);
    }

    /**
     * Bind an event handler to an event.
     *
     * @param string $event The name of the event to bind to.
     * @param callable|LazyEventHandler $callback The callback of the event.
     * @param int $priority The priority of the event.
     */
    private function bindInternal($event, $callback, $priority = EventManager::PRIORITY_NORMAL) {
        if ($priority > self::PRIORITY_MAX) {
            trigger_error("Events cannot have a priority greater than ".self::PRIORITY_MAX.'.', E_USER_NOTICE);
            $priority = self::PRIORITY_MAX;
        }

        $event = strtolower($event);
        $sortKey = (self::PRIORITY_MAX - $priority).'e'.$this->count;
        $this->handlers[$event][$sortKey] = $callback;
        $this->toSort[$event] = true;
        $this->count++;
    }

    /**
     * Remove an event handler.
     *
     * @param string $event The name of the event to unbind.
     * @param callable $callback The event handler to remove.
     */
    public function unbind($event, callable $callback) {
        $event = strtolower($event);

        if (!empty($this->handlers[$event]) && $index = array_search($callback, $this->handlers[$event], true)) {
            unset($this->handlers[$event][$index]);
        }
    }

    /**
     * Bind a class' declared event handlers.
     *
     * Plugin classes declare event handlers in the following way:
     *
     * ```
     * // Bind to a normal event.
     * public function eventName_handler($arg1, $arg2, ...) { ... }
     *
     * // Add/override a method called with Event::callUserFuncArray().
     * public function className_methodName($sender, $arg1, $arg2) { ... }
     * public function className_methodName_create($sender, $arg1, $arg2) { ... } // deprecated
     *
     * // Call the handler before or after a method called with Event::callUserFuncArray().
     * public function className_methodName_before($sender, $arg1, $arg2) { ... }
     * public function className_methodName_after($sender, $arg1, $arg2) { ... }
     * ```
     *
     * @param mixed $class The class name or an object instance.
     * @param int $priority The priority of the event.
     * @throws \InvalidArgumentException Throws an exception when binding to a class name with no `instance()` method.
     */
    public function bindClass($class, $priority = EventManager::PRIORITY_NORMAL) {
        $methodNames = get_class_methods($class);

        foreach ($methodNames as $method) {
            if (strpos($method, '_') == false) { // == instead of === filters out methods starting with _
                continue;
            }

            $method = strtolower($method);
            $suffix = strrchr($method, '_');
            $basename = substr($method, 0, -strlen($suffix));
            switch ($suffix) {
                case '_handler':
                case '_override':
                    $eventName = $basename;
                    break;
                case '_create':
                case '_method':
                    $eventName = $basename.'_method';
                    break;
                case '_before':
                case '_after':
                default:
                    $eventName = $method;
                    break;
            }
            // Bind the event if we have one.
            if ($eventName) {
                $callback = is_string($class) ? new LazyEventHandler($class, $method) : [$class, $method];

                $this->bindInternal($eventName, $callback, $priority);
            }
        }
    }

    /**
     * Remove all of the events associated with a class.
     *
     * Note that this walks all event handlers so should not be called very often.
     *
     * @param string|object $class The name of the class to unbind.
     */
    public function unbindClass($class) {
        foreach ($this->handlers as $event => $handlers) {
            foreach ($handlers as $key => $handler) {
                if ($handler instanceof LazyEventHandler && is_string($class) && strcasecmp($handler->class, $class) === 0) {
                    unset($this->handlers[$event][$key]);
                    continue;
                }
                if (!is_array($handler)) {
                    continue;
                }
                if (is_object($class)) {
                    if ($handler[0] === $class) {
                        unset($this->handlers[$event][$key]);
                    }
                } elseif (is_string($handler[0]) && strcasecmp($handler[0], $class) === 0) {
                    unset($this->handlers[$event][$key]);
                } elseif (is_object($handler[0]) && is_a($handler[0], $class)) {
                    unset($this->handlers[$event][$key]);
                }
            }
        }
    }

    /**
     * Bind a lazy event handler to an event.
     *
     * A lazy event handler will fetch the instance from the container when the event is fired.
     *
     * @param string $event The name of the event to bind to.
     * @param string $class The name of the class of the event handler.
     * @param string $method The name of the method to call.
     * @param int $priority The priority of the event.
     */
    public function bindLazy($event, $class, $method, $priority = EventManager::PRIORITY_NORMAL) {
        $this->bindInternal($event, new LazyEventHandler($class, $method), $priority);
    }

    /**
     * Strip the namespace from a class.
     *
     * @param string|object $class The name of the class or a class instance.
     * @return string Returns the base name as a string.
     */
    public static function classBasename($class) {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (($i = strrpos($class, '\\')) !== false) {
            $result = substr($class, $i + 1);
        } else {
            $result = $class;
        }
        return $result;
    }

    /**
     * Checks if an event has a handler.
     *
     * @param string $event The name of the event.
     * @return bool Returns **true** if the event has at least one handler, **false** otherwise.
     */
    public function hasHandler($event) {
        return !empty($this->handlers[strtolower($event)]);
    }

    /**
     * Fire an event handler, but only on a class.
     *
     * @param string|object $class The class or instance to fire on.
     * @param string $event The name of event.
     * @param mixed ...$args The event arguments.
     * @return mixed|null Returns the result of the event handler or **null** if no event handler was found.
     */
    public function fireClass($class, $event, ...$args) {
        $handlers = $this->getHandlers($event);

        if (empty($handlers)) {
            return null;
        }

        foreach ($handlers as $callback) {
            if (!is_array($callback)) {
                continue;
            }
            $instance = $callback[0];

            if ($instance === $class || (is_string($class) && is_object($instance) && is_a($instance, $class))) {
                call_user_func_array($callback, $args);
            }
        }
    }

    /**
     * Fire an event.
     *
     * @param string $event The name of the event.
     * @param mixed ...$args Any arguments to pass along to the event handlers.
     * @return array Returns the result of the event handlers where each handler's result is an item in the array.
     */
    public function fire($event, ...$args) {
        $handlers = $this->getHandlers($event);

        if (empty($handlers) && empty($this->handlers[self::EVENT_META])) {
            return [];
        }

        // Do some backwards compatible kludges here.
//        if (count($args) === 1 && is_object($args[0]) && property_exists($args[0], 'EventArguments')) {
//            $args[] = $args[0]->EventArguments;
//        }

        $result = [];
        foreach ($handlers as $callback) {
            $result[] = call_user_func_array($callback, $args);
        }

        // Call the meta event if it's there.
        if (!empty($this->handlers[self::EVENT_META])) {
            $this->callMetaHandlers($event, $args, $result);
        }

        return $result;
    }

    /**
     * Fire a deprecated event.
     *
     * This method is the same as {@link EventManager::fire()} except will trigger an *E_USER_DEPRECATED* notice if there
     * are any event handlers.
     *
     * @param string $event The name of the event.
     * @param mixed ...$args Any arguments to pass along to the event handlers.
     * @return array Returns the result of the event handlers.
     */
    public function fireDeprecated($event, ...$args) {
        if ($this->hasHandler($event)) {
            trigger_error("The $event event is deprecated.", E_USER_DEPRECATED);
            return $this->fire($event, ...$args);
        }
        return [];
    }

    /**
     * Get all of handlers.
     *
     * @return array Returns all the handlers.
     */
    public function getAllHandlers() {
        return $this->handlers;
    }

    /**
     * Get all of the handlers bound to an event.
     *
     * @param string $name The name of the event.
     * @return array Returns the handlers that are watching {@link $name}.
     */
    public function getHandlers($name) {
        $name = strtolower($name);

        if (!isset($this->handlers[$name])) {
            return [];
        }

        // See if the handlers need to be sorted.
        if (isset($this->toSort[$name])) {
            $this->sortHandlers($this->handlers[$name]);

            // Convert lazy event handlers to callbacks.
            foreach ($this->handlers[$name] as &$handler) {
                if ($handler instanceof LazyEventHandler) {
                    $handler = [$this->container->get($handler->class), $handler->method];
                }
            }

            unset($this->toSort[$name]);
        }

        return $this->handlers[$name];
    }

    /**
     * Sort an event handler array.
     *
     * This method is useful in combination with {@link EventManager::getHandlers()}.
     *
     * @param array &$handlers The event handler array.
     */
    public function sortHandlers(array &$handlers) {
        uksort($handlers, 'strnatcasecmp');
    }

    /**
     * Call the meta event handlers for an event.
     *
     * Event handlers can attach to a special "meta" event to get information about all fired events. When any event is
     * fired, the meta event handlers are called.
     *
     * The use of meta event handlers are intended for debugging-style plugins and should be used sparingly as they
     * incur a significant performance overhead.
     *
     * @param string $event The name of the event being fired.
     * @param array $args The arguments being called with the event.
     * @param mixed $result The result of the call.
     */
    private function callMetaHandlers($event, array $args, $result) {
        $metaHandlers = $this->getHandlers(self::EVENT_META);
        foreach ($metaHandlers as $metaHandler) {
            call_user_func($metaHandler, $event, $args, $result);
        }
    }

    /**
     * Chain several event handlers together.
     *
     * This method will fire the first handler and pass its result as the first argument to the next event handler and
     * so on. A chained event handler can have more than one parameter, but must have at least one parameter.
     *
     * @param string $event The name of the event to fire.
     * @param mixed $value The value to pass into the filter.
     * @param array $args Any arguments the event takes.
     * @return mixed The result of the chained event or `$value` if there were no handlers.
     */
    public function fireFilter($event, $value, ...$args) {
        $handlers = $this->getHandlers($event);
        if (empty($handlers) && empty($this->handlers[self::EVENT_META])) {
            return $value; // gotcha, return value
        }

        $result = $value;
        foreach ($handlers as $callback) {
            $result = call_user_func($callback, $result, ...$args);
        }

        // Call the meta event if it's there.
        if (!empty($this->handlers[self::EVENT_META])) {
            $this->callMetaHandlers($event, array_merge([$value], $args), $result);
        }

        return $result;
    }

    /**
     * Fire an event with an array of arguments.
     *
     * This method is to {@link EventManager::fire()} as {@link call_user_func_array()} is to {@link call_user_funct()}.
     * The main purpose though is to allow you to have event handlers that can take references.
     *
     * @param string $event The name of the event.
     * @param array $args The arguments for the event handlers.
     * @return mixed Returns the result of the last event handler.
     */
    public function fireArray($event, $args = []) {
        $handlers = $this->getHandlers($event);

        if (empty($handlers) && empty($this->handlers[self::EVENT_META])) {
            return [];
        }

        // Grab the handlers and call them.
        $result = [];
        foreach ($handlers as $callback) {
            $result[] = call_user_func_array($callback, $args);
        }

        // Call the meta event if it's there.
        if (!empty($this->handlers[self::EVENT_META])) {
            $this->callMetaHandlers($event, $args, $result);
        }

        return $result;
    }

    /**
     * Get the IOC container.
     *
     * @return ContainerInterface Returns the container.
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * Set the IOC container.
     *
     * @param ContainerInterface $container The new container.
     *
     * @return EventManager Returns `$this` for fluent calls.
     */
    public function setContainer($container) {
        $this->container = $container;
        return $this;
    }

    /**
     * For debugging.
     *
     * @return array
     */
//    public function dumpAllHandlers() {
//        return $this->handlers;
//    }
}
