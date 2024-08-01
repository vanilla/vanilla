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
trait PrivateAccessTrait
{
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
    public static function callOn(object $on, \Closure $callable, ...$args)
    {
        $fn = $callable->bindTo($on, $on);
        return $fn(...$args);
    }

    /**
     * Call a protected or private method on an object.
     *
     * @param object $on The object to call the method on.
     * @param string $method The name of the method to call.
     * @param array $args Arguments to pass to the method.
     * @return mixed Returns the result of the method.
     * @psalm-suppress InvalidScope
     */
    public static function callMethodOn(object $on, string $method, ...$args)
    {
        $fn = function (...$args) use ($method) {
            // phpcs:disable Squiz.Scope.StaticThisUsage
            return $this->$method(...$args);
            // phpcs:enable Squiz.Scope.StaticThisUsage
        };
        return self::callOn($on, $fn, ...$args);
    }
}
