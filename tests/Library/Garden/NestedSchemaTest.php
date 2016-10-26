<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Tests\Schemas;

use Garden\Schema;
use Garden\Validation;

/**
 * Tests for nested object schemas.
 */
class NestedSchemaTest extends SchemaTest {

    /**
     * Test a basic nested object.
     */
    public function testBasicNested() {
        $schema = Schema::create([
            'o:obj' => [
                'i:id',
                's:name?'
            ]
        ]);

        $expected = [
            'obj' => [
                'name' => 'obj',
                'type' => 'object',
                'required' => true,
                'properties' => [
                    'id' => ['name' => 'id', 'type' => 'integer', 'required' => true],
                    'name' => ['name' => 'name', 'type' => 'string', 'required' => false]
                ]
            ]
        ];

        $actual = $schema->jsonSerialize();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test to see if a nested schema can be used to create an identical nested schema.
     */
    public function testNestedLongform() {
        $schema = $this->getNestedSchema();

        // Make sure the long form can be used to create the schema.
        $schema2 = Schema::create($schema->jsonSerialize());
        $this->assertEquals($schema->jsonSerialize(), $schema2->jsonSerialize());
    }

    /**
     * Test a double nested schema.
     */
    public function testDoubleNested() {
        $schema = Schema::create([
            'o:obj' => [
                'o:obj?' => [
                    'i:id'
                ]
            ]
        ]);

        $expected = [
            'obj' => [
                'name' => 'obj',
                'type' => 'object',
                'required' => true,
                'properties' => [
                    'obj' => [
                        'name' => 'obj',
                        'type' => 'object',
                        'required' => false,
                        'properties' => [
                            'id' => [
                                'name' => 'id',
                                'type' => 'integer',
                                'required' => true
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $schema->jsonSerialize());
    }

    /**
     * Test nested schema validation with valid data.
     */
    public function testNestedValid() {
        $schema = $this->getNestedSchema();

        $validData = [
            'id' => 123,
            'name' => 'Todd',
            'addr' => [
                'street' => '414 rue McGill',
                'city' => 'Montreal',
            ]
        ];

        $isValid = $schema->isValid($validData);
        $this->assertTrue($isValid);
    }

    /**
     * Test a nested schema with som invalid data.
     */
    public function testNestedInvalid() {
        $schema = $this->getNestedSchema();

        $invalidData = [
            'id' => 123,
            'name' => 'Toddo',
            'addr' => [
                'zip' => 'H2Y 2G1'
            ]
        ];

        /* @var Validation $validation */
        $isValid = $schema->isValid($invalidData, $validation);
        $this->assertFalse($isValid);

        $this->assertFalse($validation->fieldValid('addr.city'), "addr.street should be invalid.");
        $this->assertFalse($validation->fieldValid('addr.zip'), "addr.zip should be invalid.");
    }

    /**
     * Test a variety of array item validation scenarios.
     */
    public function testArrayItemsType() {
        $schema = Schema::create(['a:arr' => 'i']);

        $validData = ['arr' => [1, '2', 3]];
        $this->assertTrue($schema->isValid($validData));

        $invalidData = ['arr' => [1, 'foo', 'bar']];
        $this->assertFalse($schema->isValid($invalidData, $validation));

        // Try a custom validator for the items.
        $schema->addValidator('arr.items', function (&$value, $field, Validation $validation) {
            if ($value > 2) {
                $validation->addError('%s must be less than 2.', $field, 422);
            }
        });
        /* @var Validation $validation2 */
        $this->assertFalse($schema->isValid($validData, $validation2));
        $this->assertFalse($validation2->fieldValid('arr.2'));
        $this->assertEquals('arr.2 must be less than 2.', $validation2->getMessage());
    }

    /**
     * Test a schema of an array of objects.
     */
    public function testArrayOfObjectsSchema() {
        $schema = $this->getArrayOfObjectsSchema();

        $expected = [
            'rows' => [
                'name' => 'rows',
                'type' => 'array',
                'required' => true,
                'items' => [
                    'type' => 'object',
                    'required' => true,
                    'properties' => [
                        'id' => ['name' => 'id', 'type' => 'integer', 'required' => true],
                        'name' => ['name' => 'name', 'type' => 'string', 'required' => false]
                    ]
                ]
            ]
        ];

        $actual = $schema->jsonSerialize();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test an array of objects to make sure it's valid.
     */
    public function testArrayOfObjectsValid() {
        $schema = $this->getArrayOfObjectsSchema();

        $data = [
            'rows' => [
                ['id' => 1, 'name' => 'Todd'],
                ['id' => 2],
                ['id' => '23', 'name' => 123]
            ]
        ];

        /* @var Validation $validation */
        $isValid = $schema->isValid($data, $validation);
        $this->assertTrue($isValid);

        $this->assertInternalType('int', $data['rows'][2]['id']);
        $this->assertInternalType('string', $data['rows'][2]['name']);
    }

    /**
     * Test an array of objects that are invalid and make sure the errors are correct.
     */
    public function testArrayOfObjectsInvalid() {
        $schema = $this->getArrayOfObjectsSchema();

        /* @var Validation $v1 */
        $missingData = [];
        $isValid = $schema->isValid($missingData, $v1);
        $this->assertFalse($isValid);
        $this->assertFalse($v1->fieldValid('rows'));

        /* @var Validation $v2 */
        $notArrayData = ['rows' => 123];
        $isValid = $schema->isValid($notArrayData, $v2);
        $this->assertFalse($isValid);
        $this->assertFalse($v2->fieldValid('rows'));

        /* @var Validation $v3 */
        $nullItemData = ['rows' => [null]];
        $isValid = $schema->isValid($nullItemData, $v3);
        $this->assertFalse($isValid);
        $this->assertFalse($v3->fieldValid('rows.0'));

        /* @var Validation $v4 */
        $invalidRowsData = ['rows' => [
            ['id' => 'foo'],
            ['id' => 123],
            ['name' => 'Todd']
        ]];
        $isValid = $schema->isValid($invalidRowsData, $v4);
        $this->assertFalse($isValid);
        $this->assertFalse($v4->fieldValid('rows.0.id'));
        $this->assertTrue($v4->fieldValid('rows.1.id'));
        $this->assertFalse($v4->fieldValid('rows.2.id'));

    }

    /**
     * Get a schema that consists of an array of objects.
     *
     * @return Schema Returns the schema.
     */
    public function getArrayOfObjectsSchema() {
        $schema = new Schema([
            'a:rows' => [
                'i:id',
                's:name?'
            ]
        ]);

        return $schema;
    }

    /**
     * Get a basic nested schema for testing.
     *
     * @return Schema Returns a new schema for testing.
     */
    public function getNestedSchema() {
        $schema = Schema::create([
            'i:id',
            's:name',
            'o:addr' => [
                's:street?',
                's:city',
                'i:zip?'
            ]
        ]);

        return $schema;
    }
}
