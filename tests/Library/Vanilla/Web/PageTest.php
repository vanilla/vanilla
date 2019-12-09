<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use VanillaTests\Fixtures\PageFixture;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests of Vanilla's base page class.
 */
class PageTest extends MinimalContainerTestCase {

    /**
     * Test that links get added properly.
     */
    public function testLinkTags() {
        /** @var PageFixture $fixture */
        $fixture = self::container()->get(PageFixture::class);

        $fixture->addLinkTag(['rel' => 'isLink']);
        $fixture->addMetaTag(['type' => 'isMeta']);
        $fixture->addOpenGraphTag('og:isOg', 'ogContent');

        $result = $fixture->render()->getData();

        $dom = new \DOMDocument();
        @$dom->loadHTML($result);

        $xpath = new \DOMXPath($dom);
        $head = $xpath->query('head')->item(0);

        // Link tag
        $link = $xpath->query("//link[@rel='isLink']", $head);
        $this->assertEquals(1, $link->count());

        // Meta tags
        $meta = $xpath->query("//meta[@type='isMeta']", $head);
        $this->assertEquals(1, $meta->count());

        $meta = $xpath->query("//meta[@property='og:isOg']", $head);
        $this->assertEquals(1, $meta->count());
    }
}
