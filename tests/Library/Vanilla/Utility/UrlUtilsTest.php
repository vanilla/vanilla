<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Vanilla\Utility\UrlUtils;
use PHPUnit\Framework\TestCase;

class UrlUtilsTest extends TestCase {
    /**
     * Provide data for testing the testPunnyEncode function.
     *
     * @return array of domains to test.
     */
    public function provideDomains() {
        $result = [
            ['www.vanillaforums.com', 'www.vanillaforums.com'],
            ['goοgle.com', 'xn--gogle-sce.com'],
            ['//goo�gle.com/', false],
        ];
        return array_column($result, null, 0);
    }

    /**
     * Test the punnyEncode() function.
     *
     * @param string $domain Test domain.
     * @param string $punnyCoded Domain converted to IDNA ASCII.
     * @dataProvider provideDomains
     */
    public function testPunnyEncode($domain, $punnyEncoded) {
        $result = UrlUtils::punnyEncode($domain);
        $this->assertEquals($result, $punnyEncoded);
    }
}
