<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\Addon;
use Vanilla\AddonManager;

/**
 * Test the /api/v2/themes endpoints.
 */
class ThemesTest extends AbstractAPIv2Test {

    /**
     * @var string The resource route.
     */
    protected $baseUrl = "/themes";

    /**
     * Undocumented function
     */
    public static function setupBeforeClass() {
        parent::setupBeforeClass();
        /** @var AddonManager */
        $theme = new Addon("/tests/fixtures/themes/asset-test");
        static::container()->get(AddonManager::class)->add($theme);
    }

    /**
     * Provide parameters for testing the validity of theme assets.
     *
     * @return array
     */
    public function provideAssetTypes(): array {
        $fixturesDir = PATH_ROOT . "/tests/fixtures";
        return [
            ["asset-test", "fonts.json", file_get_contents("{$fixturesDir}/themes/asset-test/assets/fonts.json"), "application/json"],
            ["asset-test", "footer.html", file_get_contents("{$fixturesDir}/themes/asset-test/assets/footer.html"), "text/html"],
            ["asset-test", "header.html", file_get_contents("{$fixturesDir}/themes/asset-test/assets/header.html"), "text/html"],
            ["asset-test", "javascript.js", file_get_contents("{$fixturesDir}/themes/asset-test/assets/javascript.js"), "application/javascript"],
            ["asset-test", "scripts.json", file_get_contents("{$fixturesDir}/themes/asset-test/assets/scripts.json"), "application/json"],
            ["asset-test", "styles.css", file_get_contents("{$fixturesDir}/themes/asset-test/assets/styles.css"), "text/css"],
            ["asset-test", "variables.json", file_get_contents("{$fixturesDir}/themes/asset-test/assets/variables.json"), "application/json"],
        ];
    }

    /**
     * Verify ability to grab individual theme assets with the proper content type.
     *
     * @param string $theme
     * @param string $assetKey
     * @param string $rawBody
     * @param string $contentType
     * @dataProvider provideAssetTypes
     */
    public function testGetAsset(string $theme, string $assetKey, string $rawBody, string $contentType) {
        $response = $this->api()->get("themes/{$theme}/assets/{$assetKey}");
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($contentType, $response->getHeader("Content-Type"));
        $this->assertEquals($rawBody, $response->getRawBody());
    }

    /**
     * Test getting a theme by its name.
     */
    public function testGetByName() {
        $response = $this->api()->get("themes/asset-test");

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringStartsWith("application/json", $response->getHeader("Content-Type"));

        $body = json_decode($response->getRawBody(), true);
        $this->assertEquals("asset-test", $body["themeID"]);
        $this->assertEquals("themeFile", $body["type"]);
        $this->assertNotEmpty($body["assets"]);

        $expectedAssets = ["fonts", "footer", "header", "javascript", "scripts", "styles", "variables"];
        foreach ($expectedAssets as $asset) {
            $this->assertArrayHasKey($asset, $body["assets"], "Theme does not have expected asset: {$asset}");
        }
    }
}
