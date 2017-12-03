<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla;

use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use Garden\Schema\Schema;
use Vanilla\DateFilterSchema;

class DateFilterSchemaTest extends TestCase {

    /**
     * Provide invalid date filter strings to test error conditions.
     */
    public function provideInvalidDates() {
        return [
            'empty string' => [''],
            'bad operator' => ['x2015-07-30'],
            'bad date' => ['2015-01-50'],
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'Range: one date' => ['[2010-01-01]'],
            'Range: year-only' => ['[2010,2011]'],
            'Range: three elements' => ['[2010-01-01,2010-01-15,2010-01-31]'],
            'Range: later opening' => ['[2012-01-01,2011-12-31]']
        ];
    }

    /**
     * Provide validate date filter strings and and their expected result.
     *
     * @return array
     */
    public function provideValidDates() {
        return [
            '2012-10-31' => [
                '2012-10-31',
                '=',
                [
                    new DateTimeImmutable('2012-10-31 00:00:00'),
                    new DateTimeImmutable('2012-10-31 23:59:59')
                ]
            ],
            '>=2017-09-01' => [
                '>=2017-09-01',
                '>=',
                new DateTimeImmutable('2017-09-01')
            ],
            '<2017-07-01' => [
                '<2017-07-01',
                '<',
                new DateTimeImmutable('2017-07-01')
            ],
            '[2017-01-01,2017-01-31]' => [
                '[2017-01-01,2017-01-31]',
                '[]',
                [
                    new DateTimeImmutable('2017-01-01 00:00:00'),
                    new DateTimeImmutable('2017-01-31 23:59:59')
                ]
            ],
            '[2017-01-01,2017-01-31)' => [
                '[2017-01-01,2017-01-31)',
                '[)',
                [
                    new DateTimeImmutable('2017-01-01 00:00:00'),
                    new DateTimeImmutable('2017-01-30 23:59:59')
                ]
            ],
            '(2017-01-01,2017-01-31]' => [
                '(2017-01-01,2017-01-31]',
                '(]',
                [
                    new DateTimeImmutable('2017-01-02 00:00:00'),
                    new DateTimeImmutable('2017-01-31 23:59:59')
                ]
            ]
        ];
    }

    /**
     * Test parsing valid date strings.
     *
     * @param string $input
     * @param string $operator
     * @param string|array $value
     * @dataProvider provideValidDates
     */
    public function testStringParsing($input, $operator, $value) {
        $data = ['myDate' => $input];
        $schema = Schema::parse([
            'myDate' => new DateFilterSchema()
        ]);
        $validated = $schema->validate($data);

        $this->assertEquals($operator, $validated['myDate']['op']);

        if (is_array($value)) {
            $this->assertContainsOnlyInstancesOf('DateTimeImmutable', $validated['myDate']['value']);
        } else {
            $this->assertInstanceOf('DateTimeImmutable', $validated['myDate']['value']);
        }
        $this->assertEquals($value, $validated['myDate']['value']);
    }

    /**
     * Test parsing invalid date strings.
     *
     * @param string $input
     * @dataProvider provideInvalidDates
     * @expectedException Garden\Schema\ValidationException
     */
    public function testStringErrors($input) {
        $data = ['myDate' => $input];
        $schema = Schema::parse([
            'myDate' => new DateFilterSchema()
        ]);

        $schema->validate($data);
    }
}
