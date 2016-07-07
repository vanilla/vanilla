<?php
/**
 * Framework dispatcher.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */
use Vanilla\Addon;
use Vanilla\AddonManager;

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

    /** @var string The name of the controller to be dispatched. */
    public $ControllerName;

    /** @var stringThe method of the controller to be called. */
    public $ControllerMethod;

    /**
     * @var array An array of folders within the application that are OK to search through
     * for controllers. This property is filled by the applications array
     * located in /conf/applications.php and included in /bootstrap.php
     */
    private $enabledApplicationFolders;

    /**
     * @var array An associative array of ApplicationName => ApplicationFolder. This
     * property is filled by the applications array located in
     * /conf/applications.php and included in /bootstrap.php
     */
    private $enabledApplications;

    /** @var string The name of the application folder that contains the controller that has been requested. */
    private $applicationFolder;

    /**
     * @var array An associative collection of AssetName => Strings that will get passed
     * into the controller once it has been instantiated.
     */
    private $controllerAssets;

    /**
     * @var array Data to pass along to the controller.
     */
    private $controllerData;

    /** @var array Any query string arguments supplied to the controller method. */
    private $controllerMethodArgs = [];

    /** @var string|false The delivery method to set on the controller. */
    private $deliveryMethod = false;

    /** @var string|false The delivery type to set on the controller. */
    private $deliveryType = false;

    /**
     * @var array An associative collection of variables that will get passed into the
     * controller as properties once it has been instantiated.
     */
    private $controllerProperties;

    /** @var string Defined by the url of the request: SYNDICATION_RSS, SYNDICATION_ATOM, or SYNDICATION_NONE (default). */
    private $syndicationMethod;

    /**
     * @var AddonManager $addonManager The addon manager that manages all of the addons.
     */
    private $addonManager;

    /** @var bool */
    private $isHomepage = false;

    /**
     * Class constructor.
     */
    public function __construct(AddonManager $addonManager = null) {
        parent::__construct();
        $this->enabledApplicationFolders = null;
        $this->applicationFolder = '';
        $this->controllerAssets = [];
        $this->ControllerName = '';
        $this->ControllerMethod = '';
        $this->controllerMethodArgs = [];
        $this->controllerProperties = [];
        $this->controllerData = [];
        $this->addonManager = $addonManager;
    }

    /**
     * Backwards compatible support for deprecated properties.
     *
     * @param string $name The name of the property to get.
     * @return mixed Returns the property value.
     */
    public function __get($name) {
        switch (strtolower($name)) {
            case 'request':
                deprecated('Gdn_Dispatcher->Request', 'Gdn::request()->path()');
                return Gdn::request()->path();
        }
    }

    /**
     *
     *
     * @throws Exception
     */
    public function cleanup() {
        $this->fireEvent('Cleanup');
    }

    public function application() {
        return $this->applicationFolder;
    }

    public function controller() {
        return $this->ControllerName;
    }

    public function controllerMethod() {
        return $this->ControllerMethod;
    }

    public function controllerArguments($Value = null) {
        if ($Value !== null) {
            $this->controllerMethodArgs = $Value;
        }
        return $this->controllerMethodArgs;
    }

    public function start() {
        $this->fireEvent('AppStartup');

        // Register callback allowing addons to modify response headers before PHP sends them.
        header_register_callback(function() {
            $this->fireEvent('SendHeaders');
        });
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

        $this->EventArguments['Request'] = &$Request;

        // Move this up to allow pre-routing
        $this->fireEvent('BeforeDispatch');

        // If we're in update mode and aren't explicitly prevented from blocking, block.
        if (Gdn::config('Garden.UpdateMode', false) && $this->getCanBlock($Request) > self::BLOCK_NEVER) {
            $Request->withURI(Gdn::router()->getDestination('UpdateMode'));
        }

        // Analyze the request AFTER checking for update mode.
        $this->analyzeRequest($Request);
        $this->fireEvent('AfterAnalyzeRequest');

        // If we're in update mode and can block, redirect to signin
        if (c('Garden.PrivateCommunity') && $this->getCanBlock($Request) > self::BLOCK_PERMISSION) {
            if ($this->deliveryType === DELIVERY_TYPE_DATA) {
                safeHeader('HTTP/1.0 401 Unauthorized', true, 401);
                safeHeader('Content-Type: application/json; charset=utf-8', true);
                echo json_encode(array('Code' => '401', 'Exception' => t('You must sign in.')));
            } else {
                redirect('/entry/signin?Target='.urlencode($Request->pathAndQuery()));
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
            if (is_array($this->controllerAssets)) {
                foreach ($this->controllerAssets as $AssetName => $Assets) {
                    foreach ($Assets as $Asset) {
                        $Controller->addAsset($AssetName, $Asset);
                    }
                }
            }

            // Instantiate Imported & Uses classes
            $Controller->getImports();

            // Pass in the syndication method
            $Controller->SyndicationMethod = $this->syndicationMethod;

            // Pass along the request
            $Controller->SelfUrl = $Request->path();

            // Pass along any objects
            foreach ($this->controllerProperties as $Name => $Mixed) {
                $Controller->$Name = $Mixed;
            }

            // Pass along any data.
            if (is_array($this->controllerData)) {
                $Controller->Data = $this->controllerData;
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
                        array_unshift($this->controllerMethodArgs, $this->ControllerMethod);
                    }

                    $this->ControllerMethod = 'Index';
                    $ControllerMethod = 'Index';

                    $PluginReplacement = Gdn::pluginManager()->hasNewMethod($this->controllerName(), $this->ControllerMethod);
                }
            }

            // Pass in the querystring values
            $Controller->ApplicationFolder = $this->applicationFolder;
            $Controller->Application = $this->enabledApplication();
            $Controller->RequestMethod = $this->ControllerMethod;
            $Controller->RequestArgs = $this->controllerMethodArgs;
            $Controller->Request = $Request;
            $Controller->deliveryType($Request->getValue('DeliveryType', $this->deliveryType));
            $Controller->deliveryMethod($Request->getValue('DeliveryMethod', $this->deliveryMethod));

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
                $InputArgs = array_merge(array($Controller), $this->controllerMethodArgs, array('Sender' => $Controller, 'Args' => $this->controllerMethodArgs));
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
                $Args = reflectArgs(array($Controller, $ControllerMethod), $this->controllerMethodArgs, $ReflectionArguments);
                $this->controllerMethodArgs = $Args;
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
     * Parses the query string looking for supplied request parameters.
     *
     * Places anything useful into this object's Controller properties.
     *
     * @param Gdn_Request $request The request to analyze.
     */
    private function analyzeRequest($request) {

        // Here is the basic format of a request:
        // [/application]/controller[/method[.json|.xml]]/argn|argn=valn

        // Here are some examples of what this method could/would receive:
        // /application/controller/method/argn
        // /controller/method/argn
        // /application/controller/argn
        // /controller/argn
        // /controller

        // Clear the slate
        $this->applicationFolder = '';
        $this->ControllerName = '';
        $this->ControllerMethod = 'index';
        $this->controllerMethodArgs = [];

        $this->rewriteRequest($request);

        switch ($request->outputFormat()) {
            case 'rss':
                $this->syndicationMethod = SYNDICATION_RSS;
                $this->deliveryMethod = DELIVERY_METHOD_RSS;
                break;
            case 'atom':
                $this->syndicationMethod = SYNDICATION_ATOM;
                $this->deliveryMethod = DELIVERY_METHOD_RSS;
                break;
            case 'default':
            default:
                $this->syndicationMethod = SYNDICATION_NONE;
                break;
        }

        if (in_array($request->path(), ['', '/'])) {
            $this->isHomepage = true;
            $defaultController = Gdn::router()->getRoute('DefaultController');
            $request->pathAndQuery($defaultController['Destination']);
        }

        $parts = explode('/', str_replace('\\', '/', $request->path()));

        // We need to save this state now because it's lost after this method.
        $this->passData('isHomepage', $this->isHomepage);

        /**
         * The application folder is either the first argument or is not provided. The controller is therefore
         * either the second argument or the first, depending on the result of the previous statement. Check that.
         */
        try {
            // if the 1st argument is a valid application, check if it has a controller matching the 2nd argument
            if (in_array($parts[0], $this->getEnabledApplicationFolders())) {
                $this->findController(1, $parts);
            }

            // if no match, see if the first argument is a controller
            $this->findController(0, $parts);

            // 3] See if there is a plugin trying to create a root method.
            list($MethodName, $DeliveryMethod) = $this->_splitDeliveryMethod(val(0, $parts), true);
            if ($MethodName && Gdn::pluginManager()->hasNewMethod('RootController', $MethodName, true)) {
                $this->deliveryMethod = $DeliveryMethod;
                $parts[0] = $MethodName;
                $parts = array_merge(array('root'), $parts);
                $this->findController(0, $parts);
            }

            throw new GdnDispatcherControllerNotFoundException();
        } catch (GdnDispatcherControllerFoundException $e) {
            switch ($this->deliveryMethod) {
                case DELIVERY_METHOD_JSON:
                case DELIVERY_METHOD_XML:
                    $this->deliveryType = DELIVERY_TYPE_DATA;
                    break;
                case DELIVERY_METHOD_TEXT:
                    $this->deliveryType = DELIVERY_TYPE_VIEW;
                    break;
                case DELIVERY_METHOD_XHTML:
                case DELIVERY_METHOD_RSS:
                    break;
                default:
                    $this->deliveryMethod = DELIVERY_METHOD_XHTML;
                    break;
            }

            return true;
        } catch (GdnDispatcherControllerNotFoundException $e) {
            $this->EventArguments['Handled'] = false;
            $Handled =& $this->EventArguments['Handled'];
            $this->fireEvent('NotFound');

            if (!$Handled) {
                safeHeader("HTTP/1.1 404 Not Found");
                $request->withRoute('Default404');
                return $this->analyzeRequest($request);
            }
        }
    }

    /**
     *
     *
     * @param string $EnabledApplications
     * @deprecated
     */
    public function enabledApplicationFolders($EnabledApplications = '') {
        deprecated('Gdn_Dispatcher->enabledApplicationFolders()');
        if ($EnabledApplications != '' && count($this->enabledApplicationFolders) == 0) {
            $this->enabledApplications = $EnabledApplications;
            $this->enabledApplicationFolders = array_values($EnabledApplications);
        }
        return $this->enabledApplicationFolders ?: [];
    }

    /**
     * Get the enabled application folders.
     *
     * This is a temporary refactor of the the {@link enabledApplicationFolders()} method and will be removed once
     * support for application prefixes has been removed from the site. Please leave this method as private and don't
     * call it unless you know what you're doing.
     *
     * @return array An array of application folders.
     */
    private function getEnabledApplicationFolders() {
        if (!isset($this->enabledApplicationFolders)) {
            $addons = $this->addonManager->getEnabled();
            $applications = array_filter($addons, Addon::makeFilterCallback(['oldType' => 'application']));

            $result = ['dashboard'];
            /* @var Addon $application */
            foreach ($applications as $application) {
                $result[] = $application->getKey();
            }
            $this->enabledApplicationFolders = array_unique($result);
        }
        return $this->enabledApplicationFolders;
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
        list($Controller, $this->deliveryMethod) = $this->_splitDeliveryMethod($Controller, false);

        // This is a kludge until we can refactor settings controllers better.
        if (strcasecmp($Controller, 'settings') === 0 && strcasecmp($Application, 'dashboard') !== 0) {
            $Controller = $Application.$Controller;
        }

        $ControllerName = $Controller.'Controller';

        try {
            // If the lookup succeeded, good to go
            if (class_exists($ControllerName, true)) {
                throw new GdnDispatcherControllerFoundException();
            }

        } catch (GdnDispatcherControllerFoundException $Ex) {
            // This was a guess search with no specified application. Look up
            // the application folder from the controller path.
            if (is_null($Application)) {
                if (class_exists($ControllerName, false)) {
                    $Reflect = new ReflectionClass($ControllerName);
                    $Found = false;
                    do {
                        $ControllerPath = $Reflect->getFilename();
                        $escapedSeparator = str_replace('\\', '\\\\', DS);
                        $regex = '`'.$escapedSeparator.'controllers'.$escapedSeparator.'`i';
                        $Found = (bool)preg_match($regex, $ControllerPath);
                        if (!$Found) {
                            $Reflect = $Reflect->getParentClass();
                        }
                    } while (!$Found && $Reflect);
                    if (!$Found) {
                        return false;
                    }
                }

                if ($ControllerPath) {
                    $InterimPath = explode(DS.'controllers'.DS, $ControllerPath);
                    array_pop($InterimPath); // Get rid of the end. Useless;
                    $InterimPath = explode(DS, trim(array_pop($InterimPath)));
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
                            if (!in_array($Application, $this->getEnabledApplicationFolders())) {
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

            $this->ControllerName = $Controller;
            $this->applicationFolder = (is_null($Application) ? '' : $Application);

            $Length = sizeof($Parts);
            if ($Length > $ControllerKey + 1) {
                list($this->ControllerMethod, $this->deliveryMethod) = $this->_splitDeliveryMethod($Parts[$ControllerKey + 1], false);
            }

            if ($Length > $ControllerKey + 2) {
                for ($i = $ControllerKey + 2; $i < $Length; ++$i) {
                    if ($Parts[$i] != '') {
                        $this->controllerMethodArgs[] = $Parts[$i];
                    }
                }
            }

            throw $Ex;
        }

        return false;
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
                return array($Name, $this->deliveryMethod);
            }
        } else {
            return array($Name, $this->deliveryMethod);
        }
    }

    /**
     * Return the properly formatted controller class name.
     */
    public function controllerName() {
        return $this->ControllerName.'Controller';
    }

    /**
     * Returns the name of the enabled application based on $ApplicationFolder.
     *
     * @param string The application folder related to the application name you want to return.
     */
    public function enabledApplication($folder = '') {
        if ($folder == '') {
            $folder = $this->applicationFolder;
        }

        if (strpos($folder, 'plugins/') === 0) {
            $plugin = StringBeginsWith($folder, 'plugins/', false, true);

            if (array_key_exists($plugin, $this->addonManager->getEnabled())) {
                return $plugin;
            }

            return false;
        } else {
            $addon = $this->addonManager->lookupAddon($folder);
            if ($addon) {
                return $addon->getRawKey();
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
        $this->controllerAssets[$AssetName][] = $Asset;
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
        $this->controllerData[$Name] = $Value;
        return $this;
    }

    /**
     * Allows the passing of any variable to the controller as a property.
     *
     * @param string $Name The name of the property to assign the variable to.
     * @param mixed $Mixed The variable to be passed as a property of the controller.
     */
    public function passProperty($Name, $Mixed) {
        $this->controllerProperties[$Name] = $Mixed;
        return $this;
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

            if (isset($this->controllerMethodArgs[$Index])) {
                $Args[] = $this->controllerMethodArgs[$Index];
            } elseif (isset($Get[$ParamName]))
                $Args[] = $Get[$ParamName];
            elseif ($MethParam->isDefaultValueAvailable())
                $Args[] = $MethParam->getDefaultValue();
            else {
                $Args[] = null;
                $MissingArgs[] = "{$Index}: {$ParamName}";
            }
        }

        $this->controllerMethodArgs = $Args;

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
            list($this->ControllerMethod, $this->deliveryMethod) = $this->_splitDeliveryMethod($Parts[$ControllerKey + 1]);
        }

        if ($Length > $ControllerKey + 2) {
            for ($i = $ControllerKey + 2; $i < $Length; ++$i) {
                if ($Parts[$i] != '') {
                    $this->controllerMethodArgs[] = $Parts[$i];
                }
            }
        }
    }

    /**
     * Figure out what kind of blocks are allowed on dispatches.
     *
     * @param Gdn_Request $request The current request being inspected.
     * @return int Returns one of the **Gdn_Dispatcher::BLOCK_*** constants.
     */
    private function getCanBlock($request) {
        $canBlock = self::BLOCK_ANY;

        $blockExceptions = array(
            '/^utility(\/.*)?$/' => self::BLOCK_NEVER,
            '/^asset(\/.*)?$/' => self::BLOCK_NEVER,
            '/^home\/error(\/.*)?/' => self::BLOCK_NEVER,
            '/^home\/leave(\/.*)?/' => self::BLOCK_NEVER,
            '/^plugin(\/.*)?$/' => self::BLOCK_NEVER,
            '/^sso(\/.*)?$/' => self::BLOCK_NEVER,
            '/^discussions\/getcommentcounts/' => self::BLOCK_NEVER,
            '/^entry(\/.*)?$/' => self::BLOCK_PERMISSION,
            '/^user\/usernameavailable(\/.*)?$/' => self::BLOCK_PERMISSION,
            '/^user\/emailavailable(\/.*)?$/' => self::BLOCK_PERMISSION,
            '/^home\/termsofservice(\/.*)?$/' => self::BLOCK_PERMISSION
        );

        $this->EventArguments['BlockExceptions'] = &$blockExceptions;
        $this->fireEvent('BeforeBlockDetect');

        $PathRequest = $request->path();
        foreach ($blockExceptions as $BlockException => $BlockLevel) {
            if (preg_match($BlockException, $PathRequest)) {
                Logger::debug(
                    "Dispatcher block: {blockException}, {blockLevel}",
                    ['blockException' => $BlockException, 'blockLevel' => $BlockLevel]
                );
                return $BlockLevel;
            }
        }

        // Never block an admin.
        if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            Logger::debug(
                "Dispatcher block: {blockException}, {blockLevel}",
                ['blockException' => 'admin', 'blockLevel' => self::BLOCK_NEVER]
            );
            return self::BLOCK_NEVER;
        }

        if (Gdn::session()->isValid()) {
            Logger::debug(
                "Dispatcher block: {blockException}, {blockLevel}",
                ['blockException' => 'signed_in', 'blockLevel' => self::BLOCK_PERMISSION]
            );
            return self::BLOCK_PERMISSION;
        }

        return $canBlock;
    }

    /**
     * Rewrite the request based on rewrite rules (currently called routes in Vanilla).
     *
     * This method modifies the passed {@link $request} object. It can also cause a redirect if a rule matches that
     * specifies a redirect.
     *
     * @param Gdn_Request $request The request to rewrite.
     */
    private function rewriteRequest($request) {
        $pathAndQuery = $request->PathAndQuery();
        $matchRoute = Gdn::router()->matchRoute($pathAndQuery);

        // We have a route. Take action.
        if (!empty($matchRoute)) {
            $request->pathAndQuery($matchRoute['FinalDestination']);

            switch ($matchRoute['Type']) {
                case 'Internal':
                    // Do nothing. The request has been rewritten.
                    break;
                case 'Temporary':
                    safeHeader("HTTP/1.1 302 Moved Temporarily");
                    safeHeader("Location: ".url($matchRoute['FinalDestination']));
                    exit();
                    break;

                case 'Permanent':
                    safeHeader("HTTP/1.1 301 Moved Permanently");
                    safeHeader("Location: ".url($matchRoute['FinalDestination']));
                    exit();
                    break;

                case 'NotAuthorized':
                    safeHeader("HTTP/1.1 401 Not Authorized");

                    break;

                case 'NotFound':
                    safeHeader("HTTP/1.1 404 Not Found");
                    break;

                case 'Drop':
                    die();

                case 'Test':
                    decho($matchRoute, 'Route');
                    decho(array(
                        'Path' => $request->path(),
                        'Get' => $request->get()
                    ), 'Request');
                    die();
            }
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
