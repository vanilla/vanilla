<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateNoLinks().
 */
class ValidateNoLinksTest extends TestCase {

    /**
     * Test string with a link.
     */
    public function testValidateNoLinksFalse() {
        $actual = validateNoLinks('this-string-has-https://a-link');
        $expected = false;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test string without a link.
     */
    public function testValidateNoLinksTrue() {
        $actual = validateNoLinks('this-string-has-no-links');
        $expected = true;
        $this->assertSame($expected, $actual);
    }
}
