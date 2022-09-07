<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

/**
 * Trait InvokeMethodTrait.
 *
 * @deprecated Use `VanillaTestCase::invokeMethod()` and `VanillaTestCase::callOn()` instead.
 */
trait InvokeMethodTrait
{
    /**
     * Call protected/private method of a class.
     *
     * Do not abuse this since it's not super fast!
     *
     * @param object|string $target Instantiated object or class that we will run method on.
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public static function invokeMethod($target, $methodName, array $parameters = [])
    {
        return VanillaTestCase::invokeMethod($target, $methodName, $parameters);
    }

    /**
     * Call a closure on a target object with private member access.
     *
     * @param object $on
     * @param \Closure $callable
     * @param mixed $args
     * @return mixed
     */
    protected static function callOn(object $on, \Closure $callable, ...$args)
    {
        return VanillaTestCase::callOn($on, $callable, ...$args);
    }
}
