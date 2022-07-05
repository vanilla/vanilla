<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Asset;

use org\bovigo\vfs\vfsStream;
use Vanilla\Theme\FsThemeProvider;
use Vanilla\Theme\ThemeService;
use Vanilla\Theme\ThemeServiceHelper;
use Vanilla\Web\Asset\LocaleAsset;
use Vanilla\Web\Asset\WebpackAsset;
use Vanilla\Web\Asset\WebpackAssetProvider;
use VanillaTests\Fixtures\MockAddon;
use VanillaTests\Fixtures\MockAddonManager;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\Fixtures\Request;
use VanillaTests\MinimalContainerTestCase;
use Webmozart\PathUtil\Path;

/**
 * Tests for the asset provider.
 */
class WebpackAssetProviderTest extends MinimalContainerTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        vfsStream::setup();
    }

    /**
     * Get simple WebpackAssetProvider instance
     *
     * @param array $addons
     * @return WebpackAssetProvider
     */
    private function getWebpackAssetProvider(array $addons = [])
    {
        $session = new \Gdn_Session();
        $addonManager = new MockAddonManager($addons);
        $config = new MockConfig(["Garden.CurrentTheme" => "default"]);
        $themeHelper = new ThemeServiceHelper($addonManager, $session, $config);
        $themeService = self::container()->getArgs(ThemeService::class, [
            $config,
            $session,
            $addonManager,
            $themeHelper,
        ]);
        $request = new Request();
        $fsThemeProvider = new FsThemeProvider($addonManager, $config, $themeHelper);
        $themeService->addThemeProvider($fsThemeProvider);
        $provider = new WebpackAssetProvider($request, $addonManager, $session, $config, $themeService);
        return $provider;
    }

    /**
     * Test the hot reload functionality.
     */
    public function testHotReload()
    {
        $provider = $this->getWebpackAssetProvider();
        $provider->setHotReloadEnabled(true);
        $sectionKey = "forum";

        $styles = $provider->getStylesheets($sectionKey);
        $this->assertEquals(0, count($styles), "Hot reload disables server managed stylesheets");

        $scripts = $provider->getScripts($sectionKey);
        $this->assertEquals(1, count($scripts), "Only 1 script should be returned with hot reload");
        $this->assertEquals(
            "https://webpack.vanilla.localhost:3030/$sectionKey-hot-bundle.js",
            $scripts[0]->getWebPath()
        );

        $otherSectionKey = "otherSection";
        $scripts = $provider->getScripts($otherSectionKey);
        $this->assertEquals(
            "https://webpack.vanilla.localhost:3030/$otherSectionKey-hot-bundle.js",
            $scripts[0]->getWebPath()
        );
    }

    /**
     * Test that the locale asset is always first when requested.
     */
    public function testLocaleAsset()
    {
        $provider = $this->getWebpackAssetProvider();
        $scripts = $provider->getScripts("someSection");
        $this->assertNotInstanceOf(
            LocaleAsset::class,
            $scripts[0] ?? null,
            "The first asset is a not locale asset if the locale key has not been specified"
        );

        $provider->setLocaleKey("en");
        $scripts = $provider->getScripts("someSection");
        $this->assertInstanceOf(LocaleAsset::class, $scripts[0]);
        $this->assertEquals(
            "http://example.com/api/v2/locales/en/translations.js",
            $scripts[0]->getWebPath(),
            "Creates a valid API js file"
        );

        $buster = "cacheBuster12345";
        $provider->setCacheBusterKey($buster);
        $scripts = $provider->getScripts("someSection");
        $this->assertEquals(
            "http://example.com/api/v2/locales/en/translations.js?h=$buster",
            $scripts[0]->getWebPath(),
            "Uses the cache buster key"
        );
    }

    /**
     * Test that addon files are added dynamically.
     */
    public function testAddonAssets()
    {
        $section = "test";
        $mockAddons = [
            new MockAddon("everything"),
            new MockAddon("js-only"),
            new MockAddon("css-only"),
            new MockAddon("default"), // Theme
            // Note there is no disabled
        ];

        $provider = $this->getWebpackAssetProvider($mockAddons);
        $provider->setFsRoot(PATH_TEST_CACHE);

        $this->writeTestDist();

        // Stylesheets
        $styleSheets = $provider->getStylesheets($section);
        $this->assertAssetUrls(
            ["/path/to/vendor1.min.css", "/path/to/everything.min.css", "/path/to/css-only.min.css"],
            $styleSheets
        );

        // For code coverage of reloading from cache.
        $provider->clearCollections();

        // Scripts
        $scripts = $provider->getScripts($section);
        $this->assertAssetUrls(
            [
                "/path/to/runtime.min.js",
                "/path/to/vendor1.min.js",
                "/path/to/addons/everything.min.js",
                "/path/to/addons/js-only.min.js",
                "/path/to/bootstrap.min.js",
            ],
            $scripts
        );
    }

    /**
     * Assert an array of asset urls in order.
     *
     * @param string[] $expected
     * @param WebpackAsset[] $assets
     */
    private function assertAssetUrls(array $expected, array $assets)
    {
        $webroot = "http://example.com/dist/v1/test/";
        $actual = array_map(function (WebpackAsset $asset) {
            return $asset->getWebPath();
        }, $assets);
        $expected = array_map(function (string $path) use ($webroot) {
            return Path::join($webroot, $path);
        }, $expected);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return string
     */
    private function writeTestDist(): string
    {
        $someHash = "a1e34123";
        $root = "/dist/v1/test/path/to";

        $manifest = [
            "vendor-$someHash.js" => $this->manifestItem($root . "/vendor1.min.js"),
            "vendor-$someHash.css" => $this->manifestItem($root . "/vendor1.min.css"),
            "runtime.js" => $this->manifestItem($root . "/runtime.min.js"),
            "bootstrap.js" => $this->manifestItem($root . "/bootstrap.min.js"),
            "addons/everything.js" => $this->manifestItem($root . "/addons/everything.min.js"),
            "addons/everything-$someHash.css" => $this->manifestItem($root . "/everything.min.css"),
            "addons/js-only-$someHash.js" => $this->manifestItem($root . "/addons/js-only.min.js"),
            "addons/css-only-$someHash.css" => $this->manifestItem($root . "/css-only.min.css"),
        ];
        $path = Path::join(PATH_TEST_CACHE, "dist/v1/test/manifest.json");
        $dirname = dirname($path);
        mkdir($dirname, 0777, true);
        file_put_contents($path, json_encode($manifest));
        return $path;
    }

    /**
     * @param string $path
     * @return array
     */
    private function manifestItem(string $path): array
    {
        return [
            "filePath" => $path,
            "dependsOnAsyncChunks" => [],
        ];
    }
}
