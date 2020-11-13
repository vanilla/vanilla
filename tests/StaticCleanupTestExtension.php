<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use PHPUnit\Runner\AfterTestHook;
use Vanilla\CurrentTimeStamp;

/**
 * Test extension to cleanup polluted static globals after every test.
 */
final class StaticCleanupTestExtension implements AfterTestHook {

    /**
     * Cleanup.
     *
     * @param string $test
     * @param float $time
     */
    public function executeAfterTest(string $test, float $time): void {
        CurrentTimeStamp::clearMockTime();
    }
}
