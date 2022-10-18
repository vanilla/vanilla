<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Middleware;

use Garden\Web\Exception\ClientException;
use Garden\Web\RequestInterface;

/**
 * Middleware for validating JSON payloads
 */
class ValidateJSONMiddleware
{
    /**
     * @param RequestInterface $request
     * @param callable $next
     * @return mixed
     * @throws ClientException
     */
    public function __invoke(RequestInterface $request, callable $next)
    {
        $body = $request->getRawBody();
        if ($request->getHeader("Content-Type") === "application/json" && !empty($body)) {
            json_decode($body);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ClientException("JSON could not be decoded");
            }
        }
        return $next($request);
    }
}
