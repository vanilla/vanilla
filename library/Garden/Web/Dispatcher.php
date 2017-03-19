<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Web;


use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\Pass;

class Dispatcher {

    /**
     * @var Route[]
     */
    private $routes;

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
                    ob_start();
                    $actionResponse = $action();
                    $ob = ob_get_clean();
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
                $response->setMetaItem('noMatch', true);
            }
        }

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
        } elseif (is_array($raw)) {
            // This is an array of response data.
            $result = new Data($raw);
        } elseif ($raw instanceof \Exception) {
            $data = $raw instanceof \JsonSerializable ? $raw->jsonSerialize() : ['message' => $raw->getMessage(), 'status' => $raw->getCode()];
            $result = new Data($data, $raw->getCode());
        } elseif ($raw instanceof \JsonSerializable) {
            $result = new Data((array)$raw->jsonSerialize());
        } elseif (!empty($ob)) {
            $result = new Data($ob);
        } else {
            $result = new Data(['message' => 'Could not encode the response.', 'status' => 500], 500);
        }

        return $result;
    }
}
