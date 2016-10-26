<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Tests\Schemas;

/**
 * Base class for schema tests.
 */
abstract class SchemaTest extends \PHPUnit_Framework_TestCase {
    /**
     * Provides all of the schema types.
     *
     * @return array Returns an array of types suitable to pass to a test method.
     */
    public function provideTypes() {
        $result = [
            'array' => ['a', 'array'],
            'object' => ['o', 'object'],
            'base64' => ['=', 'base64'],
            'integer' => ['i', 'integer'],
            'string' => ['s', 'string'],
            'float' => ['f', 'float'],
            'boolean' => ['b', 'boolean'],
            'timestamp' => ['ts', 'timestamp'],
            'datetime' => ['dt', 'datetime']
        ];
        return $result;
    }

    /**
     * Provide a variety of invalid data for the supported types.
     *
     * @return array Returns an data set with rows in the form [short type, value].
     */
    public function provideInvalideData() {
        $result = [
            ['a', false],
            ['a', 123],
            ['a', 'foo'],
            ['a', ['bar' => 'baz']],
            ['o', false],
            ['o', 123],
            ['o', 'foo'],
            ['o', [1, 2, 3]],
            ['=', false],
            ['=', 123],
            ['=', [1, 2, 3]],
            ['=', 'Iñtërnâtiônàlizætiøn'],
            ['i', false],
            ['i', 'foo'],
            ['i', [1, 2, 3]],
            ['s', false],
            ['s', [1, 2, 3]],
            ['f', false],
            ['f', 'foo'],
            ['f', [1, 2, 3]],
            ['b', 123],
            ['b', 'foo'],
            ['b', [1, 2, 3]],
            ['ts', false],
            ['ts', 'foo'],
            ['ts', [1, 2, 3]],
            ['dt', 'foo'],
            ['dt', [1, 2, 3]]
        ];

        return $result;
    }
}
