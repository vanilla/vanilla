<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use Garden\Schema\Schema;
use Vanilla\DateFilterSchema;

/**
 * Tests for the date filter schema.
 */
class DateFilterSchemaTest extends TestCase
{
    /**
     * Provide invalid date filter strings to test error conditions.
     */
    public function provideInvalidDateFilters()
    {
        return [
            "empty string" => ["", "myDate cannot be empty."],
            "bad operator" => ["<>2015-07-30", "is not a valid operator"],
            "bad date" => ["2015-01-50", "myDate is not a valid datetime"],
            "null" => [null, "cannot be empty"],
            "true" => [true, "myDate is not a valid datetime"],
            "false" => [false, "myDate is not a valid datetime"],
            "Range: one date" => ["[2010-01-01]", "range must contain two values"],
            "Range: year-only" => [
                "[2010,2011]",
                "myDate.from is not a valid datetime. myDate.to is not a valid datetime.",
            ],
            "Range: three elements" => ["[2010-01-01,2010-01-15,2010-01-31]", "range must contain two values"],
            "Range: later opening" => ["[2012-01-01,2011-12-31]", "End of myDate range must come after beginning."],
            "Range: incorrect delimiter 1" => ['[2017-12-04,2017-12-06$', "is not a valid datetime"],
            "Range: incorrect delimiter 2" => ["[2017-12-04,2017-12-06[", "is not a valid datetime"],
            "Range: incorrect delimiter 4" => ["(2017-12-04,2017-12-06}", "is not a valid datetime"],
        ];
    }

    /**
     * Provide validate date filter strings and and their expected result.
     *
     * @return array
     */
    public function provideValidDateFilters()
    {
        return [
            "2012-10-31 00:01:01" => ["2012-10-31 00:01:01", "=", [new DateTimeImmutable("2012-10-31 00:01:01")]],
            "2012-10-31" => [
                "2012-10-31",
                "[]",
                [new DateTimeImmutable("2012-10-31 00:00:00"), new DateTimeImmutable("2012-10-31 23:59:59")],
            ],
            "=2012-10-31" => [
                "=2012-10-31",
                "[]",
                [new DateTimeImmutable("2012-10-31 00:00:00"), new DateTimeImmutable("2012-10-31 23:59:59")],
            ],
            ">=2017-09-01" => [">=2017-09-01", ">=", [new DateTimeImmutable("2017-09-01 00:00:00")]],
            "<2017-07-01" => ["<2017-07-01", "<", [new DateTimeImmutable("2017-07-01")]],
            "[2017-01-01,2017-01-31]" => [
                "[2017-01-01,2017-01-31]",
                "[]",
                [new DateTimeImmutable("2017-01-01 00:00:00"), new DateTimeImmutable("2017-01-31 23:59:59")],
            ],
            "[2017-01-01,2017-01-31)" => [
                "[2017-01-01,2017-01-31)",
                "[)",
                [new DateTimeImmutable("2017-01-01 00:00:00"), new DateTimeImmutable("2017-01-30 23:59:59")],
            ],
            "(2017-01-01,2017-01-31]" => [
                "(2017-01-01,2017-01-31]",
                "(]",
                [new DateTimeImmutable("2017-01-02 00:00:00"), new DateTimeImmutable("2017-01-31 23:59:59")],
            ],
        ];
    }

    /**
     * Test parsing valid date strings.
     *
     * @param string $dateFilter
     * @param string $operator
     * @param string|array $value
     * @dataProvider provideValidDateFilters
     */
    public function testStringParsing(string $dateFilter, string $operator, $value)
    {
        $data = ["myDate" => $dateFilter];
        $schema = Schema::parse([
            "myDate" => new DateFilterSchema(),
        ]);
        $validated = $schema->validate($data);

        $this->assertEquals($operator, $validated["myDate"]["operator"]);

        $this->assertContainsOnlyInstancesOf("DateTimeImmutable", $validated["myDate"]["date"]);
        $this->assertEquals($value, $validated["myDate"]["date"]);
    }

    /**
     * Test parsing invalid date strings.
     *
     * @param string|bool|null $dateFilter
     * @param string $errorMessage
     * @dataProvider provideInvalidDateFilters
     */
    public function testStringErrors($dateFilter, string $errorMessage)
    {
        $this->expectException(\Garden\Schema\ValidationException::class);

        $data = ["myDate" => $dateFilter];
        $schema = Schema::parse([
            "myDate" => new DateFilterSchema(),
        ]);

        $this->expectExceptionMessage($errorMessage);
        $schema->validate($data);
    }

