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
 * Tess for the {@link Schema} object.
 */
class BasicSchemaTest extends SchemaTest {
    /**
     * Test the basic atomic types in a schema.
     */
    public function testAtomicTypes() {
        $schema = $this->getAtomicSchema();

        $expected = [
            'id' => ['name' => 'id', 'type' => 'integer', 'required' => true],
            'name' => ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'The name of the object.'],
            'description' => ['name' => 'description', 'type' => 'string', 'required' => false],
            'timestamp' => ['name' => 'timestamp', 'type' => 'timestamp', 'required' => false],
            'date' => ['name' => 'date', 'type' => 'datetime', 'required' => false],
            'amount' => ['name' => 'amount', 'type' => 'float', 'required' => false],
            '64ish' => ['name' => '64ish', 'type' => 'base64', 'required' => false],
            'enabled' => ['name' => 'enabled', 'type' => 'boolean', 'required' => false]
        ];

        $actual = $schema->getSchema();
        $this->assertEquals($expected, $actual['properties']);
    }

    /**
     * Test some basic validation.
     */
    public function testAtomicValidation() {
        $schema = $this->getAtomicSchema();
        $data = [
            'id' => 123,
            'name' => 'foo',
            'timestamp' => '13 oct 1975',
            'amount' => '99.50',
            '64ish' => base64_encode(openssl_random_pseudo_bytes(20)),
            'enabled' => 'yes'
        ];
        $dataBefore = $data;

        $isValid = $schema->isValid($data, $validation);
        $this->assertTrue($isValid);
        $data = $dataBefore;

        $expected = $data;
        $expected['timestamp'] = strtotime($data['timestamp']);
        $expected['64ish'] = base64_decode($expected['64ish']);
        $expected['enabled'] = true;

        $schema->validate($data);
        $this->assertEquals($expected, $data);
    }

    /**
     * Test some data that doesn't need to be be coerced (except one string).
     */
    public function testAtomicValidation2() {
        $schema = $this->getAtomicSchema();
        $data = [
            'id' => 123,
            'name' => 'foo',
            'description' => 456,
            'timestamp' => time(),
            'date' => new \DateTime(),
            'amount' => 5.99,
            '64ish' => base64_encode(123),
            'enabled' => true
        ];

        $validated = $data;
        $data['64ish'] = base64_decode($data['64ish']);
        $schema->validate($validated);
        $this->assertEquals($data, $validated);
    }

    /**
     * Test boolean data validation.
     *
     * @param mixed $input The input data.
     * @param bool $expected The expected boolean value.
     * @dataProvider provideBooleanData
     */
    public function testBooleanSchema($input, $expected) {
        $schema = Schema::create(['bool:b']);
        $expected = ['bool' => $expected];

        // Test some false data.
        $data = ['bool' => $input];

        $schema->validate($data);
        $this->assertEquals($expected, $data);
    }

    /**
     * Test an array type.
     */
    public function testArrayType() {
        $schema = Schema::create(['arr:a']);

        $expectedProperties = [
            'arr' => ['name' => 'arr', 'type' => 'array', 'required' => true]
        ];

        // Basic array without a type.
        $actual = $schema->getSchema();
        $this->assertEquals($expectedProperties, $actual['properties']);

        $data = ['arr' => [1, 2, 3]];
        $this->assertTrue($schema->isValid($data));
        $data = ['arr' => []];
        $this->assertTrue($schema->isValid($data));

        // Array with a description and not a type.
        $expectedProperties['arr']['description'] = 'Hello world!';
        $schema = Schema::create(['arr:a' => 'Hello world!']);
        $actual = $schema->getSchema();
        $this->assertEquals($expectedProperties, $actual['properties']);

        // Array with an items type.
        unset($expectedProperties['arr']['description']);
        $expectedProperties['arr']['items']['type'] = 'integer';
        $expectedProperties['arr']['items']['required'] = true;

        $schema = Schema::create(['arr:a' => 'i']);
        $actual = $schema->getSchema();
        $this->assertEquals($expectedProperties, $actual['properties']);

        // Test the longer syntax.
        $expectedProperties['arr']['description'] = 'Hello world!';
        $schema = Schema::create(['arr:a' => [
            'description' => 'Hello world!',
            'items' => ['type' => 'integer', 'required' => true]
        ]]);
        $actual = $schema->getSchema();
        $this->assertEquals($expectedProperties, $actual['properties']);
    }

    /**
     * Test that the schema long form can be used to create a schema.
     */
    public function testLongCreate() {
        $schema = $this->getAtomicSchema();
        $schema2 = Schema::create($schema->getSchema());

        $expected = $schema->getSchema();
        $actual = $schema2->getSchema();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test data that is not required, but provided as empty.
     *
     * @param string $shortType The short data type.
     * @param string $longType The long data type.
     * @dataProvider provideTypes
     */
    public function testNotRequired($shortType, $longType) {
        $schema = new Schema([
            "col:$shortType?"
        ]);

        $emptyData = ['col' => ''];
        $isValid = $schema->isValid($emptyData, $validation);
        $this->assertTrue($isValid);
        $this->assertNull($emptyData['col']);

        $nullData = ['col' => null];
        $isValid = $schema->isValid($nullData, $validation);
        $this->assertTrue($isValid);
        $this->assertNull($nullData['col']);

        $missingData = [];
        $isValid = $schema->isValid($missingData, $validation);
        $this->assertTrue($isValid);
        $this->assertArrayNotHasKey('col', $missingData);
    }

    /**
     * Test data that is not required, but provided as empty.
     *
     * @param string $shortType The short data type.
     * @param string $longType The long data type.
     * @dataProvider provideTypes
     */
    public function testRequiredEmpty($shortType, $longType) {
        // Bools and strings are special cases.
        if (in_array($shortType, ['b'])) {
            return;
        }

        $schema = new Schema([
            "col:$shortType"
        ]);

        $emptyData = ['col' => ''];
        $isValid = $schema->isValid($emptyData, $validation);
        $this->assertFalse($isValid);

        $nullData = ['col' => null];
        $isValid = $schema->isValid($nullData, $validation);
        $this->assertFalse($isValid);
    }

    /**
     * Test empty boolean values.
     * In genreal, bools should be cast to false if they are passed, but falsey.
     */
    public function testRequiredEmptyBool() {
        $schema = new Schema([
            'col:b'
        ]);
        /* @var Validation $validation */
        $emptyData = ['col' => ''];
        $isValid = $schema->isValid($emptyData, $validation);
        $this->assertTrue($isValid);
        $this->assertFalse($emptyData['col']);

        $nullData = ['col' => null];
        $isValid = $schema->isValid($nullData, $validation);
        $this->assertTrue($isValid);
        $this->assertFalse($nullData['col']);

        $missingData = [];
        $isValid = $schema->isValid($missingData, $validation);
        $this->assertFalse($isValid);
        $this->assertFalse($validation->fieldValid('col'));
    }

    /**
     * Test a required empty string with a min length of 0.
     */
    public function testRequiredEmptyString() {
        $schema = new Schema([
            'col:s' => ['minLength' => 0]
        ]);

        /* @var Validation $validation */
        $emptyData = ['col' => ''];
        $isValid = $schema->isValid($emptyData, $validation);
        $this->assertTrue($isValid);
        $this->assertEmpty($emptyData['col']);
        $this->assertInternalType('string', $emptyData['col']);

        $nullData = ['col' => null];
        $isValid = $schema->isValid($nullData, $validation);
        $this->assertTrue($isValid);
        $this->assertEmpty($nullData['col']);
        $this->assertInternalType('string', $nullData['col']);

        $missingData = [];
        $isValid = $schema->isValid($missingData, $validation);
        $this->assertFalse($isValid);
        $this->assertFalse($validation->fieldValid('col'));
    }

    /**
     * Test {@link Schema::requireOneOf()}.
     */
    public function testRequireOneOf() {
        $schema = $this
            ->getAtomicSchema()
            ->requireOneOf(['description', 'enabled']);

        $valid1 = ['id' => 123, 'name' => 'Foo', 'description' => 'Hello'];
        $this->assertTrue($schema->isValid($valid1));

        $valid2 = ['id' => 123, 'name' => 'Foo', 'enabled' => true];
        $this->assertTrue($schema->isValid($valid2));

        $invalid1 = ['id' => 123, 'name' => 'Foo'];
        $this->assertFalse($schema->isValid($invalid1));

        // Test requiring one of nested.
        $schema = $this
            ->getAtomicSchema()
            ->requireOneOf(['description', ['amount', 'enabled']]);

        $this->assertTrue($schema->isValid($valid1));

        $valid3 = ['id' => 123, 'name' => 'Foo', 'amount' => 99, 'enabled' => true];
        $this->assertTrue($schema->isValid($valid3));

        $this->assertFalse($schema->isValid($invalid1));

        $invalid2 = ['id' => 123, 'name' => 'Foo', 'enabled' => true];
        $this->assertFalse($schema->isValid($invalid2));

        // Test requiring 2 of.
        $schema = $this
            ->getAtomicSchema()
            ->requireOneOf(['description', 'amount', 'enabled'], 2);

        $valid4 = ['id' => 123, 'name' => 'Foo', 'description' => 'Hello', 'enabled' => true];
        $this->assertTrue($schema->isValid($valid4));

        $this->assertFalse($schema->isValid($valid1));
        $this->assertFalse($schema->isValid($valid2));
    }

    /**
     * Test a variety of invalid values.
     *
     * @param string $type The type short code.
     * @param mixed $value A value that should be invalid for the type.
     * @dataProvider provideInvalideData
     */
    public function testInvalidValues($type, $value) {
        $schema = new Schema([
            "col:$type?"
        ]);
        $strval = print_r($value, true);

        $invaldData = ['col' => $value];
        /* @var Validation $validation */
        $isValid = $schema->isValid($invaldData, $validation);
        $this->assertFalse($isValid, "isValid: type $type with value $strval should not be valid.");
        $this->assertFalse($validation->fieldValid('col'), "fieldValid: type $type with value $strval should not be valid.");
    }

    /**
     * Test merging basic schemas.
     */
    public function testBasicMerge() {
        $schemaOne = new Schema(['foo:s']);
        $schemaTwo = new Schema(['bar:s']);

        $schemaOne->merge($schemaTwo);

        $expected = [
            'foo' => ['name' => 'foo', 'type' => 'string', 'required' => true],
            'bar' => ['name' => 'bar', 'type' => 'string','required' => true]
        ];

        $actual = $schemaOne->getSchema();
        $this->assertEquals($expected, $actual['properties']);
    }

    /**
     * Provide a variety of valid boolean data.
     *
     * @return array Returns an array of boolean data.
     */
    public function provideBooleanData() {
        return [
            'false' => [false, false],
            'false str' => ['false', false],
            '0' => [0, false],
            '0 str' => ['0', false],
            'off' => ['off', false],
            'no' => ['no', false],

            'true' => [true, true],
            'true str' => ['true', true],
            '1' => [1, true],
            '1 str' => ['1', true],
            'on' => ['on', true],
            'yes' => ['yes', true]
        ];
    }

    /**
     * Call validate on an instance of Schema where the data contains unexpected parameters.
     * *
     * @param int $validationBehavior
     */
    protected function doValidationBehavior($validationBehavior) {
        $schema = new Schema([
            'userID:i' => 'The ID of the user.',
            'name:s' => 'The username of the user.',
            'email:s' => 'The email of the user.',
        ]);
        $schema->setValidationBehavior($validationBehavior);

        $data = [
            'userID' => 123,
            'name' => 'foo',
            'email' => 'user@example.com',
            'admin' => true,
            'role' => 'Administrator'
        ];

        $schema->validate($data);
        $this->assertArrayNotHasKey('admin', $data);
        $this->assertArrayNotHasKey('role', $data);
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
     * Get a schema of atomic types.
     *
     * @return Schema Returns the schema of atomic types.
     */
    public function getAtomicSchema() {
        $schema = Schema::create(
            [
                'id:i',
                'name:s' => 'The name of the object.',
                'description:s?',
                'timestamp:ts?',
                'date:dt?',
                'amount:f?',
                '64ish:=?',
                'enabled:b?',
            ]
        );

        return $schema;
    }
}
