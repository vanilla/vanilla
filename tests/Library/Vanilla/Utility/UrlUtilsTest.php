<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Vanilla\Utility\UrlUtils;
use PHPUnit\Framework\TestCase;

/**
 * Class UrlUtilsTest Tests punnyEncode() function.
 *
 * @package VanillaTests\Library\Vanilla\Utility
 */
class UrlUtilsTest extends TestCase {
    /**
     * Provide data for testing the domainAsAscii function.
     *
     * @return array of domains to test.
     */
    public function provideUnicodeDomains(): array {
        $result = [
            'Valid ASCII domain' => ['www.vanillaforums.com', 'www.vanillaforums.com'],
            'Valid Unicode domain' => ['goοgle.com', 'xn--gogle-sce.com'],
            'Invalid Unicode domain (contains illegal characters)' => ['//goo�gle.com/', false],
        ];
        return array_column($result, null, 0);
    }

    /**
     * Test the domainAsAscii() function.
     *
     * @param string $domain Test domain.
     * @param string $punyEncoded Domain converted to IDNA ASCII.
     * @dataProvider provideUnicodeDomains
     */
    public function testDomainAsAscii($domain, $punyEncoded) {
        $result = UrlUtils::domainAsAscii($domain);
        $this->assertEquals($result, $punyEncoded);
    }
}
