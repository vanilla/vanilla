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
