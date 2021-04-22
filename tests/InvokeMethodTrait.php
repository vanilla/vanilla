<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;


/**
 * Trait InvokeMethodTrait.
 */
trait InvokeMethodTrait {
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
     * @throws \ReflectionException
     */
    protected function invokeMethod($target, $methodName, array $parameters = []) {
        $reflection = new \ReflectionClass($target);

        if (is_object($target)) {
            $instance = $target;
        } else {
            $instance = null;
        }

        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        if ($method->isStatic()) {
            return $method->invokeArgs(null, $parameters);
        } else {
            if (!isset($instance)) {
                throw new \Exception('Cannot call an instance method on a class.');
            }
            return $method->invokeArgs($target, $parameters);
        }
    }
}
