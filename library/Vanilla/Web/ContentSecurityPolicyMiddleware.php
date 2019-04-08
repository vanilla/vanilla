<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel;
use Vanilla\Web\ContentSecurityPolicy\Policy;

/**
 * Dispatcher middleware for handling content-security headers.
 */
class ContentSecurityPolicyMiddleware {
    const CONTENT_SECURITY_POLICY = 'Content-Security-Policy';

    /**
     * @var ContentSecurityPolicyModel
     */
    private $contentSecurityPolicyModel;

    /**
     * ContentSecurityPolicyMiddleware constructor.
     * @param ContentSecurityPolicyModel $contentSecurityPolicyModel
     */
    public function __construct(ContentSecurityPolicyModel $contentSecurityPolicyModel) {
        $this->contentSecurityPolicyModel = $contentSecurityPolicyModel;
    }

    /**
     * Invoke the content security policy headers on a request.
     *
     * @param RequestInterface $request The incoming request.
     * @param callable $next The next middleware.
     * @return mixed Returns the response of the inner middleware.
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $response = Data::box($next($request));

        $response->setHeader(
            self::CONTENT_SECURITY_POLICY,
            $this->getHeaderString()
        );

        return $response;
    }

    /**
     * Compose content security header string from policies list
     *
     * @return string
     */
    private function getHeaderString(): string {
        $directives = [];
        $policies = $this->contentSecurityPolicyModel->getPolicies();
        foreach ($policies as $policy) {
            if (array_key_exists($policy->getDirective(), $directives)) {
                $directives[$policy->getDirective()] .= ' ' . $policy->getArgument();
            } else {
                $directives[$policy->getDirective()] = $policy->getDirective() . ' ' . $policy->getArgument();
            }
        }
        return implode('; ', $directives);
    }
}
