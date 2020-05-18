<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use PHPUnit\Framework\TestCase;
use Vanilla\Schema\RangeExpression;

/**
 * Test the `RangeExpression` class.
 *
 */
class RangeExpressionTest extends TestCase {
    /**
     * Test basic parsing.
     *
     * @param string $expression
     * @param string $fromOp
     * @param string $toOp
     * @dataProvider provideRangeExpressions
     */
    public function testParseRange(string $expression, string $fromOp, string $toOp = null): void {
        /** @var RangeExpression $range */
        $range = RangeExpression::createSchema()->validate($expression);

        $ops = array_keys($range->getValues());
        $values = array_values($range->getValues());

        $this->assertSame($fromOp, $ops[0], 'From Op:');
        $this->assertSame('foo', $values[0], 'From:');
        $this->assertSame($toOp ?? $fromOp, $ops[1], 'To Op:');
        $this->assertSame('bar', $values[1], 'To:');
    }

    /**
     * Provide tests for parsing.
     *
     * @return array
     */
    public function provideRangeExpressions(): array {
        $r = [
            ['foo..bar', '>=', '<='],
            ['[foo,bar', '>=', '<='],
            ['[foo..bar]', '>=', '<='],
            ['(foo,bar)', '>', '<'],
            ['foo..bar)', '>=', '<'],
            ['(foo...bar', '>', '<='],
        ];

        $r = array_column($r, null, 0);
        return $r;
    }

    /**
     * Test parsing with operators.
     *
     * @param string $expr
     * @param string $op
     * @dataProvider provideExpressions
     */
    public function testParseOperator(string $expr, string $op): void {
        /** @var RangeExpression $range */
        $range = RangeExpression::createSchema()->validate($expr);
        $values = $range->getValues();

        $this->assertCount(1, $values);

        $value = reset($values);
        $actualOp = key($values);

        $this->assertSame('foo', $value, 'Value:');
        $this->assertSame($op, $actualOp, 'Op:');
    }

    /**
     * Provide operator expression tests.
     *
     * @return array
     */
    public function provideExpressions(): array {
        $r = [
            ['>foo', '>'],
            ['>= foo', '>='],
            ['=foo', '='],
            ['foo', '='],
            ['<foo', '<'],
            ['<=foo', '<='],
        ];

        $r = array_column($r, null, 0);
        return $r;
    }

    /**
     * Test a custom value schema.
     *
     * @param string $expr
     * @dataProvider provideIntegerRanges
     */
    public function testIntegerValueSchema(string $expr): void {
        /** @var RangeExpression $range */
        $range = RangeExpression::createSchema([':int'])->validate($expr);
        foreach ($range->getValues() as $op => $value) {
            $this->assertIsInt($value);
        }
    }

    /**
     * Provide some integer range tests.
     *
     * @return array
     */
    public function provideIntegerRanges(): array {
        $r = [
            ['>1'],
            ['>= 123'],
            ['=123'],
            ['234'],
            ['1..1000'],
            ['1..'],
        ];

        $r = array_column($r, null, 0);
        return $r;
    }

    /**
     * You should be able to pass a CSV of data to the provider.
     *
     * @param string|array $expr
     * @param array $expected
     * @param array|null $schema
     * @dataProvider provideCSVStrings
     */
    public function testCSVParsing($expr, array $expected, array $schema = null): void {
        if ($schema !== null) {
            $schema = Schema::parse($schema);
        }

        $actual = RangeExpression::parse($expr, $schema);
        $this->assertSame($expected, $actual->getValues()['=']);
    }

    /**
     * @return array
     */
    public function provideCSVStrings(): array {
        $r = [
            'a,b' => ['a,b', ['a', 'b']],
            'spaces' => ['a, b, c', ['a', 'b', 'c']],
            'int' => ['1,2', [1, 2], [':int']],
            'array' => [[1, 2], [1, 2]],
            'strip array keys' => [['a' => 1], [1]],
            'array schema' => [['1'], [1], [':int']]
        ];
        return $r;
    }

    /**
     * An invalid operator name should not be allowed.
     */
    public function testInvalidOperator(): void {
        $this->expectException(\InvalidArgumentException::class);
        $range = new RangeExpression('bla', '');
    }

    /**
     * An invalid value should throw an exception.
     */
    public function testInvalidValue(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('is not a valid integer');
        $range = RangeExpression::parse(
            '3,foo',
            Schema::parse([':int'])
        );
    }

    /**
     * Check basic expession validity.
     */
    public function testInvalidSchemaValidate(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("not a valid range expression");
        $valid = RangeExpression::createSchema()->validate(new \DateTimeImmutable());
    }

    /**
     * Test a parsed string's `__toString()` preservation.
     *
     * @param string $expr
     * @dataProvider provideParsedStrings
     */
    public function testParsedToString(string $expr) {
        $actual = RangeExpression::parse($expr, null, true)->__toString();
        $this->assertSame($expr, $actual);
    }

    /**
     * Test a parsed string's `__toString()` canonicalization..
     *
     * @param string $expr
     * @param string $expected
     * @dataProvider provideParsedStrings
     */
    public function testCanonicalToString(string $expr, string $expected) {
        $actual = RangeExpression::parse($expr)->__toString();
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide some expressions and canonicalizations.
     *
     * @return array
     */
    public function provideParsedStrings() {
        $r = [
            ['a, b', 'a,b'],
            ['[a,b]', 'a..b'],
            ['a..b)', '[a,b)'],
            ['=a', 'a'],
            ['a..', '>=a'],
            ['..a', '<=a'],
        ];

        return array_column($r, null, 0);
    }

    /**
     * Test an empty from part of the range.
     */
    public function testEmptyFrom(): void {
        $actual = RangeExpression::parse('..a')->__toString();
        $this->assertSame('<=a', $actual);
    }
}
