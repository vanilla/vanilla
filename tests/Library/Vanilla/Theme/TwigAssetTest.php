<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Theme;

use Vanilla\Theme\TwigThemeAsset;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for the twig asset.
 */
class TwigAssetTest extends MinimalContainerTestCase {

    // Saturday, July 27, 2015 12:00:01 AM
    const NOW = 1437955201;

    /**
     * Test our twig rendering.
     */
    public function testRender() {
        $template = <<<HTML
<div>Hello world. The date is {{ currentTime|date("m/d/Y")}}</div>
HTML;

        $asset = new TwigThemeAsset($template);
        $this->assertEquals(
            "<div>Hello world. The date is 07/27/2015</div>",
            $asset->renderHtml(['currentTime' => self::NOW])
        );
    }

    /**
     * Test our data output.
     */
    public function testData() {
        $template = '<div>Hello world</div>';
        $asset = new TwigThemeAsset($template);
        $encoded = json_decode(json_encode($asset), true);
        $this->assertEquals(['type' => 'twig', 'template' => $template, 'data' => $template], $encoded);
    }
}
