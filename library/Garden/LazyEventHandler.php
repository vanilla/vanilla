<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden;

/**
 * Contains information about a callback that requires a class to be obtained from a container beforehand.
 *
 * This class is meant to be used internally by the {@link EventManager}. It contains the least amount of information to
 * conserve memory. Properties are left public for quicker access.
 *
 * This class is basically a tuple. The reasons arrays aren't used are the following:
 *
 * 1. Arrays are valid callbacks and these lazy event handlers will live side-by-side with callbacks.
 * 2. [Objects can use less memory than arrays](https://gist.github.com/nikic/5015323).
 */
class LazyEventHandler {
    /**
     * @var string The name of the class.
     */
    public $class;
    /**
     * @var string The name of the method to call.
     */
    public $method;

    /**
     * Instantiate a new instance of the {@link LazyEventHandler} class.
     *
     * @param string $class The name of the class.
     * @param string $method The name of the method.
     */
    public function __construct($class, $method) {
        $this->class = $class;
        $this->method = $method;
    }
}
