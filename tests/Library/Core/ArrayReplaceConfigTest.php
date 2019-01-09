<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;

/**
 * Tests for the **arrayReplaceConfig** function.
 */
class ArrayReplaceConfigTest extends SharedBootstrapTestCase {

    /**
     * An empty default should return the override.
     */
    public function testEmptyDefault() {
        $r = arrayReplaceConfig([], ['a' => 'b']);
        $this->assertEquals(['a' => 'b'], $r);
    }

    /**
     * An empty override should return the default.
     */
    public function testEmptyOverride() {
        $r = arrayReplaceConfig(['a' => 'b'], []);
        $this->assertEquals(['a' => 'b'], $r);
    }

    /**
     * Numeric arrays should be replaced completely.
     */
    public function testNumericOverride() {
        $r = arrayReplaceConfig(['a' => [1, 2, 3]], ['a' => [2, 3]]);
        $this->assertEquals(['a' => [2, 3]], $r);
    }

    /**
     * Nested keyed arrays should be merged like **array_replace_recursive()**.
     */
    public function testNestedMerge() {
        $r = arrayReplaceConfig(['a' => ['b' => 1]], ['a' => ['c' => 2]]);
        $this->assertEquals(['a' => ['b' => 1, 'c' => 2]], $r);
    }

    /**
     * Nested numeric arrays should be replaced.
     */
    public function testNestedNumericReplace() {
        $r = arrayReplaceConfig(['a'=> ['b' => ['c' => [1, 2]]]], ['a'=> ['b' => ['c' => [3, 4, 5]]]]);
        $this->assertEquals(['a'=> ['b' => ['c' => [3, 4, 5]]]], $r);
    }

    /**
     * Test a numeric array default with no override.
     */
    public function testNoOverrideNumeric() {
        $r = arrayReplaceConfig(['a' => [1, 2, 3]], ['b' => 1]);
        $this->assertEquals(['a' => [1, 2, 3], 'b' => 1], $r);
    }

    /**
     * Test a scalar value default with a numeric array override.
     */
    public function testOverwriteScalarWithNumeric() {
        $r = arrayReplaceConfig(['a' => 1], ['a' => [1, 2, 3]]);
        $this->assertEquals(['a' => [1, 2, 3]], $r);
    }

    /**
     * A numeric array should be overwritten by a keyed array.
     */
    public function testOverrideNumericWithKeyed() {
        $r = arrayReplaceConfig(['a' => [1, 2, 3]], ['a' => ['b' => 333]]);
        $this->assertEquals(['a' => ['b' => 333]], $r);
    }
}
