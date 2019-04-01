<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Web\Asset\DeploymentCacheBuster;

/**
 * Deployment middleware for handling deployment key headers.
 */
class DeploymentHeaderMiddleware {
    const VANILLA_DEPLOYMENT_KEY = 'Vdk';

    /**
     * Invoke the deployment key on a request.
     *
     * @param RequestInterface $request The incoming request.
     * @param callable $next The next middleware.
     * @return mixed Returns the response of the inner middleware.
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $response = Data::box($next($request));
        $cacheBuster = \Gdn::getContainer()->get(DeploymentCacheBuster::class);
        $response->setHeader(
            self::VANILLA_DEPLOYMENT_KEY,
            APPLICATION_VERSION.'-'.$cacheBuster->value()
        );
        return $response;
    }
}
