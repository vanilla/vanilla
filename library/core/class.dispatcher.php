<?php
/**
 * Framework dispatcher.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Handles all requests and routing.
 */
class Gdn_Dispatcher extends Gdn_Pluggable {

    /** Block condition. */
    const BLOCK_NEVER = 0;

    /** Block condition. */
    const BLOCK_PERMISSION = 1;

    /** Block condition. */
    const BLOCK_ANY = 2;

    /**
     * @var array An array of folders within the application that are OK to search through
     * for controllers. This property is filled by the applications array
     * located in /conf/applications.php and included in /bootstrap.php
     */
    private $_EnabledApplicationFolders;

    /**
     * @var array An associative array of ApplicationName => ApplicationFolder. This
     * property is filled by the applications array located in
     * /conf/applications.php and included in /bootstrap.php
     */
    private $_EnabledApplications;

    /** @var string The currently requested url (defined in _AnalyzeRequest). */
    public $Request;

    /** @var string The name of the application folder that contains the controller that has been requested. */
    private $_ApplicationFolder;

    /**
     * @var array An associative collection of AssetName => Strings that will get passed
     * into the controller once it has been instantiated.
     */
    private $_AssetCollection;

    /** @var string The name of the controller to be dispatched. */
    public $ControllerName;

    /** @var stringThe method of the controller to be called. */
    public $ControllerMethod;

    /** @var stringAny query string arguments supplied to the controller method. */
    private $_ControllerMethodArgs = array();

    /** @var string|FALSE The delivery method to set on the controller. */
    private $_DeliveryMethod = false;


    /** @var string|FALSE The delivery type to set on the controller. */
    private $_DeliveryType = false;

    /**
     * @var array An associative collection of variables that will get passed into the
     * controller as properties once it has been instantiated.
     */
    private $_PropertyCollection;

    /** @var string Defined by the url of the request: SYNDICATION_RSS, SYNDICATION_ATOM, or SYNDICATION_NONE (default). */
    private $_SyndicationMethod;

    /**
     * Class constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->_EnabledApplicationFolders = array();
        $this->Request = '';
        $this->_ApplicationFolder = '';
        $this->_AssetCollection = array();
        $this->ControllerName = '';
        $this->ControllerMethod = '';
        $this->_ControllerMethodArgs = array();
        $this->_PropertyCollection = array();
        $this->_Data = array();
    }

    /**
     *
     *
     * @throws Exception
     */
    public function cleanup() {
        $this->fireEvent('Cleanup');
    }

    /**
     * Return the properly formatted controller class name.
     */
    public function controllerName() {
        return $this->ControllerName.'Controller';
    }

    public function application() {
        return $this->_ApplicationFolder;
    }

    public function controller() {
        return $this->ControllerName;
    }

    public function controllerMethod() {
        return $this->ControllerMethod;
    }

    public function controllerArguments($Value = null) {
        if ($Value !== null) {
            $this->_ControllerMethodArgs = $Value;
        }
        return $this->_ControllerMethodArgs;
    }

    public function start() {
        $this->fireEvent('AppStartup');
    }

