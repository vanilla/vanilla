<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationField;
use PHPUnit\Framework\TestCase;
use Vanilla\Utility\SchemaFilters;

/**
 * Tests for SchemaFilters class
 */

class SchemaFiltersTest extends TestCase {

    /**
     * Test encodeValue() and decodeValue() with empty string.
     */
    public function testEncodeEmptyString() {
        $testValidation = new Validation();
        $testSchema = new Schema('foo');
        $testField = new ValidationField($testValidation, $testSchema, $testSchema);
        $testValue = '';
        $encoded = SchemaFilters::encodeValue($testValue, $testField);
        $this->assertSame(null, $encoded);

        $decoded = SchemaFilters::decodeValue($encoded, $testField);
        $this->assertSame(null, $decoded);
    }

    /**
     * Test encodeValue() and decodeValue() with array.
     */
    public function testEncodeString() {
        $testValidation = new Validation();
        $testSchema = new Schema('foo');
        $testField = new ValidationField($testValidation, $testSchema, $testSchema);
        $testValue = ['foo' => 'bar'];
        $encoded = SchemaFilters::encodeValue($testValue, $testField);
        $this->assertSame('{"foo":"bar"}', $encoded);

        $decoded = SchemaFilters::decodeValue($encoded, $testField);
        $this->assertSame($testValue, $decoded);
    }
}
