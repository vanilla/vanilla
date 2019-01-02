<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Addon;


/**
 * Tests of the {@link Addon::checkVersion()} method.
 */
class CheckVersionTest extends SharedBootstrapTestCase {
    /**
     * Check exact version matching.
     */
    public function testExact() {
        $this->assertTrue(Addon::checkVersion('1.0.2', '1.0.2'));
        $this->assertFalse(Addon::checkVersion('1.0.2', '2.0.3'));
    }

    /**
     * Check comparison operators.
     *
     * @param string $version The version to check.
     * @param string $requirement The requirement to check against.
     * @param bool $expected Whether the check should pass or fail.
     * @dataProvider provideComparisonChecks
     */
    public function testComparisons($version, $requirement, $expected) {
        $actual = Addon::checkVersion($version, $requirement);

//        if ($actual !== $expected) {
//            $again = Addon::checkVersion($version, $requirement);
//        }

        $this->assertSame(
            $actual,
            $expected
        );
    }

    /**
     * Test that a version with boolean operators works.
     *
     * @param string $version The version to check.
     * @param string $requirement The requirement to check against.
     * @param bool $expected Whether the check should pass or fail.
     * @dataProvider provideBooleanLogicChecks
     */
    public function testBooleanLogic($version, $requirement, $expected) {
        $actual = Addon::checkVersion($version, $requirement);

//        if ($actual !== $expected) {
//            $again = Addon::checkVersion($version, $requirement);
//        }

        $this->assertSame(
            $actual,
            $expected
        );
    }

    /**
     * Provide some tests for {@link testBooleanLogic}.
     *
     * @return array Returns a data provider.
     */
    public function provideBooleanLogicChecks() {
        $r = [
            ['1.5', '1.5'],
            ['1.5', '>=1.0 <2.0'],
            ['1.5', '>=1.0,<2.0'],
            ['1.5', '>=1.0, <2.0'],
            ['2.1', '>=1.0 <2.0', false],
            ['1.3', '>=1.0 <1.1 || >=1.2'],
            ['1.0', '>=1.0 <1.1 || >=1.2']
        ];

        return $this->makeProvider($r);
    }

    /**
     * Provide a bunch of basic version comparisons.
     *
     * @return array Returns a data provider array.
     */
    public function provideComparisonChecks() {
        $r = [
            ['2.0', '>1.0.0'],
            ['1.0', '> 2.0', false],
            ['2.0', '>=2.0'],
            ['2.0', '>= 1.0'],
            ['1.0', ' >=2.0', false],
            ['1.0', '<2.0'],
            ['2.0', '<1.0', false],
            ['1.0', '<=2.0'],
            ['1.0', '<=1.0'],
            ['2.0', '<=1.0', false],
            ['1.0', '!=2.0'],
            ['1.0', '!=1.0', false],
            ['1.1', '1.0 - 2.0'],
            ['3.0', '1.0 - 2.0', false]
        ];
        return $this->makeProvider($r);
    }

    /**
     * Make a provider that will report its passes/failures better.
     *
     * @param array $array The array to reformat.
     * @return array Returns a data provider array.
     */
    private function makeProvider(array $array) {
        $r = [];
        foreach ($array as $row) {
            $row += [2 => true];
            $r["{$row[0]} {$row[1]}"] = $row;
        }
        return $r;
    }

    /**
     * Assert that a check version succeeds.
     *
     * @param string $version The version to check.
     * @param string $requirement The version requirement.
     * @param string $message An optional failure message.
     */
    protected function assertCheckVersion($version, $requirement, $message = '') {
        $this->assertTrue(Addon::checkVersion($version, $requirement), $message);
    }

    /**
     * Assert that a check version fails.
     *
     * @param string $version The version to check.
     * @param string $requirement The version requirement.
     * @param string $message An optional failure message.
     */
    protected function assertCheckVersionFail($version, $requirement, $message = '') {
        $this->assertFalse(Addon::checkVersion($version, $requirement), $message);
    }
}
