<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Web;

/**
 * Allows classes to add and execute middleware when transforming requests into responses.
 */
trait MiddlewareAwareTrait {
    /**
     * @var callable The middleware stack. This property must be initialized to the inner function that performs the main operation.
     */
    protected $middleware;

    /**
     * Add a new middleware to the stack.
     *
     * All middleware are callables that have the following signature:
     *
     * ```php
     * function middleware(RequestInterface $request): Data {
     * ...
     * }
     * ```
     *
     * @param callable $middleware The middleware to add.
     * @return $this
     */
    public function addMiddleware(callable $middleware) {
        if ($this->middleware === null) {
            throw new \RuntimeException("Cannot add middleware before the inner middleware is initialized.", 500);
        }

        $next = $this->middleware;
        $this->middleware = function (RequestInterface $request) use ($middleware, $next): Data {
            $response = $middleware($request, $next);
            if (!$response instanceof Data) {
                throw new \BadFunctionCallException('Middware must return a '.Data::class.' object.', 500);
            }
            return $response;
        };

        return $this;
    }

    /**
     * Call all of the middleware and the inner function.
     *
     * @param RequestInterface $request The request to process.
     * @return Data The response.
     */
    public function callMiddleware(RequestInterface $request): Data {
        if ($this->middleware === null) {
            throw new \RuntimeException("Cannot call middleware before the inner middleware is initialized.", 500);
        }
        $response = call_user_func($this->middleware, $request);

        return $response;
    }
}
