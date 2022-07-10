<?php
/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Middleware;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Web\RequestInterface;
use Vanilla\Web\Middleware\CookiePassMiddleware;
use VanillaTests\Fixtures\Request;
use VanillaTests\MinimalContainerTestCase;

/**
 * Class CookiePassMiddlewareTest
 * @package VanillaTests\Library\Vanilla\Web\Middleware
 */
class CookiePassMiddlewareTest extends MinimalContainerTestCase {

    /** @var CookiePassMiddleware */
    protected $middleware;

    protected $session;

    /** @var RequestInterface */
    protected $request;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();
        $this->request = new Request('/', 'GET', []);
        $this->request->setHeader('Cookie', 'name=value');
        $this->middleware = new CookiePassMiddleware($this->request);
    }

    /**
     * Call the middleware and return the response.
     *
     * @param HttpRequest $request The request being called.
     * @return HttpResponse Returns the augmented request.
     */
    protected function callMiddleware(HttpRequest $request): HttpResponse {
        /** @var HttpResponse $response */
        $response = call_user_func($this->middleware, $request, function ($request) {
            return new HttpResponse();
        });

        return $response;
    }

    /**
     * Test cookie pass
     */
    public function testCookiePass(): void {
        $httpRequest = new HttpRequest('GET', 'http://'.$this->request->getHost());
        $this->callMiddleware($httpRequest);
        $this->assertSame($this->request->getHeader('Cookie'), $httpRequest->getHeader('Cookie'));

        // test external domain, cookies should not be passed
        $httpRequest = new HttpRequest('GET', 'https://vanillaforums.com');
        $this->callMiddleware($httpRequest);
        $this->assertSame('', $httpRequest->getHeader('Cookie'));
    }
}
