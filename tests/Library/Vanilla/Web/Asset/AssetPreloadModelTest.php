<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Asset;

use Vanilla\Web\Asset\AssetPreloader;
use Vanilla\Web\Asset\AssetPreloadModel;
use Vanilla\Web\Asset\ExternalAsset;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;
use VanillaTests\SharedBootstrapTestCase;

/**
 * Tests for the asset preload model.
 */
class AssetPreloadModelTest extends SharedBootstrapTestCase {

    use HtmlNormalizeTrait;

    /**
     * Test that unique keys work.
     */
    public function testDuplication() {
        $model = new AssetPreloadModel();
        $first = "https://test.asset.com/1.js";
        $second = "https://test.asset.com/2.js";
        $key = "uniqueKey";
        $model->addScript(new ExternalAsset($first), AssetPreloader::REL_PRELOAD, $key);
        $model->addScript(new ExternalAsset($second), AssetPreloader::REL_PRELOAD, $key);
        $this->assertCount(1, $model->getPreloads());
        $this->assertEquals($first, $model->getPreloads()[0]->getAsset()->getWebPath());
    }

    /**
     * Test the HTML rendering.
     */
    public function testRendering() {
        $model = new AssetPreloadModel();
        $model->addScript(new ExternalAsset("test-script"), AssetPreloader::REL_FULL);
        $model->addScript(new ExternalAsset("test-script-preload"), AssetPreloader::REL_PRELOAD);
        $model->addScript(new ExternalAsset("test-script-prefetch"), AssetPreloader::REL_PREFETCH);
        $model->addStylesheet(new ExternalAsset("test-style"), AssetPreloader::REL_FULL);
        $model->addStylesheet(new ExternalAsset("test-style-preload"), AssetPreloader::REL_PRELOAD);
        $model->addStylesheet(new ExternalAsset("test-style-prefetch"), AssetPreloader::REL_PREFETCH);

        $expected = <<<HTML
<!-- Preload links, scripts, and stylesheets -->

<script defer src="test-script"></script>
<link href="test-style" rel="stylesheet" type="text/css" />
<link href="test-script-preload" as="script" rel="preload" />
<link href="test-script-prefetch" as="script" rel="prefetch" />
<link href="test-style-preload" as="style" rel="preload" />
<link href="test-style-prefetch" as="style" rel="prefetch" />

HTML;

        $this->assertEquals(
            $expected,
            $model->renderHtml()
        );
    }
}
