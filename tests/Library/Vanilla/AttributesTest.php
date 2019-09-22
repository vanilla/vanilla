<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Attributes;

/**
 * Tests for the **Attributes** class.
 */
class AttributesTest extends SharedBootstrapTestCase {
    /**
     * Empty attributes should encode as a JSON object.
     */
    public function testEncodeEmpty() {
        $attr = new Attributes();
        $this->assertEquals('{}', json_encode($attr));
    }

    /**
     * Numeric arrays should encode as JSON arrays.
     */
    public function testEncodeNumeric() {
        $attr = new Attributes([1, 2]);
        $this->assertEquals('[1,2]', json_encode($attr));
    }

    /**
     * Nested empty arrays should encode as JSON arrays.
     */
    public function testNestedEmpty() {
        $attr = new Attributes(['a' => []]);
        $this->assertEquals('{"a":[]}', json_encode($attr));
    }

    /**
     * Nested attributes should encode properly.
     */
    public function testNestedEmptyAttributes() {
        $attr = new Attributes(['a' => new Attributes()]);
        $this->assertEquals('{"a":{}}', json_encode($attr));
    }

    /**
     * Dates should properly encode within attributes.
     */
    public function testDateEncoding() {
        $attr = new Attributes(['dt' => new \DateTimeImmutable('2019-09-22', new \DateTimeZone('Z'))]);
        $actual = json_encode($attr);
        $this->assertSame('{"dt":"2019-09-22T00:00:00+00:00"}', $actual);
    }
}
