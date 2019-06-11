<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Web\HttpStrictTransportSecurity\HttpStrictTransportSecurityModel;

/**
 * Dispatcher middleware for handling HSTS header.
 */
class HttpStrictTransportSecurityMiddleware {
    /**
     * @var HttpStrictTransportSecurityModel
     */
    private $hstsModel;

    /**
     * HttpStrictTransportSecurityMiddleware constructor.
     * @param HttpStrictTransportSecurityModel $hstsModel
     */
    public function __construct(HttpStrictTransportSecurityModel $hstsModel) {
        $this->hstsModel = $hstsModel;
    }

    /**
     * Invoke the hsts headers on a request.
     *
     * @param RequestInterface $request The incoming request.
     * @param callable $next The next middleware.
     * @return mixed Returns the response of the inner middleware.
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $response = Data::box($next($request));

        $response->setHeader(
            HttpStrictTransportSecurityModel::HSTS_HEADER,
            $this->hstsModel->getHsts()
        );

        return $response;
    }


}
