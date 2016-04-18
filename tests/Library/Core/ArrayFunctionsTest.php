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
     *
     */
    public function testDbEncodeObject() {
        $data = new \stdClass();
        $data->Forum = 'Vanilla';
        $encoded = dbencode($data);
        $decoded = dbdecode($encoded);

        $this->assertTrue(is_object($decoded) && $decoded->Forum === 'Vanilla');
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
