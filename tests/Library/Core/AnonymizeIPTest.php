<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

/**
 * Test anonymizing IP addresses.
 */
class AnonymizeIPTest extends \PHPUnit\Framework\TestCase {

    /**
     * Provide data for testing the anonymizeIP function.
     *
     * @return array
     */
    public function provideIPAddresses(): array {
        $result = [
            ['8.8.8.8', '8.8.8.0'],
            ['8.8.4.4', '8.8.4.0'],
            ['192.168.1.1', '192.168.1.0'],
            ['2001:4860:4860::8888', '2001:4860:4860::'],
            ['2001:db8:85a3:0:0:8a2e:0370:7334', '2001:db8:85a3::'],
            ['::1', '::'],
            ['1', false],
        ];
        return array_column($result, null, 0);
    }

    /**
     * Test the anonymizeIP function.
     *
     * @param string $ip An IPv4 or IPv6 address.
     * @param string|bool $anonymized The expected anonymized version of `$ip`.
     * @dataProvider provideIPAddresses
     */
    public function testAnonymization($ip, $anonymized) {
        $result = anonymizeIP($ip);
        $this->assertSame($anonymized, $result);
    }
}
