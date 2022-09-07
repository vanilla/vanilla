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
class TestCache extends \Gdn_Dirtycache
{
    /**
     * Assert that our cache is empty.
     */
    public function assertEmpty(): void
    {
        TestCase::assertEmpty($this->cache, "Expected cache to be empty.");
    }

    /**
     * Assert that our cache is not empty.
     */
    public function assertNotEmpty(): void
    {
        TestCase::assertNotEmpty($this->cache, "Expected cache not to be empty.");
    }

    /**
     * Assert count of successful cache gets.
     *
     * @param string $keyPattern
     * @param int $expected
     */
    public function assertGetCount(string $keyPattern, int $expected)
    {
        TestCase::assertEquals($expected, $this->getCount($keyPattern, $this->countGets));
    }

    /**
     * Assert count of successful cache sets.
     *
     * @param string $keyPattern
     * @param int $expected
     */
    public function assertSetCount(string $keyPattern, int $expected)
    {
        TestCase::assertEquals($expected, $this->getCount($keyPattern, $this->countSets));
    }

    /**
     * Fetch a count of gets.
     *
     * @param string $keyPattern The key too lookup. Null for all keys.
     *
     * @return int
     */
    public function getGetCount(string $keyPattern): int
    {
        return $this->getCount($keyPattern, $this->countGets);
    }

    /**
     * Fetch a count of sets.
     *
     * @param string $keyPattern The key too lookup. Null for all keys.
     *
     * @return int
     */
    public function getSetCount(string $keyPattern): int
    {
        return $this->getCount($keyPattern, $this->countSets);
    }

    /**
     * Fetch a count of gets or sets.
     *
     * @param string $keyPattern The key too lookup. Null for all keys.
     * @param array $from The collection to pull from.
     *
     * @return int
     */
    private function getCount(string $keyPattern, array $from): int
    {
        $actual = 0;
        foreach ($from as $actualKey => $item) {
            if (!fnmatch($keyPattern, $actualKey)) {
                continue;
            }
            $actual += $item;
        }
        return $actual;
    }
}
