<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv0;

use VanillaTests\SharedBootstrapTestCase;

/**
 * Tests an alternate install method.
 */
class AltTest extends SharedBootstrapTestCase {
    /** @var APIv0  $api */
    protected static $api;

    /**
     * Make sure there is a fresh copy of Vanilla for the class' tests.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        $api = new APIv0();
        self::$api = $api;
    }

    /**
     * Test an alternate install method.
     *
     * @large
     */
    public function testAltInstall() {
        $this->doAltInstallWithUpdateToken(false, 'xkcd', '');
    }

    /**
     * Test an ALT install with a valid update token.
     */
    public function testAltInstallWithUpdateToken() {
        $this->doAltInstallWithUpdateToken(true, 'xkcd', 'xkcd');
    }

    /**
     * Test an ALT install with no update token.
     */
    public function testAltInstallWithNoUpdateToken() {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);
        $this->doAltInstallWithUpdateToken(true, 'xkcd', '');
    }

    /**
     * Run an alt install with optional update tokens.
     *
     * @param bool $enabled Whether the update token feature should be enabled.
     * @param string $updateToken The update token to use.
     * @param string $postUpdateToken The update token to post during `utility/update`.
     */
    private function doAltInstallWithUpdateToken(bool $enabled, string $updateToken, string $postUpdateToken) {
        $this->api()->uninstall();
        $this->api()->createDatabase();

        $config = $this->getBaseConfig();
        $config['Feature']['updateTokens']['Enabled'] = $enabled;
        $config['Garden']['UpdateToken'] = $updateToken;

        $this->api()->saveToConfig($config);
        $r = $this->api()->post('/utility/update.json', ['updateToken' => $postUpdateToken])->getBody();
        $this->api()->saveToConfig(['Garden.Installed' => true]);

        // Do a simple get to make sure there isn't an error.
        $r = $this->api()->get('/discussions.json');
        $data = $r->getBody();
        $this->assertNotEmpty($data['Discussions']);
    }

    /**
     * Get the config to be applied to the site before update.
     */
    private function getBaseConfig() {
        $api = $this->api();

        $config = [
            'Database' => [
                'Host' => $api->getDbHost(),
                'Name' => $api->getDbName(),
                'User' => $api->getDbUser(),
                'Password' => $api->getDbPassword(),
            ],
            'EnabledApplications' => [
                'Vanilla' => 'vanilla',
                'Conversations' => 'conversations',
            ],
            'EnabledPlugins' => [
                'vanillicon' => true,
                'Facebook' => true,
                'Twitter' => true,
                'Akismet' => true,
                'StopForumSpam' => true,
            ],
            'Garden' => [
                'Installed' => null, // Important to bypass the redirect to /dashboard/setup. False would not do here.
                'Title' => get_called_class(),
                'Domain' => parse_url($api->getBaseUrl(), PHP_URL_HOST),
                'Cookie' => [
                    'Salt' => 'salt',
                    'Name' => 'vf_'.strtolower(get_called_class()).'_ENDTX',
                    'Domain' => '',
                ],
                'Email' => [
                    'SupportAddress' => 'noreply@vanilla.test',
                    'SupportName' => get_called_class()
                ],
            ]
        ];

        return $config;
    }

    /**
     * Get the API to make requests against.
     *
     * @return APIv0 Returns the API.
     */
    public function api() {
        return self::$api;
    }
}
