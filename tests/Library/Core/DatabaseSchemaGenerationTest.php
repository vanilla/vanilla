<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use Garden\Schema\Schema;
use VanillaTests\BootstrapTestCase;

/**
 * Tests for the legacy Gdn_Schema.
 */
class DatabaseSchemaGenerationTest extends BootstrapTestCase
{
    /**
     * Test that we correctly parse out column byte lengths for Gdn_Schema.
     */
    public function testTableGdnSchemaLengths()
    {
        $structure = \Gdn::structure();
        $structure
            ->table("schemaTest")
            ->column("varchar", "varchar(80)")
            ->column("tinytext", "tinytext")
            ->column("text", "text")
            ->column("mediumtext", "mediumtext")
            ->column("longtext", "longtext")
            ->set();

        $schema = new \Gdn_Schema("schemaTest", \Gdn::database());
        $this->assertGdnSchemaFieldLength($schema, "varchar", 80, 320); // varchars are multiplied by 4 because of utf8-mb4.
        $this->assertGdnSchemaFieldLength($schema, "tinytext", \Gdn_MySQLDriver::BYTE_LENGTH_TINY_TEXT);
        $this->assertGdnSchemaFieldLength($schema, "text", \Gdn_MySQLDriver::BYTE_LENGTH_TEXT);
        $this->assertGdnSchemaFieldLength($schema, "mediumtext", \Gdn_MySQLDriver::BYTE_LENGTH_MEDIUMTEXT);
        $this->assertGdnSchemaFieldLength($schema, "longtext", \Gdn_MySQLDriver::BYTE_LENGTH_LONGTEXT);
    }

    /**
     * Test that we correctly parse out column byte lengths for Garden\Schema.
     *
     * @depends testTableGdnSchemaLengths
     */
    public function testTableSchemaLengths()
    {
        $schema = \Gdn::database()->simpleSchema("schemaTest");
        $this->assertSchemaFieldLength($schema, "varchar", 80, 320);
        $this->assertSchemaFieldLength($schema, "tinytext", \Gdn_MySQLDriver::BYTE_LENGTH_TINY_TEXT);
        $this->assertSchemaFieldLength($schema, "text", \Gdn_MySQLDriver::BYTE_LENGTH_TEXT);
        $this->assertSchemaFieldLength($schema, "mediumtext", \Gdn_MySQLDriver::BYTE_LENGTH_MEDIUMTEXT);
        $this->assertSchemaFieldLength($schema, "longtext", \Gdn_MySQLDriver::BYTE_LENGTH_LONGTEXT);
    }

    /**
     * Assert that a schema field has a particular length value.
     *
     * @param \Gdn_Schema $schema
     * @param string $fieldName
     * @param int $expectedLength
     * @param int|null $expectedByteLength
     */
    private function assertGdnSchemaFieldLength(
        \Gdn_Schema $schema,
        string $fieldName,
        int $expectedLength,
        int $expectedByteLength = null
    ) {
        $expectedByteLength = $expectedByteLength ?? $expectedLength;
        $field = $schema->getField($fieldName);
        $this->assertEquals(
            $expectedLength,
            $field->Length,
            "Expected field $fieldName to have length $expectedLength"
        );
        $this->assertEquals(
            $expectedByteLength,
            $field->ByteLength,
            "Expected field $fieldName to have byte length $expectedByteLength"
        );
    }

    /**
     * Assert that a schema field has a particular length value.
     *
     * @param Schema $schema
     * @param string $fieldName
     * @param int $expectedLength
     * @param int|null $expectedByteLength
     */
    private function assertSchemaFieldLength(
        Schema $schema,
        string $fieldName,
        int $expectedLength,
        int $expectedByteLength = null
    ) {
        $expectedByteLength = $expectedByteLength ?? $expectedLength;
        $field = $schema->getSchemaArray()["properties"][$fieldName];
        $this->assertEquals(
            $expectedLength,
            $field["maxLength"],
            "Expected field $fieldName to have length $expectedLength"
        );
        $this->assertEquals(
            $expectedByteLength,
            $field["maxByteLength"],
            "Expected field $fieldName to have bytelength $expectedByteLength"
        );
    }
}
