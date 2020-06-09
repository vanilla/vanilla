<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Garden\Schema\Invalid;
use Garden\Schema\Validation;
use Garden\Schema\ValidationField;
use PHPUnit\Framework\TestCase;
use Vanilla\Utility\SchemaUtils;

/**
 * Tests for the `SchemaUtils` class.
 */
class SchemaUtilsTest extends TestCase {
    /**
     * Test some only one of error cases.
     *
     * @param array $properties
     * @param int $count
     * @dataProvider provideOnlyOneOfErrors
     */
    public function testOnlyOneOfErrors(array $properties, int $count = 1) {
        $field = new ValidationField(new Validation(), [], '');
        $fn = SchemaUtils::onlyOneOf($properties, $count);

        $value = $fn(new \ArrayObject(['a' => 1, 'b' => 1, 'c' => 1]), $field);
        $this->assertSame(Invalid::value(), $value);
        $this->assertFalse($field->isValid());
        $this->assertStringContainsString(implode(', ', $properties), $field->getValidation()->getMessage());
    }

    /**
     * Provide some only one of errors.
     *
     * @return array
     */
    public function provideOnlyOneOfErrors(): array {
        $r = [
            'a, b' => [['a', 'b']],
            'a, b, c' => [['a', 'b', 'c'], 2],
        ];
        return $r;
    }

    /**
     * Test some only one of happy paths.
     *
     * @param array $properties
     * @param int $count
     * @dataProvider provideOnlyOneOfHappy
     */
    public function testOnlyOneOfHappy(array $properties, int $count = 1) {
        $field = new ValidationField(new Validation(), [], '');
        $fn = SchemaUtils::onlyOneOf($properties, $count);

        $value = $fn(['a' => 1, 'b' => 1], $field);
        $this->assertSame(['a' => 1, 'b' => 1], $value);
        $this->assertTrue($field->isValid());
    }

    /**
     * Provide some only one of errors.
     *
     * @return array
     */
    public function provideOnlyOneOfHappy(): array {
        $r = [
            'a, c' => [['a', 'c']],
            'a, b, c' => [['a', 'b', 'c'], 2],
        ];
        return $r;
    }

    /**
     * The only one of validator should only validate arrayish values.
     */
    public function testOnlyOneOfNotArray(): void {
        $field = new ValidationField(new Validation(), [], '');
        $fn = SchemaUtils::onlyOneOf(['a', 'b']);

        $value = $fn('foo', $field);
        $this->assertSame('foo', $value);
    }
}
