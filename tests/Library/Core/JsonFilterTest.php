<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Core;

/**
 * Test the jsonFilter function.
 */
class JsonFilterTest extends \PHPUnit_Framework_TestCase {

    public function testJsonFilterDateTime() {
        $date = new \DateTime();
        $data = ['Date' => $date];
        jsonFilter($data);

        $this->assertSame($date->format('r'), $data['Date']);
    }

    public function testJsonFilterDateTimeRecursive() {
        $date = new \DateTime();
        $data = [
            'Dates' => ['FirstDate' => $date]
        ];
        jsonFilter($data);

        $this->assertSame($date->format('r'), $data['Dates']['FirstDate']);
    }

    public function testJsonFilterEncodedIP() {
        $ip = '127.0.0.1';
        $data = ['IP' => ipEncode($ip)];
        jsonFilter($data);

        $this->assertSame($ip, $data['IP']);
    }

    public function testJsonFilterEncodedIPRecursive() {
        $ip = '127.0.0.1';
        $data = [
            'IPs' => ['FirstIP' => ipEncode($ip)]
        ];
        jsonFilter($data);

        $this->assertSame($ip, $data['IPs']['FirstIP']);
    }

    public function testJsonFilterPassThrough() {
        $data = [
            'Array' => ['Key' => 'Value'],
            'Boolean' => true,
            'Float' => 1.234,
            'Integer' => 10,
            'Null' => null,
            'String' => 'The quick brown fox jumps over the lazy dog.'
        ];

        $filteredData = $data;
        jsonFilter($filteredData);

        $this->assertSame($data, $filteredData);
    }
}
