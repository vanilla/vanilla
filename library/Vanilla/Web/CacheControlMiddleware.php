<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Gdn_Session as SessionInterface;

class CacheControlMiddleware {
    const PUBLIC_CACHE = 'public, max-age=120';
    const NO_CACHE = 'private, no-cache, max-age=0, must-revalidate';

    private $session;

    public function __construct(SessionInterface $session) {
        $this->session = $session;
    }

    public static function getHttp10Headers(string $cacheControl): array {
        $result = [];

        if (preg_match('`max-age=(\d+)`', $cacheControl, $m)) {
            if ($m[1] === '0') {
                $result['Expires'] = 'Sat, 01 Jan 2000 00:00:00 GMT';
                $result['Pragma'] = 'no-cache';
            } else {
                $result['Expires'] = gmdate('D, d M Y H:i:s T', time() + $m[1]);
            }
        }

        return $result;
    }

    /**
     * Invoke the smart ID middleware on a request.
     *
     * @param RequestInterface $request The incoming request.
     * @param callable $next The next middleware.
     * @return mixed Returns the response of the inner middleware.
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $response = Data::box($next($request));

        if (!$response->hasHeader('Cache-Control')) {
            $response->setHeader(
                'Cache-Control',
                $this->session->isValid() || $request->getMethod() !== 'GET' ?  self::NO_CACHE : self::PUBLIC_CACHE
            );
        }
        foreach (static::getHttp10Headers($response->getHeader('Cache-Control')) as $key => $value) {
            $response->setHeader($key, $value);
        }

        return $response;
    }
}
