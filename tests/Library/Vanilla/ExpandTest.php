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
class ExpandTest extends TestCase
{
    /**
     * Test our expand defintions.
     *
     * @param Schema|null $schema
     * @param array $passedExpand
     * @param string[] $expectedExpands Fields that are expected to be "expanded".
     * @param string[] $expectedNotExpands Fields that are expected not to be "expanded".
     * @dataProvider provideExpandDefinitions
     */
    public function testGetExpand(
        ?Schema $schema,
        array $passedExpand,
        array $expectedExpands,
        array $expectedNotExpands
    ) {
        $validated = $schema ? $schema->validate($passedExpand)["expand"] : $passedExpand["expand"];
        foreach ($expectedExpands as $expectedExpand) {
            $this->assertTrue(
                ModelUtils::isExpandOption($expectedExpand, $validated),
                "Could not find expand option $expectedExpand in: \n " . json_encode($validated, JSON_PRETTY_PRINT)
            );
        }

        foreach ($expectedNotExpands as $expectedNotExpand) {
            $this->assertFalse(
                ModelUtils::isExpandOption($expectedNotExpand, $validated),
                "Found expand option $expectedNotExpand, but it was expected not to be in found in: \n " .
                    json_encode($validated, JSON_PRETTY_PRINT)
            );
        }
    }

    /**
     * @return array|array[]
     */
    public function provideExpandDefinitions(): array
    {
        $schema = Schema::parse([
            "expand" => ApiUtils::getExpandDefinition(["field1", "field2", "field3.val"]),
        ]);
        $negativeSchema = Schema::parse([
            "expand" => ApiUtils::getExpandDefinition(["-field1", "-field2", "field3.val"]),
        ]);
        return [
            "Specific fields" => [$schema, ["expand" => ["field1", "field2"]], ["field1", "field2"], ["field3.val"]],
            "true" => [$schema, ["expand" => true], ["field1", "field2", "field3.val"], []],
            "all" => [$schema, ["expand" => true], ["field1", "field2", "field3.val"], []],
            "negativeExpand" => [
                $negativeSchema,
                ["expand" => true],
                ["field1", "field2", "field3.val"],
                ["-field1", "-field2"],
            ],
            "negativeExpandDefaulted" => [
                $negativeSchema,
                ["expand" => ["field2"]],
                ["field1", "field2"],
                ["field3.val"],
            ],
            "negativeExpandExcluded" => [
                $negativeSchema,
                ["expand" => ["-field2"]],
                ["field1"],
                ["field3.val", "field2"],
            ],
            "expand fields with negative from expand options all" => [
                $negativeSchema,
                ["expand" => true],
                ["field3.val"],
                ["-field1"],
            ],
            "no schema + negative value + expand true" => [
                null,
                ["expand" => true],
                ["anyPositiveField", "anyPositiveField", "anyPositiveFieldAtAll"],
                ["-field1", "-anyOtherField", "-an.negative.at.all"],
            ],
            "false" => [$negativeSchema, ["expand" => false], [], ["field1", "field2", "field3.val"]],
            "empty" => [$negativeSchema, [], ["field1", "field2"], ["field3.val"]],
            "passed crawl" => [$schema, ["expand" => "crawl"], [], ["field1", "field2", "field3.val"]],
            "checked crawl from all" => [$schema, ["expand" => "all"], ["field1", "field2", "field3.val"], ["crawl"]],
            "checked crawl from true" => [$schema, ["expand" => true], ["field1", "field2", "field3.val"], ["crawl"]],
            "checked crawl from crawl" => [
                $schema,
                ["expand" => "crawl"],
                ["crawl"],
                ["field1", "field2", "field3.val"],
            ],
            "checked crawl from crawl array" => [
                $schema,
                ["expand" => ["crawl"]],
                ["crawl"],
                ["field1", "field2", "field3.val"],
            ],
        ];
    }

    /**
     * Test that an expand check can ignore "all" values.
     */
    public function testExcludeAll()
    {
        $schema = Schema::parse([
            "expand" => ApiUtils::getExpandDefinition(["field1", "field2", "field3.val"]),
        ]);
        $expand = $schema->validate(["expand" => ["all", "field2"]])["expand"];

        $this->assertTrue(ModelUtils::isExpandOption("field1", $expand));
        $this->assertTrue(ModelUtils::isExpandOption("field2", $expand));
        $this->assertFalse(ModelUtils::isExpandOption("field3.val", $expand, true));
        $this->assertTrue(ModelUtils::isExpandOption("field2", $expand, true));
    }
}
