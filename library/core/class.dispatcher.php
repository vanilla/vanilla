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
    private $controllerMethodArgs;

    /** @var string|false The delivery method to set on the controller. */
    private $deliveryMethod;

    /** @var string|false The delivery type to set on the controller. */
    private $deliveryType;

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
    private $isHomepage;

    /**
     * Class constructor.
     */
    public function __construct(AddonManager $addonManager = null) {
        parent::__construct();
        $this->addonManager = $addonManager;
        $this->reset();
    }

    /**
     * Reset the dispatchert to its default state.
     */
    public function reset() {
        $this->enabledApplicationFolders = null;
        $this->applicationFolder = '';
        $this->controllerAssets = [];
        $this->ControllerName = '';
        $this->ControllerMethod = '';
        $this->controllerMethodArgs = [];
        $this->controllerProperties = [];
        $this->controllerData = [];
        $this->deliveryType = DELIVERY_TYPE_ALL;
        $this->deliveryMethod = DELIVERY_METHOD_XHTML;
        $this->isHomepage = false;
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
        header_register_callback(function () {
            $this->fireEvent('SendHeaders');
        });
    }

    /**
     * Dispatch a request to a controller method.
     *
     * This method analyzes a request and figures out which controller and method it maps to. It also instantiates the
     * controller and calls the method.
     *
     * @param string|Gdn_Request|null $ImportRequest The request to dispatch. This can be a string URL or a Gdn_Request object,
     * @param bool $Permanent Whether or not to set {@link Gdn::request()} with the dispatched request.
     */
    public function dispatch($ImportRequest = null, $Permanent = true) {

        if ($ImportRequest && is_string($ImportRequest)) {
            $ImportRequest = Gdn_Request::create()->fromEnvironment()->withURI($ImportRequest);
        }

        if (is_a($ImportRequest, 'Gdn_Request') && $Permanent) {
            Gdn::request($ImportRequest);
        }

        $request = is_a($ImportRequest, 'Gdn_Request') ? $ImportRequest : Gdn::request();

        $this->EventArguments['Request'] = $request;

        // Move this up to allow pre-routing
        $this->fireEvent('BeforeDispatch');

        // If we're in update mode and aren't explicitly prevented from blocking, block.
        if (Gdn::config('Garden.UpdateMode', false) && $this->getCanBlock($request) > self::BLOCK_NEVER) {
            $request->withURI(Gdn::router()->getDestination('UpdateMode'));
        }

        // Check for URL rewrites.
        $request = $this->rewriteRequest($request);

        // We need to save this state now because it's lost after this method.
        $this->passData('isHomepage', $this->isHomepage);

        // Let plugins change augment the request before dispatch, but after internal routing.
        $this->fireEvent('BeforeAnalyzeRequest');

        // If we're in a private community and can block, redirect to signin
        if (c('Garden.PrivateCommunity') && $this->getCanBlock($request) > self::BLOCK_PERMISSION) {
            if ($this->deliveryType === DELIVERY_TYPE_DATA) {
                safeHeader('HTTP/1.0 401 Unauthorized', true, 401);
                safeHeader('Content-Type: application/json; charset=utf-8', true);
                echo json_encode(array('Code' => '401', 'Exception' => t('You must sign in.')));
            } else {
                redirect('/entry/signin?Target='.urlencode($request->pathAndQuery()));
            }
            exit();
        }

        // Analyze the request AFTER checking for update mode.
        $routeArgs = $this->analyzeRequest($request);
        $this->fireEvent('AfterAnalyzeRequest');

        // Now that the controller has been found, dispatch to a method on it.
        $this->dispatchController($request, $routeArgs);
    }

    /*
     * Dispatch a 404 with an event that can be handled.
     *
     * @param string $reason A developer-readable reason code to aid debugging.
     * @param Gdn_Request|null The request object to rewrite to the 404.
     */
    private function dispatchNotFound($reason = 'notfound', $request = null) {
        if (!$request) {
            $request = Gdn::request();
        }
        $this->EventArguments['Handled'] = false;
        $handled =& $this->EventArguments['Handled'];
        $this->fireEvent('NotFound');

        if (!$handled) {
            $request->withRoute('Default404');
            return $this->passData('Reason', $reason)->dispatch($request, true);
        } else {
            return $handled;
        }
    }

    /**
     * A helper function to search through the get/post for a key.
     *
     * The get array is expected to have lowercase keys while the post array can be unaltered.
     *
     * @param string $key The key to search for.
     * @param array $get The get array.
     * @param array $post The post array.
     * @param mixed $default The default value to get if the key does not exist.
     * @return mixed Returns the value in one of the collections or {@link $default}.
     */
    private function requestVal($key, $get, $post, $default = null) {
        $keys = [$key, lcfirst($key), strtolower($key)];

        if (isset($get[$keys[2]])) {
            return $get[$keys[2]];
        }

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                return $post[$key];
            }
        }

        return $default;
    }

    /**
     * Parses the query string looking for supplied request parameters.
     *
     * Places anything useful into this object's Controller properties.
     *
     * @param Gdn_Request $request The request to analyze.
     */
    private function analyzeRequest($request) {
        // Initialize the result of our request.
        $result = [
            'method' => $request->requestMethod(),
            'path' => $request->path(),
            'addon' => null,
            'controller' => '',
            'controllerMethod' => '',
            'pathArgs' => [],
            'query' => array_change_key_case($request->get()),
            'post' => $request->post()
        ];

        // Here is the basic format of a request:
        // [/application]/controller[/method[.json|.xml]]/argn|argn=valn

        // Here are some examples of what this method could/would receive:
        // /application/controller/method/argn
        // /controller/method/argn
        // /application/controller/argn
        // /controller/argn
        // /controller

        $parts = explode('/', str_replace('\\', '/', $request->path()));

        // Parse the file extension.
        list($parts, $deliveryMethod) = $this->parseDeliveryMethod($parts);

        // Set some special properties based on the deliver method.
        $deliveryType = $this->deliveryType;
        switch ($deliveryMethod) {
            case DELIVERY_METHOD_JSON:
            case DELIVERY_METHOD_XML:
                $deliveryType = DELIVERY_TYPE_DATA;
                break;
            case DELIVERY_METHOD_ATOM:
            case DELIVERY_METHOD_RSS:
                $result['syndicationMethod'] = DELIVERY_METHOD_RSS; //$deliveryMethod;
                break;
            case DELIVERY_METHOD_TEXT:
                $deliveryType = DELIVERY_TYPE_VIEW;
                break;
        }
        // An explicitly passed delivery type/method overrides the default.
        $result['deliveryMethod'] = self::requestVal('DeliveryMethod', $result['query'], $result['post'], $deliveryMethod ?: $this->deliveryMethod);
        $result['deliveryType'] = self::requestVal('DeliveryType', $result['query'], $result['post'], $deliveryType);

        // Figure out the controller.
        list($controllerName, $pathArgs) = $this->findController($parts);
        $result['pathArgs'] = $pathArgs;

        if ($controllerName) {
            // The controller was found based on the path.
            $result['controller'] = $controllerName;
        } elseif (Gdn::pluginManager()->hasNewMethod('RootController', val(0, $parts))) {
            // There is a plugin defining a new root method.
            $result['controller'] = 'RootController';
        } else {
            // No controller was found, fire a not found event.
            // TODO: Move this outside this method.
            $this->EventArguments['Handled'] = false;
            $Handled =& $this->EventArguments['Handled'];
            $this->fireEvent('NotFound');

            if (!$Handled) {
                safeHeader("HTTP/1.1 404 Not Found");
                return $this
                    ->passData('Reason', 'controller_notfound')
                    ->analyzeRequest($request->withRoute('Default404'));
            }
            return;
        }

        // A controller has been found. Find the addon that manages it.
        $addon = Gdn::addonManager()->lookupByClassname($result['controller']);

        // The result should be properly set now. Set the legacy properties though.
        if ($addon) {
            $result['addon'] = $addon;
            $this->applicationFolder = stringBeginsWith($addon->getSubdir(), 'applications/', true, true);
        }
        $this->ControllerName = $result['controller'];
        $this->controllerMethodArgs = [];
        $this->syndicationMethod = val('syndicationMethod', $result, SYNDICATION_NONE);
        $this->deliveryMethod = $result['deliveryMethod'];

        return $result;
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
     * Find the controller object corresponding to a request path.
     *
     * @param array $parts The path parts in lowercase.
     * @return array Returns an array in the form `[$controllerName, $parts]` where `$parts` is the remaining path parts.
     * If a controller cannot be found then an array in the form of `['', $parts]` is returned.
     */
    private function findController($parts) {
        // Look for the old-school application name as the first part of the path.
        if (in_array(val(0, $parts), $this->getEnabledApplicationFolders())) {
            $application = array_shift($parts);
        } else {
            $application = '';
        }
        $controller = ucfirst(reset($parts));

        // This is a kludge until we can refactor- settings controllers better.
        if ($controller === 'Settings' && $application !== 'dashboard') {
            $controller = ucfirst($application).$controller;
        }

        $controllerName = $controller.'Controller';

        // If the lookup succeeded, good to go
        if (class_exists($controllerName, true)) {
            array_shift($parts);
            return [$controllerName, $parts];
        } elseif (!empty($application) && class_exists($application.'Controller', true)) {
            // There is a controller with the same name as the application so use it.
            return [ucfirst($application).'Controller', $parts];
        } else {
            return ['', $parts];
        }
    }

    /**
     * Find the method to call on a controller, based on a path.
     *
     * @param Gdn_Controller $controller The controller or name of the controller class to look at.
     * @param string[] $pathArgs An array of path arguments.
     * @return array Returns an array in the form `[$methodName, $pathArgs]`.
     * If the method is not found then an empty string is returned for the method name.
     */
    private function findControllerMethod($controller, $pathArgs) {
        if ($this->methodExists($controller, reset($pathArgs))) {
            return [array_shift($pathArgs), $pathArgs];
        } elseif ($this->methodExists($controller, 'x'.reset($pathArgs))) {
            $method = array_shift($pathArgs);
            deprecated(get_class($controller)."->x$method", get_class($controller)."->$method");
            return ['x'.$method, $pathArgs];
        } elseif ($this->methodExists($controller, 'index')) {
            // "index" is the default controller method if an explicit method cannot be found.
            return ['index', $pathArgs];
        } else {
            return ['', $pathArgs];
        }
    }

    /**
     * Check to see if a controller has a method.
     *
     * @param string|Gdn_Controller $object The name of the controller class or the controller itself.
     * @param string $method The name of the method.
     * @return bool Returns **true** if the controller has the method or there is a plugin that creates the method or **false** otherwise.
     */
    private function methodExists($object, $method) {
        $class = is_string($object) ? $object : get_class($object);

        if (empty($method)) {
            return false;
        } elseif (method_exists($object, $method) && (is_string($object) || !$object->isInternal($method))) {
            return true;
        } elseif (Gdn::pluginManager()->hasNewMethod($class, $method)) {
            return true;
        } else {
            return false;
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

    /**
     * Figure out what kind of blocks are allowed for dispatches.
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
            $dest = $matchRoute['FinalDestination'];

            if (strpos($dest, '?') === false) {
                // The rewrite rule doesn't include a query string so keep the current one intact.
                $request->path($dest);
            } else {
                // The rewrite rule has a query string so rewrite that too.
                $request->pathAndQuery($dest);
            }

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
        } elseif (in_array($request->path(), ['', '/'])) {
            $this->isHomepage = true;
            $defaultController = Gdn::router()->getRoute('DefaultController');
            $request->pathAndQuery($defaultController['Destination']);
        }

        return $request;
    }

    /**
     * Parse the delivery method base on the file extension.
     *
     * If a valid file extension is found then it will be removed from {@link $parts}.
     *
     * @param string[] $parts The path parts to parse.
     * @return array Returns an array in the form `[$parts, $deliveryMethod]`.
     */
    private function parseDeliveryMethod($parts) {
        $methods = [
            DELIVERY_METHOD_JSON,
            DELIVERY_METHOD_XHTML,
            DELIVERY_METHOD_XML,
            DELIVERY_METHOD_TEXT,
            DELIVERY_METHOD_RSS,
            DELIVERY_METHOD_ATOM
        ];

        if ($ext = pathinfo(end($parts), PATHINFO_EXTENSION)) {
            $ext = strtoupper($ext);
            if (in_array($ext, $methods, true)) {
                // Remove the extension.
                $filename = substr(array_pop($parts), 0, -(strlen($ext) + 1));
                $parts[] = $filename;
                return [$parts, $ext];
            }
        }

        return [$parts, ''];
    }

    /**
     * Dispatch to a controller that's already been found with {@link Gdn_Dispatcher::analyzeRequest()}.
     *
     * Although the controller has been found, its method may not have been found and will render an error if so.
     *
     * @param Gdn_Request $request The request being dispatched.
     * @param array $routeArgs The result of {@link Gdn_Dispatcher::analyzeRequest()}.
     */
    private function dispatchController($request, $routeArgs) {
        // Create the controller first.
        $controllerName = $routeArgs['controller'];
        $controller = $this->createController($controllerName, $request, $routeArgs);

        // Find the method to call.
        list($controllerMethod, $pathArgs) = $this->findControllerMethod($controller, $routeArgs['pathArgs']);
        if (!$controllerMethod) {
            // The controller method was not found.
            return $this->dispatchNotFound('method_notfound', $request);
        }

        // The method has been found, set it on the controller.
        $controller->RequestMethod = $controllerMethod;
        $controller->RequestArgs = $pathArgs;
        $controller->ResolvedPath = ($routeArgs['addon'] ? $routeArgs['addon']->getKey().'/' : '').
            strtolower(stringEndsWith($controllerName, 'Controller', true, true)).'/'.
            strtolower($controllerMethod);

        $reflectionArguments = $request->get();
        $this->EventArguments['Arguments'] = &$reflectionArguments;
        $this->fireEvent('BeforeReflect');

        // Get the callback to call.
        if (Gdn::pluginManager()->hasNewMethod(get_class($controller), $controllerMethod)) {
            $callback = Gdn::pluginManager()->getCallback(get_class($controller), $controllerMethod);

            // Augment the arguments to the plugin with the sender and these arguments.
            // The named sender and args keys are an old legacy format before plugins could override controller methods properly.
            $InputArgs = array_merge([$controller], $pathArgs, ['sender' => $controller, 'args' => $pathArgs]);
            $args = reflectArgs($callback, $InputArgs, $reflectionArguments);
        } else {
            $callback = [$controller, $controllerMethod];
            $args = reflectArgs($callback, $pathArgs, $reflectionArguments);
        }
        $controller->ReflectArgs = $args;

        // Now that we have everything its time to call the callback for the controller.
        try {
            $this->fireEvent('BeforeControllerMethod');
            Gdn::pluginManager()->callEventHandlers($controller, $controllerName, $controllerMethod, 'before');

            call_user_func_array($callback, $args);
        } catch (Exception $ex) {
            $controller->renderException($ex);
            exit();
        }
    }

    /**
     * Create a controller and initialize it with data from the dispatcher.
     *
     * @param string $controllerName The name of the controller to create.
     * @param Gdn_Request $request The current request.
     * @param array &$routeArgs Arguments from a call to {@link Gdn_Dispatcher::analyzeRequest}.
     * @return Gdn_Controller Returns a new {@link Gdn_Controller} object.
     */
    private function createController($controllerName, $request, &$routeArgs) {
        /* @var Gdn_Controller $controller */
        $controller = new $controllerName();
        Gdn::controller($controller);

        $this->EventArguments['Controller'] =& $controller;
        $this->fireEvent('AfterControllerCreate');

        // Pass along any assets
        if (is_array($this->controllerAssets)) {
            foreach ($this->controllerAssets as $AssetName => $Assets) {
                foreach ($Assets as $Asset) {
                    $controller->addAsset($AssetName, $Asset);
                }
            }
        }

        // Instantiate Imported & Uses classes
        $controller->getImports();

        // Pass along any objects
        foreach ($this->controllerProperties as $Name => $Mixed) {
            $controller->$Name = $Mixed;
        }

        // Pass along any data.
        if (!empty($this->controllerData)) {
            $controller->Data = $this->controllerData;
        }

        $controller->Request = $request;
        $controller->SelfUrl = $routeArgs['path'];
        /* @var Addon $addon */
        $addon = $routeArgs['addon'];
        if ($addon) {
            $controller->Application = $addon->getKey();
            $controller->ApplicationFolder = stringBeginsWith(ltrim($addon->getSubdir(), '/'), 'applications/', true, true);
        }
        $controller->Request = $request;
        $controller->deliveryType($routeArgs['deliveryType']);
        $controller->deliveryMethod($routeArgs['deliveryMethod']);
        $controller->SyndicationMethod = val('syndicationMethod', $routeArgs, SYNDICATION_NONE);

        $this->deliveryType = $routeArgs['deliveryType'];
        $this->deliveryMethod = $routeArgs['deliveryMethod'];

        // Kludge: We currently have a couple of plugins that modify the path arguments on initialize.
        $this->controllerArguments($routeArgs['pathArgs']);
        // End kludge.
        $controller->initialize();

        // Kludge for controllers that modify the dispatcher.
        $pathArgs = $this->controllerArguments();
        if (!empty($this->ControllerMethod)) {
            array_unshift($pathArgs, Gdn::Dispatcher()->ControllerMethod);
        }
        $routeArgs['pathArgs'] = $pathArgs;
        // End kluge.

        $this->EventArguments['Controller'] = $controller;
        $this->fireEvent('AfterControllerInit');

        return $controller;
    }
}
