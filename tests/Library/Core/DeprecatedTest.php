<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for deprecated().
 */

class DeprecatedTest extends TestCase {

    /**
     * Test {@link deprecated()} against two scenarios ($newName and !$newName).
     *
     * @param string $testOldName The name of the deprecated function.
     * @param string $testNewName The name of the new function that should be used instead.
     * @dataProvider provideDeprecatedArrays
     */
    public function testDeprecated($testOldName, $testNewName) {
        $this->expectDeprecation();
        $this->expectDeprecationMessage("$testOldName is deprecated. Use $testNewName instead");
        deprecated($testOldName, $testNewName);
    }

    /**
     * Provide test data for {@link deprecated()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideDeprecatedArrays() {
        $r = [
            'normalCase' => [
                'deprecatedFunction',
                'newFunction',
                trigger_error('deprecatedFunction is deprecated. Use newFunction instead.', E_USER_DEPRECATED),
            ],
            'noNewName' => [
                'deprecatedFunction',
                trigger_error('deprecatedFunction is deprecated.')
            ]
        ];

        return $r;
    }
}