    /**
     * Analyzes the supplied query string and decides how to dispatch the request.
     */
    public function dispatch($ImportRequest = null, $Permanent = true) {

        if ($ImportRequest && is_string($ImportRequest)) {
            $ImportRequest = Gdn_Request::create()->fromEnvironment()->withURI($ImportRequest);
        }

        if (is_a($ImportRequest, 'Gdn_Request') && $Permanent) {
            Gdn::request($ImportRequest);
        }

        $Request = is_a($ImportRequest, 'Gdn_Request') ? $ImportRequest : Gdn::request();

        if (Gdn::session()->newVisit()) {
            Gdn::userModel()->fireEvent('Visit');
        }

        $this->EventArguments['Request'] = &$Request;

        // Move this up to allow pre-routing
        $this->fireEvent('BeforeDispatch');

        // By default, all requests can be blocked by UpdateMode/PrivateCommunity
        $CanBlock = self::BLOCK_ANY;

        try {
            $BlockExceptions = array(
                '/^utility(\/.*)?$/' => self::BLOCK_NEVER,
                '/^home\/error(\/.*)?/' => self::BLOCK_NEVER,
                '/^plugin(\/.*)?$/' => self::BLOCK_NEVER,
                '/^sso(\/.*)?$/' => self::BLOCK_NEVER,
                '/^discussions\/getcommentcounts/' => self::BLOCK_NEVER,
                '/^entry(\/.*)?$/' => self::BLOCK_PERMISSION,
                '/^user\/usernameavailable(\/.*)?$/' => self::BLOCK_PERMISSION,
                '/^user\/emailavailable(\/.*)?$/' => self::BLOCK_PERMISSION,
                '/^home\/termsofservice(\/.*)?$/' => self::BLOCK_PERMISSION
            );

            $this->EventArguments['BlockExceptions'] = &$BlockExceptions;
            $this->fireEvent('BeforeBlockDetect');

            $PathRequest = Gdn::request()->path();
            foreach ($BlockExceptions as $BlockException => $BlockLevel) {
                if (preg_match($BlockException, $PathRequest)) {
                    throw new Exception("Block detected - {$BlockException}", $BlockLevel);
                }
            }

            // Never block an admin
            if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
                throw new Exception("Block detected", self::BLOCK_NEVER);
            }

            if (Gdn::session()->isValid()) {
                throw new Exception("Block detected", self::BLOCK_PERMISSION);
            }

        } catch (Exception $e) {
            // BlockLevel
            //  TRUE = Block any time
            //  FALSE = Absolutely no blocking
            //  NULL = Block for permissions (e.g. PrivateCommunity)
            $CanBlock = $e->getCode();
        }

        // If we're in updatemode and arent explicitly prevented from blocking, block
        if (Gdn::config('Garden.UpdateMode', false) && $CanBlock > self::BLOCK_NEVER) {
            $Request->withURI(Gdn::router()->getDestination('UpdateMode'));
        }

        // Analyze the request AFTER checking for update mode.
        $this->analyzeRequest($Request);
        $this->fireEvent('AfterAnalyzeRequest');

        // If we're in update mode and can block, redirect to signin
        if (C('Garden.PrivateCommunity') && $CanBlock > self::BLOCK_PERMISSION) {
            if ($this->_DeliveryType === DELIVERY_TYPE_DATA) {
                safeHeader('HTTP/1.0 401 Unauthorized', true, 401);
                safeHeader('Content-Type: application/json; charset='.c('Garden.Charset', 'utf-8'), true);
                echo json_encode(array('Code' => '401', 'Exception' => t('You must sign in.')));
            } else {
                redirect('/entry/signin?Target='.urlencode($this->Request));
            }
            exit();
        }

