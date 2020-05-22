<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\AddonManager;
use Vanilla\Theme\ThemeService;
use Vanilla\Theme\ThemeProviderInterface;
use VanillaTests\Fixtures\MockAddonManager;
use VanillaTests\Fixtures\MockThemeProvider;

class ThemeAssetsGetPutTest extends AbstractAPIv2Test {

    protected static $addons = ['vanilla', 'dashboard', 'conversations', 'stubcontent'];

    /** @var MockThemeProvider */
    protected static $mockThemeProvider;

    /**
     * Prepare the container.
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        self::$mockThemeProvider = self::container()->get(MockThemeProvider::class);
        self::$mockThemeProvider->setThemeKeyType(ThemeProviderInterface::TYPE_DB);

        self::container()
            ->rule(ThemeService::class)
            ->setShared(true)
            ->addCall('addThemeProvider', [self::$mockThemeProvider])
        ;
    }

    public function testGetPut() {
        $theme = $this->createTheme();
        $themeID = $theme['themeID'];

        $inOut = [
            'hello' => 'world',
        ];

        $this->api()->put("/themes/$themeID/assets/variables.json", $inOut);

        $response = $this->api()->get("/themes/$themeID/assets/variables.json")->getBody();
        $this->assertEquals($inOut, $response);
    }

    private function createTheme() {
        return $this->api()->post("/themes", [
            'name' => 'test theme',
            'parentTheme' => 'theme-foundation',
            'parentVersion' => '1.0.0',
        ])->getBody();
    }
}

