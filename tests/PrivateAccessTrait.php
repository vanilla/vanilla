<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

/**
 * Class PrivateAccessTrait
 */
trait PrivateAccessTrait {
    /**
     * Call a closure on another object to access its private properties.
     *
     * FOR TESTING ONLY.
     *
     * @param object $on The object to bind the closure to.
     * @param \Closure $callable The closure to bind.
     * @param array $args The arguments to pass to the call.
     * @return mixed Returns the result of the call.
     */
    public static function callOn(object $on, \Closure $callable, ...$args) {
        $fn = $callable->bindTo($on, $on);
        return $fn(...$args);
    }
}
