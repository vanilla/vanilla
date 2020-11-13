<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Database;

use Gdn_Database;
use Gdn_DatabaseStructure;
use VanillaTests\BootstrapTestCase;
use VanillaTests\VanillaTestCase;

/**
 * Verify behavior of the abstract database structure class.
 */
class DatabaseStructureTest extends BootstrapTestCase {
    /** @var Gdn_Database */
    private $db;

    /** @var Gdn_DatabaseStructure */
    private $structure;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

        $this->db = $this->createMock(Gdn_Database::class);
        $this->structure = new class($this->db) extends Gdn_DatabaseStructure {
        };
    }

    /**
     * Verify basic creation of a column.
     */
    public function testColumnBasic(): void {
        $this->structure->column(__FUNCTION__, "text");
        $result = (array)$this->structure->columns(__FUNCTION__);
        $this->assertNotEmpty($result);

        $expected = [
            "AllowNull" => false,
            "AutoIncrement" => false,
            "Default" => null,
            "Enum" => false,
            "KeyType" => false,
            "Length" => "",
            "Name" => __FUNCTION__,
            "Precision" => "",
            "Type" => "text",
            "Unsigned" => false,
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Verify effect of enabling or disabling full-text indexing when defining a column configured for it.
     *
     * @param mixed $fullTextIndexingEnabled
     * @param array $expectedKeyType
     * @dataProvider provideColumnFullTextData
     */
    public function testColumnFullText(bool $fullTextIndexingEnabled, $expectedKeyType): void {
        $this->structure->setFullTextIndexingEnabled($fullTextIndexingEnabled);
        $this->structure->column(
            __FUNCTION__,
            "text",
            false,
            Gdn_DatabaseStructure::KEY_TYPE_FULLTEXT
        );
        $result = (array)$this->structure->columns(__FUNCTION__);
        $this->assertNotEmpty($result);

        $this->assertSame($expectedKeyType, $result["KeyType"]);
    }

    /**
     * Provide data for defining a column with full-text indexing.
     *
     * @return array
     */
    public function provideColumnFullTextData(): array {
        return [
            "disabled" => [false, false],
            "enabled, no name" => [true, Gdn_DatabaseStructure::KEY_TYPE_FULLTEXT],
        ];
    }

    /**
     * Verify setting the key type of a column.
     *
     * @param mixed $keyType
     * @param array $expected
     * @dataProvider provideColumnKeyTypes
     */
    public function testColumnKeyType($keyType, array $expected): void {
        $this->structure->column(__FUNCTION__, "text", false, $keyType);
        $result = (array)$this->structure->columns(__FUNCTION__);
        $this->assertNotEmpty($result);

        VanillaTestCase::assertArraySubsetRecursive($expected, $result);
    }

    /**
     * Provide key types for the column method.
     *
     * @return array
     */
    public function provideColumnKeyTypes(): array {
        return [
            "invalid key type" => [
                "foobar",
                ["KeyType" => false],
            ],
            "key" => [
                "key",
                ["KeyType" => "key"],
            ],
            "multiple keys, valid" => [
                ["index.foo", "key.bar"],
                [
                    "KeyType" => ["index.foo", "key.bar"],
                ],
            ],
            "multiple keys, partially valid" => [
                ["index.foo", "hello.world"],
                ["KeyType" => "index.foo"],
            ],
            "multiple keys, invalid" => [
                ["hello.world", "foo.bar"],
                ["KeyType" => false],
            ],
            "none" => [
                false,
                ["KeyType" => false],
            ],
            "no name" => [
                Gdn_DatabaseStructure::KEY_TYPE_INDEX,
                ["KeyType" => Gdn_DatabaseStructure::KEY_TYPE_INDEX],
            ],
            "named" => [
                "index.foo",
                ["KeyType" => "index.foo"],
            ],
            "primary" => [
                Gdn_DatabaseStructure::KEY_TYPE_PRIMARY,
                ["KeyType" => Gdn_DatabaseStructure::KEY_TYPE_PRIMARY],
            ],
            "unique" => [
                Gdn_DatabaseStructure::KEY_TYPE_UNIQUE,
                ["KeyType" => Gdn_DatabaseStructure::KEY_TYPE_UNIQUE],
            ],
        ];
    }

    /**
     * Verify setting allow-null/default-value options for a column.
     *
     * @param mixed $nullDefault
     * @param array $expected
     * @dataProvider provideColumnNullDefaults
     */
    public function testColumnNullDefault($nullDefault, array $expected): void {
        $this->structure->column(__FUNCTION__, "text", $nullDefault);
        $result = (array)$this->structure->columns(__FUNCTION__);
        $this->assertNotEmpty($result);

        VanillaTestCase::assertArraySubsetRecursive($expected, $result);
    }

    /**
     * Provide parameters to test setting a column's allow-null/default-value options.
     *
     * @return array
     */
    public function provideColumnNullDefaults(): array {
        return [
            "nullable, no default" => [
                null,
                [
                    "AllowNull" => true,
                    "Default" => null,
                ]
            ],
            "nullable, alt, no default" => [
                true,
                [
                    "AllowNull" => true,
                    "Default" => null,
                ]
            ],
            "not nullable, no default" => [
                false,
                [
                    "AllowNull" => false,
                    "Default" => null,
                ]
            ],
            "array, explicit" => [
                [
                    "Null" => true,
                    "Default" => "xyz",
                ],
                [
                    "AllowNull" => true,
                    "Default" => "xyz",
                ]
            ],
            "not nullable, default value" => [
                "foobar",
                [
                    "AllowNull" => false,
                    "Default" => "foobar",
                ]
            ],
        ];
    }

    /**
     * Verify setting types for a column.
     *
     * @param mixed $type
     * @param array $expected
     * @dataProvider provideColumnTypes
     */
    public function testColumnType($type, array $expected): void {
        $this->structure->column(__FUNCTION__, $type);
        $result = (array)$this->structure->columns(__FUNCTION__);
        $this->assertNotEmpty($result);

        VanillaTestCase::assertArraySubsetRecursive($expected, $result);
    }

    /**
     * Provide parameters to test setting a column type.
     *
     * @return array
     */
    public function provideColumnTypes(): array {
        $params = [
            [
                "float(10,2)",
                [
                    "Enum" => false,
                    "Length" => "10",
                    "Precision" => "2",
                    "Type" => "float",
                    "Unsigned" => false,
                ],
            ],
            [
                "int",
                [
                    "Enum" => false,
                    "Length" => "",
                    "Precision" => "",
                    "Type" => "int",
                    "Unsigned" => false,
                ],
            ],
            [
                "text",
                [
                    "Enum" => false,
                    "Length" => "",
                    "Precision" => "",
                    "Type" => "text",
                    "Unsigned" => false,
                ]
            ],
            [
                "uint",
                [
                    "Enum" => false,
                    "Length" => "",
                    "Precision" => "",
                    "Type" => "int",
                    "Unsigned" => true,
                ],
            ],
            [
                "varchar(20)",
                [
                    "Enum" => false,
                    "Length" => "20",
                    "Precision" => "",
                    "Type" => "varchar",
                    "Unsigned" => false,
                ],
            ],
        ];
        $params = array_column($params, null, 0);

        $params += [
            "enum, typed" => [
                [
                    "string",
                    ["foo", "bar"]
                ],
                [
                    "Enum" => ["foo", "bar"],
                    "Length" => "",
                    "Precision" => "",
                    "Type" => "string",
                    "Unsigned" => false,
                ],
            ],
            "enum, no type" => [
                ["foo", "bar"],
                [
                    "Enum" => ["foo", "bar"],
                    "Length" => "",
                    "Precision" => "",
                    "Type" => "enum",
                    "Unsigned" => false,
                ],
            ]
        ];

        return $params;
    }

    /**
     * Verify toggling full-text indexing property.
     *
     * @param bool $value
     * @dataProvider providerFullTextIndexingProperties
     */
    public function testSetFullTextIndexingProperty(bool $value): void {
        $this->structure->setFullTextIndexingEnabled($value);
        $this->assertSame($value, $this->structure->isFullTextIndexingEnabled());
    }

    /**
     * Provide parameters to test setting the full-text indexing property.
     *
     * @return array
     */
    public function providerFullTextIndexingProperties(): array {
        return [
            "disable" => [false],
            "enable" => [true],
        ];
    }
}
