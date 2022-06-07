<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Theme;

use Vanilla\Theme\Asset\CssThemeAsset;
use Vanilla\Theme\Asset\HtmlThemeAsset;
use Vanilla\Theme\Asset\ImageThemeAsset;
use Vanilla\Theme\Asset\JavascriptThemeAsset;
use Vanilla\Theme\Asset\JsonThemeAsset;
use Vanilla\Theme\Asset\TwigThemeAsset;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\BootstrapTestCase;
use Vanilla\Theme\ThemeAssetFactory;
use function Amp\Iterator\concat;

/**
 * Test ThemeAssetFactory.
 */
class ThemeAssetFactoryTest extends BootstrapTestCase
{
    /** @var ThemeAssetFactory */
    private $themeAssetFactory;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->themeAssetFactory = self::container()->get(ThemeAssetFactory::class);
    }

    /**
     * Test merging Css assets.
     */
    public function testMergeCSSAssets(): void
    {
        $cssStyle1 = ".display { inline }";
        $cssStyle2 = ".header { background: white }";
        $cssAsset1 = new CssThemeAsset($cssStyle1, "");
        $cssAsset2 = new CssThemeAsset($cssStyle2, "/discussions");
        $mergedCssAssets = $this->themeAssetFactory->mergeAssets($cssAsset1, $cssAsset2);
        $this->assertEquals($cssStyle1 . $cssStyle2, $mergedCssAssets->getData());
    }

    /**
     * Test merging NonMergeable assets.
     *
     * @dataProvider provideNonMergeable
     *
     * @param string $type
     * @param string $value
     */
    public function testNonMergeableAssets(string $type, string $value): void
    {
        $className = "Vanilla\\Theme\Asset\\" . $type;
        $asset = new $className($value, "");
        $mergedCssAssets = $this->themeAssetFactory->mergeAssets($asset);
        $this->assertFalse($mergedCssAssets->canMerge());
    }

    /**
     * Test merging JS assets.
     */
    public function testMergeJsAssets(): void
    {
        $data = [
            "js1" => [
                "asset" => 'console.log("Hello world");',
            ],
            "js2" => [
                "asset" => 'function myFunction(p1, p2) {
                return p1 * p2;}',
                "url" => "/categories,",
            ],
        ];
        $jsAsset1 = new JavascriptThemeAsset($data["js1"]["asset"], "");
        $jsAsset2 = new JavascriptThemeAsset($data["js2"]["asset"], $data["js2"]["url"]);
        $mergedAssets = $this->themeAssetFactory->mergeAssets($jsAsset1, $jsAsset2);
        $this->assertSame($data["js1"]["asset"] . $data["js2"]["asset"], $mergedAssets->getValue());
        $this->assertSame($data["js2"]["url"], $mergedAssets->getUrl());
    }

    /**
     * Test Merging Json assets.
     *
     * @dataProvider provideJson
     *
     * @param array $asset1
     * @param array $asset2
     */
    public function testMergeJsonAssets(array $asset1, array $asset2): void
    {
        $jsonAsset1 = new JsonThemeAsset($asset1["asset"], $asset1["url"]);
        $jsonAsset2 = new JsonThemeAsset($asset2["asset"], $asset2["url"]);
        $mergedAssets = $this->themeAssetFactory->mergeAssets($jsonAsset1, $jsonAsset2);
        $expected = ArrayUtils::mergeRecursive($jsonAsset1->getValue(), $jsonAsset2->getValue());
        $this->assertEquals($expected, $mergedAssets->getValue());
    }

    /**
     * NonMergeableAssets data provider.
     */
    public function provideNonMergeable(): array
    {
        $html = <<<HTML
<div>Hello world.</div>
HTML;
        return [
            [
                "type" => "HtmlThemeAsset",
                "value" => $html,
            ],
            [
                "type" => "ImageThemeAsset",
                "value" => "https://example.com",
            ],
            [
                "type" => "TwigThemeAsset",
                "value" => $html,
            ],
        ];
    }

    /**
     * Json data provider.
     *
     * @return array
     */
    public function provideJson(): array
    {
        $json1 = <<<JSON
{
  "name": "test Json",
  "description": "test Json",
  "version": "1.0.0",
  "type": "test",
  "authors": [
    {
      "name": "test",
      "email": "test@example.com",
      "homepage": "https://vanillaforums.com"
    }
  ],
  "require": {
    "vanilla": ">=2.8"
  }
}
JSON;
        $json2 = <<<JSON
{
  "key" : "test"
}
JSON;
        return [
            "merge-2" => [
                [
                    "asset" => $json1,
                    "url" => "",
                ],
                [
                    "asset" => $json2,
                    "url" => "/discussions",
                ],
            ],
        ];
    }
}
