<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Web;

use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\Pass;
use Vanilla\Permissions;

class Dispatcher {

    /**
     * @var array
     */
    private $routes;

    /**
     * @var string|array|callable
     */
    private $allowedOrigins;

    /**
     * Add a route to the routes list.
     *
     * @param Route $route The route to add.
     * @param string $key An optional key of the route. If set you can modify the route later.
     * @return $this
     */
    public function addRoute(Route $route, $key = '') {
        if ($key !== '') {
            $this->routes[$key] = $route;
        } else {
            $this->routes[] = $route;
        }
        return $this;
    }

    /**
     * Remove the route with a specified key.
     *
     * @param string|int $key The key of the route to remove.
     * @return $this
     */
    public function removeRoute($key) {
        unset($this->routes[$key]);
        return $this;
    }

    /**
     * Get the route with a specified key.
     *
     * @param string|int $key The route to get.
     * @return Route|null Returns a {@link Route} or **null** if no route exists with the given key.
     */
    public function getRoute($key) {
        return isset($this->routes[$key]) ? $this->routes[$key] : null;
    }

    /**
     * Dispatch a request and return a response.
     *
     * This method currently returns a {@link Data} object that will be directly rendered. This really only for API calls
     * and will be changed in the future. If you use this method now you'll have to refactor later.
     *
     * @param RequestInterface $request The request to handle.
     * @return Data Returns the response as a data object.
     */
    public function dispatch(RequestInterface $request) {
        $ex = null;

        foreach ($this->routes as $route) {
            try {
                $action = $route->match($request);
                if ($action instanceof \Exception) {
                    // Hold the action in case another route succeeds.
                    $ex = $action;
                } elseif ($action !== null) {
                    // KLUDGE: Check for CSRF here because we can only do a global check for new dispatches.
                    // Once we can test properly then a route can be added that checks for CSRF on all requests.
                    if ($request->getMethod() === 'POST' && $request instanceof \Gdn_Request) {
                        /* @var \Gdn_Request $request */
                        try {
                            $request->isAuthenticatedPostBack(true);
                        } catch (\Exception $ex) {
                            \Gdn::session()->getPermissions()->addBan(
                                Permissions::BAN_CSRF,
                                ['msg' => t('Invalid CSRF token.', 'Invalid CSRF token. Please try again.'), 'code' => 403]
                            );
                        }
                    }

                    try {
                        ob_start();
                        $actionResponse = $action();
                    } finally {
                        $ob = ob_get_clean();
                    }
                    $response = $this->makeResponse($actionResponse, $ob);
                    break;
                }
            } catch (Pass $pass) {
                // Pass to the next route.
                continue;
            } catch (\Exception $dispatchEx) {
                $response = $this->makeResponse($dispatchEx);
                break;
            }
        }

        if (!isset($response)) {
            if ($ex) {
                $response = $this->makeResponse($ex);
            } else {
                $response = $this->makeResponse(new NotFoundException($request->getPath()));
                // This is temporary. Only use internally.
                $response->setMeta('noMatch', true);
            }
        } else {
            if ($response->getMeta('status', null) === null) {
                switch ($request->getMethod()) {
                    case 'GET':
                    case 'PATCH':
                    case 'PUT':
                        $response->setStatus(200);
                        break;
                    case 'POST':
                        $response->setStatus(201);
                        break;
                    case 'DELETE':
                        $response->setStatus(204);
                        break;
                }
            }
        }

        $this->addAccessControl($response, $request);

        return $response;
    }

    /**
     * Handle a request.
     *
     * This method dispatches the request to an appropriate callback depending on the available routes and renders the contents.
     *
     * @param RequestInterface $request The request to handle.
     */
    public function handle(RequestInterface $request) {
        $response = $this->dispatch($request);
        $response->render();
    }

    /**
     * Make a structured response object from a raw response.
     *
     * @param mixed $raw The raw response.
     * @param string $ob The contents of the output buffer, if any.
     * @return Data Returns the data response.
     */
    private function makeResponse($raw, $ob = '') {
        if ($raw instanceof Data) {
            $result = $raw;
        } elseif (is_array($raw) || is_string($raw)) {
            // This is an array of response data.
            $result = new Data($raw);
        } elseif ($raw instanceof \Exception) {
            $data = $raw instanceof \JsonSerializable ? $raw->jsonSerialize() : ['message' => $raw->getMessage(), 'status' => $raw->getCode()];
            $result = new Data($data, $raw->getCode());
            // Provide stack trace as meta information.
            $result->setMeta('error_trace', $raw->getTraceAsString());
        } elseif ($raw instanceof \JsonSerializable) {
            $result = new Data((array)$raw->jsonSerialize());
        } elseif (!empty($ob)) {
            $result = new Data($ob);
        } elseif ($raw === null) {
            $result = new Data(null);
        } else {
            $result = new Data(['message' => 'Could not encode the response.', 'status' => 500], 500);
        }

        return $result;
    }

    /**
     * Add access control headers to the response.
     *
     * @param Data $response The current response being dispatched.
     * @param RequestInterface $request The current request.
     */
    private function addAccessControl(Data $response, RequestInterface $request) {
        if (!$response->hasHeader('Access-Control-Allow-Origin') && $allowOrigin = $this->allowOrigin($request)) {
            $response->setHeader('Access-Control-Allow-Origin', $allowOrigin);

            if ($request->hasHeader('Access-Control-Request-Method')) {
                $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            }
            if ($request->hasHeader('Access-Control-Request-Headers')) {
                $response->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type');
            }

            $response->setHeader('Access-Control-Max-Age', strtotime('1 hour'));
        }
    }

    /**
     * Return the allow origin for the given host.
     *
     * @param RequestInterface $request The request to check.
     * @return string Returns a value valid for a **Access-Control-Allow-Origin** header or an empty string if the origin isn't allowed.
     */
    private function allowOrigin(RequestInterface $request) {
        $origin = $request->getHeader('Origin');
        if (empty($origin)) {
            return '';
        }
        $host = parse_url($origin, PHP_URL_HOST);
        if (strcasecmp($host, $request->getHost()) === 0) {
            // Same origin, no need for header.
            return '';
        }
        $hostAndScheme = parse_url($origin, PHP_URL_SCHEME).'://'.$host;

        if ($this->allowedOrigins === '*') {
            return '*';
        } elseif (is_callable($this->allowedOrigins) && call_user_func($this->allowedOrigins, $origin)) {
            return $origin;
        } elseif (is_string($this->allowedOrigins) && in_array($this->allowedOrigins, [$host, $hostAndScheme], true)) {
            return $origin;
        } elseif (is_array($this->allowedOrigins) && (in_array($host, $this->allowedOrigins) || in_array($hostAndScheme, $this->allowedOrigins))) {
            return $origin;
        }
        return '';
    }

    /**
     * Get the allowedOrigins.
     *
     * @return array|callable|string Returns the allowedOrigins.
     */
    public function getAllowedOrigins() {
        return $this->allowedOrigins;
    }

    /**
     * Set the allowed origins for CORS requests.
     *
     * This can be one of the following:
     *
     * - **string**. Either a single host name or "*" for all hosts.
     * - **array**. An array of host names that are allowed.
     * - **callable**. A function that returns **true** or **false** when an origin is passed in.
     *
     * @param array|callable|string $origins The allowed origins.
     * @return $this
     */
    public function setAllowedOrigins($origins) {
        $this->allowedOrigins = $origins;
        return $this;
    }
}
