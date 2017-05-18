<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden\Web;

/**
 * Represents a callback that has been routed from a {@link Request}.
 *
 * This class is invokable which means that it can be thought about as a callback for simple dispatchers or inspected
 * to infer additional information for more complex dispatchers.
 */
class Action {

    private $callback;

    private $args;

    /**
     * @var array Meta information about the action.
     */
    private $meta;

    /**
     * Create an new instance of an {@link Action}.
     *
     * @param callable $callback The callback that will be invoked for the action.
     * @param array $args The arguments passed to the action.
     */
    public function __construct(callable $callback, array $args = []) {
        $this->callback = $callback;
        $this->args = $args;
    }

    /**
     * Get the callback for the action.
     *
     * @return mixed Returns the callback.
     */
    public function getCallback() {
        return $this->callback;
    }

    /**
     * Set the callback for the action.
     *
     * @param callable $callback The new callback.
     * @return $this
     */
    public function setCallback(callable $callback) {
        $this->callback = $callback;
        return $this;
    }

    /**
     * Get the args that will be applied to the action when called.
     *
     * @return array Returns the arguments.
     */
    public function getArgs() {
        return $this->args;
    }

    /**
     * Set the args that will be applied to the action when called.
     *
     * @param array $args The new arguments.
     * @return $this
     */
    public function setArgs(array $args) {
        $this->args = $args;
        return $this;
    }

    /**
     * Call the callback with the arguments.
     */
    public function __invoke() {
        $result = call_user_func_array($this->callback, $this->args);
        return $result;
    }

    /**
     * Get the a meta value.
     *
     * @param string $name The name of the meta value.
     * @param mixed $default The value to return if there is no meta value.
     * @return mixed Returns a meta value or **null** if one does not exist.
     */
    public function getMeta($name, $default = null) {
        return isset($this->meta[$name]) ? $this->meta[$name] : $default;
    }

    /**
     * Set a meta value.
     *
     * @param string $name The meta key to set.
     * @param mixed $value The meta value to set.
     * @return $this
     */
    public function setMeta($name, $value) {
        $this->meta[$name] = $value;
        return $this;
    }
}
