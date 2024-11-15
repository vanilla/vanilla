<?php
/**
 * Gdn_Controller
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Models\DashboardPreloadProvider;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Theme\ThemePreloadProvider;
use Vanilla\Utility\DebugUtils;
use Vanilla\Utility\HtmlUtils;
use Vanilla\Utility\StringUtils;
use Vanilla\Web\Asset\LegacyAssetModel;
use Vanilla\Web\Asset\LocaleAsset;
use Vanilla\Web\Asset\NoScriptStylesAsset;
use Vanilla\Web\Asset\ViteAssetProvider;
use Vanilla\Web\CacheControlConstantsInterface;
use Vanilla\Web\CacheControlTrait;
use Vanilla\Web\HttpStrictTransportSecurityModel;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel;
use Vanilla\Web\ContentSecurityPolicy\Policy;
use Vanilla\Web\JsInterpop\StatePreloadTrait;
use Vanilla\Web\MasterViewRenderer;
use Vanilla\Dashboard\Pages\LegacyDashboardPage;
use Vanilla\Web\Middleware\CloudflareChallengeMiddleware;
use Vanilla\Web\SeoMetaModel;
use Vanilla\Web\TwigStaticRenderer;

/**
 * Controller base class.
 *
 * A base class that all controllers can inherit for common properties and methods.
 *
 * @method void render($view = '', $controllerName = false, $applicationFolder = false, $assetName = 'Content') Render the controller's view.
 */
class Gdn_Controller extends Gdn_Pluggable implements CacheControlConstantsInterface
{
    use \Garden\MetaTrait, StatePreloadTrait, CacheControlTrait;

    /** Seconds before reauthentication is required for protected operations. */
    const REAUTH_TIMEOUT = 1200; // 20 minutes

    /** @var bool Check if user is already re-authenticated. */
    protected static $isAuthenticated = false;

    /** @var string The name of the application that this controller can be found in. */
    public $Application;

    /** @var string The name of the application folder that this controller can be found in. */
    public $ApplicationFolder;

    /**
     * @var array An associative array that contains content to be inserted into the
     * master view. All assets are placed in this array before being passed to
     * the master view. If an asset's key is not called by the master view,
     * that asset will not be rendered.
     */
    public $Assets = [];

    /** @var string */
    protected $_CanonicalUrl;

    /**
     * @var string The name of the controller that holds the view (used by $this->FetchView
     * when retrieving the view). Default value is $this->ClassName.
     */
    public $ControllerName;

    /**
     * @var string A CSS class to apply to the body tag of the page. Note: you can only
     * assume that the master view will use this property (ie. a custom theme
     * may not decide to implement this property).
     */
    public $CssClass;

    /** @var array The data that a controller method has built up from models and other calculations. */
    public $Data = [];

    /** @var HeadModule The Head module that this controller should use to add CSS files. */
    public $Head;

    /**
     * @var string The name of the master view that has been requested. Typically this is
     * part of the master view's file name. ie. $this->MasterView.'.master.tpl'
     */
    public $MasterView;

    /** @var object A Menu module for rendering the main menu on each page. */
    public $Menu;

    /**
     * @var string An associative array of assets and what order their modules should be rendered in.
     * You can set module sort orders in the config using Modules.ModuleSortContainer.AssetName.
     * @example $Configuration['Modules']['Vanilla']['Panel'] = array('CategoryModule', 'NewDiscussionModule');
     */
    public $ModuleSortContainer;

    /** @var string The method that was requested before the dispatcher did any re-routing. */
    public $OriginalRequestMethod;

    /**
     * @deprecated
     * @var string The URL to redirect the user to by ajax'd forms after the form is successfully saved.
     */
    public $RedirectUrl;

    /**
     * @var string The URL to redirect the user to by ajax'd forms after the form is successfully saved.
     */
    protected $redirectTo;

    /** @var string Fully resolved path to the application/controller/method. */
    public $ResolvedPath;

    /** @var array The arguments passed into the controller mapped to their proper argument names. */
    public $ReflectArgs;

    /**
     * @var mixed This is typically an array of arguments passed after the controller
     * name and controller method in the query string. Additional arguments are
     * parsed out by the @@Dispatcher and sent to $this->RequestArgs as an
     * array. If there are no additional arguments specified, this value will
     * remain FALSE.
     * ie. http://localhost/index.php?/controller_name/controller_method/arg1/arg2/arg3
     * translates to: array('arg1', 'arg2', 'arg3');
     */
    public $RequestArgs;

    /**
     * @var string The method that has been requested. The request method is defined by the
     * @@Dispatcher as the second parameter passed in the query string. In the
     * following example it would be "controller_method" and it relates
     * directly to the method that will be called in the controller. This value
     * is also used as $this->View unless $this->View has already been
     * hard-coded to be something else.
     * ie. http://localhost/index.php?/controller_name/controller_method/
     */
    public $RequestMethod;

    /** @var Gdn_Request Reference to the Request object that spawned this controller. */
    public $Request;

    /** @var string The requested url to this controller. */
    public $SelfUrl;

    /**
     * @var string The message to be displayed on the screen by ajax'd forms after the form
     * is successfully saved.
     *
     * @deprecated since 2.0.18; $this->errorMessage() and $this->informMessage()
     * are to be used going forward.
     */
    public $StatusMessage;

    /** @var string Defined by the dispatcher: SYNDICATION_RSS, SYNDICATION_ATOM, or SYNDICATION_NONE (default). */
    public $SyndicationMethod;

    /**
     * @var string The name of the folder containing the views to be used by this
     * controller. This value is retrieved from the $Configuration array when
     * this class is instantiated. Any controller can then override the property
     * before render if there is a need.
     */
    public $Theme;

    /** @var array Specific options on the currently selected theme. */
    public $ThemeOptions;

    /** @var string Name of the view that has been requested. Typically part of the view's file name. ie. $this->View.'.php' */
    public $View;

    /** @var bool Indicate that the controller add the `defer` attribute to it's legacy scripts. */
    protected $useDeferredLegacyScripts;

    /** @var bool Disable this to disabled custom theming for the page. */
    protected $allowCustomTheming = true;

    /** @var array An array of CSS file names to search for in theme folders & include in the page. */
    protected $_CssFiles;

    /**
     * @var array A collection of definitions that will be written to the screen in a hidden unordered list
     * so that JavaScript has access to them (ie. for language translations, web root, etc).
     */
    protected $_Definitions;

    /**
     * @var string An enumerator indicating how the response should be delivered to the
     * output buffer. Options are:
     *    DELIVERY_METHOD_XHTML: page contents are delivered as normal.
     *    DELIVERY_METHOD_JSON: page contents and extra information delivered as JSON.
     * The default value is DELIVERY_METHOD_XHTML.
     */
    protected $_DeliveryMethod;

    /**
     * @var string An enumerator indicating what should be delivered to the screen. Options are:
     *    DELIVERY_TYPE_ALL: The master view and everything in the requested asset (DEFAULT).
     *    DELIVERY_TYPE_ASSET: Everything in the requested asset.
     *    DELIVERY_TYPE_VIEW: Only the requested view.
     *    DELIVERY_TYPE_BOOL: Deliver only the success status (or error) of the request
     *    DELIVERY_TYPE_NONE: Deliver nothing
     */
    protected $_DeliveryType;

    /** @var string A string of html containing error messages to be displayed to the user. */
    protected $_ErrorMessages;

    /** @var bool Allows overriding 'FormSaved' property to send with DELIVERY_METHOD_JSON. */
    protected $_FormSaved;

    /** @var array An associative array of header values to be sent to the browser before the page is rendered. */
    protected $_Headers;

    /** @var array An array of internal methods that cannot be dispatched. */
    protected $internalMethods;

    /** @var array A collection of "inform" messages to be displayed to the user. */
    protected $_InformMessages;

    /** @var array An array of JS file names to search for in app folders & include in the page. */
    protected $_JsFiles;

    /**
     * @var array If JSON is going to be delivered to the client (see the render method),
     * this property will hold the values being sent.
     */
    protected $_Json;

    /** @var array A collection of view locations that have already been found. Used to prevent re-finding views. */
    protected $_ViewLocations;

    /** @var string|null */
    protected $_PageName = null;

    /** @var bool */
    protected $isReactView = false;

    /**
     * Gdn_Controller constructor.
     */
    public function __construct()
    {
        $this->useDeferredLegacyScripts = \Vanilla\FeatureFlagHelper::featureEnabled("DeferredLegacyScripts");
        $this->Application = "";
        $this->ApplicationFolder = "";
        $this->Assets = [];
        $this->CssClass = "";
        $this->Data = [];
        $this->Head = Gdn::factory("Dummy");
        $this->internalMethods = [
            "addasset",
            "addbreadcrumb",
            "addcssfile",
            "adddefinition",
            "addinternalmethod",
            "addjsfile",
            "addmodule",
            "allowjsonp",
            "canonicalurl",
            "clearcssfiles",
            "clearjsfiles",
            "contenttype",
            "cssfiles",
            "data",
            "definitionlist",
            "deliverymethod",
            "deliverytype",
            "description",
            "errormessages",
            "fetchview",
            "fetchviewlocation",
            "finalize",
            "getasset",
            "getimports",
            "getjson",
            "getstatusmessage",
            "image",
            "informmessage",
            "intitialize",
            "isinternal",
            "jsfiles",
            "json",
            "jsontarget",
            "masterview",
            "pagename",
            "permission",
            "removecssfile",
            "render",
            "xrender",
            "renderasset",
            "renderdata",
            "renderexception",
            "rendermaster",
            "renderreact",
            "sendheaders",
            "setdata",
            "setformsaved",
            "setheader",
            "setjson",
            "setlastmodified",
            "statuscode",
            "title",
        ];
        $this->MasterView = "";
        $this->ModuleSortContainer = "";
        $this->OriginalRequestMethod = "";
        $this->RedirectUrl = "";
        $this->RequestMethod = "";
        $this->RequestArgs = false;
        $this->Request = null;
        $this->SelfUrl = "";
        $this->SyndicationMethod = SYNDICATION_NONE;
        $this->Theme = theme();
        $this->ThemeOptions = Gdn::config("Garden.ThemeOptions", []);
        $this->View = "";
        $this->_CssFiles = [];
        $this->_JsFiles = [];
        $this->_Definitions = [];
        $this->_DeliveryMethod = DELIVERY_METHOD_XHTML;
        $this->_DeliveryType = DELIVERY_TYPE_ALL;
        $this->_FormSaved = "";
        $this->_Json = [];
        $this->_Headers = [
            "X-Vanilla-Version" => APPLICATION_VERSION,
            "Content-Type" => Gdn::config("Garden.ContentType", "") . "; charset=utf-8", // PROPERLY ENCODE THE CONTENT
            //         'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT', // PREVENT PAGE CACHING (this can be overridden by specific controllers)
        ];

        if (Gdn::session()->isValid() || Gdn::request()->getMethod() !== "GET") {
            $this->_Headers = array_merge($this->_Headers, [
                self::HEADER_CACHE_CONTROL => self::NO_CACHE, // PREVENT PAGE CACHING: HTTP/1.1
            ]);
        } else {
            $this->_Headers = array_merge($this->_Headers, [
                self::HEADER_CACHE_CONTROL => self::PUBLIC_CACHE,
                "Vary" => self::VARY_COOKIE,
            ]);
        }

        $hsts = Gdn::getContainer()->get(HttpStrictTransportSecurityModel::class);
        $this->_Headers[HttpStrictTransportSecurityModel::HSTS_HEADER] = $hsts->getHsts();

        //get additional security headers added in HttpStrictTransportSecurityModel::class
        foreach ($hsts->getAdditionalSecurityHeaders() as $header) {
            [$name, $value] = $hsts->getSecurityHeaderEntry($header);
            $this->_Headers[$name] = $value;
        }

        $cspModel = Gdn::getContainer()->get(ContentSecurityPolicyModel::class);
        $this->_Headers[ContentSecurityPolicyModel::CONTENT_SECURITY_POLICY] = $cspModel->getHeaderString([
            Policy::FRAME_ANCESTORS,
            Policy::BASE_URI,
            Policy::OBJECT_SRC,
        ]);
        $xFrameString = $cspModel->getXFrameString();
        if ($xFrameString !== null) {
            $this->_Headers[ContentSecurityPolicyModel::X_FRAME_OPTIONS] = $xFrameString;
        }

        $cloudflareChallengeMiddleware = Gdn::getContainer()->get(CloudflareChallengeMiddleware::class);
        if ($cloudflareChallengeMiddleware->shouldUserReceiveChallenge()) {
            $this->setHeader(...CloudflareChallengeMiddleware::CF_CHALLENGE_HEADER);
        }

        $this->_ErrorMessages = "";
        $this->_InformMessages = [];
        $this->StatusMessage = "";

        parent::__construct();
        $this->ControllerName = strtolower($this->ClassName);

        $currentTheme = Gdn::getContainer()
            ->get(\Vanilla\AddonManager::class)
            ->getTheme();
        if ($currentTheme instanceof \Vanilla\Addon) {
            $this->addDefinition("currentThemePath", $currentTheme->getSubdir());
        }
    }

