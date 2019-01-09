<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;
use VanillaTests\Fixtures\FooBarController;
use VanillaTests\Fixtures\UnitTestGdnDispatcher;

class DispatcherTest extends SharedBootstrapTestCase {

    /**
     * Test **Gdn_Dispatcher::filterName()**.
     *
     * @param string $name The name to filter.
     * @param string $expected The expected name.
     * @dataProvider provideFilterNameTests
     */
    public function testFilterName(string $name, string $expected) {
        $dis = new UnitTestGdnDispatcher();
        $filtered = $dis->filterName($name);
        $this->assertEquals($expected, $filtered);
    }

    /**
     * Test **Gdn_Dispatcher::dashCase().
     *
     * @param string $name The name to convert.
     * @param string $expected The expected dash case.
     * @dataProvider provideDashCaseTests
     */
    public function testDashCase(string $name, string $expected) {
        $dis = new UnitTestGdnDispatcher();
        $dashed = $dis->dashCase($name);
        $this->assertEquals($expected, $dashed);
    }

    /**
     * Test **Gdn_Dispatcher::makeCanonicalUrl()**.
     *
     * @param object $controller The controller being dispatched.
     * @param string $method The controller method being dispatched.
     * @param array $args The args.
     * @param string $expected The expected canonical URL.
     * @dataProvider provideMakeCanonicalUrlTests
     */
    public function testMakeCanonicalUrl($controller, string $method, array $args, string $expected) {
        $reflectedMethod = new \ReflectionMethod($controller, $method);
        $reflectedArgs = reflectArgs($reflectedMethod, $args);

        $dis = new UnitTestGdnDispatcher();
        $url = $dis->makeCanonicalUrl($controller, $reflectedMethod, $reflectedArgs);
        $this->assertEquals($expected, $url);
    }

    /**
     * Provide test data for **testMakeCanonicalUrl**.
     *
     * @return array Returns a data provider array.
     */
    public function provideMakeCanonicalUrlTests(): array {
        $foo = new FooBarController();

        $r = [
            [$foo, 'index', ['page' => 'p1'], '/foo-bar'],
            [$foo, 'index', ['page' => 'p2'], '/foo-bar/p2'],
            [$foo, 'index', ['p2', 'baz', 'bam'], '/foo-bar/p2'],

            [$foo, 'search', ['foo', 'p1'], '/foo-bar/search?search=foo'],
            [$foo, 'search', ['search' => 'foo', 'page' => 'p2'], '/foo-bar/search?page=p2&search=foo'],

            [$foo, 'doit', ['9'], '/foo-bar/do-it'],

            [$foo, 'foobarcontroller_foobar_create', ['sender' => $foo], '/foo-bar/foo-bar'],
        ];

        $result = [];
        foreach ($r as $v) {
            $key = $v[3];
            $k2 = $key;
            $i = 1;
            while (isset($result[$k2])) {
                $k2 = "$key-$i";
                $i++;
            }
            $result[$k2] = $v;
        }

        return $result;
    }

    /***
     * Provide data for **testDashCase()**.
     *
     * @return array Returns a data provider array.
     */
    public function provideDashCaseTests(): array {
        $r = [
            ['DashCase', 'dash-case'],
            ['dashCase', 'dash-case'],
            ['OneTwoThree', 'one-two-three'],
            ['FooAPI', 'foo-api'],
            ['FooBar2', 'foo-bar2'],
        ];

        return array_column($r, null, 0);
    }

    /**
     * Provide test data for **testFilterName()**.
     *
     * @return array Returns a data provider array.
     */
    public function provideFilterNameTests(): array {
        $r = [
            ['discussions', 'Discussions'],
            ['addon-cache', 'AddonCache'],
            ['addoncache', 'Addoncache'],
        ];

        return array_column($r, null, 0);
    }
}
