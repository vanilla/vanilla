<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use PHPUnit\Framework\TestCase;
use VanillaTests\SiteTestTrait;
use Vanilla\DateFilterSchema;
use DateTimeImmutable;

/**
 * Test concrete methods in AbstractApiController class.
 *
 * @package VanillaTests\APIv2
 */
class AbstractApiControllerTest extends TestCase {

    use SiteTestTrait;

    /**
     * Get a mock abstract API controller.
     *
     * @return \AbstractApiController
     */
    private function getMockController() {
        /** @var \AbstractApiController $result */
        $result = $this->getMockForAbstractClass('AbstractApiController');
        return $result;
    }

    /**
     * @param string $param The name of a parameter.
     * @param array $data A representation of request data (e.g. query string or request body).
     * @param array|bool $expected
     * @param string|null $field
     * @dataProvider provideDateFilterFields
     */
    public function testDateFilterField($param, array $data, $field, $expected) {
        /** @var \AbstractApiController $controller */
        $controller = $this->getMockController();
        $result = $controller->dateFilterField($param, $data, $field);
        $this->assertEquals($expected, $result);
    }

    /**
     *
     */
    public function provideDateFilterFields() {
        $schema = new DateFilterSchema();

        return [
            'Equal' => [
                'dateInserted',
                ['dateInserted' => $schema->validate('2017-01-01')],
                null,
                [
                    'dateInserted >=' => new DateTimeImmutable('2017-01-01 00:00:00'),
                    'dateInserted <=' => new DateTimeImmutable('2017-01-01 23:59:59'),
                ]
            ],
            'Greater-Than' => [
                'dateInserted',
                ['dateInserted' => $schema->validate('>2017-01-01')],
                null,
                ['dateInserted >' => new DateTimeImmutable('2017-01-01 00:00:00')]
            ],
            'Greater-Than or Equal' => [
                'dateInserted',
                ['dateInserted' => $schema->validate('>=2017-01-01')],
                null,
                ['dateInserted >=' => new DateTimeImmutable('2017-01-01 00:00:00')]
            ],
            'Less-Than' => [
                'dateInserted',
                ['dateInserted' => $schema->validate('<2017-01-01')],
                null,
                ['dateInserted <' => new DateTimeImmutable('2017-01-01 00:00:00')]
            ],
            'Less-Than or Equal' => [
                'dateInserted',
                ['dateInserted' => $schema->validate('<=2017-01-01')],
                null,
                ['dateInserted <=' => new DateTimeImmutable('2017-01-01 00:00:00')]
            ],
            'Not Set' => [
                'dateUpdated',
                ['dateInserted' => $schema->validate('<=2017-01-01')],
                null,
                false
            ],
            'Rename Field' => [
                'inserted',
                ['inserted' => $schema->validate('<=2017-01-01')],
                'dateInserted',
                ['dateInserted <=' => new DateTimeImmutable('2017-01-01 00:00:00')]
            ],
            'Range Inclusive' => [
                'dateInserted',
                ['dateInserted' => $schema->validate('[2017-01-01,2017-01-31]')],
                null,
                [
                    'dateInserted >=' => new DateTimeImmutable('2017-01-01 00:00:00'),
                    'dateInserted <=' => new DateTimeImmutable('2017-01-31 23:59:59')
                ]
            ],
            'Range Exclusive' => [
                'dateInserted',
                ['dateInserted' => $schema->validate('(2017-01-01,2017-01-31)')],
                null,
                [
                    'dateInserted >=' => new DateTimeImmutable('2017-01-02 00:00:00'),
                    'dateInserted <=' => new DateTimeImmutable('2017-01-30 23:59:59')
                ]
            ],
            'Range Inclusive, Exclusive' => [
                'dateInserted',
                ['dateInserted' => $schema->validate('[2017-01-01,2017-01-31)')],
                null,
                [
                    'dateInserted >=' => new DateTimeImmutable('2017-01-01 00:00:00'),
                    'dateInserted <=' => new DateTimeImmutable('2017-01-30 23:59:59')
                ]
            ],
            'Range Exclusive, Inclusive' => [
                'dateInserted',
                ['dateInserted' => $schema->validate('(2017-01-01,2017-01-31]')],
                null,
                [
                    'dateInserted >=' => new DateTimeImmutable('2017-01-02 00:00:00'),
                    'dateInserted <=' => new DateTimeImmutable('2017-01-31 23:59:59')
                ]
            ],
        ];
    }
}
