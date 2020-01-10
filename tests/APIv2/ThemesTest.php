<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Gdn_Configuration;
use Gdn_Request;
use Gdn_Upload;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Garden\Container\Reference;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Models\FsThemeProvider;
use Garden\Web\Exception\ClientException;
use Vanilla\Models\ThemeModel;

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
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();

        $root = '/tests/fixtures';
        $addonManager = new AddonManager(
            [
                Addon::TYPE_ADDON => [
                    "$root/addons", "$root/applications", "$root/plugins"
                ],
                Addon::TYPE_THEME => "$root/themes",
                Addon::TYPE_LOCALE => "$root/locales"
            ],
            PATH_ROOT.'/tests/cache/am/test-manager'
        );

        $request = self::container()->get(Gdn_Request::class);
        $config = self::container()->get(Gdn_Configuration::class);

        static::container()
            ->rule(FsThemeProvider::class)
            ->setConstructorArgs(
                [
                    $addonManager,
                    $request,
                    $config
                ]
            );

        /** @var AddonManager */
        $theme = new Addon("/tests/fixtures/themes/asset-test");

        static::container()
            ->get(AddonManager::class)
            ->add($theme);
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

    /**
     * Test POSTing a theme new name. Should fail since there is no dynamic theme provider.
     */
    public function testPostTheme() {
        $this->expectException(ClientException::class);
        $response = $this->api()->post("themes", ['name'=>'custom theme']);
    }

    /**
     * Test PATCHing a theme. Should fail since there is no dynamic theme provider.
     */
    public function testPatchTheme() {
        $this->expectException(ClientException::class);
        $response = $this->api()->post("themes", ['name'=>'custom theme']);
    }

    /**
     * Test getting a theme's logo.
     *
     * @depends testGetByName
     */
    public function testLogo() {
        $logo = "logo.png";
        self::container()->get(Gdn_Configuration::class)->set("Garden.Logo", $logo);

        $response = $this->api()->get("themes/asset-test");
        $body = json_decode($response->getRawBody(), true);
        $this->assertEquals($body["assets"]["logo"]["url"], Gdn_Upload::url($logo));
    }

    /**
     * Test getting a theme's mobile logo.
     *
     * @depends testGetByName
     */
    public function testMobileLogo() {
        $mobileLogo = "mobileLogo.png";
        self::container()->get(Gdn_Configuration::class)->set("Garden.MobileLogo", $mobileLogo);

        $response = $this->api()->get("themes/asset-test");
        $body = json_decode($response->getRawBody(), true);
        $this->assertEquals($body["assets"]["mobileLogo"]["url"], Gdn_Upload::url($mobileLogo));
    }

    /**
     * Test /themes endpoint returns all available themes.
     *
     * Note: If "hidden" variable isn't explicitly declared false
     * and "sites" or "Garden.Themes.Visible" are set then a theme
     * will not be available.
     *
     */
    public function testIndex() {
        $response = $this->api()->get("themes");
        $body = $response->getBody();
        $this->assertEquals(2, count($body));
    }

    /**
     * Test /themes/current endpoint returns active theme (keystone).
     */
    public function testCurrent() {
        $response = $this->api()->get("themes/current");
        $body = $response->getBody();
        $this->assertEquals('keystone', $body['themeID']);
    }

    /**
     * Test getThemeViewPath method of ThemeModel.
     */
    public function testGetThemeViewPath() {
        /** @var ThemeModel $themeModel */
        $themeModel = self::container()->get(ThemeModel::class);
        $viewPath = $themeModel->getThemeViewPath('keystone');
        $this->assertStringEndsWith('/themes/keystone/views/', $viewPath);
    }
}
