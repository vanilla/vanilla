<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

/**
 * Tests for the /addons endpoints
 */
class AddonsTest extends AbstractAPIv2Test {
    private $coreAddons = [
        'conversations', // applications
        'allviewed', 'emojiextender', 'facebook', 'flagging',
        'googleprettify', 'gravatar', 'indexphotos', 'profileextender', 'quotes',
        'splitmerge', 'stopforumspam', 'twitter', 'vanillainthisdiscussion', 'vanillastats', 'editor', 'oauth2',
        'recaptcha', 'stubcontent', 'vanillicon', 'googlesignin'// plugins
    ];

    private $coreThemes = [
        'EmbedFriendly', 'bittersweet', 'default', 'mobile', 'keystone',  // themes
    ];

    private $hiddenAddons = [
        'dashboard', 'vanilla', 'gettingstarted', 'googleplus'
    ];

    /**
     * Test listing core addons.
     */
    public function testIndexCoreAddons() {
        $addons = $this->api()->get('/addons');
        $addons = array_column($addons->getBody(), null, 'key');

        $expected = array_merge($this->coreAddons, $this->coreThemes);

        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $addons);
        }

        foreach ($this->hiddenAddons as $key) {
            $this->assertArrayNotHasKey($key, $addons);
        }
    }

    /**
     * Test getting a few core addons.
     */
    public function testGet() {
        $addon = $this->api()->get('/addons/facebook');
        $this->assertEquals('facebook', $addon['key']);
        $this->assertFalse($addon['enabled']);

        $addon2 = $this->api()->get('/addons/conversations');
        $this->assertTrue($addon2['enabled']);
    }

    /**
     * Enable and disable a sample addon.
     */
    public function testEnableDisable() {
        $quotes = $this->api()->get('/addons/quotes');
        $this->assertFalse($quotes['enabled']);

        $enabled = $this->api()->patch('/addons/quotes', ['enabled' => true])->getBody();
        $this->assertEquals('quotes', $enabled[0]['addonID']);
        $this->assertTrue($enabled[0]['enabled']);

        $quotes2 = $this->api()->get('/addons/quotes');
        $this->assertTrue($quotes2['enabled']);

        $disabled = $this->api()->patch('/addons/quotes', ['enabled' => false])->getBody();
        $this->assertEquals('quotes', $disabled[0]['addonID']);
        $this->assertFalse($disabled[0]['enabled']);
    }

    /**
     * Hidden addons should appear to not exist.
     *
     * @param string $key The key of an addon that exists, but should be hidden.
     * @dataProvider provideHiddenAddons
     */
    public function testGetHidden($key) {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(404);

        $this->api()->get("/addons/$key");
    }

    /**
     * Hidden addons should appear to not exist.
     *
     * @param string $key The key of an addon that exists, but should be hidden.
     * @dataProvider provideHiddenAddons
     */
    public function testPatchHidden($key) {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(404);

        $this->api()->patch("/addons/$key", ['enabled' => false]);
    }

    /**
     * Test changing themes.
     */
    public function testChangeTheme() {
        $desktop = $this->api()->get('/addons', ['type' => 'theme', 'enabled' => true, 'themeType' => 'desktop'])[0];
        $this->assertEquals('keystone-theme', $desktop['addonID']);

        $mobile = $this->api()->get('/addons', ['type' => 'theme', 'enabled' => true, 'themeType' => 'mobile'])[0];
        $this->assertEquals('mobile-theme', $mobile['addonID']);

        // Set the desktop and mobile theme.
        $this->api()->patch('/addons/bittersweet-theme', ['enabled' => true, 'themeType' => 'desktop']);
        $this->api()->patch('/addons/default-theme', ['enabled' => true, 'themeType' => 'mobile']);

        $desktop = $this->api()->get('/addons', ['type' => 'theme', 'enabled' => true, 'themeType' => 'desktop'])[0];
        $this->assertEquals('bittersweet-theme', $desktop['addonID']);

        $mobile = $this->api()->get('/addons', ['type' => 'theme', 'enabled' => true, 'themeType' => 'mobile'])[0];
        $this->assertEquals('default-theme', $mobile['addonID']);
    }

    /**
     * Test enabling a plugin with the API, but disabling it with the plugin manager.
     */
    public function testAddonModelPluginManagerInterop() {
        // Enable via the API.
        $this->api()->patch('/addons/profileextender', ['enabled' => true]);
        $this->assertPluginEnabled('profileextender', true);

        // Disable via plugin manager.
        $pm = \Gdn::pluginManager();
        $pm->disablePlugin('profileextender');
        $this->assertPluginEnabled('profileextender', false);

        // Enable via plugin manager.
        $pm->enablePlugin('profileextender', new \Gdn_Validation());
        $this->assertPluginEnabled('profileextender', true);

        // Disable via API.
        $this->api()->patch('/addons/profileextender', ['enabled' => false]);
        $this->assertPluginEnabled('profileextender', false);
    }

    /**
     * Assert that a plugin is enabled/disabled in the config.
     * @param $pluginKey
     * @param $pluginEnabled
     */
    private function assertPluginEnabled($pluginKey, $pluginEnabled) {
        $plugins = $this->container()->get(\Gdn_Configuration::class)->get('EnabledPlugins');

        // Since this is a single request we can't reload the state from the config so must check it directly.
        foreach ($plugins as $key => $enabled) {
            if (strcasecmp($key, $pluginKey) === 0) {
                $this->assertSame($pluginEnabled, $enabled, "The plugin with key $key has the wrong enabled value.");
            }
        }
    }

    /**
     * Provide a list of hidden addons.
     *
     * @return array Returns a data provider.
     */
    public function provideHiddenAddons() {
        $r = array_map(function ($v) {
            return [$v];
        }, array_combine($this->hiddenAddons, $this->hiddenAddons));
        return $r;
    }
}
