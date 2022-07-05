<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Theme;

use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Theme\Asset\ThemeAsset;
use Vanilla\Theme\ThemeService;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Fixtures\TestAddonManager;

/**
 * Class ThemeServiceParentTest
 */
class ThemeServiceParentTest extends BootstrapTestCase
{
    /**
     * Test that parent assets are merged in.
     */
    public function testMergeParentAssets()
    {
        $testAddonManager = new TestAddonManager();
        self::container()->setInstance(AddonManager::class, $testAddonManager);
        /** @var ThemeService $themeService */
        $themeService = self::container()->get(ThemeService::class);

        $basicThemeAddon = $testAddonManager->lookupTheme("asset-test2");
        $this->assertInstanceOf(Addon::class, $basicThemeAddon);

        $basicTheme = $themeService->getTheme("asset-test2");
        $this->assertEquals($basicThemeAddon, $basicTheme->getAddon());

        $this->assertAssetContents(
            [
                "fsParentTheme" => false,
                "fsChildTheme" => true,
                "nested" => [
                    "fsParent" => true,
                    "fsChild" => true,
                ],
            ],
            $basicTheme->getAsset("variables")
        );

        $this->assertAssetContents(
            "console.log(\"Hello FS parent\");\nconsole.log(\"Hello FS child\");\nconsole.log(\"Hello FS child2\");\n",
            $basicTheme->getAsset("javascript")
        );
        $css = <<<CSS
body {
    :--fs-parent-theme: #fff;
}
body {
    :--fs-child-theme: #fff;
}
body {
    :--fs-child-theme2: #fff;
}

CSS;
        $this->assertAssetContents($css, $basicTheme->getAsset("styles"));
    }

    /**
     * Assert asset contents.
     *
     * @param string $expected
     * @param ThemeAsset $asset
     */
    private function assertAssetContents($expected, ThemeAsset $asset)
    {
        $this->assertEquals($expected, $asset->getValue());
    }
}
