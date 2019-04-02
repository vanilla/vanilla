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

    /** @var DeploymentCacheBuster */
    public $deploymentCacheBuster;

    /**
     * DeploymentHeaderMiddleware constructor.
     *
     * @param DeploymentCacheBuster $deploymentCacheBuster
     */
    public function __construct(DeploymentCacheBuster $deploymentCacheBuster) {
        $this->deploymentCacheBuster = $deploymentCacheBuster;
    }

    /**
     * Invoke the deployment key on a request.
     *
     * @param RequestInterface $request The incoming request.
     * @param callable $next The next middleware.
     * @return mixed Returns the response of the inner middleware.
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $response = Data::box($next($request));

        $response->setHeader(
            self::VANILLA_DEPLOYMENT_KEY,
            $this->deploymentCacheBuster->value()
        );
        return $response;
    }
}
