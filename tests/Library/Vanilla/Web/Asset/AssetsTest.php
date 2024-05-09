<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Asset;

use Vanilla\Web\Asset\WebAsset;
use Vanilla\Web\Asset\LocaleAsset;
use VanillaTests\Fixtures\Request;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for various assets.
 */
class AssetsTest extends MinimalContainerTestCase
{
    /**
     * Tests for the site asset.
     */
    public function testStatic()
    {
        $locale = new LocaleAsset(new Request(), "en");
        $this->assertFalse($locale->isStatic());

        $external = new WebAsset("http://test.com/script.js");
        $this->assertFalse($external->isStatic());
    }
}
