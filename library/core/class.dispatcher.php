<?php
/**
 * Framework dispatcher.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Container\Container;
use Garden\EventManager;
use Garden\Web\ControllerDispatchedEvent;
use Garden\Web\Data;
use Garden\Web\Dispatcher;
use Garden\Web\DispatcherExceptionEvent;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Analytics\Models\OpenAIPrompt;
use Vanilla\Analytics\Models\OpenAIPrompter;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Events\AccessDeniedEvent;
use Vanilla\Exception\ExitException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Utility\DebugUtils;
use Vanilla\Utility\StringUtils;
use Vanilla\Utility\Timers;

/**
 * Handles all requests and routing.
 */
class Gdn_Dispatcher extends Gdn_Pluggable
{
    /** Can never be blocked. */
    const BLOCK_NEVER = 0;

    /** Can be blocked in certain cases but should not be blocked by lack of permissions. */
    const BLOCK_PERMISSION = 1;

    /** Free to be blocked. */
    const BLOCK_ANY = 2;

    /** @var string Meta flag for a request if it's being dispatched on the homepage. */
    const META_IS_HOMEPAGE = "isHomepage";

    private $wasBlockExceptionEventTriggered = false;

    /** @var array List of exceptions not to block */
    private $blockExceptions = [
        "#^api/v2/vfspoof#" => self::BLOCK_NEVER,
        "#^api/v2/#" => self::BLOCK_PERMISSION,
        '#^dist(/|$)#' => self::BLOCK_NEVER,
        '#^asset(/|$)#' => self::BLOCK_NEVER,
        '#^authenticate(/|$)#' => self::BLOCK_NEVER,
        '#^discussions/getcommentcounts(/|$)#' => self::BLOCK_NEVER,
        '#^entry(/|$)#' => self::BLOCK_PERMISSION,
        '#^home/error(/|$)#' => self::BLOCK_NEVER,
        '#^home/leaving(/|$)#' => self::BLOCK_NEVER,
        '#^home/termsofservice(/|$)#' => self::BLOCK_PERMISSION,
        '#^home/updatemode(/|$)#' => self::BLOCK_NEVER,
        '#^home/unauthorized(/|$)#' => self::BLOCK_NEVER,
        '#^plugin(/|$)#' => self::BLOCK_NEVER,
        '#^settings/analyticstick.json$#' => self::BLOCK_PERMISSION,
        '#^sso(/|$)#' => self::BLOCK_NEVER,
        '#^user/emailavailable(/|$|\.json)#' => self::BLOCK_PERMISSION,
        '#^user/usernameavailable(/|$|\.json)#' => self::BLOCK_PERMISSION,
        '#^utility(/|$)#' => self::BLOCK_NEVER,
    ];

    /** @var string The name of the controller to be dispatched. */
    public $ControllerName;

    /** @var string The method of the controller to be called. */
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

    /** @var  Dispatcher The forwards compatible dispatcher used for resourceful dispatching. */
    private $dispatcher;

    /** @var Throwable */
    private $dispatchException;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $sentHeaders = [];

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

    /**
     * @var Container
     */
    private $container;

    /** @var Timers */
    private $timers;

    /** @var ConfigurationInterface */
    private $config;

    /** @var bool */
    private $rethrowExceptions = false;

    /** @var EventManager */
    private $eventManager;

    /**
     * Gdn_Dispatcher constructor.
     *
     * @param AddonManager $addonManager
     * @param Container $container
     * @param Dispatcher $dispatcher
     */
    public function __construct(
        AddonManager $addonManager,
        Container $container,
        Dispatcher $dispatcher,
        EventManager $eventManager
    ) {
        parent::__construct();
        $this->addonManager = $addonManager;
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->logger = $this->container->get(LoggerInterface::class);
        $this->timers = $this->container->get(Timers::class);
        $this->config = $this->container->get(ConfigurationInterface::class);
        $this->eventManager = $eventManager;
        $this->reset();
    }

    /**
     * @param bool $rethrowExceptions
     */
    public function setRethrowExceptions(bool $rethrowExceptions): void
    {
        $this->rethrowExceptions = $rethrowExceptions;
    }

    /**
     * Reset the dispatchert to its default state.
     */
    public function reset()
    {
        $this->enabledApplicationFolders = null;
        $this->applicationFolder = "";
        $this->controllerAssets = [];
        $this->ControllerName = "";
        $this->ControllerMethod = "";
        $this->controllerMethodArgs = [];
        $this->controllerProperties = [];
        $this->controllerData = [];
        $this->deliveryType = DELIVERY_TYPE_ALL;
        $this->deliveryMethod = DELIVERY_METHOD_XHTML;
        $this->dispatchException = null;
    }

