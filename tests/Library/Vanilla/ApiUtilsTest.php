<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Garden\Schema\Schema;
use PHPUnit\Framework\TestCase;
use Vanilla\Schema\RangeExpression;
use VanillaTests\BootstrapTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SharedBootstrapTestCase;
use Vanilla\ApiUtils;

/**
 * Class ApiUtilsTest
 */
class ApiUtilsTest extends TestCase {

    use BootstrapTrait, SetupTraitsTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->setupTestTraits();
    }

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
                    'processor' => function ($fieldName, $value) {
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

    /**
     * Do a basic test of the page header parsing functionality.
     */
    public function testParsePageHeader() {
        $link = <<<EOT
<http://vanilla.test/categoriestest/api/v2/categories?page=1&limit=1>; rel="first",
<http://vanilla.test/categoriestest/api/v2/categories?page=2&limit=1>; rel="next",
<http://vanilla.test/categoriestest/api/v2/categories?page=2&limit=1>; rel="last"
EOT;

        $expected = array (
            'first' => 'http://vanilla.test/categoriestest/api/v2/categories?page=1&limit=1',
            'next' => 'http://vanilla.test/categoriestest/api/v2/categories?page=2&limit=1',
            'last' => 'http://vanilla.test/categoriestest/api/v2/categories?page=2&limit=1',
        );

        $actual = ApiUtils::parsePageHeader($link);

        $this->assertSame($expected, $actual);
    }

    /**
     * A garbled link header should just return null.
     */
    public function testParsePageHeaderNull() {
        $link = 'garbled';
        $actual = ApiUtils::parsePageHeader($link);

        $this->assertNull($actual);
    }

    /**
     * Test a basic call of `ApiUtils::sortEnum()`.
     */
    public function testSortEnum(): void {
        $r = ApiUtils::sortEnum('a', 'b');
        $this->assertSame(['a', 'b', '-a', '-b'], $r);
    }

    /**
     * You should be able to pass `"-$field"` to `ApiUtils::sortEnum()`.
     */
    public function testSortEnumException(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('-a');
        $r = ApiUtils::sortEnum('-a');
    }

    /**
     * Verify objects in a query will be converted to strings when building pagination headers.
     */
    public function testPagerUrlFormatStringify(): void {
        $range = "1..10";
        $schema = Schema::parse([
            'range' => RangeExpression::createSchema([':int'])
        ]);
        $parameters = [
            "limit" => 10,
            "page" => 1,
            "range" => RangeExpression::parse($range),
        ];

        $pagination = ApiUtils::morePagerInfo(100, "https://example.com/api/foo", $parameters, $schema);
        $queryString = parse_url($pagination["urlFormat"], PHP_URL_QUERY);
        parse_str($queryString, $query);
        $this->assertArrayHasKey("range", $query, "Object lost when building query.");
        $this->assertSame($range, $query["range"]);
    }
}
