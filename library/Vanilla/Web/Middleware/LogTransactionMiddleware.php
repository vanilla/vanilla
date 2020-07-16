<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Middleware;

use Garden\Http\HttpRequest;
use Garden\Web\Data;
use Garden\Web\RequestInterface;

/**
 * Middleware for applying a consistent transcationID for logs across a single request.
 */
class LogTransactionMiddleware {

    const HEADER_NAME = 'x-log-transaction-id';

    /** @var int|null */
    private $transactionID = null;

    /**
     * Invoke the cache control middleware on a request.
     *
     * @param RequestInterface|HttpRequest $request The incoming request.
     * @param callable $next The next middleware.
     * @return mixed Returns the response of the inner middleware.
     */
    public function __invoke($request, callable $next) {
        $transactionID = $request->getHeader(self::HEADER_NAME) ?: null;

        if (is_numeric($transactionID)) {
            $this->setTransactionID((int) $transactionID);
        }

        return $next($request);
    }

    /**
     * @return int
     */
    public function getTransactionID(): ?int {
        return $this->transactionID;
    }

    /**
     * @param int|null $transactionID
     */
    public function setTransactionID(?int $transactionID): void {
        $this->transactionID = $transactionID;
    }
}
