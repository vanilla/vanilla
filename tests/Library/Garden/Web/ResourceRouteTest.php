<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden\Web;

use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\RootHelpController;
use VanillaTests\Fixtures\ArticlesHelpController;
use Garden\Web\Action;
use Garden\Web\ResourceRoute;
use Garden\Web\Route;
use VanillaTests\Fixtures\AddonsController;
use VanillaTests\Fixtures\CommentsController;
use VanillaTests\Fixtures\DiscussionsController;
use VanillaTests\Fixtures\Request;

/**
 * Test the {@link ResourceRoute} class.
 */
class ResourceRouteTest extends TestCase {
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
        $request = new Request($path, $method, $method === 'GET' ? [] : ['!']);

        $match = $route->match($request);

        if ($expectedCall === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf(Action::class, $match, "The route was supposed to match and return an array.");
            $callback = $match->getCallback();
            $this->assertSame($expectedCall[0], get_class($callback[0]));
            $this->assertEquals(strtolower($expectedCall[1]), strtolower($callback[1]));
            $this->assertEquals((array)$expectedArgs, $match->getArgs());
        }
    }

    /**
     * Provide test data for {@link testKnownRoutes()}.
     *
     * @return array Returns test data.
     */
    public function provideKnownRoutes() {
        $ac = AddonsController::class;
        $dc = DiscussionsController::class;
        $cc = CommentsController::class;

        $r = [
            'index' => ['GET', '/discussions', [$dc, 'index'], ['page' => '']],
            'index w page' => ['GET', '/discussions/p1', [$dc, 'index'], ['page' => 'p1']],
            'index with bad page' => ['GET', '/discussions/xxx', null],

            'get' => ['GET', '/discussions/123', [$dc, 'get'], ['id' => '123']],

            'get recent' => ['GET', '/discussions/recent?after=1', [$dc, 'get_recent'], ['query' => ['after' => '1']]],
            'get recent too long' => ['GET', '/discussions/recent/1', null],
            'get bookmarked' => ['GET', '/discussions/bookmarked', [$dc, 'get_bookmarked'], ['page' => '']],

            'get string id' => ['GET', '/addons/editor', [$ac, 'get'], ['id' => 'editor']],
            'patch string id' => ['PATCH', '/addons/editor', [$ac, 'patch'], ['id' => 'editor']],
            'delete string id' => ['DELETE', '/addons/editor', [$ac, 'delete'], ['id' => 'editor']],

            'map body' => ['POST', '/discussions', [$dc, 'post'], ['body' => ['!']]],
            'map data' => ['PATCH', '/discussions/1', [$dc, 'patch'], ['id' => '1', 'data' => ['id' => '1', 0 => '!']]],
            'post and patch' => ['POST', '/discussions/1', [$dc, 'patch'], ['id' => '1', 'data' => ['id' => '1', 0 => '!']]],

            'no mapping' => ['POST', '/discussions/no-map/a/b/c?f=b', [$dc, 'post_noMap'], ['query' => 'a', 'body' => 'b', 'data' => 'c']],

            // Nested get and index
            'index /sub' => ['GET', '/discussions/sub', [$dc, 'index_sub'], []],
            'get /sub/:arg' => ['GET', '/discussions/sub/abc', [$dc, 'get_sub'], ['arg' => 'abc']],
            'index /:id/idsub' => ['GET', '/discussions/123/idsub', [$dc, 'index_idsub'], ['id' => '123']],
            'get /:id/idsub/:id2' => ['GET', '/discussions/123/idsub/abc', [$dc, 'get_idsub'], ['id' => '123', 'id2' => 'abc']],

            // Integer type hints.
            'index comments' => ['GET', '/comments/p1', [$cc, 'index'], ['param' => 'p1']],
            'get comments/:id' => ['GET', '/comments/1', [$cc, 'get'], ['id' => '1']],
            'get comments/archives' => ['GET', '/comments/archives', [$cc, 'index_archives']],
            'get comments/:id/archives' => ['GET', '/comments/1/archives', [$cc, 'get_archives'], ['id' => '1']],

            // Special routes are special.
            'bad index' => ['GET', '/discussions/index', null],
            'bad get' => ['GET', '/discussions/get/123', null],
            'bad post' => ['PATCH', '/discussions/post', null],

            // File extensions.
            'foo.js' => ['GET', '/discussions/123/foo.js', [$dc, 'get_foo_js'], ['id' => '123']],
            'foos.js' => ['GET', '/discussions/foos.js', [$dc, 'index_foos_js'], []],
        ];

        return $r;
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
        $this->assertSame($request, $match->getArgs()['request']);
    }

    /**
     * A controller method with a type hint to the concrete request object should inject that request.
     */
    public function testRequestInjectionConcrete() {
        $route = $this->createRoute();
        $request = new Request('/discussions/search', 'POST');

        $match = $route->match($request);
        $this->assertNotNull($match);
        $this->assertSame($request, $match->getArgs()['request']);
    }

    /**
     * A controller method with a type hint that matches the controller should be injected for the controller.
     */
    public function testControllerInjection() {
        $route = $this->createRoute();
        $request = new Request('/discussions/me/bar');

        $match = $route->match($request);
        $this->assertNotNull($match);
        $this->assertInstanceOf(DiscussionsController::class, $match->getArgs()['sender']);
        $this->assertSame('bar', $match->getArgs()['foo']);
    }

    /**
     * A variadic controller method should capture the remaining path into the variadic parameter.
     */
    public function testVariadic() {
        $route = $this->createRoute();
        $request = new Request('/discussions/123/help/foo/bar/baz');

        $match = $route->match($request);
        $this->assertSame(['foo', 'bar', 'baz'], $match());
    }

    /**
     * Getters and setters should not match routes.
     */
    public function testGettersSetters() {
        $route = $this->createRoute();

        $this->assertNull($route->match(new Request('/discussions/getsomething')));
        $this->assertNull($route->match(new Request('/discussions/setsomething/123')));
        $this->assertNull($route->match(new Request('/discussions/ispublic/foo')));
    }

    /**
     * Make sure that the index method doesn't act like another HTTP method.
     */
    public function testIndexProtection() {
        $route = $this->createRoute();
        $this->assertNull($route->match(new Request('/discussions/index_foo')));
    }

    /**
     * A path parameter should be filled with the remaining path.
     *
     * @param string $path A request path.
     * @param array $expectedArgs The expected arguments after the mapping.
     * @dataProvider providePathMappingTests
     */
    public function testPathMapping($path, array $expectedArgs) {
        $request = new Request($path);

        $route = $this->createRoute();
        $action = $route->match($request);
        $this->assertNotNull($action);
        $this->assertEquals($expectedArgs, $action->getArgs());
    }

    /**
     * Provide path mapping tests.
     *
     * @return array Returns a data provider.
     */
    public function providePathMappingTests() {
        $r = [
            ['/discussions/path1/a/b/c', ['path' => '/a/b/c']],
            ['/discussions/path2/a/b/c', ['a' => 'a', 'path' => '/b/c']],
            ['/discussions/path3/a/b/c', ['path' => '/a/b', 'b' => 'c']],
            ['/discussions/path4/a/b/c', ['a' => 'a', 'path' => '/b', 'b' => 'c']],

            'path constraint' => ['/discussions/article/a/p1', ['path' => '/a', 'page' => 'p1']],
            'path constraint capture 1' => ['/discussions/article/a/b', ['path' => '/a/b', 'page' => '']],
            'path constraint capture 2' => ['/discussions/article/a/b/c', ['path' => '/a/b/c', 'page' => '']],
        ];
        return $r;
    }

    /**
     * A path mapping hinted as an array should get the path parts as an array.
     */
    public function testPathMappingArray() {
        $route = $this->createRoute();
        $route->setMapping('body', Route::MAP_PATH);

        $request = new Request('/discussions/a/b/c', 'POST');
        $a = $route->match($request);
        $this->assertEquals(['a', 'b', 'c'], $a->getArgs()['body']);
    }

    /**
     * A constraint's path placement should force it's location in the path.
     */
    public function testConstraintPosition() {
        $route = $this->createRoute();
        $request = new Request('/discussions/help/1/a/b/c');

        $a = $route->match($request);
        $this->assertNull($a);
    }

    /**
     * Test that correct casing on method names is enforced.
     */
    public function testMethodCaseSensitivity() {
        $this->expectNotice();
//        post_noMap($query, $body, $data)
        $route = $this->createRoute();
        $request = new Request('/discussions/nomap/1/a/b/c', 'POST');

        $a = $route->match($request);
        $this->assertNull($a);
    }

    /**
     * Test allowing resource routes to specify a root controller.
     *
     * @param string $method The HTTP method of the request.
     * @param string $path The path to test.
     * @param array|null $expectedCall The expected callback signature in the form `[className, methodName]`.
     * @param array $expectedArgs The expected arguments.
     *
     * @dataProvider provideRootControllerTests
     */
    public function testRootController(string $method, string $path, $expectedCall, $expectedArgs = []) {
        $route = new ResourceRoute(
            '/help/',
            '\\VanillaTests\\Fixtures\\%sHelpController'
        );
        $route->setRootController(RootHelpController::class);

        $request = new Request($path, $method, $method === 'GET' ? [] : ['!']);

        $match = $route->match($request);

        if ($expectedCall === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf(Action::class, $match, "The route was supposed to match and return an array.");
            $callback = $match->getCallback();
            $this->assertSame($expectedCall[0], get_class($callback[0]));
            $this->assertEquals(strtolower($expectedCall[1]), strtolower($callback[1]));
            $this->assertEquals((array)$expectedArgs, $match->getArgs());
        }
    }

    /**
     * Provide path mapping tests.
     *
     * @return array Returns a data provider.
     */
    public function provideRootControllerTests() {
        $ac = ArticlesHelpController::class;
        $rc = RootHelpController::class;

        $r = [
            'non-root' => ['GET', '/help/articles/123', [$ac, 'get'], ['id' => 123]],
            'root-index' => ['GET', '/help', [$rc, 'index']],
            'root-get' => ['GET', '/help/something', [$rc, 'get'], ['code' => 'something']],
        ];

        return $r;
    }
}
