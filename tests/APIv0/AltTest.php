<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv0;

use Garden\Http\HttpClient;
use Garden\Http\HttpResponse;
use PDO;

/**
 * Tests an alternate install method.
 */
class AltTest extends \PHPUnit_Framework_TestCase {
    /** @var APIv0  $api */
    protected static $api;

    /**
     * Make sure there is a fresh copy of Vanilla for the class' tests.
     */
    public static function setUpBeforeClass() {
        $api = new APIv0();
        self::$api = $api;
    }

    /**
     * Test an alternate install method.
     *
     * @large
     */
    public function testAltInstall() {
        $this->api()->uninstall();
        $this->api()->createDatabase();

        $this->api()->saveToConfig($this->getBaseConfig());
        $this->api()->post('/utility/update');
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
                'Host' => 'localhost',
                'Name' => $api->getDbName(),
                'User' => $api->getDbUser(),
                'Password' => $api->getDbPassword(),
            ],
            'EnabledApplications' => [
                'Vanilla' => 'vanilla',
                'Conversations' => 'conversations',
            ],
            'EnabledPlugins' => [
                'HtmLawed' => true,
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
                    'Salt' => '',
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
