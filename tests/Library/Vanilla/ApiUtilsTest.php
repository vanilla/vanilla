<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Garden\Schema\Schema;
use VanillaTests\SharedBootstrapTestCase;
use Vanilla\ApiUtils;

/**
 * Class ApiUtilsTest
 */
class ApiUtilsTest extends SharedBootstrapTestCase {

    /**
     * @param Schema $schema
     * @param array $validatedQuery
     * @param array $expectedResult
     * @dataProvider queryToFiltersProvider
     */
    public function testQueryToFilters(Schema $schema, array $validatedQuery, array $expectedResult) {
        $this->assertEquals(ApiUtils::queryToFilters($schema, $validatedQuery), $expectedResult);
    }

    /**
     * Provider function for testQueryToFilters.
     * @return array
     */
    public function queryToFiltersProvider() {
        $fieldOnlySchema = Schema::parse([
            'parameter:s' => [
                'x-filter' => [
                    'field' => 'FilterFieldName',
                ],
            ],
        ]);
        $fieldWProcessorSchema = Schema::parse([
            'parameter:s' => [
                'x-filter' => [
                    'field' => 'FilterFieldName',
                    'processor' => function($fieldName, $value) {
                        return [$fieldName.'Processed' => $value.'Processed'];
                    }
                ],
            ],
        ]);

        return [
            'FieldOnly w data' => [
                $fieldOnlySchema,
                ['parameter' => 'test'],
                ['FilterFieldName' => 'test'],
            ],
            'FieldOnly no data' => [
                $fieldOnlySchema,
                ['somethingElse' => 'test'],
                [],
            ],
            'FieldAndProcessor w data' => [
                $fieldWProcessorSchema,
                ['parameter' => 'test'],
                ['FilterFieldNameProcessed' => 'testProcessed'],
            ],
            'FieldAndProcessor no data' => [
                $fieldWProcessorSchema,
                ['somethingElse' => 'test'],
                []
            ],
        ];
    }

    /**
     * Test the `x-filter` schema modifier.
     */
    public function testQueryToFiltersWInvalidProcessor() {
        $this->expectException(\Exception::class);

        $schema = Schema::parse([
            'parameter:s' => [
                'x-filter' => [
                    'field' => 'FilterFieldName',
                    'processor' => [$this, uniqid('non_existing_method')],
                ],
            ],
        ]);

        ApiUtils::queryToFilters($schema, ['parameter' => 'test']);
    }
}