    /**
     * @return bool
     */
    public static function isReauthenticated(): bool
    {
        return self::$isAuthenticated;
    }

    /**
     * @param bool $isAuthenticated
     */
    public static function setIsReauthenticated(bool $isAuthenticated): void
    {
        self::$isAuthenticated = $isAuthenticated;
    }

    /**
     * Add a breadcrumb to the list.
     *
     * @param string $name Translation code
     * @param string $link Optional. Hyperlink this breadcrumb somewhere.
     * @param string $position Optional. Where in the list to add it? 'front', 'back'
     */
    public function addBreadcrumb($name, $link = null, $position = "back")
    {
        $breadcrumb = [
            "Name" => t($name),
            "Url" => $link,
        ];

        $breadcrumbs = $this->data("Breadcrumbs", []);
        switch ($position) {
            case "back":
                $breadcrumbs = array_merge($breadcrumbs, [$breadcrumb]);
                break;
            case "front":
                $breadcrumbs = array_merge([$breadcrumb], $breadcrumbs);
                break;
        }
        $this->setData("Breadcrumbs", $breadcrumbs);
    }

    /**
     * Adds as asset (string) to the $this->Assets collection.
     *
     * The assets will later be added to the view if their $assetName is called by
     * $this->renderAsset($assetName) within the view.
     *
     * @param string $assetContainer The name of the asset container to add $asset to.
     * @param mixed $asset The asset to be rendered in the view. This can be one of:
     * - <b>string</b>: The string will be rendered.
     * - </b>Gdn_IModule</b>: Gdn_IModule::render() will be called.
     * @param string $assetName The name of the asset being added. This can be
     * used later to sort assets before rendering.
     */
    public function addAsset($assetContainer, $asset, $assetName = "")
    {
        if (is_object($assetName)) {
            return false;
        } elseif ($assetName == "") {
            $this->Assets[$assetContainer][] = $asset;
        } else {
            if (isset($this->Assets[$assetContainer][$assetName])) {
                if (!is_string($asset)) {
                    $asset = $asset->toString();
                }
                $this->Assets[$assetContainer][$assetName] .= $asset;
            } else {
                $this->Assets[$assetContainer][$assetName] = $asset;
            }
        }
    }

    /**
     * Adds a CSS file to search for in the theme folder(s).
     *
     * @param string $fileName The CSS file to search for.
     * @param string $appFolder The application folder that should contain the CSS file. Default is to
     * use the application folder that this controller belongs to.
     *  - If you specify plugins/PluginName as $appFolder then you can contain a CSS file in a plugin's design folder.
     * @param ?array $options Options that are passed along to the CSS file set.
     */
    public function addCssFile($fileName, $appFolder = "", $options = null)
    {
        $this->_CssFiles[] = ["FileName" => $fileName, "AppFolder" => $appFolder, "Options" => $options];
    }

    /**
     * Adds a key-value pair to the definition collection for JavaScript.
     *
     * @param string $term
     * @param ?string $definition
     * @return mixed
     */
    public function addDefinition($term, $definition = null)
    {
        if (!is_null($definition)) {
            $this->_Definitions[$term] = $definition;
        }
        return val($term, $this->_Definitions);
    }

    /**
     * Add an method to the list of internal methods.
     *
     * @param string $methodName The name of the internal method to add.
     */
    public function addInternalMethod($methodName)
    {
        $this->internalMethods[] = strtolower($methodName);
    }

    /**
     * Mapping of how certain legacy javascript files have been split up.
     *
     * If you include the key, all of the files in it's value will be included as well.
     */
    const SPLIT_JS_MAPPINGS = [
        "global.js" => ["flyouts.js"],
    ];

    /**
     * Adds a JS file to search for in the application or global js folder(s).
     *
     * @param string $fileName The js file to search for.
     * @param string $appFolder The application folder that should contain the JS file. Default is to use the
     * application folder that this controller belongs to.
     * @param ?array $options
     */
    public function addJsFile($fileName, $appFolder = "", $options = null)
    {
        // Reactions has been moved to core.
        // This is a shim to make sure custom addons referencing reaction javascript still work.
        if ($appFolder === "plugins/Reactions") {
            $appFolder = "vanilla";
        }
        $jsInfo = ["FileName" => $fileName, "AppFolder" => $appFolder, "Options" => $options];

        $this->_JsFiles[] = $jsInfo;

        if ($appFolder === "" && array_key_exists($fileName, self::SPLIT_JS_MAPPINGS)) {
            $items = self::SPLIT_JS_MAPPINGS[$fileName];
            foreach ($items as $item) {
                $this->addJsFile($item, $appFolder, $options);
            }
        }
    }

    /**
     * Adds the specified module to the specified asset target.
     *
     * If no asset target is defined, it will use the asset target defined by the
     * module's AssetTarget method.
     *
     * @param mixed $module A module or the name of a module to add to the page.
     * @param string $assetTarget
     */
    public function addModule($module, $assetTarget = "")
    {
        $this->fireEvent("BeforeAddModule");
        $assetModule = $module;

        if (!is_object($assetModule)) {
            if (property_exists($this, $module) && is_object($this->$module)) {
                $assetModule = $this->$module;
            } else {
                $moduleClassExists = class_exists($module);

                if ($moduleClassExists) {
                    // Make sure that the class implements Gdn_IModule
                    $reflectionClass = new ReflectionClass($module);
                    if ($reflectionClass->implementsInterface("Gdn_IModule")) {
                        $assetModule = new $module($this);
                    }
                }
            }
        }

        if (is_object($assetModule)) {
            $assetTarget = $assetTarget == "" ? $assetModule->assetTarget() : $assetTarget;
            $this->addAsset($assetTarget, $assetModule, $assetModule->name());
        }

        $this->fireEvent("AfterAddModule");
    }

    /**
     * Whether or not to allow JSONP responses.
     *
     * @param ?bool $value
     * @return mixed|null
     */
    public function allowJSONP($value = null)
    {
        static $allowJSONP;

        if (isset($value)) {
            $allowJSONP = $value;
        }

        if (isset($allowJSONP)) {
            return $allowJSONP;
        } else {
            return c("Garden.AllowJSONP");
        }
    }

    /**
     * Check to see if we've gone off the end of the page.
     *
     * @param int $offset The offset requested.
     * @param int $totalCount The total count of records.
     * @throws Exception Throws an exception if the offset is past the last page.
     */
    protected function checkPageRange(int $offset, int $totalCount)
    {
        if ($offset > 0 && $offset >= $totalCount) {
            throw notFoundException();
        }
    }

    /**
     * Get/set the canonical URL.
     *
     * @param ?string $value
     * @return null|string
     */
    public function canonicalUrl($value = null)
    {
        if ($value === null) {
            if ($this->_CanonicalUrl || $this->_CanonicalUrl === "") {
                return $this->_CanonicalUrl;
            } else {
                $parts = [];

                $controller = strtolower(stringEndsWith($this->ControllerName, "Controller", true, true));

                if ($controller == "settings") {
                    $parts[] = strtolower($this->ApplicationFolder);
                }

                if ($controller != "root") {
                    $parts[] = $controller;
                }

                if (strcasecmp($this->RequestMethod, "index") != 0) {
                    $parts[] = strtolower($this->RequestMethod);
                }

                // The default canonical url is the fully-qualified url.
                if (is_array($this->RequestArgs)) {
                    $parts = array_merge($parts, $this->RequestArgs);
                } elseif (is_string($this->RequestArgs)) {
                    $parts = trim($this->RequestArgs, "/");
                }

                $path = implode("/", $parts);
                $result = url($path, true);
                return $result;
            }
        } else {
            $this->_CanonicalUrl = $value;
            return $value;
        }
    }

    /**
     * Clear all of the currently set CSS files.
     */
    public function clearCssFiles()
    {
        $this->_CssFiles = [];
    }

    /**
     * Clear all js files from the collection.
     */
    public function clearJsFiles()
    {
        $this->_JsFiles = [];
    }

    /**
     * Set the content type.
     *
     * @param string $contentType
     */
    public function contentType($contentType)
    {
        $this->setHeader("Content-Type", $contentType);
    }

    /**
     * Get all of the currently added CSS files.
     *
     * @return array
     */
    public function cssFiles()
    {
        return $this->_CssFiles;
    }

    /**
     * Get a value out of the controller's data array.
     *
     * @param string $path The path to the data.
     * @param mixed $default The default value if the data array doesn't contain the path.
     * @return mixed
     * @see getValueR()
     */
    public function data($path, $default = "")
    {
        $result = valr($path, $this->Data, $default);
        return $result;
    }

    /**
     * Validate that our meta values serialize properly.
     *
     * @return string
     */
    public function validateDefinitionList()
    {
        // Generate the list.
        $this->definitionList();
        return StringUtils::jsonEncodeChecked($this->_Definitions);
    }

