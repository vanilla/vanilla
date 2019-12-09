<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Asset;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use Vanilla\Web\Asset\LocaleAsset;
use Vanilla\Web\Asset\WebpackAssetProvider;
use VanillaTests\Fixtures\MockAddon;
use VanillaTests\Fixtures\MockAddonProvider;
use VanillaTests\Fixtures\Request;

/**
 * Tests for the asset provider.
 */
class WebpackAssetProviderTest extends TestCase {

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        vfsStream::setup();
    }

    /**
     * Test the hot reload functionality.
     */
    public function testHotReload() {
        $provider = new WebpackAssetProvider(new Request(), new MockAddonProvider([]));
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
        $provider = new WebpackAssetProvider(new Request(), new MockAddonProvider([]));
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
            '/api/v2/locales/en/translations.js',
            $scripts[0]->getWebPath(),
            "Creates a valid API js file"
        );

        $buster = "cacheBuster12345";
        $provider->setCacheBusterKey($buster);
        $scripts = $provider->getScripts('someSection');
        $this->assertEquals(
            "/api/v2/locales/en/translations.js?h=$buster",
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
            // Note there is no disabled
        ];

        $fileSystem = vfsStream::create($structure);
        $provider = new WebpackAssetProvider(new Request(), new MockAddonProvider($mockAddons));
        $provider->setFsRoot($fileSystem->url());
        $buster = 'buster12345';
        $provider->setCacheBusterKey($buster);
        $root = '/dist/test/';
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
