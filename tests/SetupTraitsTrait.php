<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Vanilla\Utility\StringUtils;

/**
 * Use this trait to automatically execute the setup/teardown methods of test traits.
 *
 * To use this trait do the following:
 *
 * 1. Use the trait in the highest base class of your test cases.
 * 2. If you want setUp/tearDown in your test traits then name their methods: `setup$traitName`. You can omit the "Trait" suffix.
 * 2. Call `setupTestTraits()` and `teatDownTestTraits()` in your `setUp()/tearDown()` methods.
 */
trait SetupTraitsTrait {
    /**
     * Call the test trait methods with a given prefix.
     *
     * @param string|object $obj
     * @param string $prefix
     */
    private static function callTestTraits($obj, string $prefix): void {
        $calls = [];
        for ($class = new \ReflectionClass(static::class); $class->getParentClass(); $class = $class->getParentClass()) {
            $uses = $class->getTraits();
            foreach ($uses as $trait) {
                /** @var \ReflectionClass $trait */
                $method = $prefix.$trait->getShortName();
                foreach ([$method, StringUtils::substringRightTrim($method, 'Trait', true)] as $methodName) {
                    if (method_exists($obj, $methodName)) {
                        array_unshift($calls, $methodName);
                    }
                }
            }
        }
        foreach ($calls as $call) {
            call_user_func([$obj, $call]);
        }
    }

    /**
     * Call all set up trait methods.
     */
    public static function setUpBeforeClassTestTraits(): void {
        self::callTestTraits(static::class, 'setUpBeforeClass');
    }

    /**
     * Call all set up trait methods.
     */
    public function setUpTestTraits(): void {
        self::callTestTraits($this, 'setUp');
    }

    /**
     * Call all tear down trait methods.
     */
    public function tearDownTestTraits(): void {
        self::callTestTraits($this, 'tearDown');
    }

    /**
     * Call all tear down trait methods.
     */
    public static function tearDownAfterClassTestTraits(): void {
        self::callTestTraits(static::class, 'tearDownAfterClass');
    }
}
