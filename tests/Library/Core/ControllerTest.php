<?php
/**
 * @author Eric Vachaviolos <eric.v@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;
use Gdn_Controller;
use stdClass;

class ControllerTest extends SharedBootstrapTestCase {

    /**
     * Testing that the same key will be used to set data and to get it back.
     *
     * @dataProvider getTestDataForSetData
     */
    public function testSetDataAndDataUseTheSameKeys($key, $value) {
        $controller = new Gdn_Controller();
        $controller->setData($key, $value);

        $keys = is_array($key) ? $key : [$key => $value];
        foreach ($keys as $key => $value) {
            $this->assertEquals($value, $controller->data($key));
        }
    }

    /**
     * Testing that using method setData results in the expected Data array
     *
     * @dataProvider getTestDataForSetData
     */
    public function testCanAddValuesToDataArrayUsingSetData($key, $value, $expectedDataArray) {
        $controller = new Gdn_Controller();
        $controller->setData($key, $value);

        // Aserts the Data array match perfectly
        $this->assertEquals($expectedDataArray, $controller->Data);
    }

    /**
     * Testing that method setData can be used to add properties to the controller instance
     *
     * @dataProvider getTestDataForSetData
     */
    public function testCanAddPropertiesUsingSetData($key, $value, $expectedDataArray) {
        $controller = new Gdn_Controller();
        $controller->setData($key, $value, true);

        // Aserts the Data array match perfectly
        $this->assertEquals($expectedDataArray, $controller->Data, 'Data array doesn\'t match');

        // Asserts the properties one by one
        $keys = is_array($key) ? $key : [$key => $value];
        foreach ($keys as $key => $value) {
            $this->assertEquals($value, valr($key, $controller), 'Controller property doesn\'t match');
        }
    }

    /**
     * Testing that using method setData won't affect previously set keys
     */
    public function testSetDataDoesntTouchTheOtherKeys() {
        $controller = new Gdn_Controller();
        $controller->setData('precious', 'value');
        $this->assertEquals('value', $controller->data('precious'));

        $controller->setData('newKey', 'newValue');
        $this->assertEquals(['precious' => 'value', 'newKey' => 'newValue'], $controller->Data);
    }

    /**
     * Provides test data to use in method setData.
     *
     * @return array
     */
    public function getTestDataForSetData() {
        return [
            // $key,             $value,                            $expectedDataArray
            ['app',             'Vanilla',                          ['app' => 'Vanilla']],
            ['app name',        'Vanilla',                          ['app name' => 'Vanilla']],
            ['&^^^&*',          new stdClass(),                     ['&^^^&*' => new stdClass()]],
            ['test an array',   ['my' => 'value'],                  ['test an array' => ['my' => 'value']]],
            [['appName' => 'Vanilla', 'appVersion' => '2'], null,   ['appName' => 'Vanilla', 'appVersion' => '2']],

            // test $key with dot-notation
            ['app.name', 'Vanilla', ['app' => ['name' => 'Vanilla']]],

            // dot-notation inside an array of $keys
            [['app.name' => 'Vanilla', 'app.version' => '2'], null, ['app' => ['name' => 'Vanilla', 'version' => '2']]],
        ];
    }

}
