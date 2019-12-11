<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;
use VanillaTests\BootstrapTrait;

/**
 * Tests for the `Gdn_Format` class.
 */
class FormatTest extends TestCase {
    use BootstrapTrait;

    /**
     * Test formatting an object that references itself.
     */
    public function testObjectFormatWithRecursiveReference() {
        $obj = new \stdClass();
        $obj->foo = '>';
        $obj->bar = $obj;

        $actual = \Gdn_Format::to($obj, 'display');

        $this->assertEquals('&gt;', $actual->foo);
        $this->assertSame($actual, $actual->bar);
    }

    /**
     * Test formatting an object with circular references.
     */
    public function testObjectFormatWithCircularReferences() {
        $a = new \stdClass();
        $a->foo = '>';
        $a->bar = new \stdClass();
        $a->bar->baz = '<';
        $a->bar->qux = $a;

        $actual = \Gdn_Format::to($a, 'display');

        $this->assertEquals('&gt;', $actual->foo);
        $this->assertEquals('&lt;', $actual->bar->baz);
        $this->assertSame($actual, $actual->bar->qux);
    }

    /**
     * Test formatting a recursive array.
     */
    public function testArrayFormatWithRecursiveReference() {
        $a = [
            'foo' => '>'
        ];
        $a['bar'] = &$a;

        $actual = \Gdn_Format::to($a, 'display');

        $this->assertEquals('&gt;', $a['foo']);
    }

    /**
     * Formatting an array should not modify the original array.
     */
    public function testArrayNonCorruption() {
        $a = ['>'];

        $actual = \Gdn_Format::to($a, 'display');

        $this->assertEquals(['&gt;'], $actual);
        $this->assertEquals(['>'], $a);
    }

    /**
     * An object listed twice inside an array should only format once.
     */
    public function testNoDoubleFormat() {
        $a = new \stdClass();
        $a->foo = '<';

        $arr = [$a, $a];

        $actual = \Gdn_Format::to($arr, 'display');
        $this->assertEquals('&lt;', $actual[0]->foo);
    }
}
