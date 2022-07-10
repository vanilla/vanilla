<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Search;

use Garden\Schema\ValidationException;
use PHPUnit\Framework\TestCase;
use Vanilla\Search\SearchTypeCollection;
use VanillaTests\Fixtures\Search\MockSearchType;

/**
 * Tests for the SearchQuery class.
 */
class SearchTypeCollectionTest extends TestCase {

    /**
     * Test that types are filtered correctly by query parameters.
     *
     * @param array $inputTypes
     * @param array $expectedTypes
     * @param array $queryData
     *
     * @dataProvider provideFilterTypes
     */
    public function testGetFilteredCollection(array $inputTypes, array $expectedTypes, array $queryData = []) {
        $collection = new SearchTypeCollection($inputTypes);
        $filtered = $collection->getFilteredCollection($queryData);
        $this->assertEquals($expectedTypes, $filtered->getAllTypes());
    }

    /**
     * @return array
     */
    public function provideFilterTypes(): array {
        $type1 = new MockSearchType('type1');
        $type2 = new MockSearchType('type2');
        $type2SubType = new MockSearchType('type2.1');
        $type2SubType->setSearchGroup('type2');

        $type1NoPermission = clone $type1;
        $type1NoPermission->setUserHasPermission(false);

        $type1Exclusive = clone $type1;
        $type1Exclusive->setIsExclusiveType(true);

        return [
            'passthrough' => [
                [
                    $type1,
                    $type2,
                    $type2SubType,
                ],
                [
                    $type1,
                    $type2,
                    $type2SubType,
                ]
            ],
            'types filtering' => [
                [
                    $type1,
                    $type2,
                    $type2SubType,
                ],
                [
                    $type1,
                    $type2,
                ],
                [
                    'types' => ['type1', 'type2']
                ]
            ],
            'recordTypes filtering' => [
                [
                    $type1,
                    $type2,
                    $type2SubType,
                ],
                [
                    $type2,
                    $type2SubType,
                ],
                [
                    'recordTypes' => ['type2']
                ]
            ],
            'exclusive default filtering' => [
                [
                    $type1Exclusive,
                    $type2,
                    $type2SubType,
                ],
                [
                    $type2,
                    $type2SubType,
                ]
            ],
            'permission default filtering' => [
                [
                    $type1NoPermission,
                    $type2,
                    $type2SubType,
                ],
                [
                    $type2,
                    $type2SubType,
                ]
            ],
        ];
    }

    /**
     * Test that correct exceptions are thrown when bad query parameters are passed.
     *
     * @param array $inputTypes
     * @param array $queryData
     * @param string $expectedException
     * @dataProvider provideExceptionTypes
     */
    public function testFilterExceptions(array $inputTypes, array $queryData, string $expectedException = ValidationException::class) {
        $this->expectException($expectedException);
        $collection = new SearchTypeCollection($inputTypes);
        $collection->getFilteredCollection($queryData);
    }

    /**
     * @return array
     */
    public function provideExceptionTypes(): array {
        $type1 = new MockSearchType('type1');
        $type2 = new MockSearchType('type2');

        $type1NoPermission = clone $type1;
        $type1NoPermission->setUserHasPermission(false);

        $type1Exclusive = clone $type1;
        $type1Exclusive->setIsExclusiveType(true);

        $type2Exclusive = clone $type2;
        $type2Exclusive->setIsExclusiveType(true);

        return [
            '2 exclusive types' => [
                [
                    $type1Exclusive,
                    $type2Exclusive,
                ],
                [
                    'types' => ['type1', 'type2'],
                ]
            ],
            '2 exclusive recordTypes' => [
                [
                    $type1Exclusive,
                    $type2Exclusive,
                ],
                [
                    'recordTypes' => ['type1', 'type2'],
                ]
            ],
            'mixed exclusive types' => [
                [
                    $type1Exclusive,
                    $type2Exclusive,
                ],
                [
                    'recordTypes' => ['type1'],
                    'types' => ['type2'],
                ],
            ],
            'no permission recordTypes' => [
                [
                    $type1NoPermission,
                    $type2,
                ],
                [
                    'recordTypes' => ['type1', 'type2'],
                ],
            ],
            'no permission types' => [
                [
                    $type1NoPermission,
                    $type2,
                ],
                [
                    'types' => ['type1', 'type2'],
                ],
            ],
        ];
    }
}
