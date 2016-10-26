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
            'enabled' => ['name' => 'enabled', 'type' => 'boolean', 'required' => false],
        ];

        $this->assertEquals($expected, $schema->jsonSerialize());
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
            'descriptiom' => 456,
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
        $schema = Schema::create(['b:bool']);
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
        $schema = Schema::create(['a:arr']);

        $expectedSchema = [
            'arr' => ['name' => 'arr', 'type' => 'array', 'required' => true]
        ];

        // Basic array without a type.
        $this->assertEquals($expectedSchema, $schema->jsonSerialize());

        $data = ['arr' => [1, 2, 3]];
        $this->assertTrue($schema->isValid($data));
        $data = ['arr' => []];
        $this->assertTrue($schema->isValid($data));

        // Array with a description and not a type.
        $expectedSchema['arr']['description'] = 'Hello world!';
        $schema = Schema::create(['a:arr' => 'Hello world!']);
        $this->assertEquals($expectedSchema, $schema->jsonSerialize());

        // Array with an items type.
        unset($expectedSchema['arr']['description']);
        $expectedSchema['arr']['items']['type'] = 'integer';
        $expectedSchema['arr']['items']['required'] = true;

        $schema = Schema::create(['a:arr' => 'i']);
        $this->assertEquals($expectedSchema, $schema->jsonSerialize());

        // Test the longer syntax.
        $expectedSchema['arr']['description'] = 'Hello world!';
        $schema = Schema::create(['a:arr' => [
            'description' => 'Hello world!',
            'items' => ['type' => 'integer', 'required' => true]
        ]]);
        $this->assertEquals($expectedSchema, $schema->jsonSerialize());
    }

    /**
     * Test that the schema long form can be used to create a schema.
     */
    public function testLongCreate() {
        $schema = $this->getAtomicSchema();
        $schema2 = Schema::create($schema->jsonSerialize());

        $this->assertEquals($schema->jsonSerialize(), $schema2->jsonSerialize());
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
            "$shortType:col?"
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
            "$shortType:col"
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
            'b:col'
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
            's:col' => ['minLength' => 0]
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
            "$type:col?"
        ]);
        $strval = print_r($value, true);

        $invaldData = ['col' => $value];
        /* @var Validation $validation */
        $isValid = $schema->isValid($invaldData, $validation);
        $this->assertFalse($isValid, "isValid: type $type with value $strval should not be valid.");
        $this->assertFalse($validation->fieldValid('col'), "fieldValid: type $type with value $strval should not be valid.");
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
     * Get a schema of atomic types.
     *
     * @return Schema Returns the schema of atomic types.
     */
    public function getAtomicSchema() {
        $schema = Schema::create(
            [
                'i:id',
                's:name' => 'The name of the object.',
                'description?',
                'ts:timestamp?',
                'dt:date?',
                'f:amount?',
                '=:64ish?',
                'b:enabled?',
            ]
        );

        return $schema;
    }
}
