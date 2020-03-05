<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Vanilla\Web\UASniffer;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for the user agent sniffer.
 */
class UASnifferTest extends MinimalContainerTestCase {

    /**
     * Test the user agent sniffer.
     *
     * @param \Gdn_Session $session
     * @param string $uaString
     * @param bool $isIE
     *
     * @dataProvider provideIEUAStrings
     */
    public function testIsIE(\Gdn_Session $session, string $uaString, bool $isIE) {
        $sniffer = new UASniffer($session);
        $_SERVER['HTTP_USER_AGENT'] = $uaString;
        $this->assertEquals($isIE, $sniffer->isIE11());
    }

    /**
     * @return array
     */
    public function provideIEUAStrings(): array {
        $guest = new \Gdn_Session();
        $user = new \Gdn_Session();

        $realIEAgent1 = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; .NET4.0E; .NET4.0C; rv:11.0) like Gecko';
        $realIEAgent2 = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko';
        $badIECompatString = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.3; Trident/7.0; .NET4.0E; .NET4.0C)';
        $user->UserID = 5;
        return [
            [$guest, $realIEAgent1, false],
            [$guest, $realIEAgent2, false],
            [$guest, $badIECompatString, false],
            [$user, $realIEAgent1, true],
            [$user, $realIEAgent2, true],
            [$user, $badIECompatString, false],
        ];
    }
}
