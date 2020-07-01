<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

/**
 * @method assertSame(mixed $expected, mixed $actual)
 */
trait ModelTestTrait {

    /**
     * Assert some arrays of IDs are the same.
     *
     * @param array $expectedIDs
     * @param array $actualIDs
     */
    private function assertIDsEqual(array $expectedIDs, array $actualIDs) {
        $this->assertSame($this->normalizeIDs($expectedIDs), $this->normalizeIDs($actualIDs));
    }

    /**
     * Normalize some record IDs.
     *
     * @param array $ids
     * @return array
     */
    private function normalizeIDs(array $ids): array {
        $fixedIDs = array_map(function ($id) {
            return (int) $id;
        }, $ids);
        sort($fixedIDs);
        return $fixedIDs;
    }
}
