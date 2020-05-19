<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Asset;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use Vanilla\Models\FsThemeProvider;
use Vanilla\Models\ThemeModel;
use Vanilla\Models\ThemeModelHelper;
use Vanilla\Models\ThemeSectionModel;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Web\Asset\LocaleAsset;
use Vanilla\Web\Asset\WebpackAssetProvider;
use VanillaTests\Fixtures\MockAddon;
use VanillaTests\Fixtures\MockAddonManager;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\Fixtures\MockSiteSectionProvider;
use VanillaTests\Fixtures\Request;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for the asset provider.
 */
class WebpackAssetProviderTest extends MinimalContainerTestCase {

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        parent::setUp();
        vfsStream::setup();
    }

    /**
     * Get simple WebpackAssetProvider instance
     *
     * @param array $addons
     * @return WebpackAssetProvider
     */
    private function getWebpackAssetProvider(array $addons = []) {
        $session = new \Gdn_Session();
        $addonManager = new MockAddonManager($addons);
        $config = new MockConfig(['Garden.CurrentTheme'=>'default']);
        $themeHelper = new ThemeModelHelper(
            $addonManager,
            $session,
            $config
        );
        $themeModel = self::container()->getArgs(ThemeModel::class, [
            $config,
            $session,
            $addonManager,
            $themeHelper,
        ]);
        $request = new Request();
        $fsThemeProvider = new FsThemeProvider(
            $addonManager,
            $request,
            $config,
            $themeHelper
        );
        $themeModel->addThemeProvider($fsThemeProvider);
        $provider = new WebpackAssetProvider(
            $request,
            $addonManager,
            $session,
            $config,
            $themeModel
        );
        return $provider;
    }

    /**
     * Test the hot reload functionality.
     */
    public function testHotReload() {
        $provider = $this->getWebpackAssetProvider();
        $provider->setHotReloadEnabled(true, '');
        $sectionKey = 'forum';

        $styles = $provider->getStylesheets($sectionKey);
        $this->assertEquals(0, count($styles), "Hot reload disables server managed stylesheets");


        $scripts = $provider->getScripts($sectionKey);
        $this->assertEquals(1, count($scripts), "Only 1 script should be returned with hot reload");
        $this->assertEquals("http://127.0.0.1:3030/$sectionKey-hot-bundle.js", $scripts[0]->getWebPath());

        // Different hostname.
        $provider->setHotReloadEnabled(true, 'localhost');
        $scripts = $provider->getScripts($sectionKey);
        $this->assertEquals("http://localhost:3030/$sectionKey-hot-bundle.js", $scripts[0]->getWebPath());

        $otherSectionKey = "otherSection";
        $scripts = $provider->getScripts($otherSectionKey);
        $this->assertEquals("http://localhost:3030/$otherSectionKey-hot-bundle.js", $scripts[0]->getWebPath());
    }

    /**
     * Test that the locale asset is always first when requested.
     */
    public function testLocaleAsset() {
        $provider = $this->getWebpackAssetProvider();
        $scripts = $provider->getScripts('someSection');
        $this->assertNotInstanceOf(
            LocaleAsset::class,
            $scripts[0],
            "The first asset is a not locale asset if the locale key has not been specified"
        );

        $provider->setLocaleKey('en');
        $scripts = $provider->getScripts('someSection');
        $this->assertInstanceOf(LocaleAsset::class, $scripts[0]);
        $this->assertEquals(
            'http://example.com/api/v2/locales/en/translations.js',
            $scripts[0]->getWebPath(),
            "Creates a valid API js file"
        );

        $buster = "cacheBuster12345";
        $provider->setCacheBusterKey($buster);
        $scripts = $provider->getScripts('someSection');
        $this->assertEquals(
            "http://example.com/api/v2/locales/en/translations.js?h=$buster",
            $scripts[0]->getWebPath(),
            "Uses the cache buster key"
        );
    }

    /**
     * Test that addon files are added dynamically.
     */
    public function testAddonAssets() {
        $section = "test";

        $structure = [
            "dist" => [
                $section => [
                    'addons' => [
                        'everything.min.js' => '',
                        'everything.min.css' => '',
                        'js-only.min.js' => '',
                        'css-only.min.css' => '',
                        'disabled.min.js' => '',
                        'disabled.min.css' => '',
                    ],
                ],
            ],
        ];

        $mockAddons = [
            new MockAddon('everything'),
            new MockAddon('js-only'),
            new MockAddon('css-only'),
            new MockAddon('default'), // Theme
            // Note there is no disabled
        ];

        $fileSystem = vfsStream::create($structure);
        $provider = $this->getWebpackAssetProvider($mockAddons);
        $provider->setFsRoot($fileSystem->url());
        $buster = 'buster12345';
        $provider->setCacheBusterKey($buster);
        $root = 'http://example.com/dist/test/';
        $addonRoot = $root . 'addons/';

        // Stylesheets
        $styleSheets = $provider->getStylesheets($section);
        $this->assertCount(2, $styleSheets);
        $this->assertEquals($addonRoot . "everything.min.css?h=$buster", $styleSheets[0]->getWebPath());
        $this->assertEquals($addonRoot . "css-only.min.css?h=$buster", $styleSheets[1]->getWebPath());

        // Scripts
        $scripts = $provider->getScripts($section);
        $this->assertCount(5, $scripts);
        $this->assertEquals($root . "runtime.min.js?h=$buster", $scripts[0]->getWebPath());
        $this->assertEquals($root . "vendors.min.js?h=$buster", $scripts[1]->getWebPath());
        $this->assertEquals($addonRoot . "everything.min.js?h=$buster", $scripts[2]->getWebPath());
        $this->assertEquals($addonRoot . "js-only.min.js?h=$buster", $scripts[3]->getWebPath());
        $this->assertEquals($root . "bootstrap.min.js?h=$buster", $scripts[4]->getWebPath());
    }
}
