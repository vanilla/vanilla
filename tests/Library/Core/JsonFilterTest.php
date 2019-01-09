<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;
use DateTime;

/**
 * Test the jsonFilter function.
 */
class JsonFilterTest extends SharedBootstrapTestCase {

    public function testJsonFilterDateTime() {
        $date = new DateTime('now', new \DateTimeZone('UTC'));
        $data = ['Date' => $date];
        jsonFilter($data);

        $this->assertSame($date->format(DateTime::RFC3339), $data['Date']);
    }

    public function testJsonFilterDateTimeRecursive() {
        $date = new DateTime();
        $data = [
            'Dates' => ['FirstDate' => $date]
        ];
        jsonFilter($data);

        $this->assertSame($date->format(DateTime::RFC3339), $data['Dates']['FirstDate']);
    }

    public function testJsonFilterEncodedIP() {
        $ip = '127.0.0.1';
        $data = ['InsertIPAddress' => ipEncode($ip)];
        jsonFilter($data);

        $this->assertSame($ip, $data['InsertIPAddress']);
    }

    public function testJsonFilterEncodedIPList() {
        $ip = ['127.0.0.1', '192.168.0.1', '10.0.0.1'];
        $encoded = ipEncodeRecursive(['AllIPAddresses' => $ip]);

        jsonFilter($encoded);

        $this->assertSame($ip, $encoded['AllIPAddresses']);
    }

    public function testJsonFilterEncodedIPRecursive() {
        $ip = '127.0.0.1';
        $data = [
            'Discussion' => ['UpdateIPAddress' => ipEncode($ip)]
        ];
        jsonFilter($data);

        $this->assertSame($ip, $data['Discussion']['UpdateIPAddress']);
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
