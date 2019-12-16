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
            ContentSecurityPolicyModel::CONTENT_SECURITY_POLICY,
            $this->contentSecurityPolicyModel->getHeaderString()
        );

        $xFrameString = $this->contentSecurityPolicyModel->getXFrameString();
        if ($xFrameString !== null) {
            $response->setHeader(
                ContentSecurityPolicyModel::X_FRAME_OPTIONS,
                $xFrameString
            );
        }

        return $response;
    }
}
