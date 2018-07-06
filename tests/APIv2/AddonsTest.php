<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

/**
 * Tests for the /addons endpoints
 */
class AddonsTest extends AbstractAPIv2Test {
    private $coreAddons = [
        'conversations', // applications
        'allviewed', 'buttonbar', 'debugger', 'emojiextender', 'facebook', 'flagging',
        'googleplus', 'googleprettify', 'gravatar', 'indexphotos', 'openid', 'profileextender', 'quotes',
        'splitmerge', 'stopforumspam', 'twitter', 'vanillainthisdiscussion', 'vanillastats', 'editor', 'oauth2',
        'recaptcha', 'stubcontent', 'vanillicon', // plugins
    ];

    private $coreThemes = [
        'EmbedFriendly', 'bittersweet', 'default', 'mobile', // themes
    ];

    private $hiddenAddons = [
        'dashboard', 'vanilla', 'gettingstarted'
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
     * @expectedException \Exception
     * @expectedExceptionCode 404
     */
    public function testGetHidden($key) {
        $this->api()->get("/addons/$key");
    }

    /**
     * Hidden addons should appear to not exist.
     *
     * @param string $key The key of an addon that exists, but should be hidden.
     * @dataProvider provideHiddenAddons
     * @expectedException \Exception
     * @expectedExceptionCode 404
     */
    public function testPatchHidden($key) {
        $this->api()->patch("/addons/$key", ['enabled' => false]);
    }

    /**
     * Test changing themes.
     */
    public function testChangeTheme() {
        $desktop = $this->api()->get('/addons', ['type' => 'theme', 'enabled' => true, 'themeType' => 'desktop'])[0];
        $this->assertEquals('default-theme', $desktop['addonID']);

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
        $this->api()->patch('/addons/buttonbar', ['enabled' => true]);
        $this->assertPluginEnabled('buttonbar', true);

        // Disable via plugin manager.
        $pm = \Gdn::pluginManager();
        $pm->disablePlugin('buttonbar');
        $this->assertPluginEnabled('buttonbar', false);

        // Enable via plugin manager.
        $pm->enablePlugin('buttonbar', new \Gdn_Validation());
        $this->assertPluginEnabled('buttonbar', true);

        // Disable via API.
        $this->api()->patch('/addons/buttonbar', ['enabled' => false]);
        $this->assertPluginEnabled('buttonbar', false);
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
     * You shouldn't be able to enable two conflicting plugins at the same time.
     *
     * @expectedException \Exception
     * @expectedExceptionCode 409
     * @expectedExceptionMessage Advanced Editor conflicts with: Button Bar.
     */
    public function testConflictingAddons() {
        $this->api()->patch('/addons/buttonbar', ['enabled' => true]);
        $this->api()->patch('/addons/editor', ['enabled' => true]);
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
