<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv0;


use Garden\Http\HttpResponse;
use VanillaTests\SharedBootstrapTestCase;

abstract class BaseTest extends SharedBootstrapTestCase {
    /** @var APIv0  $api */
    protected static $api;

    /**
     * Make sure there is a fresh copy of Vanilla for the class' tests.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        $api = new APIv0();

        $api->uninstall();
        $api->install(get_called_class());
        self::$api = $api;

        self::$api->saveToConfig([
            'Vanilla.Discussion.SpamCount' => 100,
        ]);

        $r = $api->get('/discussions.json');
        $data = $r->getBody();
        if (empty($data['Discussions'])) {
            throw new \Exception("The discussion stub content is missing.", 500);
        }
    }

    public static function tearDownAfterClass(): void {
        self::$api->uninstall();
        self::$api->terminate();
        parent::tearDownAfterClass();
    }

    /**
     * Get the API to make requests against.
     *
     * @return APIv0 Returns the API.
     */
    public function api() {
        return self::$api;
    }

    /**
     * Assert that the API returned a successful response.
     *
     * @param HttpResponse $response The response to test.
     */
    public function assertResponseSuccess(HttpResponse $response) {
        $this->assertNotNull($response);
        $this->assertTrue($response->isSuccessful());
    }
}
