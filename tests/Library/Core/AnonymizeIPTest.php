<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Core;

/**
 * Test anonymizing IP addresses.
 */
class AnonymizeIPTest extends \PHPUnit\Framework\TestCase {

    /**
     * @return array
     */
    public function provideIPAddresses(): array {
        $result = [
            ['8.8.8.8', '8.8.8.0'],
            ['2001:4860:4860::8888', '2001:4860:4860::'],
            ['1', false],
        ];
        return $result;
    }

    /**
     * @param string $ip
     * @param string|bool $anonymized
     * @dataProvider provideIPAddresses
     */
    public function testAnonymization($ip, $anonymized) {
        $result = anonymizeIP($ip);
        $this->assertSame($anonymized, $result);
    }
}