    /**
     * Gets the javascript definition list used to pass data to the client.
     *
     * @param bool $wrap Whether or not to wrap the result in a `script` tag.
     * @return string Returns a string containing the `<script>` tag of the definitions. .
     */
    public function definitionList($wrap = true)
    {
        $session = Gdn::session();
        /** @var \Vanilla\Models\SiteMeta $siteMeta */
        $siteMeta = Gdn::getContainer()->get(\Vanilla\Models\SiteMeta::class);
        $siteValue = $siteMeta->value();
        if (!array_key_exists("TransportError", $this->_Definitions)) {
            $this->_Definitions["TransportError"] = t(
                "Transport error: %s",
                "A fatal error occurred while processing the request.<br />The server returned the following response: %s"
            );
        }

        if (!array_key_exists("TransientKey", $this->_Definitions)) {
            $this->_Definitions["TransientKey"] = $session->transientKey();
            unset($siteValue["TransientKey"]);
        }

        if (!array_key_exists("WebRoot", $this->_Definitions)) {
            $this->_Definitions["WebRoot"] = combinePaths([Gdn::request()->domain(), Gdn::request()->webRoot()], "/");
        }

        if (!array_key_exists("UrlFormat", $this->_Definitions)) {
            $this->_Definitions["UrlFormat"] = url("{Path}");
        }

        if (!array_key_exists("Path", $this->_Definitions)) {
            $this->_Definitions["Path"] = Gdn::request()->path();
        }

        if (!array_key_exists("Args", $this->_Definitions)) {
            $this->_Definitions["Args"] = http_build_query(Gdn::request()->get());
        }

        if (!array_key_exists("ResolvedPath", $this->_Definitions)) {
            $this->_Definitions["ResolvedPath"] = $this->ResolvedPath;
        }

        if (!array_key_exists("ResolvedArgs", $this->_Definitions)) {
            // Get a filtered list of arguments that are not pluggables.
            $reflectArgs = array_filter($this->ReflectArgs, function ($arg) {
                return !($arg instanceof Gdn_Pluggable);
            });

            $this->_Definitions["ResolvedArgs"] = $reflectArgs;
        }

        if (!array_key_exists("SignedIn", $this->_Definitions)) {
            if (Gdn::session()->checkPermission("Garden.Moderation.Manage")) {
                $signedIn = 2;
            } else {
                $signedIn = (int) Gdn::session()->isValid();
            }
            $this->_Definitions["SignedIn"] = $signedIn;
        }

        if (Gdn::session()->isValid()) {
            // Tell the client what our hour offset is so it can compare it to the user's real offset.
            touchValue("SetHourOffset", $this->_Definitions, Gdn::session()->User->HourOffset);
            touchValue(
                "SetTimeZone",
                $this->_Definitions,
                Gdn::session()->getAttribute("TimeZone") ?: Gdn::session()->getAttribute("SetTimeZone")
            );
        }

        if (!array_key_exists("ConfirmHeading", $this->_Definitions)) {
            $this->_Definitions["ConfirmHeading"] = t("Confirm");
        }

        if (!array_key_exists("ConfirmText", $this->_Definitions)) {
            $this->_Definitions["ConfirmText"] = t("Are you sure you want to do that?");
        }

        if (!array_key_exists("Okay", $this->_Definitions)) {
            $this->_Definitions["Okay"] = t("Okay");
        }

        if (!array_key_exists("Cancel", $this->_Definitions)) {
            $this->_Definitions["Cancel"] = t("Cancel");
        }

        if (!array_key_exists("Search", $this->_Definitions)) {
            $this->_Definitions["Search"] = t("Search");
        }

        if (debug()) {
            $this->_Definitions["debug"] = true;
        }

        // These items are added in a controlled matter for newer client-side apps so are nested.
        $this->_Definitions += [
            "ui" => [],
        ];

        $this->_Definitions = array_merge_recursive($this->_Definitions, $siteValue);

        $this->_Definitions["useNewFlyouts"] = \Vanilla\FeatureFlagHelper::featureEnabled("NewFlyouts");

        $this->_Definitions["ui"] += [
            "siteName" => c("Garden.Title"),
            "siteTitle" => c("Garden.HomepageTitle", c("Garden.Title")),
            "locale" => Gdn::locale()->current(),
            "inputFormat" => strtolower(c("Garden.InputFormatter")),
        ];

        // Output a JavaScript object with all the definitions.
        $result =
            "gdn=window.gdn||{};" .
            "gdn.meta=" .
            json_encode($this->_Definitions) .
            ";\n" .
            "gdn.permissions=" .
            json_encode(Gdn::session()->getPermissions()) .
            ";\n";

        if ($wrap) {
            $result = "<script>$result</script>";
        }
        return $result;
    }

    /**
     * Returns the requested delivery type of the controller if $default is not
     * provided. Sets and returns the delivery type otherwise.
     *
     * @param string $default One of the DELIVERY_TYPE_* constants.
     */
    public function deliveryType($default = "")
    {
        if ($default) {
            // Make sure we only set a defined delivery type.
            // Use constants' name pattern instead of a strict whitelist for forwards-compatibility.
            if (defined("DELIVERY_TYPE_" . $default)) {
                $this->_DeliveryType = $default;
            }
        }

        return $this->_DeliveryType;
    }

    /**
     * Check if this request is rendering a masterview.
     *
     * @returns bool
     */
    public function isRenderingMasterView(): bool
    {
        return $this->deliveryType() === DELIVERY_TYPE_ALL;
    }

    /**
     * Returns the requested delivery method of the controller if $default is not
     * provided. Sets and returns the delivery method otherwise.
     *
     * @param string $default One of the DELIVERY_METHOD_* constants.
     * @return string
     */
    public function deliveryMethod($default = "")
    {
        if ($default != "") {
            $this->_DeliveryMethod = $default;
        }

        return $this->_DeliveryMethod;
    }

    /**
     * Tell the controller to render as a full react view, instead of custom theme views.
     *
     * @param bool $isReactView
     */
    public function setIsReactView(bool $isReactView)
    {
        $this->isReactView = $isReactView;
    }

    /**
     * @return bool
     */
    public function getIsReactView(): bool
    {
        return $this->isReactView;
    }

    /**
     * Get/set the page description.
     *
     * @param bool $value
     * @param bool $plainText
     * @return mixed
     */
    public function description($value = false, $plainText = false)
    {
        if ($value != false) {
            if ($plainText) {
                $value = Gdn_Format::plainText($value);
            }
            $this->setData("_Description", $value);
        }
        return $this->data("_Description");
    }

    /**
     * Get the contextual title.
     *
     * If this page is part of a site section, it will return the section's name.
     * Otherwise, it will return title()
     *
     * @return string
     */
    public function contextualTitle()
    {
        $category = $this->data("Category", null);
        if (!$category) {
            $siteSection = Gdn::getContainer()
                ->get(SiteSectionModel::class)
                ->getCurrentSiteSection();
            $categoryIdentifier = $siteSection->getAttributes()["categoryID"] ?? null;
            if ($categoryIdentifier && $categoryIdentifier > 0) {
                $category = CategoryModel::categories($categoryIdentifier);
            }
        }
        if (is_object($category)) {
            $category = (array) $category;
        }
        if ($category) {
            return $category["Name"] ?? "";
        }
        return $this->title();
    }

    /**
     * Get the contextual description.
     *
     * If this page is part of a site section, it will return the section's description.
     * Otherwise, it will return description()
     *
     * @return string
     */
    public function contextualDescription()
    {
        $category = $this->data("Category", null);
        if (!$category) {
            $siteSection = Gdn::getContainer()
                ->get(SiteSectionModel::class)
                ->getCurrentSiteSection();
            $categoryIdentifier = $siteSection->getAttributes()["categoryID"] ?? null;
            if ($categoryIdentifier && $categoryIdentifier > 0) {
                $category = CategoryModel::categories($categoryIdentifier);
            }
        }
        if (is_object($category)) {
            $category = (array) $category;
        }
        if ($category) {
            return $category["Description"] ?? "";
        }
        return $this->description();
    }

    /**
     * Add error messages to be displayed to the user.
     *
     * @param string $messages The html of the errors to be display.
     * @since 2.0.18
     *
     */
    public function errorMessage($messages)
    {
        $this->_ErrorMessages = $messages;
    }

    /**
     * Fetches the contents of a view into a string and returns it. Returns
     * false on failure.
     *
     * @param string $View The name of the view to fetch. If not specified, it will use the value
     * of $this->View. If $this->View is not specified, it will use the value
     * of $this->RequestMethod (which is defined by the dispatcher class).
     * @param string|false $ControllerName The name of the controller that owns the view if it is not $this.
     * @param string|false $ApplicationFolder The name of the application folder that contains the requested controller
     * if it is not $this->ApplicationFolder.
     * @return string Returns the view contents.
     */
    public function fetchView($View = "", $ControllerName = false, $ApplicationFolder = false)
    {
        $ViewPath = $this->fetchViewLocation($View, $ControllerName, $ApplicationFolder);

        // Check to see if there is a handler for this particular extension.
        $ViewHandler = Gdn::factory("ViewHandler" . strtolower(strrchr($ViewPath, ".")));

        $ViewContents = "";
        ob_start();
        if (is_null($ViewHandler)) {
            // Parse the view and place it into the asset container if it was found.
            include $ViewPath;
        } else {
            // Use the view handler to parse the view.
            $ViewHandler->render($ViewPath, $this);
        }
        $ViewContents = ob_get_clean();

        return $ViewContents;
    }