    /**
     * Backwards compatible support for deprecated properties.
     *
     * @param string $name The name of the property to get.
     * @return mixed Returns the property value.
     */
    public function __get($name)
    {
        switch (strtolower($name)) {
            case "request":
                deprecated("Gdn_Dispatcher->Request", "Gdn::request()->path()");
                return Gdn::request()->path();
        }
    }

    /**
     * Fire an event plugins can use to clean up the dispatcher.
     */
    public function cleanup()
    {
        $this->fireEvent("Cleanup");
    }

    /**
     * Get the currently dispatched application's folder name.
     *
     * @return string
     */
    public function application()
    {
        return $this->applicationFolder;
    }

    /**
     * Get the name of the currently dispatched controller.
     *
     * @return string
     */
    public function controller()
    {
        return $this->ControllerName;
    }

    /**
     * Get the name of the method on the currently dispatched controller.
     *
     * Note: This is not an HTTP method name. It's a class method name.
     *
     * @return string
     */
    public function controllerMethod()
    {
        return $this->ControllerMethod;
    }

    /**
     * Get/set the arguments that are being passed to the currently dispatched controller method.
     *
     * @param array|null $value
     * @return array|null
     */
    public function controllerArguments($value = null)
    {
        if ($value !== null) {
            $this->controllerMethodArgs = $value;
        }
        return $this->controllerMethodArgs;
    }

    /**
     * Start the dispatcher to make it ready to handle requests.
     */
    public function start()
    {
        $this->fireEvent("AppStartup");

        // Register callback allowing addons to modify response headers before PHP sends them.
        header_register_callback(function () {
            $this->fireEvent("SendHeaders");
        });
    }

    /**
     * Convert a dash-cased name into capital case.
     *
     * @param string $name The name to convert.
     * @return string Returns the filtered name.
     */
    protected function filterName($name)
    {
        if (empty($name) || $name[0] === "-" || substr($name, -1) === "-" || preg_match("`--`", $name)) {
            return $name;
        }

        $result = implode("", array_map("ucfirst", explode("-", $name)));
        return $result;
    }

    /**
     * Dispatch a request to a controller method.
     *
     * This method analyzes a request and figures out which controller and method it maps to. It also instantiates the
     * controller and calls the method.
     *
     * @param string|Gdn_Request|null $importRequest The request to dispatch. This can be a string URL or a Gdn_Request object,
     * @param bool $permanent Whether or not to set {@link Gdn::request()} with the dispatched request.
     */
    public function dispatch($importRequest = null, $permanent = true)
    {
        $timers = \Gdn::getContainer()->get(Timers::class);
        if ($importRequest && is_string($importRequest)) {
            $importRequest = Gdn_Request::create()
                ->fromEnvironment()
                ->setURI($importRequest);
        }

        if (is_a($importRequest, "Gdn_Request") && $permanent) {
            Gdn::request($importRequest);
        }

        $request = is_a($importRequest, "Gdn_Request") ? $importRequest : Gdn::request();

        $this->EventArguments["Request"] = $request;

        // Move this up to allow pre-routing
        $this->fireTimedEvent("BeforeDispatch");

        $routerTimer = $timers->startGeneric("rewriteRequest");
        // If we're in update mode and aren't explicitly prevented from blocking, block.
        if (inMaintenanceMode() && $this->getCanBlock($request) > self::BLOCK_NEVER) {
            $request->setURI(Gdn::router()->getDestination("UpdateMode"));
        }

        // Check for URL rewrites.
        $request = $this->rewriteRequest($request);
        $routerTimer->finish();

        if ($requestID = $request->getAttribute("requestID")) {
            safeHeader("X-Request-Id: $requestID");
        }

        // We need to save this state now because it's lost after this method.
        $isHomepage = $request->getMeta(self::META_IS_HOMEPAGE, false);
        $this->passData("isHomepage", $isHomepage);

        // Let plugins change augment the request before dispatch, but after internal routing.
        $this->fireTimedEvent("BeforeAnalyzeRequest");

        // If we're in a private community and can block, redirect to signin
        if (c("Garden.PrivateCommunity") && $this->getCanBlock($request) > self::BLOCK_PERMISSION) {
            if ($this->deliveryType === DELIVERY_TYPE_DATA) {
                safeHeader("HTTP/1.0 401 Unauthorized", true, 401);
                safeHeader("Content-Type: application/json; charset=utf-8", true);
                echo json_encode(["Code" => "401", "Exception" => t("You must sign in.")]);
            } else {
                redirectTo("/entry/signin?Target=" . urlencode($request->pathAndQuery()));
            }
            exit();
        }

        // If we're rendering the homepage, and we don't have custom layout home, and the legacy homepage doesn't need
        // The new dispatcher, then we should skip the new dispatcher to avoid incorrectly loading a controller that doesn't
        // have it's feature flag enabled.
        // For example:
        // - Enable new discussionList feature flag to render a new controller for /discussions
        // - But have the legacy layout performing an internal redirect to /discussions
        // We still want to load the legacy controller.
        // Throwing a wrench in is that some "legacy homepages" can be knowledge base, which requires the new dispatcher.
        // For now, we just kludge it in.
        $skipNewDispatcher =
            $isHomepage && !FeatureFlagHelper::featureEnabled("customLayout.home") && $request->getPath() !== "/kb";

        if (!$skipNewDispatcher) {
            // Try and dispatch with the new dispatcher.
            // This is temporary. We will eventually just have the new dispatcher.
            $response = $this->dispatcher->dispatch($request);

            // If we received a matched response with the new dispatcher
            // Use that.
            if (!$response->getMeta("noMatch")) {
                $this->dispatcher->render($request, $response);
                return;
            }
        }

        // Otherwise use the `Gdn_Controller`s for dispatching.
        // Analyze the request AFTER checking for update mode.
        $routeArgs = $this->analyzeRequest($request);
        $this->fireEvent("AfterAnalyzeRequest");

        // Now that the controller has been found, dispatch to a method on it.
        $this->dispatchController($request, $routeArgs);
    }

