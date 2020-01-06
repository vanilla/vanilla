<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for foreignIDHash().
 */

class ForeignIDHashTest extends TestCase {

    /**
     * Tests {@link foreignIDHash()} with string under 32 characters.
     */
    public function testForeignIDHashUnder32Chars() {
        $data = '123456789';
        $actual = foreignIDHash($data);
        $this->assertSame($data, $actual);
    }

    /**
     * Test with string over 32 characters
     */
    public function testForeignIDHashOver32Chars() {
        $data = 'This string is absolutely positively longer than 32 characters';
        $expected = md5($data);
        $actual = foreignIDHash($data);
        $this->assertSame($expected, $actual);
    }
}
