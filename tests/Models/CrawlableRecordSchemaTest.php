<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Gdn;
use Garden\Schema\Schema;
use Vanilla\Models\CrawlableRecordSchema;
use VanillaTests\BootstrapTestCase;

/**
 * Tests CrawlableRecordSchema
 */
class CrawlableRecordSchemaTest extends BootstrapTestCase
{
    /**
     * Tests that {@link CrawlableRecordSchema::applyExpandedSchema} fires the
     * filter event and uses it.
     */
    public function testApplyExpandedSchemaFilterEvent()
    {
        // Bind the filter event to verify it works
        Gdn::eventManager()->bind("crawlableRecordSchema_applyExpandedSchema", function (
            Schema $schema,
            Schema $originalSchema,
            string $defaultType,
            array $expand
        ) {
            $this->assertEquals("testing", $defaultType);
            $this->assertEquals(["crawl"], $expand);

            return $schema->merge(Schema::parse(["testAdditionalField1"]));
        });

        // Bind the second filter stage to test $schema vs $originalSchema
        Gdn::eventManager()->bind("crawlableRecordSchema_applyExpandedSchema", function (
            Schema $schema,
            Schema $originalSchema,
            string $defaultType,
            array $expand
        ) {
            // Verify that `testAdditionalField1` has been added by the first filter
            $this->assertNotNull($schema->getField("properties.testAdditionalField1"));

            // but is absent from the original unmodified schema
            $this->assertNull($originalSchema->getField("properties.testAdditionalField1"));

            return $schema->merge(Schema::parse(["testAdditionalField2"]));
        });

        $testSchema = Schema::parse(["name?", "body?"]);
        $appliedSchema = CrawlableRecordSchema::applyExpandedSchema($testSchema, "testing", ["crawl"]);

        $validated = $appliedSchema->validate([
            "name" => "Testing Thing",
            "body" => "This is a test case",
            "scope" => "public",
            "excerpt" => "testing testing",
            "invalidField" => "should be filtered out",
            "testAdditionalField1" => "this should be in",
            "testAdditionalField2" => "this should also be in",
        ]);

        $this->assertArrayNotHasKey("invalidField", $validated);
        $this->assertEquals("this should be in", $validated["testAdditionalField1"]);
        $this->assertEquals("this should also be in", $validated["testAdditionalField2"]);
    }
}
