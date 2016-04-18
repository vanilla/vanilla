<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Core;

/**
 * Test some of the global functions that operate (or mostly operate) on arrays.
 */
class ArrayFunctionsTest extends \PHPUnit_Framework_TestCase {

    /**
     *
     */
    public function testDbEncodeArray() {
        $data = ['Forum' => 'Vanilla'];
        $encoded = dbencode($data);
        $this->assertSame($data, dbdecode($encoded));
    }

    /**
     * Test the basic encoding/decoding of data.
     *
     * @param mixed $data The data to test.
     * @dataProvider provideDbEncodeValues
     */
    public function testDbEncodeDecode($data) {
        $this->assertNotNull($data);

        $encoded = dbencode($data);
        $this->assertNotFalse($encoded);
        $this->assertTrue(is_string($encoded));

        $decoded = dbdecode($encoded);

        $this->assertSame($data, $decoded);
    }

    /**
     * Provide some values for {@link testDbEncodeDecode()}.
     *
     * @return array Returns a data provider.
     */
    public function provideDbEncodeValues() {
        $r = [
            'string' => ['Hello world!'],
            'int' => [123],
            'true' => [true],
            'false' => [false],
            'array' => [['Forum' => 'Vanilla']],
            'array-nested' => [['userID' => 123, 'prefs' => ['foo' => true, 'bar' => [1, 2, 3]]]]
        ];

        return $r;
    }

    /**
     * Encoding a value of null should be null.
     */
    public function testDbEncodeNull() {
        $this->assertNull(dbencode(null));
    }

    /**
     * Decoding a value of null should be null.
     */
    public function testDbDecodeNull() {
        $this->assertNull(dbdecode(null));
    }
}
