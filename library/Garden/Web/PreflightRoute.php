<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden\Web;


/**
 * A route that handles all **OPTIONS** requests to aid [CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS) support.
 */
class PreflightRoute extends Route {

    /**
     * Match the route to a request.
     *
     * @param RequestInterface $request The request to match against.
     * @return mixed Returns match information or **null** if the route doesn't match.
     */
    public function match(RequestInterface $request) {
        if (strcasecmp($request->getMethod(), 'OPTIONS') === 0) {
            return [$this, 'dispatch'];
        }
    }

    /**
     * Handle the pre-flight request.
     *
     * This method doesn't do anything because the dispatcher should handle the origin headers.
     *
     * @return null Returns **null** for a "no content" response.
     */
    public function dispatch() {
        return null;
    }
}
