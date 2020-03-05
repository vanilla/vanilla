<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;

/**
 * Test some of the global functions that operate (or mostly operate) on arrays.
 */
class DbEncodeDecodeTest extends SharedBootstrapTestCase {

    /**
     * Test encoding/decoding an array.
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
        $this->assertNull(dbencode(''));
    }

    /**
     * Decoding a value of null should be null.
     */
    public function testDbDecodeNull() {
        $this->assertNull(dbdecode(null));
        $this->assertNull(dbdecode(''));
    }

    /**
     * You should be able to call {@link dbdecode()} on an array and have it just pass through.
     */
    public function testDoubleDecodeArray() {
        $arr = [1, 2, 'foo' => [3, 4]];
        $encoded = dbencode($arr);
        $decoded = dbdecode($encoded);
        $decoded2 = dbdecode($decoded);

        $this->assertSame($arr, $decoded2);
    }

    /**
     * Make sure we have a bad string for {@link dbdecode()}.
     *
     * @param string $str The bad string to decode.
     * @dataProvider provideBadDbDecodeStrings
     */
    public function testBadDbDecodeString($str) {
        $this->expectNotice();
        $decoded = unserialize($str);
    }

    /**
     * Test {@link dbdecode()} with a bogus string.
     *
     * The trick here is that {@link dbdecode()} should not raise an exception or throw an error.
     *
     * @param mixed $str The bad string to decode.
     * @dataProvider  provideBadDbDecodeStrings
     * @see testBadDbDecodeString()
     */
    public function testDbDecodeError($str) {
        $decoded = dbdecode($str);
        $this->assertFalse($decoded);
    }

    /**
     * Provide some strings that would normally cause a deserialization error.
     *
     * @return array Returns a data provider string.
     */
    public function provideBadDbDecodeStrings() {
        $r = [
            ['a:3:{i:0;i:1;i:'],
            ['{"foo": "bar"'],
        ];

        return $r;
    }
}
