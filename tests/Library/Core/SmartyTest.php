<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the `Gdn_Smarty` class.
 */
class SmartyTest extends TestCase {
    /**
     * Some keys should be removed.
     */
    public function testSanitizeRemove() {
        $arr = ['Password' => 'a', 'AccessToken' => 'a', 'Fingerprint' => 'a', 'Updatetoken' => 'a'];
        $actual = \Gdn_Smarty::sanitizeVariables($arr);
        $this->assertEmpty($actual);
    }

    /**
     * Some keys should be obscured.
     */
    public function testSanitizeObscure() {
        $arr = [
            'insertipaddress' => 'a',
            'updateipaddress' => 'a',
            'lastipaddress' => 'a',
            'allipaddresses' => 'a',
            'dateofbirth' => 'a',
            'hashmethod' => 'a',
            'email' => 'a',
            'firstemail' => 'a',
            'lastemail' => 'a',
        ];

        $actual = \Gdn_Smarty::sanitizeVariables($arr);

        foreach ($actual as $key => $value) {
            $this->assertSame('***OBSCURED***', $value);
        }
    }

    /**
     * Arrays should sanitize recursively.
     */
    public function testArrayRecurse() {
        $arr = [
            'a' => [
                'b' => 'c',
                'password' => 'foo',
                'lastEmail' => 'bar',
            ],
        ];

        $expected = [
            'a' => [
                'b' => 'c',
                'lastEmail' => '***OBSCURED***',
            ],
        ];

        $actual = \Gdn_Smarty::sanitizeVariables($arr);
        $this->assertSame($expected, $actual);
    }

    /**
     * A nested object should be sanitized, but not change the original object.
     */
    public function testStdClass() {
        $arr = [
            'a' => (object)[
                'b' => 'c',
                'password' => 'foo',
            ],
        ];

        $actual = \Gdn_Smarty::sanitizeVariables($arr);
        $this->assertSame('foo', $arr['a']->password);
        $this->assertInstanceOf(\stdClass::class, $actual['a']);
        $this->assertNotTrue(isset($actual['a']->password));
    }
}
