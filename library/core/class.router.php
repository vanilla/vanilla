<?php
/**
 * Routing system.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Allows paths within the application to redirect, either internally or via
 * http, to other locations.
 */
class Gdn_Router extends Gdn_Pluggable {

    /** @var array */
    public $Routes;

    /** @var array */
    public $ReservedRoutes;

    /** @var array */
    public $RouteTypes;

    /**
     *
     */
    public function __construct() {
        parent::__construct();
        $this->RouteTypes = [
            'Internal'      => 'Internal',
            'Temporary'     => 'Temporary (302)',
            'Permanent'     => 'Permanent (301)',
            'NotAuthorized' => 'Not Authorized (401)',
            'NotFound'      => 'Not Found (404)',
            'Drop'          => 'Drop Request',
            'Test'          => 'Test'
        ];
        $this->ReservedRoutes = ['DefaultController', 'DefaultForumRoot', 'Default404', 'DefaultPermission', 'UpdateMode'];
        $this->_loadRoutes();
    }

    /**
     * Get an route that exactly matches a string.
     * @param string|int $route The route to search for.
     * @param int $indexed If the route is a number then it will be looked up as an index.
     *
     * @return array|bool A route or false if there is no matching route.
     */
    public function getRoute($route, $indexed = true) {
        if ($indexed && is_numeric($route) && $route !== false) {
            $keys = array_keys($this->Routes);
            $route = val($route, $keys);
        }

        $decoded = $this->_decodeRouteKey($route);
        if ($decoded !== false && array_key_exists($decoded, $this->Routes)) {
            $route = $decoded;
        }

        if ($route === false || !array_key_exists($route, $this->Routes)) {
            return false;
        }

        //return $this->Routes[$Route];

        return array_merge($this->Routes[$route], [
            'TypeLocale' => t($this->RouteTypes[$this->Routes[$route]['Type']]),
            'FinalDestination' => $this->Routes[$route]['Destination']
        ]);

    }

    /**
     *
     *
     * @param $request
     * @return bool
     */
    public function getDestination($request) {
        $route = $this->matchRoute($request);

        if ($route !== false) {
            return isset($route['FinalDestination']) ? $route['FinalDestination'] : $route['Destination'];
        }

        return false;
    }

    /**
     * Update or add a route to the config table
     *
     * @param string $route
     * @param string $destination
     * @param string $type
     * @param bool $save Optional. Save this to the config or just in memory?
     */
    public function setRoute($route, $destination, $type, $save = true) {
        $key = $this->_encodeRouteKey($route);
        saveToConfig('Routes.'.$key, [$destination, $type], $save);
        $this->_loadRoutes();
    }

    /**
     *
     *
     * @param $route
     */
    public function deleteRoute($route) {
        $route = $this->getRoute($route);

        // Is a valid route?
        if ($route !== false) {
            if (!in_array($route['Route'], $this->ReservedRoutes)) {
                removeFromConfig('Routes.'.$route['Key']);
                $this->_loadRoutes();
            }
        }
    }

    /**
     *
     *
     * @param $request
     * @return array|bool
     */
    public function matchRoute($request) {
        // Check for a literal match
        if ($this->getRoute($request, false)) {
            return $this->getRoute($request);
        }

        foreach ($this->Routes as $route => $routeData) {
            // Check for wild-cards
            $route = str_replace(
                [':alphanum', ':num'],
                ['([0-9a-zA-Z-_]+)', '([0-9]+)'],
                $route
            );

            // Check for a match
            if (preg_match('#^'.$route.'#', $request)) {
                // Route matched!
                $final = $this->getRoute($route);
                $final['FinalDestination'] = $final['Destination'];

                // Do we have a back-reference?
                if (strpos($final['Destination'], '$') !== false && strpos($final['Route'], '(') !== false) {
                    $final['FinalDestination'] = preg_replace('#^'.$final['Route'].'#', $final['Destination'], $request);
                }

                return $final;
            }
        }

        return false; // No route matched
    }

    /**
     *
     *
     * @param $url
     * @return bool|int|string
     */
    public function reverseRoute($url) {
        $root = rtrim(Gdn::request()->domain().'/'.Gdn::request()->webRoot(), '/');

        if (stringBeginsWith($url, $root)) {
            $url = stringBeginsWith($url, $root, true, true);
            $withDomain = true;
        } else {
            $withDomain = false;
        }

        $url = '/'.ltrim($url, '/');

        foreach ($this->Routes as $route => $routeData) {
            if ($routeData['Type'] != 'Internal' || ($routeData['Reserved'] && $routeData['Route'] != 'DefaultController')) {
                continue;
            }

            $destination = '/'.ltrim($routeData['Destination'], '/');
            if ($destination == $url) {
                $route = '/'.ltrim($routeData['Route'], '/');

                if ($route == '/DefaultController') {
                    $route = '/';
                }

                if ($withDomain) {
                    return $root.$route;
                } else {
                    return $route;
                }
            }
        }
        if ($withDomain) {
            return $root.$url;
        } else {
            return $url;
        }
    }

    /**
     *
     *
     * @return array
     */
    public function getRouteTypes() {
        $rT = [];
        foreach ($this->RouteTypes as $routeType => $routeTypeText) {
            $rT[$routeType] = t($routeTypeText);
        }
        return $rT;
    }

    /**
     *
     *
     * @throws Exception
     */
    private function _loadRoutes() {
        $routes = Gdn::config('Routes', []);
        $this->EventArguments['Routes'] = &$routes;
        $this->fireEvent("BeforeLoadRoutes");
        foreach ($routes as $key => $destination) {
            $route = $this->_decodeRouteKey($key);
            $routeData = $this->_parseRoute($destination);

            $this->Routes[$route] = array_merge([
                'Route' => $route,
                'Key' => $key,
                'Reserved' => in_array($route, $this->ReservedRoutes)
            ], $routeData);
        }
        $this->fireEvent("AfterLoadRoutes");
    }

    /**
     *
     *
     * @param $destination
     * @return array|mixed
     */
    private function _parseRoute($destination) {
        // If Destination is a serialized array
        if (is_string($destination) && ($decoded = @unserialize($destination)) !== false) {
            $destination = $decoded;
        }

        // If Destination is a short array
        if (is_array($destination) && sizeof($destination) == 1) {
            $destination = $destination[0];
        }

        // If Destination is a simple string...
        if (!is_array($destination)) {
            $destination = $this->_formatRoute($destination, 'Internal');
        }

        // If Destination is an array with no named keys...
        if (!array_key_exists('Destination', $destination)) {
            $destination = $this->_formatRoute($destination[0], $destination[1]);
        }

        return $destination;
    }

    /**
     *
     *
     * @param $destination
     * @param $routeType
     * @return array
     */
    private function _formatRoute($destination, $routeType) {
        return [
            'Destination' => $destination,
            'Type' => $routeType
        ];
    }

    /**
     *
     *
     * @param $key
     * @return mixed
     */
    protected function _encodeRouteKey($key) {
        return str_replace('/', '_', in_array($key, $this->ReservedRoutes) ? $key : base64_encode($key));
    }

    /**
     *
     *
     * @param $key
     * @return string
     */
    protected function _decodeRouteKey($key) {
        return in_array($key, $this->ReservedRoutes) ? $key : base64_decode(str_replace('_', '/', $key));
    }
}
