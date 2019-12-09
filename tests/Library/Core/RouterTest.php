<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;

/**
 * Basic tests for `Gdn_Router`.
 */
class RouterTest extends SharedBootstrapTestCase {
    /**
     * @var \Gdn_Router
     */
    private $router;

    /**
     * @var callable
     */
    private $parseRoute;

    /**
     * Basic setup.
     */
    public function setUp(): void {
        parent::setUp();

        // New up a router without default routes to load.
        $this->runWithConfig(['Routes' => []], function () {
            $this->router = new \Gdn_Router();
        });

        $this->parseRoute = \Closure::bind(function ($destination) {
            /* @var \Gdn_Router $this */
            $r = $this->_parseRoute($destination);
            return $r;
        }, $this->router, $this->router);
    }

    /**
     * Test `Gdn_Router::_parseRoute()`.
     *
     * @param mixed $destination
     * @param array|mixed $expected
     * @dataProvider provideSavedRoutes
     */
    public function testParseRoute($destination, $expected): void {
        $parsed = $this->parseRoute($destination);
        $this->assertEquals($expected, $parsed);
    }

    /**
     * Provide some routes to parse.
     *
     * @return array
     */
    public function provideSavedRoutes(): array {
        $default = ['Destination' => 'discussions', 'Type' => 'Internal'];
        $raw = array_values($default);

        $r = [
            'default' => [$raw, $default],
            'serialized' => [serialize($raw), $default],
            'string' => [$default['Destination'], $default],
            'one element' => [[$default['Destination']], $default],
        ];

        return $r;
    }

    /**
     * Lift the private access of `Gdn_Router::_parseRoute()` for testing.
     *
     * @param mixed $destination
     * @return mixed
     */
    protected function parseRoute($destination) {
        return call_user_func($this->parseRoute, $destination);
    }
}