        $ControllerName = $this->controllerName();
        if ($ControllerName != '' && class_exists($ControllerName)) {
            // Create it and call the appropriate method/action
            /* @var Gdn_Controller $Controller */
            $Controller = new $ControllerName();
            Gdn::controller($Controller);

            $this->EventArguments['Controller'] =& $Controller;
            $this->fireEvent('AfterControllerCreate');

            // Pass along any assets
            if (is_array($this->_AssetCollection)) {
                foreach ($this->_AssetCollection as $AssetName => $Assets) {
                    foreach ($Assets as $Asset) {
                        $Controller->addAsset($AssetName, $Asset);
                    }
                }
            }

            // Instantiate Imported & Uses classes
            $Controller->getImports();

            // Pass in the syndication method
            $Controller->SyndicationMethod = $this->_SyndicationMethod;

            // Pass along the request
            $Controller->SelfUrl = $this->Request;

            // Pass along any objects
            foreach ($this->_PropertyCollection as $Name => $Mixed) {
                $Controller->$Name = $Mixed;
            }

            // Pass along any data.
            if (is_array($this->_Data)) {
                $Controller->Data = $this->_Data;
            }

            // Set up a default controller method in case one isn't defined.
            $ControllerMethod = str_replace('_', '', $this->ControllerMethod);
            $Controller->OriginalRequestMethod = $ControllerMethod;
            $this->EventArguments['ControllerMethod'] =& $ControllerMethod;

            // Take enabled plugins into account, as well
            $PluginReplacement = Gdn::pluginManager()->hasNewMethod($this->controllerName(), $this->ControllerMethod);
            if (!$PluginReplacement && ($this->ControllerMethod == '' || !method_exists($Controller, $ControllerMethod)) && !$Controller->isInternal($ControllerMethod)) {
                // Check to see if there is an 'x' version of the method.
                if (method_exists($Controller, 'x'.$ControllerMethod)) {
                    // $PluginManagerHasReplacementMethod = TRUE;
                    $ControllerMethod = 'x'.$ControllerMethod;
                } else {
                    if ($this->ControllerMethod != '') {
                        array_unshift($this->_ControllerMethodArgs, $this->ControllerMethod);
                    }

                    $this->ControllerMethod = 'Index';
                    $ControllerMethod = 'Index';

                    $PluginReplacement = Gdn::pluginManager()->hasNewMethod($this->controllerName(), $this->ControllerMethod);
                }
            }

            // Pass in the querystring values
            $Controller->ApplicationFolder = $this->_ApplicationFolder;
            $Controller->Application = $this->enabledApplication();
            $Controller->RequestMethod = $this->ControllerMethod;
            $Controller->RequestArgs = $this->_ControllerMethodArgs;
            $Controller->Request = $Request;
            $Controller->deliveryType($Request->getValue('DeliveryType', $this->_DeliveryType));
            $Controller->deliveryMethod($Request->getValue('DeliveryMethod', $this->_DeliveryMethod));

            // Set special controller method options for REST APIs.
            $Controller->initialize();

            $this->EventArguments['Controller'] = &$Controller;
            $this->fireEvent('AfterControllerInit');

            $ReflectionArguments = $Request->get();
            $this->EventArguments['Arguments'] = &$ReflectionArguments;
            $this->fireEvent('BeforeReflect');

            // Call the requested method on the controller - error out if not defined.
            if ($PluginReplacement) {
                // Reflect the args for the method.
                $Callback = Gdn::pluginManager()->getCallback($Controller->ControllerName, $ControllerMethod);
                // Augment the arguments to the plugin with the sender and these arguments.
                $InputArgs = array_merge(array($Controller), $this->_ControllerMethodArgs, array('Sender' => $Controller, 'Args' => $this->_ControllerMethodArgs));
                $Args = reflectArgs($Callback, $InputArgs, $ReflectionArguments);
                $Controller->ReflectArgs = $Args;

                try {
                    $this->fireEvent('BeforeControllerMethod');
                    Gdn::pluginManager()->callEventHandlers($Controller, $Controller->ControllerName, $ControllerMethod, 'Before');

                    call_user_func_array($Callback, $Args);
                } catch (Exception $Ex) {
                    $Controller->renderException($Ex);
                }
            } elseif (method_exists($Controller, $ControllerMethod) && !$Controller->isInternal($ControllerMethod)) {
                $Args = reflectArgs(array($Controller, $ControllerMethod), $this->_ControllerMethodArgs, $ReflectionArguments);
                $this->_ControllerMethodArgs = $Args;
                $Controller->ReflectArgs = $Args;

                try {
                    $this->fireEvent('BeforeControllerMethod');
                    Gdn::pluginManager()->callEventHandlers($Controller, $Controller->ControllerName, $ControllerMethod, 'Before');

                    call_user_func_array(array($Controller, $ControllerMethod), $Args);
                } catch (Exception $Ex) {
                    $Controller->renderException($Ex);
                    exit();
                }
            } else {
                $this->EventArguments['Handled'] = false;
                $Handled =& $this->EventArguments['Handled'];
                $this->fireEvent('NotFound');

                if (!$Handled) {
                    Gdn::request()->withRoute('Default404');
                    return $this->dispatch();
                } else {
                    return $Handled;
                }
            }
        }
    }

    /**
     *
     *
     * @param string $EnabledApplications
     */
    public function enabledApplicationFolders($EnabledApplications = '') {
        if ($EnabledApplications != '' && count($this->_EnabledApplicationFolders) == 0) {
            $this->_EnabledApplications = $EnabledApplications;
            $this->_EnabledApplicationFolders = array_values($EnabledApplications);
        }
        return $this->_EnabledApplicationFolders;
    }

    /**
     * Returns the name of the enabled application based on $ApplicationFolder.
     *
     * @param string The application folder related to the application name you want to return.
     */
    public function enabledApplication($ApplicationFolder = '') {
        if ($ApplicationFolder == '') {
            $ApplicationFolder = $this->_ApplicationFolder;
        }

        if (strpos($ApplicationFolder, 'plugins/') === 0) {
            $Plugin = StringBeginsWith($ApplicationFolder, 'plugins/', false, true);

            if (array_key_exists($Plugin, Gdn::pluginManager()->availablePlugins())) {
                return $Plugin;
            }

            return false;
        } else {
            foreach (Gdn::applicationManager()->availableApplications() as $ApplicationName => $ApplicationInfo) {
                if (val('Folder', $ApplicationInfo, false) === $ApplicationFolder) {
                    $EnabledApplication = $ApplicationName;
                    $this->EventArguments['EnabledApplication'] = $EnabledApplication;
                    $this->fireEvent('AfterEnabledApplication');
                    return $EnabledApplication;
                }
            }
        }
        return false;
    }

    /**
     * Allows the passing of a string to the controller's asset collection.
     *
     * @param string $AssetName The name of the asset collection to add the string to.
     * @param mixed $Asset The string asset to be added. The asset can be one of two things.
     * - <b>string</b>: The string will be rendered to the page.
     * - <b>Gdn_IModule</b>: The Gdn_IModule::Render() method will be called when the asset is rendered.
     */
    public function passAsset($AssetName, $Asset) {
        $this->_AssetCollection[$AssetName][] = $Asset;
        return $this;
    }

    /**
     *
     *
     * @param $Name
     * @param $Value
     * @return $this
     */
    public function passData($Name, $Value) {
        $this->_Data[$Name] = $Value;
        return $this;
    }

    /**
     * Allows the passing of any variable to the controller as a property.
     *
     * @param string $Name The name of the property to assign the variable to.
     * @param mixed $Mixed The variable to be passed as a property of the controller.
     */
    public function passProperty($Name, $Mixed) {
        $this->_PropertyCollection[$Name] = $Mixed;
        return $this;
    }

    /**
     * Parses the query string looking for supplied request parameters. Places
     * anything useful into this object's Controller properties.
     *
     * @param int $FolderDepth
     */
    protected function analyzeRequest(&$Request) {

        // Here is the basic format of a request:
        // [/application]/controller[/method[.json|.xml]]/argn|argn=valn

        // Here are some examples of what this method could/would receive:
        // /application/controller/method/argn
        // /controller/method/argn
        // /application/controller/argn
        // /controller/argn
        // /controller

        // Clear the slate
        $this->_ApplicationFolder = '';
        $this->ControllerName = '';
        $this->ControllerMethod = 'index';
        $this->_ControllerMethodArgs = array();
        $this->Request = $Request->path(false);

        $PathAndQuery = $Request->PathAndQuery();
        $MatchRoute = Gdn::router()->matchRoute($PathAndQuery);

        // We have a route. Take action.
        if ($MatchRoute !== false) {
            switch ($MatchRoute['Type']) {
                case 'Internal':
                    $Request->pathAndQuery($MatchRoute['FinalDestination']);
                    $this->Request = $Request->path(false);
                    break;

                case 'Temporary':
                    safeHeader("HTTP/1.1 302 Moved Temporarily");
                    safeHeader("Location: ".Url($MatchRoute['FinalDestination']));
                    exit();
                    break;

                case 'Permanent':
                    safeHeader("HTTP/1.1 301 Moved Permanently");
                    safeHeader("Location: ".Url($MatchRoute['FinalDestination']));
                    exit();
                    break;

                case 'NotAuthorized':
                    safeHeader("HTTP/1.1 401 Not Authorized");
                    $this->Request = $MatchRoute['FinalDestination'];
                    break;

                case 'NotFound':
                    safeHeader("HTTP/1.1 404 Not Found");
                    $this->Request = $MatchRoute['FinalDestination'];
                    break;
                case 'Test':
                    $Request->pathAndQuery($MatchRoute['FinalDestination']);
                    $this->Request = $Request->path(false);
                    decho($MatchRoute, 'Route');
                    decho(array(
                        'Path' => $Request->path(),
                        'Get' => $Request->get()
                    ), 'Request');
                    die();
            }
        }

        switch ($Request->outputFormat()) {
            case 'rss':
                $this->_SyndicationMethod = SYNDICATION_RSS;
                $this->_DeliveryMethod = DELIVERY_METHOD_RSS;
                break;
            case 'atom':
                $this->_SyndicationMethod = SYNDICATION_ATOM;
                $this->_DeliveryMethod = DELIVERY_METHOD_RSS;
                break;
            case 'default':
            default:
                $this->_SyndicationMethod = SYNDICATION_NONE;
                break;
        }

        if ($this->Request == '') {
            $DefaultController = Gdn::router()->getRoute('DefaultController');
            $this->Request = $DefaultController['Destination'];
        }

        $Parts = explode('/', str_replace('\\', '/', $this->Request));

        /**
         * The application folder is either the first argument or is not provided. The controller is therefore
         * either the second argument or the first, depending on the result of the previous statement. Check that.
         */
        try {
            // if the 1st argument is a valid application, check if it has a controller matching the 2nd argument
            if (in_array($Parts[0], $this->enabledApplicationFolders())) {
                $this->findController(1, $Parts);
            }

            // if no match, see if the first argument is a controller
            $this->findController(0, $Parts);

            // 3] See if there is a plugin trying to create a root method.
            list($MethodName, $DeliveryMethod) = $this->_splitDeliveryMethod(GetValue(0, $Parts), true);
            if ($MethodName && Gdn::pluginManager()->hasNewMethod('RootController', $MethodName, true)) {
                $this->_DeliveryMethod = $DeliveryMethod;
                $Parts[0] = $MethodName;
                $Parts = array_merge(array('root'), $Parts);
                $this->findController(0, $Parts);
            }

            throw new GdnDispatcherControllerNotFoundException();
        } catch (GdnDispatcherControllerFoundException $e) {
            switch ($this->_DeliveryMethod) {
                case DELIVERY_METHOD_JSON:
                case DELIVERY_METHOD_XML:
                    $this->_DeliveryType = DELIVERY_TYPE_DATA;
                    break;
                case DELIVERY_METHOD_TEXT:
                    $this->_DeliveryType = DELIVERY_TYPE_VIEW;
                    break;
                case DELIVERY_METHOD_XHTML:
                case DELIVERY_METHOD_RSS:
                    break;
                default:
                    $this->_DeliveryMethod = DELIVERY_METHOD_XHTML;
                    break;
            }

            return true;
        } catch (GdnDispatcherControllerNotFoundException $e) {
            $this->EventArguments['Handled'] = false;
            $Handled =& $this->EventArguments['Handled'];
            $this->fireEvent('NotFound');

            if (!$Handled) {
                safeHeader("HTTP/1.1 404 Not Found");
                $Request->withRoute('Default404');
                return $this->analyzeRequest($Request);
            }
        }
    }

    /**
     *
     *
     * @param $ControllerKey
     * @param $Parts
     * @return bool
     * @throws Exception
     * @throws GdnDispatcherControllerFoundException
     */
    protected function findController($ControllerKey, $Parts) {
        $Controller = val($ControllerKey, $Parts, null);
        $Controller = ucfirst(strtolower($Controller));
        $Application = val($ControllerKey - 1, $Parts, null);

        // Check for a file extension on the controller.
        list($Controller, $this->_DeliveryMethod) = $this->_splitDeliveryMethod($Controller, false);

        // If we're loading from a fully qualified path, prioritize this app's library
        if (!is_null($Application)) {
            Gdn_Autoloader::priority(
                Gdn_Autoloader::CONTEXT_APPLICATION,
                $Application,
                Gdn_Autoloader::MAP_CONTROLLER,
                Gdn_Autoloader::PRIORITY_TYPE_RESTRICT,
                Gdn_Autoloader::PRIORITY_ONCE
            );
        }

        $ControllerName = $Controller.'Controller';
        $ControllerPath = Gdn_Autoloader::lookup($ControllerName, array('MapType' => null));

        try {
            // If the lookup succeeded, good to go
            if (class_exists($ControllerName, false)) {
                throw new GdnDispatcherControllerFoundException();
            }

        } catch (GdnDispatcherControllerFoundException $Ex) {
            // This was a guess search with no specified application. Look up
            // the application folder from the controller path.
            if (is_null($Application)) {
                if (!$ControllerPath && class_exists($ControllerName, false)) {
                    $Reflect = new ReflectionClass($ControllerName);
                    $Found = false;
                    do {
                        $ControllerPath = $Reflect->getFilename();
                        $Found = (bool)preg_match('`\/controllers\/`i', $ControllerPath);
                        if (!$Found) {
                            $Reflect = $Reflect->getParentClass();
                        }
                    } while (!$Found && $Reflect);
                    if (!$Found) {
                        return false;
                    }
                }

                if ($ControllerPath) {
                    $InterimPath = explode('/controllers/', $ControllerPath);
                    array_pop($InterimPath); // Get rid of the end. Useless;
                    $InterimPath = explode('/', trim(array_pop($InterimPath)));
                    $Application = array_pop($InterimPath);
                    $AddonType = array_pop($InterimPath);
                    switch ($AddonType) {
                        case 'plugins':
                            if (!in_array($Application, Gdn::pluginManager()->enabledPluginFolders())) {
                                return false;
                            }
                            $Application = 'plugins/'.$Application;
                            break;
                        case 'applications':
                            if (!in_array($Application, $this->enabledApplicationFolders())) {
                                return false;
                            }
                            break;
                        default:
                            return false;
                    }


                } else {
                    return false;
                }
            }

            // If we need to autoload the class, do it here
            if (!class_exists($ControllerName, false)) {
                Gdn_Autoloader::priority(
                    Gdn_Autoloader::CONTEXT_APPLICATION,
                    $Application,
                    Gdn_Autoloader::MAP_CONTROLLER,
                    Gdn_Autoloader::PRIORITY_TYPE_PREFER,
                    Gdn_Autoloader::PRIORITY_PERSIST
                );

                require_once($ControllerPath);
            }

            $this->ControllerName = $Controller;
            $this->_ApplicationFolder = (is_null($Application) ? '' : $Application);

            $Length = sizeof($Parts);
            if ($Length > $ControllerKey + 1) {
                list($this->ControllerMethod, $this->_DeliveryMethod) = $this->_splitDeliveryMethod($Parts[$ControllerKey + 1], false);
            }

            if ($Length > $ControllerKey + 2) {
                for ($i = $ControllerKey + 2; $i < $Length; ++$i) {
                    if ($Parts[$i] != '') {
                        $this->_ControllerMethodArgs[] = $Parts[$i];
                    }
                }
            }

            throw $Ex;
        }

        return false;
    }

    /**
     * An internal method used to map parts of the request to various properties
     * of this object that represent the controller, controller method, and
     * controller method arguments.
     *
     * @param array $Parts An array of parts of the request.
     * @param int $ControllerKey An integer representing the key of the controller in the $Parts array.
     */
    private function _mapParts($Parts, $ControllerKey) {
        $Length = count($Parts);
        if ($Length > $ControllerKey) {
            $this->ControllerName = ucfirst(strtolower($Parts[$ControllerKey]));
        }

        if ($Length > $ControllerKey + 1) {
            list($this->ControllerMethod, $this->_DeliveryMethod) = $this->_splitDeliveryMethod($Parts[$ControllerKey + 1]);
        }

        if ($Length > $ControllerKey + 2) {
            for ($i = $ControllerKey + 2; $i < $Length; ++$i) {
                if ($Parts[$i] != '') {
                    $this->_ControllerMethodArgs[] = $Parts[$i];
                }
            }
        }
    }

    protected function _reflectControllerArgs($Controller) {
        // Reflect the controller arguments based on the get.
        if (count($Controller->Request->get()) == 0) {
            return;
        }

        if (!method_exists($Controller, $this->ControllerMethod)) {
            return;
        }

        $Meth = new ReflectionMethod($Controller, $this->ControllerMethod);
        $MethArgs = $Meth->getParameters();
        $Args = array();
        $Get = array_change_key_case($Controller->Request->get());
        $MissingArgs = array();

        if (count($MethArgs) == 0) {
            // The method has no arguments so just pass all of the arguments in.
            return;
        }

        // Set all of the parameters.
        foreach ($MethArgs as $Index => $MethParam) {
            $ParamName = strtolower($MethParam->getName());

            if (isset($this->_ControllerMethodArgs[$Index])) {
                $Args[] = $this->_ControllerMethodArgs[$Index];
            } elseif (isset($Get[$ParamName]))
                $Args[] = $Get[$ParamName];
            elseif ($MethParam->isDefaultValueAvailable())
                $Args[] = $MethParam->getDefaultValue();
            else {
                $Args[] = null;
                $MissingArgs[] = "{$Index}: {$ParamName}";
            }
        }

        $this->_ControllerMethodArgs = $Args;

    }

    /**
     * Parses methods that may be using dot-syntax to express a delivery type
     *
     * For example, /controller/method.json
     * method.json should be split up and return array('method', 'JSON')
     *
     * @param type $Name Name of method to search for forced delivery types
     * @param type $AllowAll Whether to allow delivery types that don't exist
     * @return type
     */
    protected function _splitDeliveryMethod($Name, $AllowAll = false) {
        $Parts = explode('.', $Name);
        if (count($Parts) >= 2) {
            $DeliveryPart = array_pop($Parts);
            $MethodPart = implode('.', $Parts);

            if ($AllowAll || in_array(strtoupper($DeliveryPart), array(DELIVERY_METHOD_JSON, DELIVERY_METHOD_XHTML, DELIVERY_METHOD_XML, DELIVERY_METHOD_TEXT, DELIVERY_METHOD_RSS))) {
                return array($MethodPart, strtoupper($DeliveryPart));
            } else {
                return array($Name, $this->_DeliveryMethod);
            }
        } else {
            return array($Name, $this->_DeliveryMethod);
        }
    }
}

/**
 * Class GdnDispatcherControllerNotFoundException
 */
class GdnDispatcherControllerNotFoundException extends Exception {
}

/**
 * Class GdnDispatcherControllerFoundException
 */
class GdnDispatcherControllerFoundException extends Exception {
}
