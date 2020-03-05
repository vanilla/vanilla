<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for changeBasename().
 */
class ChangeBaseNameTest extends TestCase {

    /**
     * Test {@link changeBaseName()} against several scenarios.
     *
     * @param string $testPath The path to alter.
     * @param string $testNewBaseName The new basename. A %s will be replaced by the old basename.
     * @param string $expected Expected result.
     * @dataProvider provideChangeBaseNameArrays
     */
    public function testChangeBaseName(string $testPath, string $testNewBaseName, string $expected) {
        $actual = changeBaseName($testPath, $testNewBaseName);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link changeBaseName()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideChangeBaseNameArrays() {
        $r = [
            'baseNameChange' => [
                'https://old-base-name.com/workspaces/rd-forum-5d39bf0a25dac00001318876/issues/vanilla/support/1132',
                'new-base-name',
                'https://new-base-name.com/workspaces/rd-forum-5d39bf0a25dac00001318876/issues/vanilla/support/1132',
            ],
        ];

        return $r;
    }
}
