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
use Vanilla\Schema\DateRangeExpression;
use Vanilla\Schema\LegacyDateRangeExpression;
use Vanilla\Schema\RangeExpression;
use VanillaTests\VanillaTestCase;

/**
 * Test the `RangeExpression` class.
 *
 */
class RangeExpressionTest extends TestCase
{
    /**
     * Test basic parsing.
     *
     * @param string $expression
     * @param string $fromOp
     * @param ?string $toOp
     * @dataProvider provideRangeExpressions
     */
    public function testParseRange(string $expression, string $fromOp, string $toOp = null): void
    {
        /** @var RangeExpression $range */
        $range = RangeExpression::createSchema()->validate($expression);

        $ops = array_keys($range->getValues());
        $values = array_values($range->getValues());

        $this->assertSame($fromOp, $ops[0], "From Op:");
        $this->assertSame("foo", $values[0], "From:");
        $this->assertSame($toOp ?? $fromOp, $ops[1], "To Op:");
        $this->assertSame("bar", $values[1], "To:");
    }

    /**
     * Provide tests for parsing.
     *
     * @return array
     */
    public function provideRangeExpressions(): array
    {
        $r = [
            ["foo..bar", ">=", "<="],
            ["[foo,bar", ">=", "<="],
            ["[foo..bar]", ">=", "<="],
            ["(foo,bar)", ">", "<"],
            ["foo..bar)", ">=", "<"],
            ["(foo...bar", ">", "<="],
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
    public function testParseOperator(string $expr, string $op): void
    {
        /** @var RangeExpression $range */
        $range = RangeExpression::createSchema()->validate($expr);
        $values = $range->getValues();

        $this->assertCount(1, $values);

        $value = reset($values);
        $actualOp = key($values);

        $this->assertSame("foo", $value, "Value:");
        $this->assertSame($op, $actualOp, "Op:");
    }

    /**
     * Provide operator expression tests.
     *
     * @return array
     */
    public function provideExpressions(): array
    {
        $r = [[">foo", ">"], [">= foo", ">="], ["=foo", "="], ["foo", "="], ["<foo", "<"], ["<=foo", "<="]];

        $r = array_column($r, null, 0);
        return $r;
    }

    /**
     * Test a custom value schema.
     *
     * @param string $expr
     * @dataProvider provideIntegerRanges
     */
    public function testIntegerValueSchema(string $expr): void
    {
        /** @var RangeExpression $range */
        $range = RangeExpression::createSchema([":int"])->validate($expr);
        foreach ($range->getValues() as $op => $value) {
            $this->assertIsInt($value);
        }
    }

    /**
     * Provide some integer range tests.
     *
     * @return array
     */
    public function provideIntegerRanges(): array
    {
        $r = [[">1"], [">= 123"], ["=123"], ["234"], ["1..1000"], ["1.."]];

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
    public function testCSVParsing($expr, array $expected, array $schema = null): void
    {
        if ($schema !== null) {
            $schema = Schema::parse($schema);
        }

        $actual = RangeExpression::parse($expr, $schema);
        $this->assertSame($expected, $actual->getValues()["="]);
    }

    /**
     * @return array
     */
    public function provideCSVStrings(): array
    {
        $r = [
            "a,b" => ["a,b", ["a", "b"]],
            "spaces" => ["a, b, c", ["a", "b", "c"]],
            "int" => ["1,2", [1, 2], [":int"]],
            "array" => [[1, 2], [1, 2]],
            "strip array keys" => [["a" => 1], [1]],
            "array schema" => [["1"], [1], [":int"]],
        ];
        return $r;
    }

    /**
     * An invalid operator name should not be allowed.
     */
    public function testInvalidOperator(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("not a valid operator");
        $range = new RangeExpression("bla", "");
    }

    /**
     * An invalid value should throw an exception.
     */
    public function testInvalidValue(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("is not a valid integer");
        $range = RangeExpression::parse("3,foo", Schema::parse([":int"]));
    }

    /**
     * Check basic expession validity.
     */
    public function testInvalidSchemaValidate(): void
    {
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
    public function testParsedToString(string $expr)
    {
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
    public function testCanonicalToString(string $expr, string $expected)
    {
        $actual = RangeExpression::parse($expr)->__toString();
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide some expressions and canonicalizations.
     *
     * @return array
     */
    public function provideParsedStrings()
    {
        $r = [["a, b", "a,b"], ["[a,b]", "a..b"], ["a..b)", "[a,b)"], ["=a", "a"], ["a..", ">=a"], ["..a", "<=a"]];

        return array_column($r, null, 0);
    }

    /**
     * Test that constructed range expressions turn into strings.
     *
     * @param RangeExpression $expression
     * @param string $expected
     *
     * @dataProvider provideConstructedToString
     */
    public function testConstructedtoString(RangeExpression $expression, string $expected)
    {
        $this->assertEquals($expected, (string) $expression);
    }

    /**
     * @return iterable
     */
    public function provideConstructedToString(): iterable
    {
        $expression = new RangeExpression(">", 0);
        $expression = $expression->withFilteredValue("=", [5, 6]);
        yield [$expression, ">0;=5,6"];

        $expression = $expression->withFilteredValue("=", [5]);
        yield [$expression, ">0;=5"];

        $expression = $expression->withFilteredValue("<=", [30]);
        yield [$expression, ">0;=5;<=30"];
    }

    /**
     * Test an empty from part of the range.
     */
    public function testEmptyFrom(): void
    {
        $actual = RangeExpression::parse("..a")->__toString();
        $this->assertSame("<=a", $actual);
    }

    /**
     * Zeros can be an edge case because they are empty.
     */
    public function testZeroRange(): void
    {
        $actual = RangeExpression::parse("[0,0]");
        $this->assertSame("0", $actual->getValues()[">="]);
        $this->assertSame("0", $actual->getValues()["<="]);
    }

    /**
     * Whitespace should be trimmed.
     */
    public function testWhitespace(): void
    {
        $actual = RangeExpression::parse("( 0 , 1 )");
        $this->assertSame("0", $actual->getValues()[">"]);
        $this->assertSame("1", $actual->getValues()["<"]);
    }

    /**
     * Just whitespace shouldn't be evaluated to a range.
     */
    public function testJustWhitespace(): void
    {
        $actual = RangeExpression::parse("(0, )");
        $this->assertArrayNotHasKey("<", $actual->getValues());

        $actual = RangeExpression::parse("( ,1)");
        $this->assertArrayNotHasKey(">", $actual->getValues());
    }

    /**
     * The value wither should return a new instance with the new value set.
     */
    public function testWithValue(): void
    {
        $r1 = RangeExpression::parse("[1,2]");
        $r2 = $r1->withValue("<=", 3);
        $this->assertNotSame($r1, $r2);
        $this->assertSame("1..3", (string) $r2);
    }

    /**
     * Adding a filtered value should add it alongside other values.
     */
    public function testWithFilteredValueDifferent(): void
    {
        $r1 = new RangeExpression("=", 1);
        $r2 = $r1->withFilteredValue(">", 5);
        $this->assertNotSame($r1, $r2);
        $this->assertSame(1, $r2->getValue("="));
        $this->assertSame(5, $r2->getValue(">"));
    }

    /**
     * Test `RangeExpression::withFilteredValue()`.
     *
     * @param string $op
     * @param mixed $value
     * @param mixed $expected
     * @dataProvider provideFilterValueMergeTests
     */
    public function testWithFilteredValueMerge(string $op, $value, $expected): void
    {
        $r1 = new RangeExpression($op, 5);
        $r2 = $r1->withFilteredValue($op, $value);
        $this->assertSame(1, count($r2->getValues()), "The new value isn't supposed to add an operator");
        $this->assertSame($expected, $r2->getValue($op));
    }

    /**
     * Provide filter merge test data.
     *
     * @return array
     */
    public function provideFilterValueMergeTests(): array
    {
        $r = [
            "no change >" => [">", 4, 5],
            "no change >=" => [">=", 4, 5],
            "no change <" => ["<", 6, 5],
            "no change <=" => ["<=", 6, 5],

            "change >" => [">", 6, 6],
            "change >=" => [">=", 6, 6],
            "change <" => ["<", 4, 4],
            "change <=" => ["<=", 4, 4],

            "intersect" => ["=", [4, 5, 6], 5],
            "empty" => ["=", [4, 6], []],
        ];
        return $r;
    }

    /**
     * Ensure that a legacy date rage can be reset with its output array.
     */
    public function testLegacyGetSet(): void
    {
        $arr = [
            "operator" => "()",
            "date" => [new \DateTimeImmutable("2021-08-01"), new \DateTimeImmutable("2021-08-21")],
        ];
        $range = LegacyDateRangeExpression::createFromLegacyArray($arr);

        $actual1 = $range->toLegacyArray();
        $this->assertLegacyDateRangeArrayEquals($arr, $actual1);

        $range->fromLegacyArray($actual1);
        $actual2 = $range->toLegacyArray();
        $this->assertLegacyDateRangeArrayEquals($actual1, $actual2);
    }

    /**
     * Assert that two legacy date range expressions are equal.
     *
     * @param array $expected
     * @param array $actual
     * @param string $message
     */
    protected static function assertLegacyDateRangeArrayEquals(array $expected, array $actual, $message = ""): void
    {
        self::assertSame($expected["operator"], $actual["operator"], $message);
        self::assertEquals($expected["date"], $actual["date"], $message);
        if (isset($expected["inclusiveRange"])) {
            self::assertEquals($expected["inclusiveRange"], $actual["inclusiveRange"]);
        }
    }

    /**
     * Test parsing and stringifying date ranges.
     *
     * @param string $expr
     * @param string $expected
     * @dataProvider provideDateRanges
     */
    public function testDateRanges(string $expr, string $expected): void
    {
        $range = DateRangeExpression::parse($expr);
        $actual = (string) $range;
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test date for `testDateRanges`.
     *
     * @return array
     */
    public function provideDateRanges(): array
    {
        $r = [
            ["[2021-06-01T00:00:00Z,2021-06-02T23:59Z]", "2021-06-01T00:00:00+00:00..2021-06-02T23:59:00+00:00"],
            ["[2021-06-01T00:00:00Z,2021-06-02T23:59:59Z]", "2021-06-01T00:00:00+00:00..2021-06-02T23:59:59+00:00"],
            ["noon 2021-08-23", "2021-08-23T12:00:00+00:00"],
            ["2021-08-23", "2021-08-23T00:00:00+00:00..2021-08-23T23:59:59+00:00"],
            [">2021-08-23", ">2021-08-23T23:59:59+00:00"],
            ["<=2021-08-23", "<=2021-08-23T23:59:59+00:00"],
        ];
        return array_column($r, null, 0);
    }
}
