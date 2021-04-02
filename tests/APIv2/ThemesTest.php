<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Gdn_Configuration;
use Gdn_Upload;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Garden\Web\Exception\ClientException;
use Vanilla\Http\InternalClient;
use Vanilla\Web\Asset\DeploymentCacheBuster;

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
            ["asset-test", "fonts.json", trim(file_get_contents("{$fixturesDir}/themes/asset-test/assets/fonts.json")), "application/json"],
            ["asset-test", "footer.html", file_get_contents("{$fixturesDir}/themes/asset-test/assets/footer.html"), "text/html"],
            ["asset-test", "header.html", file_get_contents("{$fixturesDir}/themes/asset-test/assets/header.html"), "text/html"],
            ["asset-test", "javascript.js", file_get_contents("{$fixturesDir}/themes/asset-test/assets/javascript.js"), "application/javascript"],
            ["asset-test", "scripts.json", trim(file_get_contents("{$fixturesDir}/themes/asset-test/assets/scripts.json")), "application/json"],
            ["asset-test", "styles.css", file_get_contents("{$fixturesDir}/themes/asset-test/assets/styles.css"), "text/css"],
            ["asset-test", "variables.json", trim(file_get_contents("{$fixturesDir}/themes/asset-test/assets/variables.json")), "application/json"],
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
     * Provide parameters for testing the validity of theme assets.
     *
     * @return array
     */
    public function provideAssetTypesNoExt(): array {
        $assetRoot = PATH_ROOT . "/tests/fixtures/themes/asset-test/assets";
        return [
            'fonts' => [
                "asset-test",
                "fonts",
                [
                    'type' => 'json',
                    'data' => json_decode(file_get_contents("$assetRoot/fonts.json"), true),
                    'content-type' => 'application/json',
                ],
            ],
            'variables' => [
                "asset-test",
                "variables",
                [
                    'type' => 'json',
                    'data' => json_decode(file_get_contents("$assetRoot/variables.json"), true),
                    'content-type' => 'application/json',
                ],
            ],
            'scripts' => [
                "asset-test",
                "scripts",
                [
                    'type' => 'json',
                    'data' => json_decode(file_get_contents("$assetRoot/scripts.json"), true),
                    'content-type' => 'application/json',
                ],
            ],
            'header' => [
                "asset-test",
                "header",
                [
                    'type' => 'html',
                    'data' => file_get_contents("$assetRoot/header.html"),
                    'content-type' => 'text/html',
                ],
            ],
            'footer' => [
                "asset-test",
                "footer",
                [
                    'type' => 'html',
                    'data' => file_get_contents("$assetRoot/footer.html"),
                    'content-type' => 'text/html',
                ],
            ],
            'javascript' => [
                "asset-test",
                "javascript",
                [
                    'type' => 'js',
                    'data' => file_get_contents("$assetRoot/javascript.js"),
                    'content-type' => 'application/javascript',
                ],
            ],
            'styles' => [
                "asset-test",
                "styles",
                [
                    'type' => 'css',
                    'data' => file_get_contents("$assetRoot/styles.css"),
                    'content-type' => 'text/css',
                ],
            ],
        ];
    }

    /**
     * Verify ability to grab individual theme assets with the proper content type.
     *
     * @param string $theme
     * @param string $assetKey
     * @param array $expected
     * @dataProvider provideAssetTypesNoExt
     */
    public function testGetAssetNotExt(string $theme, string $assetKey, array $expected) {
        $response = $this->api()->get("themes/{$theme}/assets/{$assetKey}");
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json; charset=utf-8", $response->getHeader("Content-Type"));
        $body = $response->getBody();

        // There's a URL here. We're not going to be testing it.
        $this->assertTrue(isset($body['url']));
        unset($body['url']);

        $this->assertEquals($expected, $body);
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
        $cacheBuster = self::container()->get(DeploymentCacheBuster::class)->value();
        $logo = "logo.png";
        self::container()->get(Gdn_Configuration::class)->set("Garden.Logo", $logo);

        $response = $this->api()->get("themes/asset-test");
        $body = json_decode($response->getRawBody(), true);
        $this->assertEquals($body["assets"]["logo"]["url"], Gdn_Upload::url($logo) . "?v=$cacheBuster");
    }

    /**
     * Test getting a theme's mobile logo.
     *
     * @depends testGetByName
     */
    public function testMobileLogo() {
        $cacheBuster = self::container()->get(DeploymentCacheBuster::class)->value();
        $mobileLogo = "mobileLogo.png";
        self::container()->get(Gdn_Configuration::class)->set("Garden.MobileLogo", $mobileLogo);

        $response = $this->api()->get("themes/asset-test");
        $body = json_decode($response->getRawBody(), true);
        $this->assertEquals($body["assets"]["mobileLogo"]["url"], Gdn_Upload::url($mobileLogo) . "?v=$cacheBuster");
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
        $this->api()->setUserID(\UserModel::GUEST_USER_ID);
        $response = $this->api()->get("themes");
        $body = $response->getBody();
        $this->assertEquals(2, count($body), 'The 2 unhidden themes, keystone and foundation are returned.');
    }

    /**
     * Test /themes/current endpoint returns active theme (keystone).
     */
    public function testGetCurrent() {
        $response = $this->api()->get("themes/current");
        $body = $response->getBody();
        $this->assertEquals('theme-foundation', $body['themeID']);
    }

    /**
     * Test the theme preview endpoint.
     */
    public function testThemePreview() {
        $response = $this->api()->put('/themes/preview', ['themeID' => 'keystone']);
        $this->assertEquals(200, $response->getStatusCode());

        // Make sure we didn't write to the config.
        $this->assertNotEquals('keystone', \Gdn::config('Garden.Theme'));
        $body = $this->api()->get('/themes/current')->getBody();
        $this->assertEquals('keystone', $body['themeID']);

        // Make sure other users don't see it.
        $this->api()->setUserID(0);
        $body = $this->api()->get('/themes/current')->getBody();
        $this->assertNotEquals('keystone', $body['themeID']);
        $this->api()->setUserID(InternalClient::DEFAULT_USER_ID);

        // Clear the preview
        $response = $this->api()->put('/themes/preview', ['themeID' => null]);
        $body = $this->api()->get('/themes/current')->getBody();
        $this->assertNotEquals('keystone', $body['themeID']);
    }


    /**
     * Test the theme preview endpoint.
     */
    public function testPutCurrent() {
        // Make sure we don't start on keystone.
        $this->assertNotEquals('keystone', \Gdn::config('Garden.Theme'));
        $body = $this->api()->get('/themes/current')->getBody();
        $this->assertNotEquals('keystone', $body['themeID']);

        // Set the current theme.
        $response = $this->api()->put('/themes/current', ['themeID' => 'keystone']);
        $this->assertEquals(200, $response->getStatusCode());

        // The theme is set.
        $this->assertEquals('keystone', \Gdn::config('Garden.Theme'));
        $this->assertEquals('keystone', \Gdn::config('Garden.MobileTheme'));
        $this->assertEquals('keystone', \Gdn::config('Garden.CurrentTheme'));
        $body = $this->api()->get('/themes/current')->getBody();
        $this->assertEquals('keystone', $body['themeID']);
    }
}
