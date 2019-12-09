<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Asset;

use Vanilla\Web\Asset\ExternalAsset;
use Vanilla\Web\Asset\HotBuildAsset;
use Vanilla\Web\Asset\LocaleAsset;
use Vanilla\Web\Asset\PolyfillAsset;
use VanillaTests\Fixtures\Request;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for various assets.
 */
class AssetsTest extends MinimalContainerTestCase {

    /**
     * Tests for the site asset.
     */
    public function testStatic() {
        $polyfill = new PolyfillAsset(new Request());
        $this->assertTrue($polyfill->isStatic());

        $locale = new LocaleAsset(new Request(), "en");
        $this->assertFalse($locale->isStatic());

        $external = new ExternalAsset("http://test.com/script.js");
        $this->assertFalse($external->isStatic());

        $hotBuild = new HotBuildAsset("test");
        $this->assertFalse($hotBuild->isStatic());
    }
}
