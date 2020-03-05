<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden\Web;

use Garden\Web\RequestInterface;
use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\MiddlewareAware;
use VanillaTests\Fixtures\Request;

/**
 * Tests for the `MiddlewareAwareTrait`.
 */
class MiddlewareAwareTraitTest extends TestCase {
    /**
     * @var MiddlewareAware
     */
    private $obj;

    /**
     * Create a fixture for tests.
     */
    public function setUp(): void {
        $this->obj = new MiddlewareAware();
    }

    /**
     * A test middleware.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return mixed
     */
    public function middleware(RequestInterface $request, callable $next) {
        return $next($request);
    }

    /**
     * You can't add a middleware if the inner middleware isn't initialized.
     */
    public function testAddMiddlewareTooSoon(): void {
        $this->expectException(\RuntimeException::class);
        $this->obj->addMiddleware([$this, 'middleware']);
    }

    /**
     * Middleware must return a data object.
     */
    public function testBadReturn(): void {
        $this->obj->setHandler(function () {
            return 'foo';
        });
        $this->obj->addMiddleware([$this, 'middleware']);
        $this->expectException(\BadFunctionCallException::class);
        $this->obj->callMiddleware(new Request());
    }

    /**
     * You can't call the middleware if there is none.
     */
    public function testNoMiddlewareCall(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Cannot call middleware before the inner middleware is initialized.");
        $this->obj->callMiddleware(new Request());
    }
}
