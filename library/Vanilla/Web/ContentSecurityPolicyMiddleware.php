<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\RequestInterface;

/**
 * Dispatcher middleware for handling content-security headers.
 */
class ContentSecurityPolicyMiddleware {
    const CONTENT_SECURITY_POLICY = 'Content-Security-Policy';

    /**
     * Invoke the smart ID middleware on a request.
     *
     * @param RequestInterface $request The incoming request.
     * @param callable $next The next middleware.
     * @return mixed Returns the response of the inner middleware.
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $response = Data::box($next($request));
        $response->setHeader(
            self::CONTENT_SECURITY_POLICY,
            implode('; ', [
                'script-src '.$this->getTrustedScriptSources().';',
                'form-action \'self\''
            ])
        );
        return $response;
    }

    private function getTrustedScriptSources(): string {
        return implode(
            ' ',
            [
                '\'self\'',
                '\'unsafe-inline\'',
                //'http://127.0.0.1',
                '\'nonce-EDNnf03nceIOfn39fn3e9h3sdfa\'',
            ]
        );
    }
}