    /**
     * Dispatch a 404 with an event that can be handled.
     *
     * @param string $reason A developer-readable reason code to aid debugging.
     * @param Gdn_Request|null $request The request object to rewrite to the 404.
     * @return mixed|bool
     */
    private function dispatchNotFound($reason = "notfound", $request = null)
    {
        if (!$request) {
            $request = Gdn::request();
        }
        $this->EventArguments["Handled"] = false;
        $handled = &$this->EventArguments["Handled"];
        $this->fireEvent("NotFound");

        if (!$handled) {
            $request->withRoute("Default404");
            return $this->passData("Reason", $reason)->dispatch($request, true);
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
    private function requestVal($key, $get, $post, $default = null)
    {
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
     * @return array|null Returns information about the request or `null` if there is no controller that matches the request.
     */
    private function analyzeRequest($request)
    {
        // Initialize the result of our request.
        $result = [
            "method" => $request->requestMethod(),
            "path" => $request->path(),
            "addon" => null,
            "controller" => "",
            "controllerMethod" => "",
            "pathArgs" => [],
            "query" => array_change_key_case($request->get()),
            "post" => $request->post(),
        ];
        $parts = explode("/", str_replace("\\", "/", $request->path()));
        // Decode path parts at the dispatcher level.
        array_walk($parts, function (&$value) {
            $value = rawurldecode($value);
        });

        // Parse the file extension.
        $deliveryMethod = $this->getDeliveryMethod($request);
        if ($deliveryMethod) {
            // Remove the extension
            $filename = substr(array_pop($parts), 0, -strlen("." . $deliveryMethod));
            $parts[] = $filename;

            if (in_array($deliveryMethod, [DELIVERY_METHOD_ATOM, DELIVERY_METHOD_RSS])) {
                $result["syndicationMethod"] = DELIVERY_METHOD_RSS;
            }
        }

        $deliveryType = $this->getDeliveryType($deliveryMethod);

        // An explicitly passed delivery type/method overrides the default.
        $result["deliveryMethod"] = self::requestVal(
            "DeliveryMethod",
            $result["query"],
            $result["post"],
            $deliveryMethod ?: $this->deliveryMethod
        );
        $result["deliveryType"] = self::requestVal("DeliveryType", $result["query"], $result["post"], $deliveryType);

        // Figure out the controller.
        [$controllerName, $pathArgs] = $this->findController($parts);
        $result["pathArgs"] = $pathArgs;

        if ($controllerName) {
            // The controller was found based on the path.
            $result["controller"] = $controllerName;
        } elseif (Gdn::pluginManager()->hasNewMethod("RootController", $parts[0] ?? false)) {
            // There is a plugin defining a new root method.
            $result["controller"] = "RootController";
        } else {
            // No controller was found, fire a not found event.
            // TODO: Move this outside this method.
            $this->EventArguments["Handled"] = false;
            $handled = &$this->EventArguments["Handled"];
            $this->fireEvent("NotFound");

            if (!$handled) {
                safeHeader("HTTP/1.1 404 Not Found");
                return $this->passData("Reason", "controller_notfound")->analyzeRequest(
                    $request->withRoute("Default404")
                );
            }
            return null;
        }

        // A controller has been found. Find the addon that manages it.
        $addon = Gdn::addonManager()->lookupByClassname($result["controller"]);

        // The result should be properly set now. Set the legacy properties though.
        if ($addon) {
            $result["addon"] = $addon;
            $this->applicationFolder = stringBeginsWith($addon->getSubdir(), "applications/", true, true);
        }
        $this->ControllerName = $result["controller"];
        $this->controllerMethodArgs = [];
        $this->syndicationMethod = val("syndicationMethod", $result, SYNDICATION_NONE);
        $this->deliveryMethod = $result["deliveryMethod"];

        return $result;
    }

    /**
     * Get the folder names of the currently enabled applications.
     *
     * @param string|array $enabledApplications
     * @return array
     * @deprecated
     */
    public function enabledApplicationFolders($enabledApplications = "")
    {
        deprecated("Gdn_Dispatcher->enabledApplicationFolders()");
        if ($enabledApplications != "" && count($this->enabledApplicationFolders) == 0) {
            $this->enabledApplications = $enabledApplications;
            $this->enabledApplicationFolders = array_values($enabledApplications);
        }
        return $this->enabledApplicationFolders ?: [];
    }

    /**
     * Get the enabled application folders.
     *
     * This is a temporary refactor of the {@link enabledApplicationFolders()} method and will be removed once
     * support for application prefixes has been removed from the site. Please leave this method as private and don't
     * call it unless you know what you're doing.
     *
     * @return array An array of application folders.
     */
    private function getEnabledApplicationFolders()
    {
        if (!isset($this->enabledApplicationFolders)) {
            $addons = $this->addonManager->getEnabled();
            $applications = array_filter($addons, Addon::makeFilterCallback(["oldType" => "application"]));

            $result = ["dashboard"];
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
    private function findController(array $parts)
    {
        // Look for the old-school application name as the first part of the path.
        if (in_array($parts[0] ?? false, $this->getEnabledApplicationFolders())) {
            $application = array_shift($parts);
        } else {
            $application = "";
        }
        $controller = $this->filterName(reset($parts));

        // This is a kludge until we can refactor- settings controllers better.
        if ($controller === "Settings" && $application !== "dashboard") {
            $controller = $this->filterName($application) . $controller;
        }

        $controllerName = $controller . "Controller";

        // If the lookup succeeded, good to go
        if (class_exists($controllerName, true)) {
            array_shift($parts);
            return [$controllerName, $parts];
        } elseif (!empty($application) && class_exists($this->filterName($application) . "Controller", true)) {
            // There is a controller with the same name as the application so use it.
            return [$this->filterName($application) . "Controller", $parts];
        } else {
            return ["", $parts];
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
    private function findControllerMethod($controller, $pathArgs)
    {
        $first = $this->filterName(reset($pathArgs));

        if ($this->methodExists($controller, $first)) {
            array_shift($pathArgs);
            return [lcfirst($first), $pathArgs];
        } elseif ($this->methodExists($controller, "x$first")) {
            array_shift($pathArgs);
            deprecated(get_class($controller) . "->x$first", get_class($controller) . "->$first");
            return ["x$first", $pathArgs];
        } elseif ($this->methodExists($controller, "index")) {
            // "index" is the default controller method if an explicit method cannot be found.
            $this->EventArguments["PathArgs"] = $pathArgs;
            $this->fireEvent("MethodNotFound");
            return ["index", $pathArgs];
        } else {
            return ["", $pathArgs];
        }
    }

    /**
     * Check to see if a controller has a method.
     *
     * @param string|Gdn_Controller $object The name of the controller class or the controller itself.
     * @param string $method The name of the method.
     * @return bool Returns **true** if the controller has the method or there is a plugin that creates the method or **false** otherwise.
     */
    private function methodExists($object, $method)
    {
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
    public function controllerName()
    {
        return $this->ControllerName . "Controller";
    }

    /**
     * Returns the name of the enabled application based on $ApplicationFolder.
     *
     * @param string $folder The application folder related to the application name you want to return.
     * @return string|false
     */
    public function enabledApplication($folder = "")
    {
        if ($folder == "") {
            $folder = $this->applicationFolder;
        }

        if (strpos($folder, "plugins/") === 0) {
            $plugin = stringBeginsWith($folder, "plugins/", false, true);

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
     * @param string $assetName The name of the asset collection to add the string to.
     * @param mixed $asset The string asset to be added. The asset can be one of two things.
     * - <b>string</b>: The string will be rendered to the page.
     * - <b>Gdn_IModule</b>: The Gdn_IModule::render() method will be called when the asset is rendered.
     * @return $this
     */
    public function passAsset($assetName, $asset)
    {
        $this->controllerAssets[$assetName][] = $asset;
        return $this;
    }

    /**
     * Set some data that will be passed to the controller when it is dispatched.
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function passData($name, $value)
    {
        $this->controllerData[$name] = $value;
        return $this;
    }

    /**
     * Allows the passing of any variable to the controller as a property.
     *
     * @param string $name The name of the property to assign the variable to.
     * @param mixed $mixed The variable to be passed as a property of the controller.
     * @return $this
     */
    public function passProperty($name, $mixed)
    {
        $this->controllerProperties[$name] = $mixed;
        return $this;
    }

    /**
     * Figure out what kind of blocks are allowed for dispatches.
     *
     * @param Gdn_Request $request The current request being inspected.
     * @return int Returns one of the **Gdn_Dispatcher::BLOCK_*** constants.
     */
    public function getCanBlock($request)
    {
        // Never block an admin.
        if (Gdn::session()->checkPermission("Garden.Settings.Manage")) {
            Logger::debug("Dispatcher block: {blockException}, {blockLevel}", [
                "blockException" => "admin",
                "blockLevel" => self::BLOCK_NEVER,
            ]);
            return self::BLOCK_NEVER;
        }

        $canBlock = self::BLOCK_ANY;

        $blockExceptions = $this->getBlockExceptions();

        $pathRequest = $request->path();
        foreach ($blockExceptions as $blockException => $blockLevel) {
            if (preg_match($blockException, $pathRequest)) {
                Logger::debug("Dispatcher block: {blockException}, {blockLevel}", [
                    "blockException" => $blockException,
                    "blockLevel" => $blockLevel,
                ]);
                return $blockLevel;
            }
        }

        if (Gdn::session()->isValid()) {
            Logger::debug("Dispatcher block: {blockException}, {blockLevel}", [
                "blockException" => "signed_in",
                "blockLevel" => self::BLOCK_PERMISSION,
            ]);
            return self::BLOCK_PERMISSION;
        }

        return $canBlock;
    }

    /**
     * Return the list of paths that potentially cannot be blocked.
     *
     * @return array
     */
    public function getBlockExceptions()
    {
        if (!$this->wasBlockExceptionEventTriggered) {
            $this->EventArguments["BlockExceptions"] = &$this->blockExceptions;
            $this->fireEvent("BeforeBlockDetect");
            $this->wasBlockExceptionEventTriggered = true;
        }

        return $this->blockExceptions;
    }

    /**
     * Adds a dispatcher block exception.
     *
     * @param string $exceptionMatch Exception string to match.
     * @param int $exceptionType Type of block exception.
     * @throws InvalidArgumentException Throws and exception if exception type is not found.
     */
    public function addBlockException(string $exceptionMatch, int $exceptionType)
    {
        $allowedExceptionTypes = [self::BLOCK_NEVER, self::BLOCK_PERMISSION, self::BLOCK_ANY];
        if (!in_array($exceptionType, $allowedExceptionTypes)) {
            throw new InvalidArgumentException("Exception type could not be found.", 500);
        }
        $this->blockExceptions[$exceptionMatch] = $exceptionType;
    }

    /**
     * Rewrite the request based on rewrite rules (currently called routes in Vanilla).
     *
     * This method modifies the passed {@link $request} object. It can also cause a redirect if a rule matches that
     * specifies a redirect.
     *
     * @param Gdn_Request $request The request to rewrite.
     * @return Gdn_Request Returns the request with re-written values.
     */
    private function rewriteRequest($request)
    {
        $pathAndQuery = $request->pathAndQuery();
        $matchRoute = Gdn::router()->matchRoute($pathAndQuery);

        // We have a route. Take action.
        if (!empty($matchRoute)) {
            $dest = $matchRoute["FinalDestination"];

            if (strpos($dest, "?") === false) {
                // The rewrite rule doesn't include a query string so keep the current one intact.
                $request->path($dest);
            } else {
                // The rewrite rule has a query string so rewrite that too.
                $request->pathAndQuery($dest);
            }

            switch ($matchRoute["Type"]) {
                case "Internal":
                    // Do nothing. The request has been rewritten.
                    break;
                case "Temporary":
                    safeHeader("HTTP/1.1 302 Moved Temporarily");
                    safeHeader("Location: " . url($matchRoute["FinalDestination"]));
                    exit();
                    break;

                case "Permanent":
                    safeHeader("HTTP/1.1 301 Moved Permanently");
                    safeHeader("Location: " . url($matchRoute["FinalDestination"]));
                    exit();
                    break;

                case "NotAuthorized":
                    safeHeader("HTTP/1.1 401 Not Authorized");

                    break;

                case "NotFound":
                    safeHeader("HTTP/1.1 404 Not Found");
                    break;

                case "Drop":
                    die();

                case "Test":
                    decho($matchRoute, "Route");
                    decho(
                        [
                            "Path" => $request->getPath(),
                            "Get" => $request->get(),
                        ],
                        "Request"
                    );
                    die();
            }
        } elseif (in_array($request->getPath(), ["", "/"])) {
            $request->setMeta(self::META_IS_HOMEPAGE, true);
            $defaultRoute = Gdn::router()->getDefaultRoute();
            $defaultRouteOverridesCustomLayout = $defaultRoute["OverridesCustomLayout"] ?? false;

            if (!$defaultRouteOverridesCustomLayout && FeatureFlagHelper::featureEnabled("customLayout.home")) {
                // We're using a custom layout homepage, don't muck with the request.
                return $request;
            }

            // Otherwise grab the home route destination from the router.
            $request->setPath($defaultRoute["Destination"]);
        }

        return $request;
    }

    /**
     * Get the delivery method, based on the file extension, of the request.
     *
     * @param Gdn_Request $request the request to parse.
     * @return string Returns the delivery method or an empty string if none.
     */
    public function getDeliveryMethod($request)
    {
        $methods = [
            DELIVERY_METHOD_JSON,
            DELIVERY_METHOD_XHTML,
            DELIVERY_METHOD_XML,
            DELIVERY_METHOD_TEXT,
            DELIVERY_METHOD_RSS,
            DELIVERY_METHOD_ATOM,
        ];

        if ($ext = pathinfo(str_replace("\\", "/", $request->path()), PATHINFO_EXTENSION)) {
            $ext = strtoupper($ext);
            if (in_array($ext, $methods, true)) {
                return $ext;
            }
        }

        return "";
    }

    /**
     * Get the delivery type based on the supplied $deliveryMethod.
     * Default to the current {@link $deliveryType} if $deliveryMethod was not found.
     *
     * @param string $deliveryMethod
     * @return false|string The delivery type
     */
    public function getDeliveryType($deliveryMethod)
    {
        // Set some special properties based on the deliver method.
        $deliveryType = $this->deliveryType;
        switch ($deliveryMethod) {
            case DELIVERY_METHOD_JSON:
            case DELIVERY_METHOD_XML:
                $deliveryType = DELIVERY_TYPE_DATA;
                break;
            case DELIVERY_METHOD_TEXT:
                $deliveryType = DELIVERY_TYPE_VIEW;
                break;
        }
        return $deliveryType;
    }

    /**
     * Dispatch to a controller that's already been found with {@link Gdn_Dispatcher::analyzeRequest()}.
     *
     * Although the controller has been found, its method may not have been found and will render an error if so.
     *
     * @param Gdn_Request $request The request being dispatched.
     * @param array $routeArgs The result of {@link Gdn_Dispatcher::analyzeRequest()}.
     * @return mixed Returns the result of a dispatch not found if the controller wasn't found or is disabled.
     * @throws ReflectionException
     * @throws Throwable
     */
    private function dispatchController($request, $routeArgs)
    {
        // Clean this out between dispatches.
        $this->sentHeaders = [];

        // Create the controller first.
        $controllerName = $routeArgs["controller"];
        $controller = $this->createController($controllerName, $request, $routeArgs);

        if ($controller instanceof VanillaController) {
            if ($controller->disabled()) {
                $routeArgs["controllerMethod"] = "disabled";
                $routeArgs["controller"] = $controllerName = "VanillaController";
                $controller = $this->createController($controllerName, $request, $routeArgs);
                safeHeader("HTTP/1.1 404 Not Found");
            }
        }
        // Find the method to call.
        [$controllerMethod, $pathArgs] = $this->findControllerMethod($controller, $routeArgs["pathArgs"]);
        if (!$controllerMethod) {
            // The controller method was not found.
            return $this->dispatchNotFound("method_notfound", $request);
        }

        // The method has been found, set it on the controller.
        $controller->RequestMethod = $controllerMethod;
        $controller->RequestArgs = $pathArgs;
        $controller->ResolvedPath =
            ($routeArgs["addon"] ? $routeArgs["addon"]->getKey() . "/" : "") .
            strtolower(stringEndsWith($controllerName, "Controller", true, true)) .
            "/" .
            strtolower($controllerMethod);

        $reflectionArguments = $request->get();
        $this->EventArguments["Arguments"] = &$reflectionArguments;
        $this->fireEvent("BeforeReflect");

        // Get the callback to call.
        if (Gdn::pluginManager()->hasNewMethod(get_class($controller), $controllerMethod)) {
            $callback = Gdn::pluginManager()->getCallback(get_class($controller), $controllerMethod);

            // Augment the arguments to the plugin with the sender and these arguments.
            // The named sender and args keys are an old legacy format before plugins could override controller methods properly.
            $inputArgs = array_merge([$controller], $pathArgs, ["sender" => $controller, "args" => $pathArgs]);
        } else {
            $callback = [$controller, $controllerMethod];
            $inputArgs = $pathArgs;
        }
        $method = is_array($callback)
            ? new ReflectionMethod($callback[0], $callback[1])
            : new ReflectionFunction($callback);
        $args = Dispatcher::reflectArgs(
            $method,
            array_merge($inputArgs, $reflectionArguments),
            $this->container,
            false
        );
        $controller->ReflectArgs = $args;

        $canonicalUrl = url($this->makeCanonicalUrl($controller, $method, $args), true);
        $controller->canonicalUrl($canonicalUrl);

        // Now that we have everything its time to call the callback for the controller.
        try {
            $this->fireEvent("BeforeControllerMethod");
            Gdn::pluginManager()->callEventHandlers($controller, $controllerName, $controllerMethod, "before");
            $this->eventManager->dispatch(new ControllerDispatchedEvent($callback, $request));
            call_user_func_array($callback, $args);
        } catch (ExitException $ex) {
            // The controller wanted to exit.
            if (!DebugUtils::isTestMode()) {
                exit();
            }
        } catch (Throwable $ex) {
            AccessDeniedEvent::tryLog($ex);

            $dispatcherExceptionEvent = new DispatcherExceptionEvent($ex, $request);
            $this->eventManager->dispatch($dispatcherExceptionEvent);
            if ($this->rethrowExceptions) {
                throw $ex;
            }
            if (!DebugUtils::isDebug()) {
                $ex = $this->sanitizeException($ex);
            }
            if ($this->dispatchException === null) {
                $this->dispatchException = $ex;
                $controller->renderException($ex);
            } else {
                trigger_error(
                    "Multiple exceptions encountered while dispatching {$request->getPath()}.",
                    E_USER_WARNING
                );
                $this->logger->warning("Multiple exceptions encountered while dispatching.", [
                    "lastException" => [
                        "message" => $ex->getMessage(),
                        "trace" => $ex->getTrace(),
                    ],
                    "originalException" => [
                        "message" => $this->dispatchException->getMessage(),
                        "trace" => $this->dispatchException->getTrace(),
                    ],
                    "timestamp" => time(),
                ]);
                safeHeader("HTTP/1.0 500", true, 500);
            }
            if (!DebugUtils::isTestMode()) {
                exit();
            }
        }
    }

    /**
     * Keep track of the headers from the last response.
     *
     * @param array $headers The headers that were sent.
     *
     * @return void
     */
    public function setSentHeaders(array $headers): void
    {
        $this->sentHeaders = $headers;
    }

    /**
     * @return array
     */
    public function getSentHeaders(): array
    {
        return $this->sentHeaders;
    }

    /**
     * This is the old default implementation of canonical URL calculation from `Gdn_Controller`.
     *
     * This method is here for debugging purposes and will be removed eventually.
     *
     * @param object $sender The controller about to be dispatched.
     * @return string Returns a URL.
     * @deprecated
     */
    private function makeCanonicalUrlOld($sender): string
    {
        $controller = strtolower(stringEndsWith($sender->ControllerName, "Controller", true, true));

        if ($controller == "settings") {
            $parts[] = strtolower($sender->ApplicationFolder);
        }

        if ($controller != "root") {
            $parts[] = $controller;
        }

        if (strcasecmp($sender->RequestMethod, "index") != 0) {
            $parts[] = strtolower($sender->RequestMethod);
        }

        // The default canonical url is the fully-qualified url.
        if (is_array($sender->RequestArgs)) {
            $parts = array_merge($parts, $sender->RequestArgs);
        } elseif (is_string($sender->RequestArgs)) {
            $parts = trim($sender->RequestArgs, "/");
        }

        $path = implode("/", $parts);
        $result = url($path, true);

        return $result;
    }

    /**
     * Make the default canonical URL.
     *
     * @param object $controller The controller to use for the canonical URL.
     * @param ReflectionFunctionAbstract $method The method being dispatched.
     * @param array $args The reflected arguments.
     * @return string Returns a URL.
     */
    protected function makeCanonicalUrl($controller, ReflectionFunctionAbstract $method, $args): string
    {
        $parts = [];

        $controllerName = stringEndsWith(EventManager::classBasename(get_class($controller)), "Controller", true, true);
        $controllerName = $this->dashCase($controllerName);

        if ($controllerName !== "root") {
            $parts[] = $controllerName;
        }

        $methodName = $method->getName();
        if (preg_match("`^[^_]+_([^_]+)`", $methodName, $m)) {
            $methodName = $m[1];
        }
        $methodName = $this->dashCase($methodName);
        if ($methodName !== "index") {
            $parts[] = $methodName;
        }

        // Remove empty parameters from the parts.
        $defaults = [];
        foreach ($method->getParameters() as $param) {
            if ($param->isDefaultValueAvailable()) {
                $defaults[strtolower($param->getName())] = $param->getDefaultValue();
            }

            if (strcasecmp($param->getName(), "page") === 0 && empty($defaults["page"])) {
                $defaults["page"] = "p1";
            }
        }

        $args = array_change_key_case($args);

        // Add args after known names to the querystring.
        $query = [];
        $split = false;
        $hasSender = false;
        foreach ($args as $key => $value) {
            if (is_object($value)) {
                $hasSender = true;
                continue;
            } elseif (in_array($key, ["search", "query"]) || $split) {
                if (!empty($value) && $value != ($defaults[$key] ?? "")) {
                    $query[$key] = $value;
                }
                $split = true;
            } elseif (is_numeric($key)) {
                break;
            } elseif ($key === "args" && is_array($value) && $hasSender) {
                $parts = array_merge($parts, $value);
            } elseif (is_scalar($value)) {
                $parts[$key] = $value;
            }
        }
        $query = array_filter($query);

        // Remove empty or default path parameters from the end.
        $keys = array_reverse(array_keys($parts));
        foreach ($keys as $key) {
            $value = $parts[$key];
            if (empty($value) || $value == ($defaults[$key] ?? "")) {
                unset($parts[$key]);
            } else {
                break;
            }
        }

        $path = implode("/", array_map("rawurlencode", $parts));
        if (!empty($query)) {
            ksort($query);
            $path .= "?" . http_build_query($query);
        }

        return "/" . $path;
    }

    /**
     * Convert a string to dash-case.
     *
     * @param string $str The string to convert.
     * @return string Returns a dash-case string.
     */
    protected function dashCase(string $str): string
    {
        if (preg_match_all("`((?:[A-Z]|^)[A-Z]*[a-z0-9]*)`", $str, $m)) {
            $result = implode("-", array_map("strtolower", $m[1]));
        } else {
            $result = strtolower($str);
        }
        return $result;
    }

    /**
     * Create a controller and initialize it with data from the dispatcher.
     *
     * @param string $controllerName The name of the controller to create.
     * @param Gdn_Request $request The current request.
     * @param array $routeArgs Arguments from a call to `Gdn_Dispatcher::analyzeRequest`.
     * @return Gdn_Controller Returns a new `Gdn_Controller` object.
     */
    private function createController($controllerName, $request, &$routeArgs)
    {
        /* @var Gdn_Controller $controller */
        $controller = $this->container->get($controllerName);

        // Allow classes to have a dependency on Gdn_Controller.
        // It is possible that the controller does not inherit Gdn_Controller :(
        if (is_a($controller, Gdn_Controller::class)) {
            $this->container->setInstance(Gdn_Controller::class, $controller);
        }
        Gdn::controller($controller);

        $this->EventArguments["Controller"] = &$controller;
        $this->fireEvent("AfterControllerCreate");

        // Pass along any assets
        if (is_array($this->controllerAssets)) {
            foreach ($this->controllerAssets as $assetName => $assets) {
                foreach ($assets as $asset) {
                    $controller->addAsset($assetName, $asset);
                }
            }
        }

        // Instantiate Imported & Uses classes
        $controller->getImports();

        // Pass along any objects
        foreach ($this->controllerProperties as $name => $mixed) {
            $controller->$name = $mixed;
        }

        // Pass along any data.
        if (!empty($this->controllerData)) {
            $controller->Data = $this->controllerData;
        }

        $controller->Request = $request;
        $controller->SelfUrl = $routeArgs["path"];
        /* @var Addon $addon */
        $addon = $routeArgs["addon"];
        if ($addon) {
            $controller->Application = $addon->getKey();
            $controller->ApplicationFolder = stringBeginsWith(
                ltrim($addon->getSubdir(), "/"),
                "applications/",
                true,
                true
            );
        }
        $controller->Request = $request;
        $controller->deliveryType($routeArgs["deliveryType"]);
        $controller->deliveryMethod($routeArgs["deliveryMethod"]);
        $controller->SyndicationMethod = val("syndicationMethod", $routeArgs, SYNDICATION_NONE);

        $this->deliveryType = $routeArgs["deliveryType"];
        $this->deliveryMethod = $routeArgs["deliveryMethod"];

        // Kludge: We currently have a couple of plugins that modify the path arguments on initialize.
        $this->controllerArguments($routeArgs["pathArgs"]);
        // End kludge.
        $controller->initialize();

        // Kludge for controllers that modify the dispatcher.
        $pathArgs = $this->controllerArguments();
        if (!empty($this->ControllerMethod)) {
            array_unshift($pathArgs, Gdn::dispatcher()->ControllerMethod);
        }
        $routeArgs["pathArgs"] = $pathArgs;
        // End kluge.

        $this->EventArguments["Controller"] = $controller;
        $this->fireEvent("AfterControllerInit");

        return $controller;
    }

    /**
     * Sanitize the exception message to prevent displaying path ref
     * can be expanded based on further use cases
     * @param Throwable $exception actual exception.
     * @return Throwable modified exception.
     */
    private function sanitizeException(Throwable $exception): Throwable
    {
        if (str_contains($exception->getMessage(), ", called in")) {
            $exception_type = get_class($exception);
            $message = StringUtils::sanitizeExceptionMessage($exception->getMessage());
            $reflectedObject = new \ReflectionClass($exception_type);
            $property = $reflectedObject->getProperty("message");
            $property->setAccessible(true);
            $property->setValue($exception, $message);
            $property->setAccessible(false);
        }
        return $exception;
    }
}