    /**
     * Fetches the location of a view into a string and returns it. Returns
     * false on failure.
     *
     * @param string $view The name of the view to fetch. If not specified, it will use the value
     * of $this->View. If $this->View is not specified, it will use the value
     * of $this->RequestMethod (which is defined by the dispatcher class).
     * @param bool|string $controllerName The name of the controller that owns the view if it is not $this.
     *  - If the controller name is FALSE then the name of the current controller will be used.
     *  - If the controller name is an empty string then the view will be looked for in the base views folder.
     * @param bool|string $applicationFolder The name of the application folder that contains the requested controller
     * if it is not $this->ApplicationFolder.
     * @param bool $throwError Whether to throw an error.
     * @param bool $useController Whether to attach a controller to the view location. Some plugins have views that
     * should not be looked up in a controller's view directory.
     * @return string The resolved location of the view.
     */
    public function fetchViewLocation(
        $view = "",
        $controllerName = false,
        $applicationFolder = false,
        $throwError = true,
        $useController = true
    ) {
        // Reactions has been moved to core.
        // This is a shim to make sure custom addons referencing reaction views still work.
        if ($applicationFolder === "plugins/Reactions") {
            $controllerName = "reactions";
            $applicationFolder = "dashboard";
        }
        // Accept an explicitly defined view, or look to the method that was called on this controller
        if ($view == "") {
            $view = $this->View;
        }

        if ($view == "") {
            $view = $this->RequestMethod;
        }

        if ($controllerName === false) {
            $controllerName = $this->ControllerName;
        }

        if (stringEndsWith($controllerName, "controller", true)) {
            $controllerName = substr($controllerName, 0, -10);
        }

        if (strtolower(substr($controllerName, 0, 4)) == "gdn_") {
            $controllerName = substr($controllerName, 4);
        }

        if (!$applicationFolder) {
            $applicationFolder = $this->ApplicationFolder;
        }

        //$ApplicationFolder = strtolower($ApplicationFolder);
        $controllerName = strtolower($controllerName);
        if (strpos($view, DS) === false) {
            // keep explicit paths as they are.
            $view = strtolower($view);
        }

        // If this is a syndication request, append the method to the view
        if ($this->SyndicationMethod == SYNDICATION_ATOM) {
            $view .= "_atom";
        } elseif ($this->SyndicationMethod == SYNDICATION_RSS) {
            $view .= "_rss";
        }

        $locationName = concatSep("/", strtolower($applicationFolder), $controllerName, $view);
        $viewPath = val($locationName, $this->_ViewLocations, false);
        if ($viewPath === false) {
            // Define the search paths differently depending on whether or not we are in a plugin or application.
            $applicationFolder = trim($applicationFolder, "/");
            if (stringBeginsWith($applicationFolder, "plugins/")) {
                $keyExplode = explode("/", $applicationFolder);
                $pluginName = array_pop($keyExplode);
                $pluginInfo = Gdn::pluginManager()->getPluginInfo($pluginName);

                $basePath = val("SearchPath", $pluginInfo);
                $applicationFolder = val("Folder", $pluginInfo);
            } elseif ($applicationFolder === "core") {
                $basePath = PATH_ROOT;
                $applicationFolder = "resources";
            } else {
                $basePath = PATH_APPLICATIONS;
                $applicationFolder = strtolower($applicationFolder);
            }

            $subPaths = [];
            // Define the subpath for the view.
            // The $ControllerName used to default to '' instead of FALSE.
            // This extra search is added for backwards-compatibility.
            if (strlen($controllerName) > 0 && $useController) {
                $subPaths[] = "views/$controllerName/$view";
            } else {
                $subPaths[] = "views/$view";

                if ($useController) {
                    $subPaths[] = "views/" . stringEndsWith($this->ControllerName, "Controller", true, true) . "/$view";
                }
            }

            // Views come from one of four places:
            $viewPaths = [];

            // 1. An explicitly defined path to a view
            if (strpos($view, DS) !== false && stringBeginsWith($view, PATH_ROOT)) {
                $viewPaths[] = $view;
            }

            if ($this->Theme) {
                // 2. Application-specific theme view. eg. /path/to/application/themes/theme_name/app_name/views/controller_name/
                foreach ($subPaths as $subPath) {
                    $viewPaths[] = PATH_THEMES . "/{$this->Theme}/$applicationFolder/$subPath.*";
                    $viewPaths[] = PATH_ADDONS_THEMES . "/{$this->Theme}/$applicationFolder/$subPath.*";
                }

                // 3. Garden-wide theme view. eg. /path/to/application/themes/theme_name/views/controller_name/
                foreach ($subPaths as $subPath) {
                    $viewPaths[] = PATH_THEMES . "/{$this->Theme}/$subPath.*";
                    $viewPaths[] = PATH_ADDONS_THEMES . "/{$this->Theme}/$subPath.*";
                }
            }

            // 4. Application/plugin default. eg. /path/to/application/app_name/views/controller_name/
            foreach ($subPaths as $subPath) {
                $viewPaths[] = "$basePath/$applicationFolder/$subPath.*";
                //$ViewPaths[] = combinePaths(array(PATH_APPLICATIONS, $ApplicationFolder, 'views', $ControllerName, $View . '.*'));
            }

            // Find the first file that matches the path.
            $viewPath = false;
            foreach ($viewPaths as $glob) {
                $paths = safeGlob($glob);
                if (is_array($paths) && count($paths) > 0) {
                    $viewPath = $paths[0];
                    break;
                }
            }

            $this->_ViewLocations[$locationName] = $viewPath;
        }
        if ($viewPath === false && $throwError) {
            Gdn::dispatcher()->passData("ViewPaths", $viewPaths);
            throw notFoundException("View");
        }

        return $viewPath;
    }

    /**
     * Cleanup any remaining resources for this controller.
     */
    public function finalize()
    {
        $this->fireAs("Gdn_Controller")->fireEvent("Finalize");
    }

    /**
     * Get an asset with the given asset name.
     *
     * @param string $assetName
     * @return mixed
     */
    public function getAsset($assetName)
    {
        if (!array_key_exists($assetName, $this->Assets)) {
            return "";
        }
        if (!is_array($this->Assets[$assetName])) {
            return $this->Assets[$assetName];
        }

        // Include the module sort
        $modules = array_change_key_case(c("Modules", []));
        $sortContainer = strtolower($this->ModuleSortContainer);
        $applicationName = strtolower($this->Application);

        if ($this->ModuleSortContainer === false) {
            $moduleSort = false; // no sort wanted
        } elseif (isset($modules[$sortContainer][$assetName])) {
            $moduleSort = $modules[$sortContainer][$assetName]; // explicit sort
        } elseif (isset($modules[$applicationName][$assetName])) {
            $moduleSort = $modules[$applicationName][$assetName]; // application default sort
        }

        // Get all the assets for this AssetContainer
        $thisAssets = $this->Assets[$assetName];
        $assets = [];

        if (isset($moduleSort) && is_array($moduleSort)) {
            // There is a specified sort so sort by it.
            foreach ($moduleSort as $name) {
                if (array_key_exists($name, $thisAssets)) {
                    $assets[] = $thisAssets[$name];
                    unset($thisAssets[$name]);
                }
            }
        }

        // Pick up any leftover assets that werent explicitly sorted
        foreach ($thisAssets as $name => $asset) {
            $assets[] = $asset;
        }

        if (count($assets) == 0) {
            return "";
        } elseif (count($assets) == 1) {
            return $assets[0];
        } else {
            $result = new Gdn_ModuleCollection();
            $result->Items = $assets;
            return $result;
        }
    }

    /**
     * Get the current Head.
     *
     * @return mixed
     */
    public function getHead()
    {
        return $this->Head;
    }

    /**
     * Get Inform messages.
     *
     * @return array
     */
    public function getInformMessages(): array
    {
        return $this->_InformMessages;
    }

    /**
     * Get all of the variable imports.
     * @deprecated
     */
    public function getImports()
    {
        if (!isset($this->Uses) || !is_array($this->Uses)) {
            return;
        }

        // Load any classes in the uses array and make them properties of this class
        foreach ($this->Uses as $Class) {
            if (strlen($Class) >= 4 && substr_compare($Class, "Gdn_", 0, 4) == 0) {
                $Property = substr($Class, 4);
            } else {
                $Property = $Class;
            }

            // Find the class and instantiate an instance..
            if (Gdn::factoryExists($Property)) {
                $this->$Property = Gdn::factory($Property);
            }
            if (Gdn::factoryExists($Class)) {
                // Instantiate from the factory.
                $this->$Property = Gdn::factory($Class);
            } elseif (class_exists($Class)) {
                // Instantiate as an object.
                $this->$Property = new $Class();
            } else {
                trigger_error(
                    errorMessage('The "' . $Class . '" class could not be found.', $this->ClassName, "__construct"),
                    E_USER_ERROR
                );
            }
        }
    }

    /**
     * Get JSON.
     *
     * @return array
     */
    public function getJson()
    {
        return $this->_Json;
    }

    /**
     * Allows images to be specified for the page, to be used by the head module
     * to add facebook open graph information.
     *
     * @param mixed $img An image or array of image urls.
     * @return array The array of image urls.
     */
    public function image($img = false)
    {
        if ($img) {
            if (!is_array($img)) {
                $img = [$img];
            }

            $currentImages = $this->data("_Images");
            if (!is_array($currentImages)) {
                $this->setData("_Images", $img);
            } else {
                $images = array_unique(array_merge($currentImages, $img));
                $this->setData("_Images", $images);
            }
        }
        $images = $this->data("_Images");
        return is_array($images) ? $images : [];
    }

    /**
     * Add an "inform" message to be displayed to the user.
     *
     * @param string $message The message to be displayed.
     * @param mixed $options An array of options for the message. If not an array, it is assumed to be a string of CSS
     * classes to apply to the message.
     * @since 2.0.18
     *
     */
    public function informMessage($message, $options = ["CssClass" => "Dismissable AutoDismiss"])
    {
        // If $Options isn't an array of options, accept it as a string of css classes to be assigned to the message.
        if (!is_array($options)) {
            $options = ["CssClass" => $options];
        }

        if (!$message && !array_key_exists("id", $options)) {
            return;
        }

        $options["Message"] = $message;
        $this->_InformMessages[] = $options;
    }

    /**
     * The initialize method is called by the dispatcher after the constructor
     * has completed, objects have been passed along, assets have been
     * retrieved, and before the requested method fires. Use it in any extended
     * controller to do things like loading script and CSS into the head.
     */
    public function initialize()
    {
        if (in_array($this->SyndicationMethod, [SYNDICATION_ATOM, SYNDICATION_RSS])) {
            $this->_Headers["Content-Type"] = "text/xml; charset=utf-8";
        }

        if (is_object($this->Menu)) {
            $this->Menu->Sort = Gdn::config("Garden.Menu.Sort");
        }
        $this->fireEvent("Initialize");
    }

    /**
     * Get all of the currently added js files.
     *
     * @return array
     */
    public function jsFiles()
    {
        return $this->_JsFiles;
    }

    /**
     * Determines whether a method on this controller is internal and can't be dispatched.
     *
     * @param string $methodName The name of the method.
     * @return bool Returns true if the method is internal or false otherwise.
     */
    public function isInternal($methodName)
    {
        $result = substr($methodName, 0, 1) === "_" || in_array(strtolower($methodName), $this->internalMethods);
        return $result;
    }

