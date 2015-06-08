<?php
/**
 * Routing system.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
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
        $this->RouteTypes = array(
            'Internal' => 'Internal',
            'Temporary' => 'Temporary (302)',
            'Permanent' => 'Permanent (301)',
            'NotAuthorized' => 'Not Authorized (401)',
            'NotFound' => 'Not Found (404)',
            'Test' => 'Test'
        );
        $this->ReservedRoutes = array('DefaultController', 'DefaultForumRoot', 'Default404', 'DefaultPermission', 'UpdateMode');
        $this->_loadRoutes();
    }

    /**
     * Get an route that exactly matches a string.
     * @param string|int $Route The route to search for.
     * @param int $Indexed If the route is a number then it will be looked up as an index.
     *
     * @return array|bool A route or false if there is no matching route.
     */
    public function getRoute($Route, $Indexed = true) {
        if ($Indexed && is_numeric($Route) && $Route !== false) {
            $Keys = array_keys($this->Routes);
            $Route = arrayValue($Route, $Keys);
        }

        $Decoded = $this->_decodeRouteKey($Route);
        if ($Decoded !== false && array_key_exists($Decoded, $this->Routes)) {
            $Route = $Decoded;
        }

        if ($Route === false || !array_key_exists($Route, $this->Routes)) {
            return false;
        }

        //return $this->Routes[$Route];

        return array_merge($this->Routes[$Route], array(
            'TypeLocale' => T($this->RouteTypes[$this->Routes[$Route]['Type']]),
            'FinalDestination' => $this->Routes[$Route]['Destination']
        ));

    }

    /**
     *
     *
     * @param $Request
     * @return bool
     */
    public function getDestination($Request) {
        $Route = $this->matchRoute($Request);

        if ($Route !== false) {
            return isset($Route['FinalDestination']) ? $Route['FinalDestination'] : $Route['Destination'];
        }

        return false;
    }

    /**
     * Update or add a route to the config table
     *
     * @param string $Route
     * @param string $Destination
     * @param string $Type
     * @param bool $Save Optional. Save this to the config or just in memory?
     */
    public function setRoute($Route, $Destination, $Type, $Save = true) {
        $Key = $this->_encodeRouteKey($Route);
        SaveToConfig('Routes.'.$Key, array($Destination, $Type), $Save);
        $this->_loadRoutes();
    }

    /**
     *
     *
     * @param $Route
     */
    public function deleteRoute($Route) {
        $Route = $this->getRoute($Route);

        // Is a valid route?
        if ($Route !== false) {
            if (!in_array($Route['Route'], $this->ReservedRoutes)) {
                RemoveFromConfig('Routes.'.$Route['Key']);
                $this->_loadRoutes();
            }
        }
    }

    /**
     *
     *
     * @param $Request
     * @return array|bool
     */
    public function matchRoute($Request) {
        // Check for a literal match
        if ($this->getRoute($Request, false)) {
            return $this->getRoute($Request);
        }

        foreach ($this->Routes as $Route => $RouteData) {
            // Check for wild-cards
            $Route = str_replace(
                array(':alphanum', ':num'),
                array('([0-9a-zA-Z-_]+)', '([0-9]+)'),
                $Route
            );

            // Check for a match
            if (preg_match('#^'.$Route.'#', $Request)) {
                // Route matched!
                $Final = $this->getRoute($Route);
                $Final['FinalDestination'] = $Final['Destination'];

                // Do we have a back-reference?
                if (strpos($Final['Destination'], '$') !== false && strpos($Final['Route'], '(') !== false) {
                    $Final['FinalDestination'] = preg_replace('#^'.$Final['Route'].'#', $Final['Destination'], $Request);
                }

                return $Final;
            }
        }

        return false; // No route matched
    }

    /**
     *
     *
     * @param $Url
     * @return bool|int|string
     */
    public function reverseRoute($Url) {
        $Root = rtrim(Gdn::request()->domain().'/'.Gdn::request()->webRoot(), '/');

        if (stringBeginsWith($Url, $Root)) {
            $Url = stringBeginsWith($Url, $Root, true, true);
            $WithDomain = true;
        } else {
            $WithDomain = false;
        }

        $Url = '/'.ltrim($Url, '/');

        foreach ($this->Routes as $Route => $RouteData) {
            if ($RouteData['Type'] != 'Internal' || ($RouteData['Reserved'] && $RouteData['Route'] != 'DefaultController')) {
                continue;
            }

            $Destination = '/'.ltrim($RouteData['Destination'], '/');
            if ($Destination == $Url) {
                $Route = '/'.ltrim($RouteData['Route'], '/');

                if ($Route == '/DefaultController') {
                    $Route = '/';
                }

                if ($WithDomain) {
                    return $Root.$Route;
                } else {
                    return $Route;
                }
            }
        }
        if ($WithDomain) {
            return $Root.$Url;
        } else {
            return $Url;
        }
    }

    /**
     *
     *
     * @return array
     */
    public function getRouteTypes() {
        $RT = array();
        foreach ($this->RouteTypes as $RouteType => $RouteTypeText) {
            $RT[$RouteType] = T($RouteTypeText);
        }
        return $RT;
    }

    /**
     *
     *
     * @throws Exception
     */
    private function _loadRoutes() {
        $Routes = Gdn::config('Routes', array());
        $this->EventArguments['Routes'] = &$Routes;
        $this->fireEvent("BeforeLoadRoutes");
        foreach ($Routes as $Key => $Destination) {
            $Route = $this->_decodeRouteKey($Key);
            $RouteData = $this->_parseRoute($Destination);

            $this->Routes[$Route] = array_merge(array(
                'Route' => $Route,
                'Key' => $Key,
                'Reserved' => in_array($Route, $this->ReservedRoutes)
            ), $RouteData);
        }
        $this->fireEvent("AfterLoadRoutes");
    }

    /**
     *
     *
     * @param $Destination
     * @return array|mixed
     */
    private function _parseRoute($Destination) {
        // If Destination is a serialized array
        if (is_string($Destination) && ($Decoded = @unserialize($Destination)) !== false) {
            $Destination = $Decoded;
        }

        // If Destination is a short array
        if (is_array($Destination) && sizeof($Destination) == 1) {
            $Destination = $Destination[0];
        }

        // If Destination is a simple string...
        if (!is_array($Destination)) {
            $Destination = $this->_formatRoute($Destination, 'Internal');
        }

        // If Destination is an array with no named keys...
        if (!array_key_exists('Destination', $Destination)) {
            $Destination = $this->_formatRoute($Destination[0], $Destination[1]);
        }

        return $Destination;
    }

    /**
     *
     *
     * @param $Destination
     * @param $RouteType
     * @return array
     */
    private function _formatRoute($Destination, $RouteType) {
        return array(
            'Destination' => $Destination,
            'Type' => $RouteType
        );
    }

    /**
     *
     *
     * @param $Key
     * @return mixed
     */
    protected function _encodeRouteKey($Key) {
        return str_replace('/', '_', in_array($Key, $this->ReservedRoutes) ? $Key : base64_encode($Key));
    }

    /**
     *
     *
     * @param $Key
     * @return string
     */
    protected function _decodeRouteKey($Key) {
        return in_array($Key, $this->ReservedRoutes) ? $Key : base64_decode(str_replace('_', '/', $Key));
    }
}
