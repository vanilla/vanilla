<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Schema\Schema;
use PHPUnit\Framework\TestCase;
use Vanilla\ApiUtils;
use Vanilla\Utility\ModelUtils;

/**
 * Tests for expand utilities.
 */
class ExpandTest extends TestCase {

    /**
     * Test our expand defintions.
     *
     * @param Schema $schema
     * @param array $passedExpand
     * @param string[] $expectedExpands Fields that are expected to be "expanded".
     * @param string[] $expectedNotExpands Fields that are expected not to be "expanded".
     *
     * @dataProvider provideExpandDefinitions
     */
    public function testGetExpand(Schema $schema, array $passedExpand, array $expectedExpands, array $expectedNotExpands) {
        $validated = $schema->validate($passedExpand)['expand'];
        foreach ($expectedExpands as $expectedExpand) {
            $this->assertTrue(
                ModelUtils::isExpandOption($expectedExpand, $validated),
                "Could not find expand option $expectedExpand in: \n "
                . json_encode($validated, JSON_PRETTY_PRINT)
            );
        }

        foreach ($expectedNotExpands as $expectedNotExpand) {
            $this->assertFalse(
                ModelUtils::isExpandOption($expectedNotExpand, $validated),
                "Found expand option $expectedNotExpand, but it was expected not to be in found in: \n "
                . json_encode($validated, JSON_PRETTY_PRINT)
            );
        }
    }

    /**
     * @return array|array[]
     */
    public function provideExpandDefinitions(): array {
        $schema = Schema::parse([
            'expand' => ApiUtils::getExpandDefinition(['field1', 'field2', 'field3.val'])
        ]);
        $negativeSchema = Schema::parse([
            'expand' => ApiUtils::getExpandDefinition(['-field1', '-field2', 'field3.val'])
        ]);
        return [
            'Specific fields' => [
                $schema,
                ['expand' => ['field1', 'field2']],
                ['field1', 'field2'],
                ['field3.val']
            ],
            'true' => [
                $schema,
                ['expand' => true],
                ['field1', 'field2', 'field3.val'],
                []
            ],
            'all' => [
                $schema,
                ['expand' => true],
                ['field1', 'field2', 'field3.val'],
                []
            ],
            'negativeExpand' => [
                $negativeSchema,
                ['expand' => true],
                ['field1', 'field2', 'field3.val'],
                [],
            ],
            'negativeExpandDefaulted' => [
                $negativeSchema,
                ['expand' => ['field2']],
                ['field1', 'field2'],
                ['field3.val'],
            ],
            'negativeExpandExcluded' => [
                $negativeSchema,
                ['expand' => ['-field2']],
                ['field1'],
                ['field3.val', 'field2'],
            ],
            'false' => [
                $negativeSchema,
                ['expand' => false],
                [],
                ['field1', 'field2', 'field3.val'],
            ],
            'empty' => [
                $negativeSchema,
                [],
                ['field1', 'field2'],
                ['field3.val'],
            ],
            'passed crawl' => [
                $schema,
                ['expand' => 'crawl'],
                [],
                ['field1', 'field2', 'field3.val'],
            ],
            'checked crawl from all' => [
                $schema,
                ['expand' => 'all'],
                ['field1', 'field2', 'field3.val'],
                ['crawl'],
            ],
            'checked crawl from true' => [
                $schema,
                ['expand' => true],
                ['field1', 'field2', 'field3.val'],
                ['crawl'],
            ],
            'checked crawl from crawl' => [
                $schema,
                ['expand' => 'crawl'],
                ['crawl'],
                ['field1', 'field2', 'field3.val'],
            ],
            'checked crawl from crawl array' => [
                $schema,
                ['expand' => ['crawl']],
                ['crawl'],
                ['field1', 'field2', 'field3.val'],
            ]
        ];
    }
}
