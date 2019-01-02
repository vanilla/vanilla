<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use PHPUnit\Framework\TestCase;

/**
 * A class to run a test in totally new process. This ensures that the test is running as if it was alone.
 *
 * Each individual test runs in its own process.
 *
 * If you use this you will need to do anything not including in tests/phpunit.php yourself.
 */
class IsolatedTestCase extends TestCase {
    /** @var bool Set this to have each test run separately without the same autoload. */
    protected $runTestInSeparateProcess = true;

    /**
     * @var bool Set this so that all global state is thrown away before running the test.
     *
     * This prevents issues with container pollution.
     */
    protected $preserveGlobalState = false;
}