    /**
     * Determine if this is a valid API v1 (Simple API) request. Write methods optionally require valid authentication.
     *
     * @param bool $validateAuth Verify access token has been validated for write methods.
     * @return bool
     */
    private function isLegacyAPI($validateAuth = true)
    {
        $result = false;

        // API v1 tags the dispatcher with an "API" property.
        if (val("API", Gdn::dispatcher())) {
            $method = strtolower(Gdn::request()->getMethod());
            $readMethods = ["get"];
            if ($validateAuth && !in_array($method, $readMethods)) {
                /**
                 * API v1 bypasses TK checks if the access token was valid.
                 * Do not trust the presence of a valid user ID. An API call could be made by a signed-in user without using an access token.
                 */
                $result = Gdn::session()->validateTransientKey(null) === true;
            } else {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * If JSON is going to be sent to the client, this method allows you to add
     * extra values to the JSON array.
     *
     * @param string $key The name of the array key to add.
     * @param mixed $value The value to be added. If null, then it won't be set.
     * @return mixed The value at the key.
     */
    public function json($key, $value = null)
    {
        if (!is_null($value)) {
            $this->_Json[$key] = $value;
        }
        return val($key, $this->_Json, null);
    }

    /**
     * Set a JSON target to pass js commands to the UI.
     *
     * @param string $target
     * @param mixed $data
     * @param string $type
     */
    public function jsonTarget($target, $data, $type = "Html")
    {
        $item = ["Target" => $target, "Data" => $data, "Type" => $type];

        if (!array_key_exists("Targets", $this->_Json)) {
            $this->_Json["Targets"] = [$item];
        } else {
            $this->_Json["Targets"][] = $item;
        }
    }

    /**
     * Define & return the master view.
     */
    public function masterView()
    {
        // Define some default master views unless one was explicitly defined
        if ($this->MasterView == "") {
            // If this is a syndication request, use the appropriate master view
            if ($this->SyndicationMethod == SYNDICATION_ATOM) {
                $this->MasterView = "atom";
            } elseif ($this->SyndicationMethod == SYNDICATION_RSS) {
                $this->MasterView = "rss";
            } else {
                $this->MasterView = "default"; // Otherwise go with the default
            }
        }
        return $this->MasterView;
    }

    /**
     * Gets or sets the name of the page for the controller.
     * The page name is meant to be a friendly name suitable to be consumed by developers.
     *
     * @param string|NULL $value A new value to set.
     */
    public function pageName($value = null)
    {
        if ($value !== null) {
            $this->_PageName = $value;
            return $value;
        }

        if ($this->_PageName === null) {
            if ($this->ControllerName) {
                $name = $this->ControllerName;
            } else {
                $name = get_class($this);
            }
            $name = strtolower($name);

            if (stringEndsWith($name, "controller", false)) {
                $name = substr($name, 0, -strlen("controller"));
            }

            return $name;
        } else {
            return $this->_PageName;
        }
    }

    /**
     * Checks that the user has the specified permissions. If the user does not, they are redirected to the DefaultPermission route.
     *
     * @param mixed $permission A permission or array of permission names required to access this resource.
     * @param bool $fullMatch If $permission is an array, $fullMatch indicates if all permissions specified are required.
     * If false, the user only needs one of the specified permissions.
     * @param string $junctionTable The name of the junction table for a junction permission.
     * @param int|string $junctionID The ID of the junction permission.
     */
    public function permission($permission, $fullMatch = true, $junctionTable = "", $junctionID = "")
    {
        $session = Gdn::session();

        if (!$session->checkPermission($permission, $fullMatch, $junctionTable, $junctionID)) {
            Logger::logAccess("security_denied", Logger::NOTICE, "{username} was denied access to {requestPath}.", [
                "permission" => $permission,
                Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                "trace" => \Vanilla\Utility\DebugUtils::stackTraceString(
                    debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
                ),
            ]);

            if (!$session->isValid() && $this->isRenderingMasterView()) {
                redirectTo("/entry/signin?Target=" . urlencode($this->Request->pathAndQuery()));
            } elseif (DebugUtils::isTestMode()) {
                throw permissionException();
            } else {
                Gdn::dispatcher()->dispatch("DefaultPermission");
                exit();
            }
        } else {
            $required = array_intersect((array) $permission, ["Garden.Settings.Manage", "Garden.Moderation.Manage"]);
            if (!empty($required)) {
                Logger::logAccess("security_access", Logger::INFO, "{username} accessed {requestPath}.", [
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                ]);
            }
        }
    }

    /**
     * Stop the current action and re-authenticate, if necessary.
     *
     * @param array $options Setting key 'ForceTimeout' to `true` will ignore the cooldown window between prompts.
     */
    public function reauth($options = [])
    {
        // If we've already gone through this then we are good.
        if (self::isReauthenticated()) {
            return;
        }

        // Make sure we're logged in.
        if (Gdn::session()->UserID == 0) {
            return;
        }

        // Make sure we aren't in an API v1 call.
        if ($this->isLegacyAPI()) {
            return;
        }

        // Don't ask for re-authentication on connect-only sites.
        if (Gdn::config("Garden.Registration.Method") === "Connect") {
            return;
        }

        // Random passwords created by SSO cannot be re-authenticated.
        $user = Gdn::userModel()->getID(Gdn::session()->UserID, DATASET_TYPE_ARRAY);
        if (($user["HashMethod"] ?? "") === "Random") {
            return;
        }

        // If the user has logged in recently enough, don't make them login again.
        $lastAuthenticated = Gdn::authenticator()
            ->identity()
            ->getAuthTime();
        $forceTimeout = $options["ForceTimeout"] ?? false;
        $inReauth = $this->Request->post("DoReauthenticate");
        if ($lastAuthenticated > 0 && !$forceTimeout && !$inReauth) {
            $sinceAuth = time() - $lastAuthenticated;
            if ($sinceAuth < self::REAUTH_TIMEOUT) {
                return;
            }
        }

        Gdn::dispatcher()->dispatch("/profile/authenticate", false);
        throw new \Vanilla\Exception\ExitException();
    }

    /**
     * Removes a CSS file from the collection.
     *
     * @param string $fileName The CSS file to search for.
     */
    public function removeCssFile($fileName)
    {
        foreach ($this->_CssFiles as $key => $fileInfo) {
            if ($fileInfo["FileName"] == $fileName) {
                unset($this->_CssFiles[$key]);
                return;
            }
        }
    }

    /**
     * Removes a JS file from the collection.
     *
     * @param string $fileName The JS file to search for.
     */
    public function removeJsFile($fileName)
    {
        foreach ($this->_JsFiles as $key => $fileInfo) {
            if ($fileInfo["FileName"] == $fileName) {
                unset($this->_JsFiles[$key]);
                return;
            }
        }
    }

    /**
     * Defines & retrieves the view and master view. Renders all content within
     * them to the screen.
     *
     * @param string $view
     * @param string|false $controllerName
     * @param string|false $applicationFolder
     * @param string $assetName The name of the asset container that the content should be rendered in.
     */
    public function xRender($view = "", $controllerName = false, $applicationFolder = false, $assetName = "Content")
    {
        // Remove the deliver type and method from the query string so they don't corrupt calls to Url.
        $this->Request->setValueOn(Gdn_Request::INPUT_GET, "DeliveryType", null);
        $this->Request->setValueOn(Gdn_Request::INPUT_GET, "DeliveryMethod", null);

        Gdn::pluginManager()->callEventHandlers($this, $this->ClassName, $this->RequestMethod, "Render");

        if ($this->_DeliveryType == DELIVERY_TYPE_NONE) {
            return;
        }

        // Handle deprecated StatusMessage values that may have been added by plugins
        $this->informMessage($this->StatusMessage);

        // If there were uncontrolled errors above the json data, wipe them out
        // before fetching it (otherwise the json will not be properly parsed
        // by javascript).
        if ($this->_DeliveryMethod == DELIVERY_METHOD_JSON) {
            if (ob_get_level()) {
                ob_clean();
            }
            $this->contentType("application/json; charset=utf-8");

            // Cross-Origin Resource Sharing (CORS)
            $this->setAccessControl();
        }

        if ($this->_DeliveryMethod == DELIVERY_METHOD_TEXT) {
            $this->contentType("text/plain");
        }

        // Make sure to clear out the content asset collection if this is a syndication request
        if ($this->SyndicationMethod !== SYNDICATION_NONE) {
            $this->Assets["Content"] = [];
        }

        // Define the view
        if (!in_array($this->_DeliveryType, [DELIVERY_TYPE_BOOL, DELIVERY_TYPE_DATA])) {
            $view = $this->fetchView($view, $controllerName, $applicationFolder);
            // Add the view to the asset container if necessary
            if ($this->_DeliveryType != DELIVERY_TYPE_VIEW) {
                $this->addAsset($assetName, $view, "Content");
            }
        }

        // Redefine the view as the entire asset contents if necessary
        if ($this->_DeliveryType == DELIVERY_TYPE_ASSET) {
            $view = $this->getAsset($assetName);
        } elseif ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
            // Or as a boolean if necessary
            $view = true;
            if (property_exists($this, "Form") && is_object($this->Form)) {
                $view = $this->Form->errorCount() > 0 ? false : true;
            }
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_MESSAGE && $this->Form) {
            $view = $this->Form->errors();
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_DATA) {
            $exitRender = $this->renderData();
            if ($exitRender) {
                return;
            }
        } else {
            // Headers are ready now.
            $this->sendHeaders();
        }

        if ($this->_DeliveryMethod == DELIVERY_METHOD_JSON) {
            // Format the view as JSON with some extra information about the
            // success status of the form so that jQuery knows what to do
            // with the result.
            if ($this->_FormSaved === "") {
                // Allow for override
                $this->_FormSaved = property_exists($this, "Form") && $this->Form->errorCount() == 0 ? true : false;
            }

            $this->setJson("FormSaved", $this->_FormSaved);
            $this->setJson("DeliveryType", $this->_DeliveryType);
            $this->setJson("Data", $view instanceof Gdn_IModule ? $view->toString() : $view);
            $this->setJson("InformMessages", $this->_InformMessages);
            $this->setJson("ErrorMessages", $this->_ErrorMessages);
            if ($this->redirectTo !== null) {
                // See redirectTo function for details about encoding backslashes.
                $this->setJson("RedirectTo", str_replace("\\", "%5c", $this->redirectTo));
                $this->setJson("RedirectUrl", str_replace("\\", "%5c", $this->redirectTo));
            } else {
                $this->setJson("RedirectTo", str_replace("\\", "%5c", $this->RedirectUrl));
                $this->setJson("RedirectUrl", str_replace("\\", "%5c", $this->RedirectUrl));
            }

            // Make sure the database connection is closed before exiting.
            $this->finalize();

            if (!check_utf8($this->_Json["Data"])) {
                $this->_Json["Data"] = utf8_encode($this->_Json["Data"]);
            }

            $json = ipDecodeRecursive($this->_Json);
            $json = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            $this->_Json["Data"] = $json;
            echo $json;
        } else {
            if ($this->SyndicationMethod === SYNDICATION_NONE) {
                if (count($this->_InformMessages) > 0) {
                    $this->addDefinition(
                        "InformMessageStack",
                        json_encode($this->_InformMessages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                    );
                }
                if ($this->redirectTo !== null) {
                    $this->addDefinition("RedirectTo", str_replace("\\", "%5c", $this->redirectTo));
                    $this->addDefinition("RedirectUrl", str_replace("\\", "%5c", $this->redirectTo));
                } else {
                    $this->addDefinition("RedirectTo", str_replace("\\", "%5c", $this->RedirectUrl));
                    $this->addDefinition("RedirectUrl", str_replace("\\", "%5c", $this->RedirectUrl));
                }
            }

            if ($this->_DeliveryMethod == DELIVERY_METHOD_XHTML && debug()) {
                $this->addModule("TraceModule");
            }

            // Render
            if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
                echo $view ? "TRUE" : "FALSE";
            } elseif ($this->isRenderingMasterView()) {
                // Render
                $this->renderMaster();
            } else {
                if ($view instanceof Gdn_IModule) {
                    $view->render();
                } else {
                    echo $view;
                }
            }
        }
    }

    /**
     * Set Access-Control-Allow-Origin header.
     *
     * If a Origin header is sent by the client, attempt to verify it against the list of
     * trusted domains in Garden.TrustedDomains.  If the value of Origin is verified as
     * being part of a trusted domain, add the Access-Control-Allow-Origin header to the
     * response using the client's Origin header value.
     */
    protected function setAccessControl()
    {
        $origin = Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, "HTTP_ORIGIN", false);
        if ($origin) {
            if (isTrustedDomain($origin)) {
                $this->setHeader("Access-Control-Allow-Origin", $origin);
                $this->setHeader("Access-Control-Allow-Credentials", "true");
            }
        }
    }

    /**
     * Searches $this->Assets for a key with $assetName and renders all items
     * within that array element to the screen. Note that any element in
     * $this->Assets can contain an array of elements itself. This way numerous
     * assets can be rendered one after another in one place.
     *
     * @param string $assetName The name of the asset to be rendered (the key related to the asset in
     * the $this->Assets associative array).
     */
    public function renderAsset($assetName)
    {
        $asset = $this->getAsset($assetName);

        $this->EventArguments["AssetName"] = $assetName;
        $this->fireEvent("BeforeRenderAsset");

        //$LengthBefore = ob_get_length();

        if (is_string($asset)) {
            echo $asset;
        } else {
            $asset->AssetName = $assetName;
            $asset->render();
        }

        $this->fireEvent("AfterRenderAsset");
    }

    /**
     * Return a twig wrapped HTML content of an asset.
     *
     * @param string $assetName The name of the asset.
     *
     * @return \Twig\Markup
     */
    public function renderAssetForTwig(string $assetName): \Twig\Markup
    {
        ob_start();
        try {
            $this->renderAsset($assetName);
            $echoedOutput = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        return new \Twig\Markup($echoedOutput, "utf-8");
    }

    /**
     * Render the data array.
     *
     * @param null $data
     * @return bool
     */
    public function renderData($data = null)
    {
        if ($data === null) {
            $data = [];

            // Remove standard and "protected" data from the top level.
            foreach ($this->Data as $key => $value) {
                if ($key && in_array($key, ["Title", "Breadcrumbs", "isHomepage"])) {
                    continue;
                }
                if (isset($key[0]) && $key[0] === "_") {
                    continue; // protected
                }
                $data[$key] = $value;
            }
            // Wipe the data.
            $this->Data = [];
        }

        // Massage the data for better rendering.
        foreach ($data as $key => $value) {
            if (is_a($value, "Gdn_DataSet")) {
                $data[$key] = $value->resultArray();
            }
        }

        $cleanOutput = c("Api.Clean", true);
        if ($cleanOutput) {
            // Remove values that should not be transmitted via api
            $remove = ["Password", "HashMethod", "TransientKey", "Permissions", "Attributes", "AccessToken"];

            // Remove PersonalInfo values for unprivileged requests.
            if (!Gdn::session()->checkPermission("Garden.Moderation.Manage")) {
                $remove[] = "InsertIPAddress";
                $remove[] = "UpdateIPAddress";
                $remove[] = "LastIPAddress";
                $remove[] = "AllIPAddresses";
                $remove[] = "Fingerprint";
                $remove[] = "DateOfBirth";
                $remove[] = "Preferences";
                $remove[] = "Banned";
                $remove[] = "Admin";
                $remove[] = "Verified";
                $remove[] = "DiscoveryText";
                $remove[] = "InviteUserID";
                $remove[] = "DateSetInvitations";
                $remove[] = "CountInvitations";
                $remove[] = "CountNotifications";
                $remove[] = "CountBookmarks";
                $remove[] = "CountDrafts";
                $remove[] = "Punished";
                $remove[] = "Troll";

                if (empty($data["UserID"]) || $data["UserID"] != Gdn::session()->UserID) {
                    if (c("Api.Clean.Email", true)) {
                        $remove[] = "Email";
                    }
                    $remove[] = "Confirmed";
                    $remove[] = "HourOffset";
                    $remove[] = "Gender";
                }
            }
            $data = removeKeysFromNestedArray($data, $remove);
        }

        if (debug() && $this->deliveryMethod() !== DELIVERY_METHOD_XML && ($Trace = trace())) {
            // Clear passwords from the trace.
            array_walk_recursive($Trace, function (&$value, $key) {
                if (in_array(strtolower($key), ["password"])) {
                    $value = "***";
                }
            });
            $data["Trace"] = $Trace;
        }

        // Make sure the database connection is closed before exiting.
        $this->EventArguments["Data"] = &$data;
        $this->finalize();

        // Add error information from the form.
        if (isset($this->Form) && sizeof($this->Form->validationResults())) {
            $this->statusCode(400);
            $data["Code"] = 400;
            $data["Exception"] = Gdn_Validation::resultsAsText($this->Form->validationResults());
        }

        $this->sendHeaders();

        $data = ipDecodeRecursive($data);

        // Check for a special view.
        $viewLocation = $this->fetchViewLocation(
            ($this->View ? $this->View : $this->RequestMethod) . "_" . strtolower($this->deliveryMethod()),
            false,
            false,
            false
        );
        if (file_exists($viewLocation)) {
            include $viewLocation;
            return;
        }

        // Add schemes to to urls.
        if (!c("Garden.AllowSSL") || c("Garden.ForceSSL")) {
            $r = array_walk_recursive($data, ["Gdn_Controller", "_fixUrlScheme"], Gdn::request()->scheme());
        }

        if (ob_get_level()) {
            ob_clean();
        }
        switch ($this->deliveryMethod()) {
            case DELIVERY_METHOD_XML:
                safeHeader("Content-Type: text/xml", true);
                echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
                $this->_renderXml($data);
                return true;
                break;
            case DELIVERY_METHOD_PLAIN:
                return true;
                break;
            case DELIVERY_METHOD_JSON:
            default:
                $jsonData = jsonEncodeChecked($data);

                if (($Callback = $this->Request->get("callback", false)) && $this->allowJSONP()) {
                    safeHeader("Content-Type: application/javascript; charset=utf-8", true);
                    // This is a jsonp request.
                    echo "{$Callback}({$jsonData});";
                    return true;
                } else {
                    safeHeader("Content-Type: application/json; charset=utf-8", true);
                    // This is a regular json request.
                    echo $jsonData;
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     * Render a page that hosts a react component.
     */
    public function renderReact(string $innerContent = "")
    {
        if (!$this->data("hasPanel")) {
            $this->CssClass .= " NoPanel";
        }
        $this->setData("seoReactContent", $innerContent);
        $this->render("react", "", "core");
    }

    /**
     * Make sure a URL has a scheme rather than the "//" notation.
     *
     * @param string|mixed $value
     * @param string $key
     * @param string $scheme
     */
    protected static function _fixUrlScheme(&$value, $key, $scheme)
    {
        if (!is_string($value)) {
            return;
        }

        if (substr($value, 0, 2) == "//" && substr($key, -3) == "Url") {
            $value = $scheme . ":" . $value;
        }
    }

    /**
     * A simple default method for rendering xml.
     *
     * @param mixed $data The data to render. This is usually $this->Data.
     * @param string $node The name of the root node.
     * @param string $indent The indent before the data for layout that is easier to read.
     */
    protected function _renderXml($data, $node = "Data", $indent = "")
    {
        // Handle numeric arrays.
        if (is_numeric($node)) {
            $node = "Item";
        }

        if (!$node) {
            return;
        }
        $node = htmlspecialchars($node, ENT_XML1);
        echo "$indent<$node>";

        if (is_scalar($data)) {
            echo htmlspecialchars($data);
        } else {
            $data = (array) $data;
            if (count($data) > 0) {
                foreach ($data as $key => $value) {
                    echo "\n";
                    $this->_renderXml($value, $key, $indent . " ");
                }
                echo "\n";
            }
        }
        echo "</$node>";
    }

    /**
     * Render an exception as the sole output.
     *
     * @param Exception $ex The exception to render.
     */
    public function renderException($ex)
    {
        $isContainerException = $ex instanceof \Garden\Container\NotFoundException;
        if ($this->deliveryMethod() == DELIVERY_METHOD_XHTML) {
            try {
                // Pick our route.
                switch ($ex->getCode()) {
                    case 401:
                    case 403:
                        $route = "DefaultPermission";
                        break;
                    case 404:
                        if ($isContainerException) {
                            $route = "/home/error";
                        } else {
                            $route = "Default404";
                        }
                        break;
                    default:
                        $route = "/home/error";
                }

                // Log forbidden exceptions as security events.
                if (in_array($ex->getCode(), [401, 403])) {
                    Logger::logAccess("security_denied", Logger::NOTICE, $ex->getMessage(), [
                        Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                        "trace" => \Vanilla\Utility\DebugUtils::stackTraceString($ex->getTrace(), 3),
                    ]);
                }

                // Redispatch to our error handler.
                $code = $ex->getCode();
                if (is_a($ex, "Gdn_UserException") && $code >= 400 && $code <= 499) {
                    // UserExceptions provide more info.
                    Gdn::dispatcher()
                        ->passData("Code", $code)
                        ->passData("Exception", $ex->getMessage())
                        ->passData("Message", $ex->getMessage())
                        ->passData("Trace", $ex->getTraceAsString())
                        ->passData("Url", url())
                        ->passData("Breadcrumbs", $this->data("Breadcrumbs", []))
                        ->dispatch($route);
                } elseif (in_array($ex->getCode(), [401, 403, 404]) && !$isContainerException) {
                    // Default forbidden & not found codes.
                    Gdn::dispatcher()
                        ->passData("Message", $ex->getMessage())
                        ->passData("Url", url());

                    if ($ex instanceof Garden\Web\Exception\HttpException) {
                        Gdn::dispatcher()->passData("Description", $ex->getDescription());
                    }

                    Gdn::dispatcher()->dispatch($route);
                } else {
                    // I dunno! Barf.
                    gdnExceptionHandler($ex);
                }
            } catch (Exception $ex2) {
                gdnExceptionHandler($ex);
            }
            return;
        }

        // Make sure the database connection is closed before exiting.
        $this->finalize();
        $this->sendHeaders();

        $code = $ex->getCode();
        $data = ["Code" => $code, "Exception" => $ex->getMessage(), "Class" => get_class($ex)];

        if (debug()) {
            if ($trace = trace()) {
                // Clear passwords from the trace.
                array_walk_recursive($trace, function (&$value, $key) {
                    if (in_array(strtolower($key), ["password"])) {
                        $value = "***";
                    }
                });
                $data["Trace"] = $trace;
            }

            if (!is_a($ex, "Gdn_UserException")) {
                $data["StackTrace"] = DebugUtils::stackTraceString($ex->getTrace());
            }

            $data["Data"] = $this->Data;
        }

        // Try cleaning out any notices or errors.
        if (ob_get_level()) {
            ob_clean();
        }

        if ($code >= 400 && $code <= 505) {
            safeHeader("HTTP/1.0 $code", true, $code);
        } else {
            safeHeader("HTTP/1.0 500", true, 500);
        }

        switch ($this->deliveryMethod()) {
            case DELIVERY_METHOD_JSON:
                if (
                    ($callback = $this->Request->getValueFrom(Gdn_Request::INPUT_GET, "callback", false)) &&
                    $this->allowJSONP()
                ) {
                    safeHeader("Content-Type: application/javascript; charset=utf-8", true);
                    // This is a jsonp request.
                    exit($callback . "(" . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . ");");
                } else {
                    safeHeader("Content-Type: application/json; charset=utf-8", true);
                    // This is a regular json request.
                    exit(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
                }
                break;
            //         case DELIVERY_METHOD_XHTML:
            //            gdnExceptionHandler($Ex);
            //            break;
            case DELIVERY_METHOD_XML:
                safeHeader("Content-Type: text/xml; charset=utf-8", true);
                array_map("htmlspecialchars", $data);
                exit(
                    "<Exception><Code>{$data["Code"]}</Code><Class>{$data["Class"]}</Class><Message>{$data["Exception"]}</Message></Exception>"
                );
                break;
            default:
                safeHeader("Content-Type: text/plain; charset=utf-8", true);
                exit($ex->getMessage());
        }
    }

    /**
     * Render the master view.
     */
    public function renderMaster()
    {
        $themeType = isMobile() ? "mobile" : "desktop";
        // Build the master view if necessary
        if ($this->isRenderingMasterView()) {
            $this->MasterView = $this->masterView();

            // Only get css & ui Components if this is NOT a syndication request
            if ($this->SyndicationMethod == SYNDICATION_NONE && is_object($this->Head)) {
                $cssAnchors = LegacyAssetModel::getAnchors();
                $assetProvider = \Gdn::getContainer()->get(ViteAssetProvider::class);
                $inlineScript = $assetProvider->getBootstrapInlineScript();
                $isMinificationEnabled = Gdn::config("minify.scripts", true);
                if ($isMinificationEnabled) {
                    $jsMinifier = new MatthiasMullie\Minify\JS($inlineScript);
                    $bootstrapInline = $jsMinifier->minify();
                    $this->Head->addScript("", "", false, ["content" => $bootstrapInline, HeadModule::SORT_KEY => -1]);
                } else {
                    $this->Head->addScript("", "", false, ["content" => $inlineScript, HeadModule::SORT_KEY => -1]);
                }

                $this->EventArguments["CssFiles"] = &$this->_CssFiles;
                $this->fireEvent("BeforeAddCss");

                $etag = LegacyAssetModel::eTag();
                /* @var LegacyAssetModel $assetModel */
                $assetModel = Gdn::getContainer()->get(LegacyAssetModel::class);

                // And now search for/add all css files.
                foreach ($this->_CssFiles as $cssInfo) {
                    $cssFile = $cssInfo["FileName"];
                    if (!array_key_exists("Options", $cssInfo) || !is_array($cssInfo["Options"])) {
                        $cssInfo["Options"] = [];
                    }
                    $options = &$cssInfo["Options"];

                    // style.css and admin.css deserve some custom processing.
                    if (in_array($cssFile, $cssAnchors)) {
                        // Grab all of the css files from the asset model.
                        $cssFiles = $assetModel->getCssFiles(
                            $themeType,
                            ucfirst(substr($cssFile, 0, -4)),
                            $etag,
                            $_,
                            $this->Theme
                        );
                        foreach ($cssFiles as $Info) {
                            $this->Head->addCss($Info[1], "all", true, $cssInfo);
                        }
                        continue;
                    }

                    $appFolder = $cssInfo["AppFolder"];
                    $lookupFolder = !empty($appFolder) ? $appFolder : $this->ApplicationFolder;
                    $search = LegacyAssetModel::cssPath($cssFile, $lookupFolder, $themeType);
                    if (!$search) {
                        continue;
                    }

                    [$path, $urlPath] = $search;

                    if (isUrl($path)) {
                        $this->Head->addCss($path, "all", val("AddVersion", $options, true), $options);
                        continue;
                    } else {
                        // Check to see if there is a CSS cacher.
                        $hasCacher = Gdn::getContainer()->has("CssCacher");
                        if ($hasCacher) {
                            $cssCacher = Gdn::getContainer()->get("CssCacher");
                            $path = $cssCacher->get($path, $appFolder);
                        }

                        if ($path !== false) {
                            $path = substr($path, strlen(PATH_ROOT));
                            $path = str_replace(DS, "/", $path);
                            $this->Head->addCss($path, "all", true, $options);
                        }
                    }
                }

                // Add a custom js file.
                if (arrayHasValue($this->_CssFiles, "style.css")) {
                    $this->addJsFile("custom.js"); // only to non-admin pages.
                }

                $cdns = [];

                // And now search for/add all JS files.
                $this->EventArguments["Cdns"] = &$cdns;
                $this->fireEvent("AfterJsCdns");

                // Add inline content meta.
                $this->Head->addScript("", "text/javascript", false, ["content" => $this->definitionList(false)]);

                // Add legacy style scripts
                foreach ($this->_JsFiles as $Index => $jsInfo) {
                    $jsFile = $jsInfo["FileName"];
                    if (!is_array($jsInfo["Options"])) {
                        $jsInfo["Options"] = [];
                    }
                    $options = &$jsInfo["Options"];

                    if ($this->useDeferredLegacyScripts) {
                        $options["defer"] = "defer";
                    }

                    if (isset($cdns[$jsFile])) {
                        $jsFile = $cdns[$jsFile];
                    }

                    $appFolder = $jsInfo["AppFolder"];
                    $lookupFolder = !empty($appFolder) ? $appFolder : $this->ApplicationFolder;
                    $search = LegacyAssetModel::jsPath($jsFile, $lookupFolder, $themeType);
                    if (!$search) {
                        continue;
                    }

                    [$path, $urlPath] = $search;

                    if ($path !== false) {
                        $addVersion = true;
                        if (!isUrl($path)) {
                            $path = substr($path, strlen(PATH_ROOT));
                            $path = str_replace(DS, "/", $path);
                            $addVersion = val("AddVersion", $options, true);
                        }
                        $this->Head->addScript($path, "text/javascript", $addVersion, $options);
                        continue;
                    }
                }

                $this->addViteAssets();
                $this->addThemeAssets();
                $this->registerDashboardReduxActions();

                // Add preloaded redux actions.
                $this->Head->addScript("", "text/javascript", false, [
                    "content" => $this->getReduxActionsAsJsVariable(),
                ]);
            }

            // Add the favicon.
            $favicon = c("Garden.FavIcon");
            if ($favicon) {
                $this->Head->setFavIcon(Gdn_Upload::url($favicon));
            }

            $touchIcon = c("Garden.TouchIcon");
            if ($touchIcon) {
                $this->Head->setTouchIcon(Gdn_Upload::url($touchIcon));
            }

            // Add address bar color.
            $mobileAddressBarColor = c("Garden.MobileAddressBarColor");
            if (!empty($mobileAddressBarColor)) {
                $this->Head->setMobileAddressBarColor($mobileAddressBarColor);
            }

            // Add config defined SEO metas.
            $seoMetaModel = \Gdn::getContainer()->get(SeoMetaModel::class);
            $seoMetas = $seoMetaModel->getMetas();
            foreach ($seoMetas as $seoMeta) {
                $this->Head->addTag("meta", $seoMeta);
            }

            // Make sure the head module gets passed into the assets collection.
            $this->addModule("Head");
        }

        // Master views come from one of four places:
        $masterViewPaths = [];

        if (strpos($this->MasterView, "/") !== false) {
            $masterViewPaths[] = combinePaths([PATH_ROOT, str_replace("/", DS, $this->MasterView) . ".master*"]);
        } else {
            if ($this->Theme) {
                // 1. Application-specific theme view. eg. root/themes/theme_name/app_name/views/
                $masterViewPaths[] = combinePaths([
                    PATH_THEMES,
                    $this->Theme,
                    $this->ApplicationFolder,
                    "views",
                    $this->MasterView . ".master*",
                ]);
                $masterViewPaths[] = combinePaths([
                    PATH_ADDONS_THEMES,
                    $this->Theme,
                    $this->ApplicationFolder,
                    "views",
                    $this->MasterView . ".master*",
                ]);
                // 2. Garden-wide theme view. eg. /path/to/application/themes/theme_name/views/
                $masterViewPaths[] = combinePaths([PATH_THEMES, $this->Theme, "views", $this->MasterView . ".master*"]);
                $masterViewPaths[] = combinePaths([
                    PATH_ADDONS_THEMES,
                    $this->Theme,
                    "views",
                    $this->MasterView . ".master*",
                ]);
            }
            // 3. Plugin default. eg. root/plugin_name/views/
            $masterViewPaths[] = combinePaths([
                PATH_ROOT,
                $this->ApplicationFolder,
                "views",
                $this->MasterView . ".master*",
            ]);
            // 4. Application default. eg. root/app_name/views/
            $masterViewPaths[] = combinePaths([
                PATH_APPLICATIONS,
                $this->ApplicationFolder,
                "views",
                $this->MasterView . ".master*",
            ]);
            // 5. Garden default. eg. root/dashboard/views/
            $masterViewPaths[] = combinePaths([
                PATH_APPLICATIONS,
                "dashboard",
                "views",
                $this->MasterView . ".master*",
            ]);
        }

        // Find the first file that matches the path.
        $masterViewPath = false;
        foreach ($masterViewPaths as $glob) {
            $paths = safeGlob($glob);
            if (is_array($paths) && count($paths) > 0) {
                $masterViewPath = $paths[0];
                break;
            }
        }

        $this->EventArguments["MasterViewPath"] = &$masterViewPath;
        $this->fireEvent("BeforeFetchMaster");

        /// A unique identifier that can be used in the body tag of the master view if needed.
        $controllerName = $this->ClassName;
        // Strip "Controller" from the body identifier.
        if (substr($controllerName, -10) == "Controller") {
            $controllerName = substr($controllerName, 0, -10);
        }

        // Strip "Gdn_" from the body identifier.
        if (substr($controllerName, 0, 4) == "Gdn_") {
            $controllerName = substr($controllerName, 4);
        }

        $themeSections = Gdn_Theme::section(null, "get");
        $sectionClasses = array_map(function ($section) {
            return "Section-" . $section;
        }, $themeSections);

        $cssClass = HtmlUtils::classNames(
            ucfirst($this->Application),
            $controllerName,
            "is" . ucfirst($themeType),
            $this->RequestMethod,
            $this->CssClass,
            ...$sectionClasses
        );
        $this->setData("CssClass", $cssClass, true);

        if ($this->MasterView === "admin") {
            /** @var LegacyDashboardPage $page */
            $page = Gdn::getContainer()->get(LegacyDashboardPage::class);
            $page->initialize($this);
            echo $page->renderPage();
            return;
        }

        if ($this->MasterView === "default" && ($this->isReactView || Gdn::themeFeatures()->useSharedMasterView())) {
            /** @var MasterViewRenderer $viewRenderer */
            $viewRenderer = Gdn::getContainer()->get(MasterViewRenderer::class);
            $result = $viewRenderer->renderGdnController($this);

            echo $result;
        } else {
            // Force our icons into the legacy master template
            // This is a little hacky but the browsers seem to render it just fine (and treat it as part of the body).
            // Modern views just properly put it as the first thing in the body.
            $this->getHead()->addString(TwigStaticRenderer::renderTwigStatic("@resources/svg-symbols.html", []));

            // Check to see if there is a handler for this particular extension.
            $viewHandler = Gdn::factory("ViewHandler" . strtolower(strrchr($masterViewPath, ".")));
            if (is_null($viewHandler)) {
                $bodyIdentifier = strtolower(
                    $this->ApplicationFolder .
                        "_" .
                        $controllerName .
                        "_" .
                        Gdn_Format::alphaNumeric(strtolower($this->RequestMethod))
                );
                $this->BodyIdentifier = $bodyIdentifier;
                include $masterViewPath;
            } else {
                $viewHandler->render($masterViewPath, $this);
            }
        }
    }

    /**
     * Get theming assets for the page.
     */
    private function addThemeAssets()
    {
        if (!$this->isRenderingMasterView()) {
            // We only want to load theme data for full page loads & controllers that require theming data.
            return;
        }

        /** @var ThemePreloadProvider $themeProvider */
        $themeProvider = Gdn::getContainer()->get(ThemePreloadProvider::class);
        if (!$this->allowCustomTheming || $this->MasterView === "admin") {
            $themeProvider->setForcedThemeKey("theme-dashboard");
        }

        $this->registerReduxActionProvider($themeProvider);
        $themeScript = $themeProvider->getThemeScript();
        if ($themeScript !== null) {
            $this->Head->addScript($themeScript->getWebPath(), "text/javascript", true, [
                "static" => $themeScript->isStatic(),
            ]);
        }
    }

    /**
     * Add the assets from ViteAssetProvider to the page.
     */
    private function addViteAssets()
    {
        $assetProvider = \Gdn::getContainer()->get(ViteAssetProvider::class);
        $section = $this->MasterView === "admin" ? "admin" : "forum";
        if ($assetProvider->isHotBuild()) {
            $hotBuildInline = $assetProvider->getHotBuildInlineScript();
            $this->Head->addScript("", "module", false, ["content" => $hotBuildInline]);
            $assets = $assetProvider->getHotBuildScriptAssets($section);
        } else {
            $assets = $assetProvider->getEnabledEntryAssets($section);
        }
        foreach ($assets as $viteAsset) {
            if ($viteAsset->isScriptModule()) {
                $this->Head->addScript($viteAsset->getWebPath(), "module", false);
            } elseif ($viteAsset->isScript()) {
                $this->Head->addScript($viteAsset->getWebPath(), "text/javascript", false, ["defer" => "defer"]);
            } elseif ($viteAsset->isStyleSheet()) {
                $this->Head->addCss($viteAsset->getWebPath(), null, false);
            }
        }

        // Noscript stylesheet
        $noScriptStyleAsset = \Gdn::getContainer()->get(NoScriptStylesAsset::class);
        $this->Head->addTag("noscript", [], "<link rel='stylesheet' href='{$noScriptStyleAsset->getWebPath()}'>");
    }

    /**
     * Register actions for DashboardApiController
     */
    private function registerDashboardReduxActions()
    {
        if ($this->MasterView === "admin") {
            $dashboardProvider = \Gdn::getContainer()->get(DashboardPreloadProvider::class);
            $this->registerReduxActionProvider($dashboardProvider);
        }
    }

    /**
     * Get the headers from the controller.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->_Headers ?? [];
    }

    /**
     * Sends all headers in $this->_Headers (defined with $this->setHeader()) to the browser.
     */
    public function sendHeaders()
    {
        // TODO: ALWAYS RENDER OR REDIRECT FROM THE CONTROLLER OR HEADERS WILL NOT BE SENT!! PUT THIS IN DOCS!!!
        foreach ($this->_Headers as $name => $value) {
            if ($name !== "Status") {
                safeHeader("$name: $value", true);
            } else {
                $shift = explode(" ", $value);
                $code = array_shift($shift);
                safeHeader("$name: $value", true, $code);
            }
        }

        if (!empty($this->_Headers[self::HEADER_CACHE_CONTROL])) {
            static::sendCacheControlHeaders($this->_Headers[self::HEADER_CACHE_CONTROL]);
        }

        // Keep track of the last rendered headers.
        // This is primarily for tests.
        \Gdn::dispatcher()->setSentHeaders($this->getHeaders());

        // Empty the collection after sending
        $this->_Headers = [];
    }

    /**
     * Allows the adding of page header information that will be delivered to
     * the browser before rendering.
     *
     * @param string $name The name of the header to send to the browser.
     * @param string $value The value of the header to send to the browser.
     */
    public function setHeader($name, $value)
    {
        $this->_Headers[$name] = $value;
    }

    /**
     * Set data from a method call.
     *
     * If $key is an array, the behaviour will be the same as calling the method
     * multiple times for each (key, value) pair in the $key array.
     * Note that the parameter $value will not be used if $key is an array.
     *
     * The $key can also use dot notation in order to set a value deeper inside the Data array.
     * Works the same way if $addProperty is true, but uses objects instead of arrays.
     *
     * @param string|array $key The key that identifies the data.
     * @param mixed $value The data.  Will not be used if $key is an array
     * @param mixed $addProperty Whether or not to also set the data as a property of this object.
     * @return mixed The $Value that was set.
     * @see setvalr
     *
     */
    public function setData($key, $value = null, $addProperty = false)
    {
        // In the case of $key being an array of (key => value),
        // it calls itself with each (key => value)
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setData($k, $v, $addProperty);
            }
            return;
        }

        setvalr($key, $this->Data, $value);

        if ($addProperty === true) {
            setvalr($key, $this, $value);
        }
        return $value;
    }

    /**
     * Set $this->_FormSaved for JSON Renders.
     *
     * @param bool $saved Whether form data was successfully saved.
     */
    public function setFormSaved($saved = true)
    {
        if ($saved === "") {
            // Allow reset
            $this->_FormSaved = "";
        } else {
            // Force true/false
            $this->_FormSaved = $saved ? true : false;
        }
    }

    /**
     * Looks for a Last-Modified header from the browser and compares it to the
     * supplied date. If the Last-Modified date is after the supplied date, the
     * controller will send a "304 Not Modified" response code to the web
     * browser and stop all execution. Otherwise it sets the Last-Modified
     * header for this page and continues processing.
     *
     * @param string $lastModifiedDate A unix timestamp representing the date that the current page was last
     *  modified.
     */
    public function setLastModified($lastModifiedDate)
    {
        $gMD = gmdate("D, d M Y H:i:s", $lastModifiedDate) . " GMT";
        $this->setHeader("Etag", '"' . $gMD . '"');
        $this->setHeader("Last-Modified", $gMD);
        $incomingHeaders = getallheaders();
        if (isset($incomingHeaders["If-Modified-Since"]) && isset($incomingHeaders["If-None-Match"])) {
            $ifNoneMatch = $incomingHeaders["If-None-Match"];
            $ifModifiedSince = $incomingHeaders["If-Modified-Since"];
            if ($gMD == $ifNoneMatch && $ifModifiedSince == $gMD) {
                $database = Gdn::database();
                if (!is_null($database)) {
                    $database->closeConnection();
                }

                $this->setHeader("Content-Length", "0");
                $this->sendHeaders();
                safeHeader("HTTP/1.1 304 Not Modified");
                exit("\n\n"); // Send two linefeeds so that the client knows the response is complete
            }
        }
    }

    /**
     * If JSON is going to be sent to the client, this method allows you to add
     * extra values to the JSON array.
     *
     * @param string $key The name of the array key to add.
     * @param string $value The value to be added. If empty, nothing will be added.
     */
    public function setJson($key, $value = "")
    {
        $this->_Json[$key] = $value;
    }

    /**
     * Set the HTTP status code.
     *
     * @param int $statusCode
     * @param null $message
     * @param bool $setHeader
     * @return null|string
     */
    public function statusCode($statusCode, $message = null, $setHeader = true)
    {
        if (is_null($message)) {
            $message = self::getStatusMessage($statusCode);
        }

        if ($setHeader) {
            $this->setHeader("Status", "{$statusCode} {$message}");
        }
        return $message;
    }

    /**
     * Get  the HTTP status message that corresponds to a status code.
     *
     * @param int $statusCode
     * @return string
     */
    public static function getStatusMessage($statusCode)
    {
        switch ($statusCode) {
            case 100:
                $message = "Continue";
                break;
            case 101:
                $message = "Switching Protocols";
                break;

            case 200:
                $message = "OK";
                break;
            case 201:
                $message = "Created";
                break;
            case 202:
                $message = "Accepted";
                break;
            case 203:
                $message = "Non-Authoritative Information";
                break;
            case 204:
                $message = "No Content";
                break;
            case 205:
                $message = "Reset Content";
                break;

            case 300:
                $message = "Multiple Choices";
                break;
            case 301:
                $message = "Moved Permanently";
                break;
            case 302:
                $message = "Found";
                break;
            case 303:
                $message = "See Other";
                break;
            case 304:
                $message = "Not Modified";
                break;
            case 305:
                $message = "Use Proxy";
                break;
            case 307:
                $message = "Temporary Redirect";
                break;

            case 400:
                $message = "Bad Request";
                break;
            case 401:
                $message = "Not Authorized";
                break;
            case 402:
                $message = "Payment Required";
                break;
            case 403:
                $message = "Forbidden";
                break;
            case 404:
                $message = "Not Found";
                break;
            case 405:
                $message = "Method Not Allowed";
                break;
            case 406:
                $message = "Not Acceptable";
                break;
            case 407:
                $message = "Proxy Authentication Required";
                break;
            case 408:
                $message = "Request Timeout";
                break;
            case 409:
                $message = "Conflict";
                break;
            case 410:
                $message = "Gone";
                break;
            case 411:
                $message = "Length Required";
                break;
            case 412:
                $message = "Precondition Failed";
                break;
            case 413:
                $message = "Request Entity Too Large";
                break;
            case 414:
                $message = "Request-URI Too Long";
                break;
            case 415:
                $message = "Unsupported Media Type";
                break;
            case 416:
                $message = "Requested Range Not Satisfiable";
                break;
            case 417:
                $message = "Expectation Failed";
                break;

            case 500:
                $message = "Internal Server Error";
                break;
            case 501:
                $message = "Not Implemented";
                break;
            case 502:
                $message = "Bad Gateway";
                break;
            case 503:
                $message = "Service Unavailable";
                break;
            case 504:
                $message = "Gateway Timeout";
                break;
            case 505:
                $message = "HTTP Version Not Supported";
                break;

            default:
                $message = "Unknown";
                break;
        }
        return $message;
    }

    /**
     * If this object has a "Head" object as a property, this will set it's Title value.
     *
     * @param ?string $title The value to pass to $this->Head->title().
     * @param ?string $subtitle The subtitle that is added after tht title.
     * @return mixed Returns the current title.
     */
    public function title($title = null, $subtitle = null)
    {
        if (!is_null($title)) {
            $this->setData("Title", $title);
        }

        if (!is_null($subtitle)) {
            $this->setData("_Subtitle", $subtitle);
        }

        return $this->data("Title");
    }

    /**
     * Get the destination URL where the page will be redirected after an ajax request.
     *
     * @return string|null
     */
    public function getRedirectTo(): ?string
    {
        return $this->redirectTo;
    }

    /**
     * Set the destination URL where the page will be redirected after an ajax request.
     *
     * @param string|null $destination Destination URL or path.
     *      Redirect to current URL if nothing or null is supplied.
     * @param bool $trustedOnly Non trusted destinations will be redirected to /home/leaving?Target=$destination
     */
    public function setRedirectTo($destination = null, $trustedOnly = true)
    {
        if ($destination === null) {
            $url = url("");
        } elseif ($trustedOnly) {
            $url = safeURL($destination);
        } else {
            $url = url($destination);
        }

        $this->redirectTo = $url;
        $this->RedirectUrl = $url;
    }
}
