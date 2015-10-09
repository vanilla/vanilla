<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\API0;


abstract class BaseTest extends \PHPUnit_Framework_TestCase {
    /** @var API0  $api */
    protected $api;

    /**
     * Make sure there is a fresh copy of Vanilla for the class' tests.
     */
    public static function setUpBeforeClass() {
        $api = new API0();

        $api->uninstall();
        $api->install(get_called_class());
    }

    /**
     * Get the API to make requests against.
     *
     * @return API0 Returns the API.
     */
    public function api() {
        if (!isset($this->api)) {
            $this->api = new API0();
        }
        return $this->api;
    }
}
