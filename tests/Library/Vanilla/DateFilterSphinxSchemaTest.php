<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Vanilla\DateFilterSphinxSchema;
use VanillaTests\SharedBootstrapTestCase;
use DateTimeImmutable;

/**
 * Class DateFilterSphinxSchemaTest
 * check implementation: https://github.com/vanilla/knowledge/pull/310
 */
class DateFilterSphinxSchemaTest extends SharedBootstrapTestCase {
    /**
     * Test static method DateFilterSphinxSchema::dateFilterRange
     *
     * @param string $dateString A representation of request data.
     *               Ex: '2017-01-01', '>2017-01-01 00:00:10', '[2017-01-01 00:00:00,2017-01-31 00:00:10]'
     * @param array $expectedResult
     * @dataProvider provideDateFilterRange
     */
    public function testDateFilterRange(string $dateString, array $expectedResult) {
        $schema = new DateFilterSphinxSchema();
        $data = $schema->validate($dateString);
        $result = DateFilterSphinxSchema::dateFilterRange($data);
        $this->assertEquals($expectedResult, $result);
    }
    /**
     * Provider data for testDateFilterRange.
     */
    public function provideDateFilterRange() {
        return [
            'Equal (Date)' => [
                '2017-01-01',
                [
                    'startDate' => new DateTimeImmutable('2017-01-01 00:00:00'),
                    'endDate' => new DateTimeImmutable('2017-01-01 23:59:59'),
                    'exclude' => false
                ]
            ],
            'Equal (DateTime)' => [
                '2017-01-01 00:00:00',
                [
                    'startDate' => new DateTimeImmutable('2017-01-01 00:00:00'),
                    'endDate' => new DateTimeImmutable('2017-01-01 00:00:00'),
                    'exclude' => false
                ]
            ],
            'Greater-Than (Date)' => [
                '>2017-01-01',
                [
                    'startDate' => new DateTimeImmutable('2017-01-01 00:00:00'),
                    'endDate' => null,
                    'exclude' => true
                ]
            ],
            'Greater-Than (DateTime)' => [
                '>2017-01-01 00:00:10',
                [
                    'startDate' => new DateTimeImmutable('2017-01-01 00:00:10'),
                    'endDate' => null,
                    'exclude' => true
                ]
            ],
            'Greater-Than or Equal (Date)' => [
                '>=2017-01-01',
                [
                    'startDate' => new DateTimeImmutable('2017-01-01 00:00:00'),
                    'endDate' => null,
                    'exclude' => false
                ]
            ],
            'Greater-Than or Equal (DateTime)' => [
                '>=2017-01-01 00:00:10',
                [
                    'startDate' => new DateTimeImmutable('2017-01-01 00:00:10'),
                    'endDate' => null,
                    'exclude' => false
                ]
            ],
            'Less-Than (Date)' => [
                '<2017-01-01',
                [
                    'startDate' => null,
                    'endDate' => new DateTimeImmutable('2017-01-01 00:00:00'),
                    'exclude' => true
                ]
            ],
            'Less-Than (DateTime)' => [
                '<2017-01-01 00:00:10',
                [
                    'startDate' => null,
                    'endDate' => new DateTimeImmutable('2017-01-01 00:00:10'),
                    'exclude' => true
                ]
            ],
            'Less-Than or Equal (Date)' => [
                '<=2017-01-01',
                [
                    'startDate' => null,
                    'endDate' => new DateTimeImmutable('2017-01-01 23:59:59'),
                    'exclude' => false
                ]
            ],
            'Less-Than or Equal (DateTime)' => [
                '<=2017-01-01 00:00:10',
                [
                    'startDate' => null,
                    'endDate' => new DateTimeImmutable('2017-01-01 00:00:10'),
                    'exclude' => false
                ]
            ],
            'Range Inclusive (Date,Date)' => [
                '[2017-01-01,2017-01-31]',
                [
                    'startDate' => new DateTimeImmutable('2017-01-01 00:00:00'),
                    'endDate' => new DateTimeImmutable('2017-01-31 23:59:59'),
                    'exclude' => false
                ]
            ],
            'Range Inclusive (DateTime,DateTime)' => [
                '[2017-01-01 00:00:00,2017-01-31 00:00:10]',
                [
                    'startDate' => new DateTimeImmutable('2017-01-01 00:00:00'),
                    'endDate' => new DateTimeImmutable('2017-01-31 00:00:10'),
                    'exclude' => false
                ]
            ],
            'Range Inclusive (Date,DateTime)' => [
                '[2017-01-01,2017-01-31 00:00:10]',
                [
                    'startDate' => new DateTimeImmutable('2017-01-01 00:00:00'),
                    'endDate' => new DateTimeImmutable('2017-01-31 00:00:10'),
                    'exclude' => false
                ]
            ],
            'Range Exclusive (Date,Date)' => [
                '(2017-01-01,2017-01-31)',
                [
                    'startDate' => new DateTimeImmutable('2017-01-02 00:00:00'),
                    'endDate' => new DateTimeImmutable('2017-01-30 23:59:59'),
                    'exclude' => true
                ]
            ],
            'Range Exclusive (DateTime,DateTime)' => [
                '(2017-01-01 00:00:10,2017-01-31 00:00:50)',
                [
                    'startDate' => new DateTimeImmutable('2017-01-01 00:00:11'),
                    'endDate' => new DateTimeImmutable('2017-01-31 00:00:49'),
                    'exclude' => true
                ]
            ]
        ];
    }
}
