<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden\Web;

use Garden\ArrayContainer;
use Garden\Web\Data;
use Garden\Web\Dispatcher;
use Garden\Web\Exception\ClientException;
use Garden\Web\RequestInterface;
use Garden\Web\ResourceRoute;
use VanillaTests\Fixtures\Locale;
use VanillaTests\SharedBootstrapTestCase;
use VanillaTests\Fixtures\Request;
use VanillaTests\Fixtures\ExactRoute;

/**
 * Test methods on the Dispatcher class.
 */
class DispatcherTest extends SharedBootstrapTestCase {
    private static $locale;

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
            ],
            function (RequestInterface $request): Data {
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

    /**
     * Test happy paths for `Dispatcher::reflectArgs()`.
     *
     * @param \ReflectionFunctionAbstract $func
     * @param array $args
     * @param array $expected
     * @dataProvider provideHappyReflectArgsTests
     */
    public function testReflectArgsHappy(\ReflectionMethod $func, array $args, array $expected): void {
        $container = new ArrayContainer();
        $container[get_class(self::$locale)] = self::$locale;

        $actual = Dispatcher::reflectArgs($func, $args, $container, true);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide some happy path tests for `testReflectHappy()`.
     *
     * @return array
     */
    public function provideHappyReflectArgsTests(): array {
        $f1 = new \ReflectionMethod($this, 'dummy1');
        $f2 = new \ReflectionMethod($this, 'dummy2');
        $basic = ['foo' => 123, 'bar' => 'foo'];
        self::$locale = new Locale();

        $r = [
            'named' => [$f1, ['bar' => 'foo', 'FOO' => 123], $basic],
            'index' => [$f1, [123, 'foo'], $basic],
            'named overrides index' => [$f1, [345, 'foo', 'foo' => 123], $basic],
            'default' => [$f1, [123], ['foo' => 123, 'bar' => 'baz']],
            'container' => [$f2, [], ['obj' => self::$locale, 'foo' => 123]],
            'container overrides args' => [$f2, ['obj' => 123], ['obj' => self::$locale, 'foo' => 123]],
        ];

        return $r;
    }

    /**
     * Test some exception for `Dispatcher::reflectArgs()`.
     *
     * @param \ReflectionMethod $func
     * @param array $args
     * @param string $message
     * @dataProvider provideReflectArgsException
     */
    public function testReflectArgsExceptions(\ReflectionMethod $func, array $args, string $message): void {
        $container = new ArrayContainer();

        $args = Dispatcher::reflectArgs($func, $args, $container, false);

        $this->expectException(\ReflectionException::class);
        $this->expectExceptionMessage($message);
        Dispatcher::reflectArgs($func, $args, $container, true);
    }

    /**
     * Provide some exception tests for `testReflectArgsExceptions()`.
     *
     * @return array
     */
    public function provideReflectArgsException(): array {
        $f1 = new \ReflectionMethod($this, 'dummy1');
        $f2 = new \ReflectionMethod($this, 'dummy2');

        $r = [
            'missing' => [$f1, [], 'VanillaTests\Library\Garden\Web\DispatcherTest::dummy1() expects the following parameters: $foo.'],
            'missing obj' => [$f2, [], 'VanillaTests\Library\Garden\Web\DispatcherTest::dummy2() expects the following parameters: $obj.'],
        ];

        return $r;
    }

    /**
     * A dummy method for testing argument reflection.
     *
     * @param int $foo
     * @param string $bar
     */
    protected function dummy1(int $foo, string $bar = 'baz'): void {
        // Do nothing.
    }

    /**
     * A dummy method for testing argument reflection.
     *
     * @param Locale $obj
     * @param int $foo
     */
    protected function dummy2(Locale $obj, int $foo = 123): void {
        // Do nothing.
    }
}
