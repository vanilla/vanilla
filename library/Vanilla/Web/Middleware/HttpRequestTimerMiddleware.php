<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Middleware;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Vanilla\Http\InternalRequest;
use Vanilla\Utility\Timers;

class HttpRequestTimerMiddleware
{
    private Timers $timers;

    /**
     * Constructor.
     */
    public function __construct(Timers $timers)
    {
        $this->timers = $timers;
    }

    /**
     * Invoke the cookie pass middleware on a request.
     *
     * @param HttpRequest $request
     * @param callable $next
     * @return HttpResponse
     */
    public function __invoke(HttpRequest $request, callable $next): HttpResponse
    {
        if ($request instanceof InternalRequest) {
            // We already track these in the dispatcher.
            return $next($request);
        }

        $span = $this->timers->startRequest($request);
        try {
            $result = $next($request);
        } finally {
            $span->finish($result ?? null);
        }

        return $result;
    }
}
