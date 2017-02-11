<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Garden\Web;

use Garden\Web\ResourceRoute;
use VanillaTests\Fixtures\DiscussionsController;
use VanillaTests\Fixtures\Request;

/**
 * Test the {@link ResourceRoute} class.
 */
class ResourceRouteTest extends \PHPUnit_Framework_TestCase {
    /**
     * Create a new {@link ResourceRoute} initialized for testing with fixtures.
     */
    protected function createRoute() {
        return new ResourceRoute(
            '/',
            '\\VanillaTests\\Fixtures\\%sController'
        );
    }

    /**
     * Test some basic known routes.
     *
     * @param string $method The HTTP method of the request.
     * @param string $path The path to test.
     * @param array|null $expectedCall The expected callback signature in the form `[className, methodName]`.
     * @param array $expectedArgs The expected arguments.
     * @dataProvider provideKnownRoutes
     */
    public function testKnownRoutes($method, $path, $expectedCall, $expectedArgs = []) {
        $route = $this->createRoute();
        $request = new Request($path, $method);

        $matched = $route->match($request);

        if ($expectedCall === null) {
            $this->assertNull($matched);
        } else {
            $this->assertTrue(is_array($matched), "The route was supposed to match and return an array.");
            $this->assertArrayHasKey('callback', $matched);
            $this->assertArrayHasKey('args', $matched);
            $callback = $matched['callback'];
            $this->assertSame($expectedCall[0], get_class($callback[0]));
            $this->assertEquals(strtolower($expectedCall[1]), strtolower($callback[1]));
            $this->assertEquals((array)$expectedArgs, $matched['args']);
        }
    }

    /**
     * Matching to a non-existent controller should return null.
     */
    public function testNoController() {
        $route = $this->createRoute();
        $request = new Request('/123nonsense');

        $match = $route->match($request);
        $this->assertNull($match);
    }

    /**
     * A controller method with a {@link \Garden\RequestInterface} type hint should inject the request.
     */
    public function testRequestInjection() {
        $route = $this->createRoute();
        $request = new Request('/discussions/search');

        $match = $route->match($request);
        $this->assertNotNull($match);
        $this->assertArrayHasKey('args', $match);
        $this->assertSame($request, $match['args']['request']);
    }

    /**
     * A controller method with a type hint to the concrete request object should inject that request.
     */
    public function testRequestInjectionConcrete() {
        $route = $this->createRoute();
        $request = new Request('/discussions/search', 'POST');

        $match = $route->match($request);
        $this->assertNotNull($match);
        $this->assertArrayHasKey('args', $match);
        $this->assertSame($request, $match['args']['request']);
    }

    /**
     * A controller method with a type hint that matches the controller should be injected for the controller.
     */
    public function testControllerInjection() {
        $route = $this->createRoute();
        $request = new Request('/discussions/me/bar');

        $match = $route->match($request);
        $this->assertNotNull($match);
        $this->assertArrayHasKey('args', $match);
        $this->isInstanceOf(DiscussionsController::class, $match['args']['sender']);
        $this->assertSame('bar', $match['args']['foo']);
    }

    /**
     * A variadic controller method should capture the remaining path into the variadic parameter.
     */
    public function testVariadic() {
        $route = $this->createRoute();
        $request = new Request('/discussions/123/help/foo/bar/baz');

        $match = $route->match($request);
        $this->assertSame(['foo', 'bar', 'baz'], $match['args']['parts']);

    }

    /**
     * Provide test data for {@link testKnownRoutes()}.
     *
     * @return array Returns test data.
     */
    public function provideKnownRoutes() {
        $dc = DiscussionsController::class;

        $r = [
            'index' => ['GET', '/discussions', [$dc, 'index'], ['page' => '']],
            'index w page' => ['GET', '/discussions/p1', [$dc, 'index'], ['page' => 'p1']],
            'index with bad page' => ['GET', '/discussions/xxx', null],

            'get' => ['GET', '/discussions/123', [$dc, 'get'], ['id' => '123']],

            'get recent' => ['GET', '/discussions/recent?after=1', [$dc, 'get_recent'], ['query' => ['after' => '1']]],
            'get recent too long' => ['GET', '/discussions/recent/1', null],

            'get bookmarked' => ['GET', '/discussions/bookmarked', [$dc, 'get_bookmarked'], ['page' => '']],

            // Special routes are special.
            'bad index' => ['GET', '/discussions/index', null],
            'bad get' => ['GET', '/discussions/get/123', null],
            'bad post' => ['PATCH', '/discussions/post', null]
        ];

        return $r;
    }
}
