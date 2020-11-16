<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use PHPUnit\Framework\TestCase;

/**
 * Dirty cache for tests.
 */
class TestCache extends \Gdn_Dirtycache {

    /**
     * Assert count of successful cache gets.
     *
     * @param string $key
     * @param int $expected
     */
    public function assertGetCount(string $key, int $expected) {
        TestCase::assertEquals($expected, $this->countGets[$key] ?? 0);
    }

    /**
     * Assert count of successful cache sets.
     *
     * @param string $key
     * @param int $expected
     */
    public function assertSetCount(string $key, int $expected) {
        TestCase::assertEquals($expected, $this->countSets[$key] ?? 0);
    }
}
