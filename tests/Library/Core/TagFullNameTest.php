<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for tagFullName().
 */

class TagFullNameTest extends TestCase {

    /**
     * Test for {@link tagFullName()} with 'FullName' field.
     */
    public function testTagFullName() {
        $testRow = ["FullName" => "John Q Public"];
        $expected = "John Q Public";
        $actual = tagFullName($testRow);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test for {@link tagFullName()} with 'Name' field.
     */
    public function testTagFullNameWithOnlyNameField() {
        $testRow = ["Name" => "John"];
        $expected = "John";
        $actual = tagFullName($testRow);
        $this->assertSame($expected, $actual);
    }
}