    /**
     * Tests for date fields.
     *
     * @param string $fieldName The name of a field.
     * @param string $expr A representation of request data (e.g. query string or request body).
     * @param array $expectedFilter
     * @param DateTimeImmutable[] $expectedRange
     *
     * @dataProvider provideDateFilterFields
     */
    public function testDateFilterField(string $fieldName, string $expr, array $expectedFilter, array $expectedRange)
    {
        $schema = new DateFilterSchema();
        $range = $schema->validate($expr);
        $filter = DateFilterSchema::dateFilterField($fieldName, $range);
        $this->assertEquals($expectedFilter, $filter, "Invalid filter for $expr");
        $this->assertRangesEquals($expectedRange, $range["inclusiveRange"], "Invalid range for $expr");
    }

    /**
     * Provider data for testDateFilterField.
     */
    public function provideDateFilterFields()
    {
        $farPast = DateFilterSchema::farPastDate();
        $farFuture = DateFilterSchema::farFutureDate();
        return [
            "Equal (Date)" => [
                "dateInserted",
                "2017-01-01",
                [
                    "dateInserted >=" => new DateTimeImmutable("2017-01-01 00:00:00"),
                    "dateInserted <=" => new DateTimeImmutable("2017-01-01 23:59:59"),
                ],
                [new DateTimeImmutable("2017-01-01 00:00:00"), new DateTimeImmutable("2017-01-01 23:59:59")],
            ],
            "Equal (DateTime)" => [
                "dateInserted",
                "2017-01-01 00:00:00",
                [
                    "dateInserted" => new DateTimeImmutable("2017-01-01 00:00:00"),
                ],
                [new DateTimeImmutable("2017-01-01 00:00:00"), new DateTimeImmutable("2017-01-01 00:00:00")],
            ],
            "Greater-Than (Date)" => [
                "dateInserted",
                ">2017-01-01",
                ["dateInserted >" => new DateTimeImmutable("2017-01-01 23:59:59")],
                [new DateTimeImmutable("2017-01-02 00:00:00"), $farFuture],
            ],
            "Greater-Than (DateTime)" => [
                "dateInserted",
                ">2017-01-01 00:00:10",
                ["dateInserted >" => new DateTimeImmutable("2017-01-01 00:00:10")],
                [new DateTimeImmutable("2017-01-01 00:00:11"), $farFuture],
            ],
            "Greater-Than or Equal (Date)" => [
                "dateInserted",
                ">=2017-01-01",
                ["dateInserted >=" => new DateTimeImmutable("2017-01-01 00:00:00")],
                [new DateTimeImmutable("2017-01-01 00:00:00"), $farFuture],
            ],
            "Greater-Than or Equal (DateTime)" => [
                "dateInserted",
                ">=2017-01-01 00:00:10",
                ["dateInserted >=" => new DateTimeImmutable("2017-01-01 00:00:10")],
                [new DateTimeImmutable("2017-01-01 00:00:10"), $farFuture],
            ],
            "Less-Than (Date)" => [
                "dateInserted",
                "<2017-01-01",
                ["dateInserted <" => new DateTimeImmutable("2017-01-01 00:00:00")],
                [$farPast, new DateTimeImmutable("2016-12-31 23:59:59")],
            ],
            "Less-Than (DateTime)" => [
                "dateInserted",
                "<2017-01-01 00:00:10",
                ["dateInserted <" => new DateTimeImmutable("2017-01-01 00:00:10")],
                [$farPast, new DateTimeImmutable("2017-01-01 00:00:09")],
            ],
            "Less-Than or Equal (Date)" => [
                "dateInserted",
                "<=2017-01-01",
                ["dateInserted <=" => new DateTimeImmutable("2017-01-01 23:59:59")],
                [$farPast, new DateTimeImmutable("2017-01-01 23:59:59")],
            ],
            "Less-Than or Equal (DateTime)" => [
                "dateInserted",
                "<=2017-01-01 00:00:10",
                ["dateInserted <=" => new DateTimeImmutable("2017-01-01 00:00:10")],
                [$farPast, new DateTimeImmutable("2017-01-01 00:00:10")],
            ],
            "Range Inclusive (Date,Date)" => [
                "dateInserted",
                "[2017-01-01,2017-01-31]",
                [
                    "dateInserted >=" => new DateTimeImmutable("2017-01-01 00:00:00"),
                    "dateInserted <=" => new DateTimeImmutable("2017-01-31 23:59:59"),
                ],
                [new DateTimeImmutable("2017-01-01 00:00:00"), new DateTimeImmutable("2017-01-31 23:59:59")],
            ],
            "Range Inclusive (DateTime,DateTime)" => [
                "dateInserted",
                "[2017-01-01 00:00:00,2017-01-31 00:00:10]",
                [
                    "dateInserted >=" => new DateTimeImmutable("2017-01-01 00:00:00"),
                    "dateInserted <=" => new DateTimeImmutable("2017-01-31 00:00:10"),
                ],
                [new DateTimeImmutable("2017-01-01 00:00:00"), new DateTimeImmutable("2017-01-31 00:00:10")],
            ],
            "Range Inclusive (Date,DateTime)" => [
                "dateInserted",
                "[2017-01-01,2017-01-31 00:00:10]",
                [
                    "dateInserted >=" => new DateTimeImmutable("2017-01-01 00:00:00"),
                    "dateInserted <=" => new DateTimeImmutable("2017-01-31 00:00:10"),
                ],
                [new DateTimeImmutable("2017-01-01 00:00:00"), new DateTimeImmutable("2017-01-31 00:00:10")],
            ],
            "Range Exclusive (Date,Date)" => [
                "dateInserted",
                "(2017-01-01,2017-01-31)",
                [
                    "dateInserted >=" => new DateTimeImmutable("2017-01-02 00:00:00"),
                    "dateInserted <=" => new DateTimeImmutable("2017-01-30 23:59:59"),
                ],
                [new DateTimeImmutable("2017-01-02 00:00:00"), new DateTimeImmutable("2017-01-30 23:59:59")],
            ],
            "Range Exclusive (DateTime,DateTime)" => [
                "dateInserted",
                "(2017-01-01 00:00:10,2017-01-31 00:00:50)",
                [
                    "dateInserted >=" => new DateTimeImmutable("2017-01-01 00:00:11"),
                    "dateInserted <=" => new DateTimeImmutable("2017-01-31 00:00:49"),
                ],
                [new DateTimeImmutable("2017-01-01 00:00:11"), new DateTimeImmutable("2017-01-31 00:00:49")],
            ],
            "Range Inclusive, Exclusive (Date,Date)" => [
                "dateInserted",
                "[2017-01-01,2017-01-31)",
                [
                    "dateInserted >=" => new DateTimeImmutable("2017-01-01 00:00:00"),
                    "dateInserted <=" => new DateTimeImmutable("2017-01-30 23:59:59"),
                ],
                [new DateTimeImmutable("2017-01-01 00:00:00"), new DateTimeImmutable("2017-01-30 23:59:59")],
            ],
            "Range Inclusive, Exclusive (DateTime,DateTime)" => [
                "dateInserted",
                "[2017-01-01 00:00:10,2017-01-31 00:10:00)",
                [
                    "dateInserted >=" => new DateTimeImmutable("2017-01-01 00:00:10"),
                    "dateInserted <=" => new DateTimeImmutable("2017-01-31 00:09:59"),
                ],
                [new DateTimeImmutable("2017-01-01 00:00:10"), new DateTimeImmutable("2017-01-31 00:09:59")],
            ],
            "Range Inclusive, Exclusive (DateTime,Date)" => [
                "dateInserted",
                "[2017-01-01 00:00:10,2017-01-31)",
                [
                    "dateInserted >=" => new DateTimeImmutable("2017-01-01 00:00:10"),
                    "dateInserted <=" => new DateTimeImmutable("2017-01-30 23:59:59"),
                ],
                [new DateTimeImmutable("2017-01-01 00:00:10"), new DateTimeImmutable("2017-01-30 23:59:59")],
            ],
            "Range Exclusive, Inclusive (Date,Date)" => [
                "dateInserted",
                "(2017-01-01,2017-01-31]",
                [
                    "dateInserted >=" => new DateTimeImmutable("2017-01-02 00:00:00"),
                    "dateInserted <=" => new DateTimeImmutable("2017-01-31 23:59:59"),
                ],
                [new DateTimeImmutable("2017-01-02 00:00:00"), new DateTimeImmutable("2017-01-31 23:59:59")],
            ],
            "Range Exclusive, Inclusive (DateTime,DateTime)" => [
                "dateInserted",
                "(2017-01-01 00:00:10,2017-01-31 00:10:00]",
                [
                    "dateInserted >=" => new DateTimeImmutable("2017-01-01 00:00:11"),
                    "dateInserted <=" => new DateTimeImmutable("2017-01-31 00:10:00"),
                ],
                [new DateTimeImmutable("2017-01-01 00:00:11"), new DateTimeImmutable("2017-01-31 00:10:00")],
            ],
        ];
    }

    /**
     * Test the far future and past dates.
     */
    public function testFarDates()
    {
        $this->assertEquals(
            "1970-01-01T00:00:00+00:00",
            DateFilterSchema::farPastDate()->format(DateTimeImmutable::RFC3339)
        );
        $this->assertEquals(
            "2300-01-01T00:00:00+00:00",
            DateFilterSchema::farFutureDate()->format(DateTimeImmutable::RFC3339)
        );
    }

    /**
     * Assert that 2 date ranges are equal.
     *
     * @param DateTimeImmutable[] $expectedRange
     * @param DateTimeImmutable[] $actualRange
     * @param string $message
     */
    public function assertRangesEquals(array $expectedRange, array $actualRange, string $message = "")
    {
        $expectedRange = [$expectedRange[0]->format(DATE_RFC3339), $expectedRange[1]->format(DATE_RFC3339)];
        $actualRange = [$actualRange[0]->format(DATE_RFC3339), $actualRange[1]->format(DATE_RFC3339)];

        $this->assertEquals($expectedRange, $actualRange, $message);
    }
}
