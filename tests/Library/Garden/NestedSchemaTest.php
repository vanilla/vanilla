<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace GardenTests\Library\Garden;

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
            'obj:o' => [
                'id:i',
                'name:s?'
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
            'obj:o' => [
                'obj:o?' => [
                    'id:i'
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
        $schema = Schema::create(['arr:a' => 'i']);

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
     * Test merging nested schemas.
     */
    public function testNestedMerge() {
        $schemaOne = $this->getArrayOfObjectsSchema();
        $schemaTwo = new Schema([
            'rows:a' => [
                'email:s'
            ]
        ]);

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
                        'name' => ['name' => 'name', 'type' => 'string', 'required' => false],
                        'email' => ['name' => 'email', 'type' => 'string', 'required' => true]
                    ]
                ]
            ]
        ];

        $schemaOne->merge($schemaTwo);

        $this->assertEquals($expected, $schemaOne->jsonSerialize());
    }

    /**
     * Test throwing an exception when removing unexpected parameters from validated data.
     *
     * @expectedException \Garden\Exception\ValidationException
     */
    public function testValidateException() {
        $this->doValidationBehavior(Schema::VALIDATE_EXCEPTION);
    }

    /**
     * Test triggering a notice when removing unexpected parameters from validated data.
     *
     * @expectedException \PHPUnit_Framework_Error_Notice
     */
    public function testValidateNotice() {
        $this->doValidationBehavior(Schema::VALIDATE_NOTICE);
    }

    /**
     * Test silently removing unexpected parameters from validated data.
     */
    public function testValidateRemove() {
        $this->doValidationBehavior(Schema::VALIDATE_REMOVE);
    }

    /**
     * The schema fields should be case-insensitive and fix the case of incorrect keys.
     */
    public function testCaseInsensitivity() {
        $schema = Schema::create([
            'obj:o' => [
                'id:i',
                'name:s?'
            ]
        ]);

        $data = [
            'Obj' => [
                'ID' => 123,
                'namE' => 'Frank'
            ]
        ];

        $schema->validate($data);

        $expected = [
            'obj' => [
                'id' => 123,
                'name' => 'Frank'
            ]
        ];

        $this->assertEquals($expected, $data);
    }

    /**
     * Test passing a schema instance as details for a parameter.
     */
    public function testSchemaAsParameter() {
        $userSchema = new Schema([
            'userID:i',
            'name:s',
            'email:s'
        ]);

        $schema = new Schema([
            'name:s' => 'The title of the discussion.',
            'body:s' => 'The body of the discussion.',
            'insertUser' => $userSchema,
            'updateUser?' => $userSchema
        ]);

        $expected = [
            'name' => ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'The title of the discussion.'],
            'body' => ['name' => 'body', 'type' => 'string', 'required' => true, 'description' => 'The body of the discussion.'],
            'insertUser' => [
                'name' => 'insertUser',
                'type' => 'object',
                'required' => true,
                'properties' => [
                        'userID' => ['name' => 'userID', 'type' => 'integer', 'required' => true],
                        'name' => ['name' => 'name', 'type' => 'string', 'required' => true],
                        'email' => ['name' => 'email', 'type' => 'string', 'required' => true]
                ]
            ],
            'updateUser' => [
                    'name' => 'updateUser',
                    'type' => 'object',
                    'required' => false,
                    'properties' => [
                        'userID' => ['name' => 'userID', 'type' => 'integer', 'required' => true],
                        'name' => ['name' => 'name', 'type' => 'string', 'required' => true],
                        'email' => ['name' => 'email', 'type' => 'string', 'required' => true]
                    ]
                ]
        ];

        $this->assertEquals($expected, $schema->getParameters());
    }

    /**
     * Get a schema that consists of an array of objects.
     *
     * @return Schema Returns the schema.
     */
    public function getArrayOfObjectsSchema() {
        $schema = new Schema([
            'rows:a' => [
                'id:i',
                'name:s?'
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
            'id:i',
            'name:s',
            'addr:o' => [
                'street:s?',
                'city:s',
                'zip:i?'
            ]
        ]);

        return $schema;
    }

    /**
     * Call validate on an instance of Schema where the data contains unexpected parameters.
     *
     * @param int $validationBehavior
     */
    protected function doValidationBehavior($validationBehavior) {
        $schema = new Schema([
            'groupID:i' => 'The ID of the group.',
            'name:s' => 'The name of the group.',
            'description:s' => 'A description of the group.',
            'member:o' => [
                'email:s' => 'The ID of the new member.'
            ]
        ]);
        $schema->setValidationBehavior($validationBehavior);

        $data = [
            'groupID' => 123,
            'name' => 'Group Foo',
            'description' => 'A group for testing.',
            'member' => [
                'email' => 'user@example.com',
                'role' => 'Leader',
            ]
        ];

        $schema->validate($data);
        $this->assertArrayNotHasKey('role', $data['member']);
    }
}
