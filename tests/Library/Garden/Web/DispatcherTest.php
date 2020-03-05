<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden\Web;

use Garden\Web\Data;
use Garden\Web\Dispatcher;
use Garden\Web\Exception\ClientException;
use Garden\Web\RequestInterface;
use Garden\Web\ResourceRoute;
use VanillaTests\SharedBootstrapTestCase;
use VanillaTests\Fixtures\Request;
use VanillaTests\Fixtures\ExactRoute;

/**
 * Test methods on the Dispatcher class.
 */
class DispatcherTest extends SharedBootstrapTestCase {
    /**
     * Test Dispatcher::callMiddlewares().
     */
    public function testCallMiddlewares() {
        $r = Dispatcher::callMiddlewares(
            new Request(),
            [
                $this->makeMiddleware('a'),
                $this->makeMiddleware('b'),
                $this->makeMiddleware('c'),
            ], function (RequestInterface $request): Data {
                $response = new Data();
                $response->setHeader("test", $request->getHeader("test").'o');
                return $response;
            }
        );

        $this->assertEquals("abcocba", $r->getHeader("test"));
    }

    /**
     * A route middleware should apply to a matched route.
     */
    public function testRouteMiddleware() {
        $dis = $this->makeDispatcher();

        $data = $dis->dispatch(new Request('/foo'));

        $this->assertEquals('a(foo)a', $data->getHeader('test'));
    }

    /**
     * A route middleware should not apply to a different matched route.
     */
    public function testRouteMiddlewareNoMatch() {
        $dis = $this->makeDispatcher();

        $data = $dis->dispatch(new Request('/bar'));
        $this->assertEquals('(bar)', $data->getHeader('test'));
    }

    /**
     * Test basic middleware dispatching.
     */
    public function testDispatcherMiddleware() {
        $dis = $this->makeDispatcher();
        $dis->addMiddleware($this->makeMiddleware('d'));

        $data = $dis->dispatch(new Request('/bar'));
        $this->assertEquals('d(bar)d', $data->getHeader('test'));
    }

    /**
     * Make a basic middleware that ads a value to the "test" header.
     *
     * @param string $v The value to add.
     * @return callable Returns a new middleware.
     */
    protected function makeMiddleware(string $v): callable {
        return function (RequestInterface $request, callable $next) use ($v): Data {
            $request->setHeader("test", $request->getHeader("test").$v);

            /* @var \Garden\Web\Data $response */
            $response = $next($request);
            $response->setHeader("test", $response->getHeader("test").$v);

            return $response;
        };
    }

    /**
     * Make a dispatcher suitable for testing.
     *
     * @return Dispatcher Returns a configured dispatcher.
     */
    protected function makeDispatcher(): Dispatcher {
        $dis = new Dispatcher();

        $route = new ExactRoute('/foo', function (RequestInterface $request) {
            $response = new Data();
            $response->setHeader('test', $request->getHeader("test").'(foo)');

            return $response;
        });

        $route->addMiddleware($this->makeMiddleware('a'));

        $dis->addRoute($route)
            ->addRoute(new ExactRoute('/bar', function (RequestInterface $request) {
                $response = new Data();
                $response->setHeader('test', $request->getHeader("test").'(bar)');

                return $response;
            }));
        return $dis;
    }

    /**
     * Test some basic route accessors.
     */
    public function testRouteAccessors() {
        $dis = new Dispatcher();

        $r = new ResourceRoute();

        $dis->addRoute($r, 'foo');
        $this->assertSame($r, $dis->getRoute('foo'));

        $dis->removeRoute('foo');
        $this->assertNull($dis->getRoute('foo'));
    }

    /**
     * Test the conversion of an exception to a response.
     */
    public function testDispatchException() {
        $dis = new Dispatcher();
        $dis->addMiddleware(function (RequestInterface $r, callable  $next) {
            throw new ClientException('foo');
        });

        $r = $dis->dispatch(new Request());

        $this->assertSame(400, $r->getStatus());
        $this->assertSame('foo', $r['message']);
    }
}
