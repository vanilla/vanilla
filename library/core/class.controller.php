<?php
/**
 * Gdn_Controller
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 * @abstract
 */

/**
 * Controller base class.
 *
 * A base class that all controllers can inherit for common controller
 * properties and methods.
 *
 * @method void Render($View = '', $ControllerName = false, $ApplicationFolder = false, $AssetName = 'Content') Render the controller's view.
 */
class Gdn_Controller extends Gdn_Pluggable {

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
    public $Assets;

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
    public $Data = array();

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

    /** @var string The url to redirect the user to by ajax'd forms after the form is successfully saved. */
    public $RedirectUrl;

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
     * @deprecated since 2.0.18; $this->ErrorMessage() and $this->InformMessage()
     * are to be used going forward.
     */
    public $StatusMessage;

    /** @var stringDefined by the dispatcher: SYNDICATION_RSS, SYNDICATION_ATOM, or SYNDICATION_NONE (default). */
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

    /**  @var array */
    protected $_Staches;

    /**
     * @var array If JSON is going to be delivered to the client (see the render method),
     * this property will hold the values being sent.
     */
    protected $_Json;

    /** @var array A collection of view locations that have already been found. Used to prevent re-finding views. */
    protected $_ViewLocations;

    /** @var string|null  */
    protected $_PageName = null;

    /**
     *
     */
    public function __construct() {
        $this->Application = '';
        $this->ApplicationFolder = '';
        $this->Assets = array();
        $this->CssClass = '';
        $this->Data = array();
        $this->Head = Gdn::factory('Dummy');
        $this->internalMethods = array(
            'addasset', 'addbreadcrumb', 'addcssfile', 'adddefinition', 'addinternalmethod', 'addjsfile', 'addmodule',
            'allowjsonp', 'canonicalurl', 'clearcssfiles', 'clearjsfiles', 'contenttype', 'cssfiles', 'data',
            'definitionlist', 'deliverymethod', 'deliverytype', 'description', 'errormessages', 'fetchview',
            'fetchviewlocation', 'finalize', 'getasset', 'getimports', 'getjson', 'getstatusmessage', 'image',
            'informmessage', 'intitialize', 'isinternal', 'jsfiles', 'json', 'jsontarget', 'masterview', 'pagename',
            'permission', 'removecssfile', 'render', 'xrender', 'renderasset', 'renderdata', 'renderexception', 'rendermaster',
            'sendheaders', 'setdata', 'setformsaved', 'setheader', 'setjson', 'setlastmodified', 'statuscode', 'title'
        );
        $this->MasterView = '';
        $this->ModuleSortContainer = '';
        $this->OriginalRequestMethod = '';
        $this->RedirectUrl = '';
        $this->RequestMethod = '';
        $this->RequestArgs = false;
        $this->Request = false;
        $this->SelfUrl = '';
        $this->SyndicationMethod = SYNDICATION_NONE;
        $this->Theme = Theme();
        $this->ThemeOptions = Gdn::config('Garden.ThemeOptions', array());
        $this->View = '';
        $this->_CssFiles = array();
        $this->_JsFiles = array();
        $this->_Definitions = array();
        $this->_DeliveryMethod = DELIVERY_METHOD_XHTML;
        $this->_DeliveryType = DELIVERY_TYPE_ALL;
        $this->_FormSaved = '';
        $this->_Json = array();
        $this->_Headers = array(
            'X-Garden-Version' => APPLICATION.' '.APPLICATION_VERSION,
            'Content-Type' => Gdn::config('Garden.ContentType', '').'; charset='.C('Garden.Charset', 'utf-8') // PROPERLY ENCODE THE CONTENT
//         'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT', // PREVENT PAGE CACHING: always modified (this can be overridden by specific controllers)
        );

        if (Gdn::session()->isValid()) {
            $this->_Headers = array_merge($this->_Headers, array(
                'Cache-Control' => 'private, no-cache, no-store, max-age=0, must-revalidate', // PREVENT PAGE CACHING: HTTP/1.1
                'Expires' => 'Sat, 01 Jan 2000 00:00:00 GMT', // Make sure the client always checks at the server before using it's cached copy.
                'Pragma' => 'no-cache', // PREVENT PAGE CACHING: HTTP/1.0
            ));
        }

        $this->_ErrorMessages = '';
        $this->_InformMessages = array();
        $this->StatusMessage = '';

        parent::__construct();
        $this->ControllerName = strtolower($this->ClassName);
    }

    /**
     * Add a breadcrumb to the list.
     *
     * @param string $Name Translation code
     * @param string $Link Optional. Hyperlink this breadcrumb somewhere.
     * @param string $Position Optional. Where in the list to add it? 'front', 'back'
     */
    public function addBreadcrumb($Name, $Link = null, $Position = 'back') {
        $Breadcrumb = array(
            'Name' => T($Name),
            'Url' => $Link
        );

        $Breadcrumbs = $this->data('Breadcrumbs', array());
        switch ($Position) {
            case 'back':
                $Breadcrumbs = array_merge($Breadcrumbs, array($Breadcrumb));
                break;
            case 'front':
                $Breadcrumbs = array_merge(array($Breadcrumb), $Breadcrumbs);
                break;
        }
        $this->setData('Breadcrumbs', $Breadcrumbs);
    }

    /**
     * Adds as asset (string) to the $this->Assets collection.
     *
     * The assets will later be added to the view if their $AssetName is called by
     * $this->RenderAsset($AssetName) within the view.
     *
     * @param string $AssetContainer The name of the asset container to add $Asset to.
     * @param mixed $Asset The asset to be rendered in the view. This can be one of:
     * - <b>string</b>: The string will be rendered.
     * - </b>Gdn_IModule</b>: Gdn_IModule::Render() will be called.
     * @param string $AssetName The name of the asset being added. This can be
     * used later to sort assets before rendering.
     */
    public function addAsset($AssetContainer, $Asset, $AssetName = '') {
        if (is_object($AssetName)) {
            return false;
        } elseif ($AssetName == '') {
            $this->Assets[$AssetContainer][] = $Asset;
        } else {
            if (isset($this->Assets[$AssetContainer][$AssetName])) {
                if (!is_string($Asset)) {
                    $Asset = $Asset->toString();
                }
                $this->Assets[$AssetContainer][$AssetName] .= $Asset;
            } else {
                $this->Assets[$AssetContainer][$AssetName] = $Asset;
            }
        }
    }

    /**
     * Adds a CSS file to search for in the theme folder(s).
     *
     * @param string $FileName The CSS file to search for.
     * @param string $AppFolder The application folder that should contain the CSS file. Default is to
     * use the application folder that this controller belongs to.
     *  - If you specify plugins/PluginName as $AppFolder then you can contain a CSS file in a plugin's design folder.
     */
    public function addCssFile($FileName, $AppFolder = '', $Options = null) {
        $this->_CssFiles[] = array('FileName' => $FileName, 'AppFolder' => $AppFolder, 'Options' => $Options);
    }

    /**
     * Adds a key-value pair to the definition collection for JavaScript.
     *
     * @param string $Term
     * @param string $Definition
     */
    public function addDefinition($Term, $Definition = null) {
        if (!is_null($Definition)) {
            $this->_Definitions[$Term] = $Definition;
        }
        return arrayValue($Term, $this->_Definitions);
    }

    /**
     * Add an method to the list of internal methods.
     *
     * @param string $methodName The name of the internal method to add.
     */
    public function addInternalMethod($methodName) {
        $this->internalMethods[] = strtolower($methodName);
    }

    /**
     * Adds a JS file to search for in the application or global js folder(s).
     *
     * @param string $FileName The js file to search for.
     * @param string $AppFolder The application folder that should contain the JS file. Default is to use the application folder that this controller belongs to.
     */
    public function addJsFile($FileName, $AppFolder = '', $Options = null) {
        $JsInfo = array('FileName' => $FileName, 'AppFolder' => $AppFolder, 'Options' => $Options);

        if (StringBeginsWith($AppFolder, 'plugins/')) {
            $Name = stringBeginsWith($AppFolder, 'plugins/', true, true);
            $Info = Gdn::pluginManager()->getPluginInfo($Name, Gdn_PluginManager::ACCESS_PLUGINNAME);
            if ($Info) {
                $JsInfo['Version'] = val('Version', $Info);
            }
        } else {
            $JsInfo['Version'] = APPLICATION_VERSION;
        }

        $this->_JsFiles[] = $JsInfo;
    }

    /**
     * Adds the specified module to the specified asset target.
     *
     * If no asset target is defined, it will use the asset target defined by the
     * module's AssetTarget method.
     *
     * @param mixed $Module A module or the name of a module to add to the page.
     * @param string $AssetTarget
     */
    public function addModule($Module, $AssetTarget = '') {
        $this->fireEvent('BeforeAddModule');
        $AssetModule = $Module;

        if (!is_object($AssetModule)) {
            if (property_exists($this, $Module) && is_object($this->$Module)) {
                $AssetModule = $this->$Module;
            } else {
                $ModuleClassExists = class_exists($Module);

                if ($ModuleClassExists) {
                    // Make sure that the class implements Gdn_IModule
                    $ReflectionClass = new ReflectionClass($Module);
                    if ($ReflectionClass->implementsInterface("Gdn_IModule")) {
                        $AssetModule = new $Module($this);
                    }
                }
            }
        }

        if (is_object($AssetModule)) {
            $AssetTarget = ($AssetTarget == '' ? $AssetModule->assetTarget() : $AssetTarget);
            // echo '<div>adding: '.get_class($AssetModule).' ('.(property_exists($AssetModule, 'HtmlId') ? $AssetModule->HtmlId : '').') to '.$AssetTarget.' <textarea>'.$AssetModule->ToString().'</textarea></div>';
            $this->addAsset($AssetTarget, $AssetModule, $AssetModule->name());
        }

        $this->fireEvent('AfterAddModule');
    }


    /**
     * Add a Mustache template to the output
     *
     * @param string $Template
     * @param string $ControllerName Optional.
     * @param string $ApplicationFolder Optional.
     * @return boolean
     */
    public function addStache($Template = '', $ControllerName = false, $ApplicationFolder = false) {

        $Template = StringEndsWith($Template, '.stache', true, true);
        $StacheTemplate = "{$Template}.stache";
        $TemplateData = $this->fetchView($StacheTemplate, $ControllerName, $ApplicationFolder);

        if ($TemplateData === false) {
            return false;
        }
        $this->_Staches[$Template] = $TemplateData;
    }

    /**
     *
     *
     * @param null $Value
     * @return mixed|null
     */
    public function allowJSONP($Value = null) {
        static $_Value;

        if (isset($Value)) {
            $_Value = $Value;
        }

        if (isset($_Value)) {
            return $_Value;
        } else {
            return c('Garden.AllowJSONP');
        }
    }

    /**
     *
     *
     * @param null $Value
     * @return null|string
     */
    public function canonicalUrl($Value = null) {
        if ($Value === null) {
            if ($this->_CanonicalUrl) {
                return $this->_CanonicalUrl;
            } else {
                $Parts = array();

                $Controller = strtolower(stringEndsWith($this->ControllerName, 'Controller', true, true));

                if ($Controller == 'settings') {
                    $Parts[] = strtolower($this->ApplicationFolder);
                }

                $Parts[] = $Controller;

                if (strcasecmp($this->RequestMethod, 'index') != 0) {
                    $Parts[] = strtolower($this->RequestMethod);
                }

                // The default canonical url is the fully-qualified url.
                if (is_array($this->RequestArgs)) {
                    $Parts = array_merge($Parts, $this->RequestArgs);
                } elseif (is_string($this->RequestArgs))
                    $Parts = trim($this->RequestArgs, '/');

                $Path = implode('/', $Parts);
                $Result = Url($Path, true);
                return $Result;
            }
        } else {
            $this->_CanonicalUrl = $Value;
            return $Value;
        }
    }

    /**
     *
     */
    public function clearCssFiles() {
        $this->_CssFiles = array();
    }

    /**
     * Clear all js files from the collection.
     */
    public function clearJsFiles() {
        $this->_JsFiles = array();
    }

    /**
     *
     *
     * @param $ContentType
     */
    public function contentType($ContentType) {
        $this->setHeader("Content-Type", $ContentType);
    }

    /**
     *
     *
     * @return array
     */
    public function cssFiles() {
        return $this->_CssFiles;
    }

    /**
     * Get a value out of the controller's data array.
     *
     * @param string $Path The path to the data.
     * @param mixed $Default The default value if the data array doesn't contain the path.
     * @return mixed
     * @see GetValueR()
     */
    public function data($Path, $Default = '') {
        $Result = valr($Path, $this->Data, $Default);
        return $Result;
    }

    /**
     * Gets the javascript definition list used to pass data to the client.
     *
     * @param bool $wrap Whether or not to wrap the result in a `script` tag.
     * @return string Returns a string containing the `<script>` tag of the definitions. .
     */
    public function definitionList($wrap = true) {
        $Session = Gdn::session();
        if (!array_key_exists('TransportError', $this->_Definitions)) {
            $this->_Definitions['TransportError'] = T('Transport error: %s', 'A fatal error occurred while processing the request.<br />The server returned the following response: %s');
        }

        if (!array_key_exists('TransientKey', $this->_Definitions)) {
            $this->_Definitions['TransientKey'] = $Session->transientKey();
        }

        if (!array_key_exists('WebRoot', $this->_Definitions)) {
            $this->_Definitions['WebRoot'] = combinePaths(array(Gdn::request()->domain(), Gdn::request()->webRoot()), '/');
        }

        if (!array_key_exists('UrlFormat', $this->_Definitions)) {
            $this->_Definitions['UrlFormat'] = url('{Path}');
        }

        if (!array_key_exists('Path', $this->_Definitions)) {
            $this->_Definitions['Path'] = Gdn::request()->path();
        }

        if (!array_key_exists('Args', $this->_Definitions)) {
            $this->_Definitions['Args'] = http_build_query(Gdn::request()->get());
        }

        if (!array_key_exists('ResolvedPath', $this->_Definitions)) {
            $this->_Definitions['ResolvedPath'] = $this->ResolvedPath;
        }

        if (!array_key_exists('ResolvedArgs', $this->_Definitions)) {
            if (sizeof($this->ReflectArgs) && (
                    (isset($this->ReflectArgs[0]) && $this->ReflectArgs[0] instanceof Gdn_Pluggable) ||
                    (isset($this->ReflectArgs['Sender']) && $this->ReflectArgs['Sender'] instanceof Gdn_Pluggable) ||
                    (isset($this->ReflectArgs['sender']) && $this->ReflectArgs['sender'] instanceof Gdn_Pluggable)
                )
            ) {
                $ReflectArgs = json_encode(array_slice($this->ReflectArgs, 1));
            } else {
                $ReflectArgs = json_encode($this->ReflectArgs);
            }

            $this->_Definitions['ResolvedArgs'] = $ReflectArgs;
        }

        if (!array_key_exists('SignedIn', $this->_Definitions)) {
            if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
                $SignedIn = 2;
            } else {
                $SignedIn = (int)Gdn::session()->isValid();
            }
            $this->_Definitions['SignedIn'] = $SignedIn;
        }

        if (Gdn::session()->isValid()) {
            // Tell the client what our hour offset is so it can compare it to the user's real offset.
            touchValue('SetHourOffset', $this->_Definitions, Gdn::session()->User->HourOffset);
        }

        if (!array_key_exists('ConfirmHeading', $this->_Definitions)) {
            $this->_Definitions['ConfirmHeading'] = t('Confirm');
        }

        if (!array_key_exists('ConfirmText', $this->_Definitions)) {
            $this->_Definitions['ConfirmText'] = t('Are you sure you want to do that?');
        }

        if (!array_key_exists('Okay', $this->_Definitions)) {
            $this->_Definitions['Okay'] = t('Okay');
        }

        if (!array_key_exists('Cancel', $this->_Definitions)) {
            $this->_Definitions['Cancel'] = t('Cancel');
        }

        if (!array_key_exists('Search', $this->_Definitions)) {
            $this->_Definitions['Search'] = t('Search');
        }

        // Output a JavaScript object with all the definitions.
        $result = 'gdn=window.gdn||{};gdn.meta='.json_encode($this->_Definitions).';';
        if ($wrap) {
            $result = "<script>$result</script>";
        }
        return $result;
    }

    /**
     * Returns the requested delivery type of the controller if $Default is not
     * provided. Sets and returns the delivery type otherwise.
     *
     * @param string $Default One of the DELIVERY_TYPE_* constants.
     */
    public function deliveryType($Default = '') {
        if ($Default) {
            // Make sure we only set a defined delivery type.
            // Use constants' name pattern instead of a strict whitelist for forwards-compatibility.
            if (defined('DELIVERY_TYPE_'.$Default)) {
                $this->_DeliveryType = $Default;
            }
        }

        return $this->_DeliveryType;
    }

    /**
     * Returns the requested delivery method of the controller if $Default is not
     * provided. Sets and returns the delivery method otherwise.
     *
     * @param string $Default One of the DELIVERY_METHOD_* constants.
     */
    public function deliveryMethod($Default = '') {
        if ($Default != '') {
            $this->_DeliveryMethod = $Default;
        }

        return $this->_DeliveryMethod;
    }

    /**
     *
     *
     * @param bool $Value
     * @param bool $PlainText
     * @return mixed
     */
    public function description($Value = false, $PlainText = false) {
        if ($Value != false) {
            if ($PlainText) {
                $Value = Gdn_Format::plainText($Value);
            }
            $this->setData('_Description', $Value);
        }
        return $this->data('_Description');
    }

    /**
     * Add error messages to be displayed to the user.
     *
     * @since 2.0.18
     *
     * @param string $Messages The html of the errors to be display.
     */
    public function errorMessage($Messages) {
        $this->_ErrorMessages = $Messages;
    }

    /**
     * Fetches the contents of a view into a string and returns it. Returns
     * false on failure.
     *
     * @param string $View The name of the view to fetch. If not specified, it will use the value
     * of $this->View. If $this->View is not specified, it will use the value
     * of $this->RequestMethod (which is defined by the dispatcher class).
     * @param string $ControllerName The name of the controller that owns the view if it is not $this.
     * @param string $ApplicationFolder The name of the application folder that contains the requested controller
     * if it is not $this->ApplicationFolder.
     */
    public function fetchView($View = '', $ControllerName = false, $ApplicationFolder = false) {
        $ViewPath = $this->fetchViewLocation($View, $ControllerName, $ApplicationFolder);

        // Check to see if there is a handler for this particular extension.
        $ViewHandler = Gdn::factory('ViewHandler'.strtolower(strrchr($ViewPath, '.')));

        $ViewContents = '';
        ob_start();
        if (is_null($ViewHandler)) {
            // Parse the view and place it into the asset container if it was found.
            include($ViewPath);
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
     * @param string $View The name of the view to fetch. If not specified, it will use the value
     * of $this->View. If $this->View is not specified, it will use the value
     * of $this->RequestMethod (which is defined by the dispatcher class).
     * @param string $ControllerName The name of the controller that owns the view if it is not $this.
     *  - If the controller name is FALSE then the name of the current controller will be used.
     *  - If the controller name is an empty string then the view will be looked for in the base views folder.
     * @param string $ApplicationFolder The name of the application folder that contains the requested controller if it is not $this->ApplicationFolder.
     */
    public function fetchViewLocation($View = '', $ControllerName = false, $ApplicationFolder = false, $ThrowError = true) {
        // Accept an explicitly defined view, or look to the method that was called on this controller
        if ($View == '') {
            $View = $this->View;
        }

        if ($View == '') {
            $View = $this->RequestMethod;
        }

        if ($ControllerName === false) {
            $ControllerName = $this->ControllerName;
        }

        if (StringEndsWith($ControllerName, 'controller', true)) {
            $ControllerName = substr($ControllerName, 0, -10);
        }

        if (strtolower(substr($ControllerName, 0, 4)) == 'gdn_') {
            $ControllerName = substr($ControllerName, 4);
        }

        if (!$ApplicationFolder) {
            $ApplicationFolder = $this->ApplicationFolder;
        }

        //$ApplicationFolder = strtolower($ApplicationFolder);
        $ControllerName = strtolower($ControllerName);
        if (strpos($View, DS) === false) { // keep explicit paths as they are.
            $View = strtolower($View);
        }

        // If this is a syndication request, append the method to the view
        if ($this->SyndicationMethod == SYNDICATION_ATOM) {
            $View .= '_atom';
        } elseif ($this->SyndicationMethod == SYNDICATION_RSS) {
            $View .= '_rss';
        }

        $ViewPath2 = viewLocation($View, $ControllerName, $ApplicationFolder);

        $LocationName = concatSep('/', strtolower($ApplicationFolder), $ControllerName, $View);
        $ViewPath = arrayValue($LocationName, $this->_ViewLocations, false);
        if ($ViewPath === false) {
            // Define the search paths differently depending on whether or not we are in a plugin or application.
            $ApplicationFolder = trim($ApplicationFolder, '/');
            if (stringBeginsWith($ApplicationFolder, 'plugins/')) {
                $KeyExplode = explode('/', $ApplicationFolder);
                $PluginName = array_pop($KeyExplode);
                $PluginInfo = Gdn::pluginManager()->getPluginInfo($PluginName);

                $BasePath = val('SearchPath', $PluginInfo);
                $ApplicationFolder = val('Folder', $PluginInfo);
            } else {
                $BasePath = PATH_APPLICATIONS;
                $ApplicationFolder = strtolower($ApplicationFolder);
            }

            $SubPaths = array();
            // Define the subpath for the view.
            // The $ControllerName used to default to '' instead of FALSE.
            // This extra search is added for backwards-compatibility.
            if (strlen($ControllerName) > 0) {
                $SubPaths[] = "views/$ControllerName/$View";
            } else {
                $SubPaths[] = "views/$View";

                $SubPaths[] = 'views/'.stringEndsWith($this->ControllerName, 'Controller', true, true)."/$View";
            }

            // Views come from one of four places:
            $ViewPaths = array();

            // 1. An explicitly defined path to a view
            if (strpos($View, DS) !== false && stringBeginsWith($View, PATH_ROOT)) {
                $ViewPaths[] = $View;
            }

            if ($this->Theme) {
                // 2. Application-specific theme view. eg. /path/to/application/themes/theme_name/app_name/views/controller_name/
                foreach ($SubPaths as $SubPath) {
                    $ViewPaths[] = PATH_THEMES."/{$this->Theme}/$ApplicationFolder/$SubPath.*";
                    // $ViewPaths[] = CombinePaths(array(PATH_THEMES, $this->Theme, $ApplicationFolder, 'views', $ControllerName, $View . '.*'));
                }

                // 3. Garden-wide theme view. eg. /path/to/application/themes/theme_name/views/controller_name/
                foreach ($SubPaths as $SubPath) {
                    $ViewPaths[] = PATH_THEMES."/{$this->Theme}/$SubPath.*";
                    //$ViewPaths[] = CombinePaths(array(PATH_THEMES, $this->Theme, 'views', $ControllerName, $View . '.*'));
                }
            }

            // 4. Application/plugin default. eg. /path/to/application/app_name/views/controller_name/
            foreach ($SubPaths as $SubPath) {
                $ViewPaths[] = "$BasePath/$ApplicationFolder/$SubPath.*";
                //$ViewPaths[] = CombinePaths(array(PATH_APPLICATIONS, $ApplicationFolder, 'views', $ControllerName, $View . '.*'));
            }

            // Find the first file that matches the path.
            $ViewPath = false;
            foreach ($ViewPaths as $Glob) {
                $Paths = safeGlob($Glob);
                if (is_array($Paths) && count($Paths) > 0) {
                    $ViewPath = $Paths[0];
                    break;
                }
            }
            //$ViewPath = Gdn_FileSystem::Exists($ViewPaths);

            $this->_ViewLocations[$LocationName] = $ViewPath;
        }
        // echo '<div>['.$LocationName.'] RETURNS ['.$ViewPath.']</div>';
        if ($ViewPath === false && $ThrowError) {
            Gdn::dispatcher()->passData('ViewPaths', $ViewPaths);
            throw NotFoundException('View');
//         trigger_error(ErrorMessage("Could not find a '$View' view for the '$ControllerName' controller in the '$ApplicationFolder' application.", $this->ClassName, 'FetchViewLocation'), E_USER_ERROR);
        }

        if ($ViewPath2 != $ViewPath) {
            Trace("View paths do not match: $ViewPath != $ViewPath2", TRACE_WARNING);
        }

        return $ViewPath;
    }

    /**
     * Cleanup any remaining resources for this controller.
     */
    public function finalize() {
        $this->fireAs('Gdn_Controller')->fireEvent('Finalize');
    }

    /**
     *
     *
     * @param string $AssetName
     */
    public function getAsset($AssetName) {
        if (!array_key_exists($AssetName, $this->Assets)) {
            return '';
        }
        if (!is_array($this->Assets[$AssetName])) {
            return $this->Assets[$AssetName];
        }

        // Include the module sort
        $Modules = Gdn::config('Modules', array());
        if ($this->ModuleSortContainer === false) {
            $ModuleSort = false; // no sort wanted
        } elseif (array_key_exists($this->ModuleSortContainer, $Modules) && array_key_exists($AssetName, $Modules[$this->ModuleSortContainer]))
            $ModuleSort = $Modules[$this->ModuleSortContainer][$AssetName]; // explicit sort
        elseif (array_key_exists($this->Application, $Modules) && array_key_exists($AssetName, $Modules[$this->Application]))
            $ModuleSort = $Modules[$this->Application][$AssetName]; // application default sort

        // Get all the assets for this AssetContainer
        $ThisAssets = $this->Assets[$AssetName];
        $Assets = array();

        if (isset($ModuleSort) && is_array($ModuleSort)) {
            // There is a specified sort so sort by it.
            foreach ($ModuleSort as $Name) {
                if (array_key_exists($Name, $ThisAssets)) {
                    $Assets[] = $ThisAssets[$Name];
                    unset($ThisAssets[$Name]);
                }
            }
        }

        // Pick up any leftover assets that werent explicitly sorted
        foreach ($ThisAssets as $Name => $Asset) {
            $Assets[] = $Asset;
        }

        if (count($Assets) == 0) {
            return '';
        } elseif (count($Assets) == 1) {
            return $Assets[0];
        } else {
            $Result = new Gdn_ModuleCollection();
            $Result->Items = $Assets;
            return $Result;
        }
    }

    /**
     *
     */
    public function getImports() {
        if (!isset($this->Uses) || !is_array($this->Uses)) {
            return;
        }

        // Load any classes in the uses array and make them properties of this class
        foreach ($this->Uses as $Class) {
            if (strlen($Class) >= 4 && substr_compare($Class, 'Gdn_', 0, 4) == 0) {
                $Property = substr($Class, 4);
            } else {
                $Property = $Class;
            }

            // Find the class and instantiate an instance..
            if (Gdn::factoryExists($Property)) {
                $this->$Property = Gdn::Factory($Property);
            }
            if (Gdn::factoryExists($Class)) {
                // Instantiate from the factory.
                $this->$Property = Gdn::factory($Class);
            } elseif (class_exists($Class)) {
                // Instantiate as an object.
                $ReflectionClass = new ReflectionClass($Class);
                // Is this class a singleton?
                if ($ReflectionClass->implementsInterface("ISingleton")) {
                    eval('$this->'.$Property.' = '.$Class.'::GetInstance();');
                } else {
                    $this->$Property = new $Class();
                }
            } else {
                trigger_error(errorMessage('The "'.$Class.'" class could not be found.', $this->ClassName, '__construct'), E_USER_ERROR);
            }
        }
    }

    /**
     *
     *
     * @return array
     */
    public function getJson() {
        return $this->_Json;
    }

    /**
     * Allows images to be specified for the page, to be used by the head module
     * to add facebook open graph information.
     *
     * @param mixed $Img An image or array of image urls.
     * @return array The array of image urls.
     */
    public function image($Img = false) {
        if ($Img) {
            if (!is_array($Img)) {
                $Img = array($Img);
            }

            $CurrentImages = $this->data('_Images');
            if (!is_array($CurrentImages)) {
                $this->setData('_Images', $Img);
            } else {
                $Images = array_unique(array_merge($CurrentImages, $Img));
                $this->setData('_Images', $Images);
            }
        }
        $Images = $this->data('_Images');
        return is_array($Images) ? $Images : array();
    }

    /**
     * Add an "inform" message to be displayed to the user.
     *
     * @since 2.0.18
     *
     * @param string $Message The message to be displayed.
     * @param mixed $Options An array of options for the message. If not an array, it is assumed to be a string of CSS classes to apply to the message.
     */
    public function informMessage($Message, $Options = 'Dismissable AutoDismiss') {
        // If $Options isn't an array of options, accept it as a string of css classes to be assigned to the message.
        if (!is_array($Options)) {
            $Options = array('CssClass' => $Options);
        }

        if (!$Message && !array_key_exists('id', $Options)) {
            return;
        }

        $Options['Message'] = $Message;
        $this->_InformMessages[] = $Options;
    }

    /**
     * The initialize method is called by the dispatcher after the constructor
     * has completed, objects have been passed along, assets have been
     * retrieved, and before the requested method fires. Use it in any extended
     * controller to do things like loading script and CSS into the head.
     */
    public function initialize() {
        if (in_array($this->SyndicationMethod, array(SYNDICATION_ATOM, SYNDICATION_RSS))) {
            $this->_Headers['Content-Type'] = 'text/xml; charset='.c('Garden.Charset', 'utf-8');
        }

        if (is_object($this->Menu)) {
            $this->Menu->Sort = Gdn::config('Garden.Menu.Sort');
        }

        $ResolvedPath = strtolower(combinePaths(array(Gdn::dispatcher()->application(), Gdn::dispatcher()->ControllerName, Gdn::dispatcher()->ControllerMethod)));
        $this->ResolvedPath = $ResolvedPath;

        $this->FireEvent('Initialize');
    }

    /**
     *
     *
     * @return array
     */
    public function jsFiles() {
        return $this->_JsFiles;
    }

    /**
     * Determines whether a method on this controller is internal and can't be dispatched.
     *
     * @param string $methodName The name of the method.
     * @return bool Returns true if the method is internal or false otherwise.
     */
    public function isInternal($methodName) {
        $result = substr($methodName, 0, 1) === '_' || in_array(strtolower($methodName), $this->internalMethods);
        return $result;
    }

    /**
     * If JSON is going to be sent to the client, this method allows you to add
     * extra values to the JSON array.
     *
     * @param string $Key The name of the array key to add.
     * @param mixed $Value The value to be added. If null, then it won't be set.
     * @return mixed The value at the key.
     */
    public function json($Key, $Value = null) {
        if (!is_null($Value)) {
            $this->_Json[$Key] = $Value;
        }
        return arrayValue($Key, $this->_Json, null);
    }

    /**
     *
     *
     * @param $Target
     * @param $Data
     * @param string $Type
     */
    public function jsonTarget($Target, $Data, $Type = 'Html') {
        $Item = array('Target' => $Target, 'Data' => $Data, 'Type' => $Type);

        if (!array_key_exists('Targets', $this->_Json)) {
            $this->_Json['Targets'] = array($Item);
        } else {
            $this->_Json['Targets'][] = $Item;
        }
    }

    /**
     * Define & return the master view.
     */
    public function masterView() {
        // Define some default master views unless one was explicitly defined
        if ($this->MasterView == '') {
            // If this is a syndication request, use the appropriate master view
            if ($this->SyndicationMethod == SYNDICATION_ATOM) {
                $this->MasterView = 'atom';
            } elseif ($this->SyndicationMethod == SYNDICATION_RSS) {
                $this->MasterView = 'rss';
            } else {
                $this->MasterView = 'default'; // Otherwise go with the default
            }
        }
        return $this->MasterView;
    }

    /**
     * Gets or sets the name of the page for the controller.
     * The page name is meant to be a friendly name suitable to be consumed by developers.
     *
     * @param string|NULL $Value A new value to set.
     */
    public function pageName($Value = null) {
        if ($Value !== null) {
            $this->_PageName = $Value;
            return $Value;
        }

        if ($this->_PageName === null) {
            if ($this->ControllerName) {
                $Name = $this->ControllerName;
            } else {
                $Name = get_class($this);
            }
            $Name = strtolower($Name);

            if (StringEndsWith($Name, 'controller', false)) {
                $Name = substr($Name, 0, -strlen('controller'));
            }

            return $Name;
        } else {
            return $this->_PageName;
        }
    }

    /**
     * Checks that the user has the specified permissions. If the user does not, they are redirected to the DefaultPermission route.
     *
     * @param mixed $Permission A permission or array of permission names required to access this resource.
     * @param bool $FullMatch If $Permission is an array, $FullMatch indicates if all permissions specified are required. If false, the user only needs one of the specified permissions.
     * @param string $JunctionTable The name of the junction table for a junction permission.
     * @param int $JunctionID The ID of the junction permission.
     */
    public function permission($Permission, $FullMatch = true, $JunctionTable = '', $JunctionID = '') {
        $Session = Gdn::session();

        if (!$Session->checkPermission($Permission, $FullMatch, $JunctionTable, $JunctionID)) {
            Logger::logAccess(
                'security_denied',
                Logger::NOTICE,
                '{username} was denied access to {path}.',
                array(
                    'permission' => $Permission,
                )
            );

            if (!$Session->isValid() && $this->deliveryType() == DELIVERY_TYPE_ALL) {
                redirect('/entry/signin?Target='.urlencode($this->Request->pathAndQuery()));
            } else {
                Gdn::dispatcher()->dispatch('DefaultPermission');
                exit();
            }
        } else {
            $Required = array_intersect((array)$Permission, array('Garden.Settings.Manage', 'Garden.Moderation.Manage'));
            if (!empty($Required)) {
                Logger::logAccess('security_access', Logger::INFO, "{username} accessed {path}.");
            }
        }
    }

    /**
     * Removes a CSS file from the collection.
     *
     * @param string $FileName The CSS file to search for.
     */
    public function removeCssFile($FileName) {
        foreach ($this->_CssFiles as $Key => $FileInfo) {
            if ($FileInfo['FileName'] == $FileName) {
                unset($this->_CssFiles[$Key]);
                return;
            }
        }
    }

    /**
     * Removes a JS file from the collection.
     *
     * @param string $FileName The JS file to search for.
     */
    public function removeJsFile($FileName) {
        foreach ($this->_JsFiles as $Key => $FileInfo) {
            if ($FileInfo['FileName'] == $FileName) {
                unset($this->_JsFiles[$Key]);
                return;
            }
        }
    }

    /**
     * Defines & retrieves the view and master view. Renders all content within
     * them to the screen.
     *
     * @param string $View
     * @param string $ControllerName
     * @param string $ApplicationFolder
     * @param string $AssetName The name of the asset container that the content should be rendered in.
     */
    public function xRender($View = '', $ControllerName = false, $ApplicationFolder = false, $AssetName = 'Content') {
        // Remove the deliver type and method from the query string so they don't corrupt calls to Url.
        $this->Request->setValueOn(Gdn_Request::INPUT_GET, 'DeliveryType', null);
        $this->Request->setValueOn(Gdn_Request::INPUT_GET, 'DeliveryMethod', null);

        Gdn::pluginManager()->callEventHandlers($this, $this->ClassName, $this->RequestMethod, 'Render');

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
            $this->contentType('application/json; charset='.c('Garden.Charset', 'utf-8'));
            $this->setHeader('X-Content-Type-Options', 'nosniff');
        }

        if ($this->_DeliveryMethod == DELIVERY_METHOD_TEXT) {
            $this->contentType('text/plain');
        }

        // Send headers to the browser
        $this->sendHeaders();

        // Make sure to clear out the content asset collection if this is a syndication request
        if ($this->SyndicationMethod !== SYNDICATION_NONE) {
            $this->Assets['Content'] = '';
        }

        // Define the view
        if (!in_array($this->_DeliveryType, array(DELIVERY_TYPE_BOOL, DELIVERY_TYPE_DATA))) {
            $View = $this->fetchView($View, $ControllerName, $ApplicationFolder);
            // Add the view to the asset container if necessary
            if ($this->_DeliveryType != DELIVERY_TYPE_VIEW) {
                $this->addAsset($AssetName, $View, 'Content');
            }
        }

        // Redefine the view as the entire asset contents if necessary
        if ($this->_DeliveryType == DELIVERY_TYPE_ASSET) {
            $View = $this->getAsset($AssetName);
        } elseif ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
            // Or as a boolean if necessary
            $View = true;
            if (property_exists($this, 'Form') && is_object($this->Form)) {
                $View = $this->Form->errorCount() > 0 ? false : true;
            }
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_MESSAGE && $this->Form) {
            $View = $this->Form->errors();
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_DATA) {
            $ExitRender = $this->renderData();
            if ($ExitRender) {
                return;
            }
        }

        if ($this->_DeliveryMethod == DELIVERY_METHOD_JSON) {
            // Format the view as JSON with some extra information about the
            // success status of the form so that jQuery knows what to do
            // with the result.
            if ($this->_FormSaved === '') { // Allow for override
                $this->_FormSaved = (property_exists($this, 'Form') && $this->Form->errorCount() == 0) ? true : false;
            }

            $this->setJson('FormSaved', $this->_FormSaved);
            $this->setJson('DeliveryType', $this->_DeliveryType);
            $this->setJson('Data', base64_encode(($View instanceof Gdn_IModule) ? $View->toString() : $View));
            $this->setJson('InformMessages', $this->_InformMessages);
            $this->setJson('ErrorMessages', $this->_ErrorMessages);
            $this->setJson('RedirectUrl', $this->RedirectUrl);

            // Make sure the database connection is closed before exiting.
            $this->finalize();

            if (!check_utf8($this->_Json['Data'])) {
                $this->_Json['Data'] = utf8_encode($this->_Json['Data']);
            }

            $Json = json_encode($this->_Json);
            $this->_Json['Data'] = $Json;
            exit($this->_Json['Data']);
        } else {
            if (count($this->_InformMessages) > 0 && $this->SyndicationMethod === SYNDICATION_NONE) {
                $this->addDefinition('InformMessageStack', base64_encode(json_encode($this->_InformMessages)));
            }

            if ($this->RedirectUrl != '' && $this->SyndicationMethod === SYNDICATION_NONE) {
                $this->addDefinition('RedirectUrl', $this->RedirectUrl);
            }

            if ($this->_DeliveryMethod == DELIVERY_METHOD_XHTML && debug()) {
                $this->addModule('TraceModule');
            }

            // Render
            if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
                echo $View ? 'TRUE' : 'FALSE';
            } elseif ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                // Render
                $this->renderMaster();
            } else {
                if ($View instanceof Gdn_IModule) {
                    $View->render();
                } else {
                    echo $View;
                }
            }
        }
    }

    /**
     * Searches $this->Assets for a key with $AssetName and renders all items
     * within that array element to the screen. Note that any element in
     * $this->Assets can contain an array of elements itself. This way numerous
     * assets can be rendered one after another in one place.
     *
     * @param string $AssetName The name of the asset to be rendered (the key related to the asset in
     * the $this->Assets associative array).
     */
    public function renderAsset($AssetName) {
        $Asset = $this->getAsset($AssetName);

        $this->EventArguments['AssetName'] = $AssetName;
        $this->fireEvent('BeforeRenderAsset');

        //$LengthBefore = ob_get_length();

        if (is_string($Asset)) {
            echo $Asset;
        } else {
            $Asset->AssetName = $AssetName;
            $Asset->render();
        }

        $this->fireEvent('AfterRenderAsset');
    }

    /**
     * Render the data array.
     *
     * @param null $Data
     * @return bool
     * @throws Exception
     */
    public function renderData($Data = null) {
        if ($Data === null) {
            $Data = array();

            // Remove standard and "protected" data from the top level.
            foreach ($this->Data as $Key => $Value) {
                if ($Key && in_array($Key, array('Title', 'Breadcrumbs'))) {
                    continue;
                }
                if (isset($Key[0]) && $Key[0] === '_') {
                    continue; // protected
                }
                $Data[$Key] = $Value;
            }
            unset($this->Data);
        }

        // Massage the data for better rendering.
        foreach ($Data as $Key => $Value) {
            if (is_a($Value, 'Gdn_DataSet')) {
                $Data[$Key] = $Value->resultArray();
            }
        }

        $CleanOutut = c('Api.Clean', true);
        if ($CleanOutut) {
            // Remove values that should not be transmitted via api
            $Remove = array('Password', 'HashMethod', 'TransientKey', 'Permissions', 'Attributes', 'AccessToken');

            // Remove PersonalInfo values for unprivileged requests.
            if (!Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
                $Remove[] = 'InsertIPAddress';
                $Remove[] = 'UpdateIPAddress';
                $Remove[] = 'LastIPAddress';
                $Remove[] = 'AllIPAddresses';
                $Remove[] = 'Fingerprint';
                if (C('Api.Clean.Email', true)) {
                    $Remove[] = 'Email';
                }
                $Remove[] = 'DateOfBirth';
                $Remove[] = 'Preferences';
                $Remove[] = 'Banned';
                $Remove[] = 'Admin';
                $Remove[] = 'Confirmed';
                $Remove[] = 'Verified';
                $Remove[] = 'DiscoveryText';
                $Remove[] = 'InviteUserID';
                $Remove[] = 'DateSetInvitations';
                $Remove[] = 'CountInvitations';
                $Remove[] = 'CountNotifications';
                $Remove[] = 'CountBookmarks';
                $Remove[] = 'CountDrafts';
                $Remove[] = 'HourOffset';
                $Remove[] = 'Gender';
                $Remove[] = 'Punished';
                $Remove[] = 'Troll';
            }
            $Data = removeKeysFromNestedArray($Data, $Remove);
        }

        if (debug() && $Trace = trace()) {
            // Clear passwords from the trace.
            array_walk_recursive($Trace, function (&$Value, $Key) {
                if (in_array(strtolower($Key), array('password'))) {
                    $Value = '***';
                }
            });
            $Data['Trace'] = $Trace;
        }

        // Make sure the database connection is closed before exiting.
        $this->EventArguments['Data'] = &$Data;
        $this->finalize();

        // Add error information from the form.
        if (isset($this->Form) && sizeof($this->Form->validationResults())) {
            $this->statusCode(400);
            $Data['Code'] = 400;
            $Data['Exception'] = Gdn_Validation::resultsAsText($this->Form->validationResults());
        }


        $this->SendHeaders();

        // Check for a special view.
        $ViewLocation = $this->fetchViewLocation(($this->View ? $this->View : $this->RequestMethod).'_'.strtolower($this->deliveryMethod()), false, false, false);
        if (file_exists($ViewLocation)) {
            include $ViewLocation;
            return;
        }

        // Add schemes to to urls.
        if (!c('Garden.AllowSSL') || c('Garden.ForceSSL')) {
            $r = array_walk_recursive($Data, array('Gdn_Controller', '_FixUrlScheme'), Gdn::request()->scheme());
        }

        if (ob_get_level()) {
            ob_clean();
        }
        switch ($this->deliveryMethod()) {
            case DELIVERY_METHOD_XML:
                safeHeader('Content-Type: text/xml', true);
                echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
                $this->_renderXml($Data);
                return true;
                break;
            case DELIVERY_METHOD_PLAIN:
                return true;
                break;
            case DELIVERY_METHOD_JSON:
            default:
                if (($Callback = $this->Request->get('callback', false)) && $this->allowJSONP()) {
                    safeHeader('Content-Type: application/javascript; charset='.c('Garden.Charset', 'utf-8'), true);
                    // This is a jsonp request.
                    echo $Callback.'('.json_encode($Data).');';
                    return true;
                } else {
                    safeHeader('Content-Type: application/json; charset='.c('Garden.Charset', 'utf-8'), true);
                    // This is a regular json request.
                    echo json_encode($Data);
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     *
     *
     * @param $Value
     * @param $Key
     * @param $Scheme
     */
    protected static function _fixUrlScheme(&$Value, $Key, $Scheme) {
        if (!is_string($Value)) {
            return;
        }

        if (substr($Value, 0, 2) == '//' && substr($Key, -3) == 'Url') {
            $Value = $Scheme.':'.$Value;
        }
    }

    /**
     * A simple default method for rendering xml.
     *
     * @param mixed $Data The data to render. This is usually $this->Data.
     * @param string $Node The name of the root node.
     * @param string $Indent The indent before the data for layout that is easier to read.
     */
    protected function _renderXml($Data, $Node = 'Data', $Indent = '') {
        // Handle numeric arrays.
        if (is_numeric($Node)) {
            $Node = 'Item';
        }

        if (!$Node) {
            return;
        }

        echo "$Indent<$Node>";

        if (is_scalar($Data)) {
            echo htmlspecialchars($Data);
        } else {
            $Data = (array)$Data;
            if (count($Data) > 0) {
                foreach ($Data as $Key => $Value) {
                    echo "\n";
                    $this->_renderXml($Value, $Key, $Indent.' ');
                }
                echo "\n";
            }
        }
        echo "</$Node>";
    }

    /**
     * Render an exception as the sole output.
     *
     * @param Exception $Ex The exception to render.
     */
    public function renderException($Ex) {
        if ($this->deliveryMethod() == DELIVERY_METHOD_XHTML) {
            try {
                // Pick our route.
                switch ($Ex->getCode()) {
                    case 401:
                        $route = 'DefaultPermission';
                        break;
                    case 404:
                        $route = 'Default404';
                        break;
                    default:
                        $route = '/home/error';
                }

                // Redispatch to our error handler.
                if (is_a($Ex, 'Gdn_UserException')) {
                    // UserExceptions provide more info.
                    Gdn::dispatcher()
                        ->passData('Code', $Ex->getCode())
                        ->passData('Exception', $Ex->getMessage())
                        ->passData('Message', $Ex->getMessage())
                        ->passData('Trace', $Ex->getTraceAsString())
                        ->passData('Url', url())
                        ->passData('Breadcrumbs', $this->Data('Breadcrumbs', array()))
                        ->dispatch($route);
                } elseif (in_array($Ex->getCode(), array(401, 404))) {
                    // Default forbidden & not found codes.
                    Gdn::dispatcher()
                        ->passData('Message', $Ex->getMessage())
                        ->passData('Url', url())
                        ->dispatch($route);
                } else {
                    // I dunno! Barf.
                    Gdn_ExceptionHandler($Ex);
                }
            } catch (Exception $Ex2) {
                Gdn_ExceptionHandler($Ex);
            }
            return;
        }

        // Make sure the database connection is closed before exiting.
        $this->finalize();
        $this->sendHeaders();

        $Code = $Ex->getCode();
        $Data = array('Code' => $Code, 'Exception' => $Ex->getMessage(), 'Class' => get_class($Ex));

        if (debug()) {
            if ($Trace = trace()) {
                // Clear passwords from the trace.
                array_walk_recursive($Trace, function (&$Value, $Key) {
                    if (in_array(strtolower($Key), array('password'))) {
                        $Value = '***';
                    }
                });
                $Data['Trace'] = $Trace;
            }

            if (!is_a($Ex, 'Gdn_UserException')) {
                $Data['StackTrace'] = $Ex->getTraceAsString();
            }

            $Data['Data'] = $this->Data;
        }

        // Try cleaning out any notices or errors.
        if (ob_get_level()) {
            ob_clean();
        }

        if ($Code >= 400 && $Code <= 505) {
            safeHeader("HTTP/1.0 $Code", true, $Code);
        } else {
            safeHeader('HTTP/1.0 500', true, 500);
        }


        switch ($this->deliveryMethod()) {
            case DELIVERY_METHOD_JSON:
                if (($Callback = $this->Request->getValueFrom(Gdn_Request::INPUT_GET, 'callback', false)) && $this->allowJSONP()) {
                    safeHeader('Content-Type: application/javascript; charset='.C('Garden.Charset', 'utf-8'), true);
                    // This is a jsonp request.
                    exit($Callback.'('.json_encode($Data).');');
                } else {
                    safeHeader('Content-Type: application/json; charset='.C('Garden.Charset', 'utf-8'), true);
                    // This is a regular json request.
                    exit(json_encode($Data));
                }
                break;
//         case DELIVERY_METHOD_XHTML:
//            Gdn_ExceptionHandler($Ex);
//            break;
            case DELIVERY_METHOD_XML:
                safeHeader('Content-Type: text/xml; charset='.C('Garden.Charset', 'utf-8'), true);
                array_map('htmlspecialchars', $Data);
                exit("<Exception><Code>{$Data['Code']}</Code><Class>{$Data['Class']}</Class><Message>{$Data['Exception']}</Message></Exception>");
                break;
            default:
                safeHeader('Content-Type: text/plain; charset='.C('Garden.Charset', 'utf-8'), true);
                exit($Ex->getMessage());
        }
    }

    /**
     *
     */
    public function renderMaster() {
        // Build the master view if necessary
        if (in_array($this->_DeliveryType, array(DELIVERY_TYPE_ALL))) {
            $this->MasterView = $this->masterView();

            // Only get css & ui components if this is NOT a syndication request
            if ($this->SyndicationMethod == SYNDICATION_NONE && is_object($this->Head)) {
//            if (ArrayHasValue($this->_CssFiles, 'style.css')) {
//               $this->AddCssFile('custom.css');
//
//               // Add the theme option's css file.
//               if ($this->Theme && $this->ThemeOptions) {
//                  $Filenames = GetValueR('Styles.Value', $this->ThemeOptions);
//                  if (is_string($Filenames) && $Filenames != '%s')
//                     $this->_CssFiles[] = array('FileName' => ChangeBasename('custom.css', $Filenames), 'AppFolder' => FALSE, 'Options' => FALSE);
//               }
//            } elseif (ArrayHasValue($this->_CssFiles, 'admin.css')) {
//               $this->AddCssFile('customadmin.css');
//            }

                $this->EventArguments['CssFiles'] = &$this->_CssFiles;
                $this->fireEvent('BeforeAddCss');

                $ETag = AssetModel::eTag();
                $CombineAssets = c('Garden.CombineAssets');
                $ThemeType = isMobile() ? 'mobile' : 'desktop';

                // And now search for/add all css files.
                foreach ($this->_CssFiles as $CssInfo) {
                    $CssFile = $CssInfo['FileName'];
                    if (!is_array($CssInfo['Options'])) {
                        $CssInfo['Options'] = array();
                    }
                    $Options = &$CssInfo['Options'];

                    // style.css and admin.css deserve some custom processing.
                    if (in_array($CssFile, array('style.css', 'admin.css'))) {
                        if (!$CombineAssets) {
                            // Grab all of the css files from the asset model.
                            $AssetModel = new AssetModel();
                            $CssFiles = $AssetModel->getCssFiles($ThemeType, ucfirst(substr($CssFile, 0, -4)), $ETag);
                            foreach ($CssFiles as $Info) {
                                $this->Head->addCss($Info[1], 'all', true, $CssInfo);
                            }
                        } else {
                            $Basename = substr($CssFile, 0, -4);

                            $this->Head->addCss(url("/utility/css/$ThemeType/$Basename-$ETag.css", '//'), 'all', false, $CssInfo['Options']);
                        }
                        continue;
                    }

                    $AppFolder = $CssInfo['AppFolder'];
                    $LookupFolder = !empty($AppFolder) ? $AppFolder : $this->ApplicationFolder;
                    $Search = AssetModel::CssPath($CssFile, $LookupFolder, $ThemeType);
                    if (!$Search) {
                        continue;
                    }

                    list($Path, $UrlPath) = $Search;

                    if (isUrl($Path)) {
                        $this->Head->AddCss($Path, 'all', val('AddVersion', $Options, true), $Options);
                        continue;

                    } else {
                        // Check to see if there is a CSS cacher.
                        $CssCacher = Gdn::factory('CssCacher');
                        if (!is_null($CssCacher)) {
                            $Path = $CssCacher->get($Path, $AppFolder);
                        }

                        if ($Path !== false) {
                            $Path = substr($Path, strlen(PATH_ROOT));
                            $Path = str_replace(DS, '/', $Path);
                            $this->Head->addCss($Path, 'all', true, $Options);
                        }
                    }
                }

                // Add a custom js file.
                if (arrayHasValue($this->_CssFiles, 'style.css')) {
                    $this->addJsFile('custom.js'); // only to non-admin pages.
                }
                // And now search for/add all JS files.
                $Cdns = array();
                if (!c('Garden.Cdns.Disable', false)) {
                    $Cdns = array(
                        'jquery.js' => "//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"
                    );
                }

                $this->EventArguments['Cdns'] = &$Cdns;
                $this->fireEvent('AfterJsCdns');

                $this->Head->addScript('', 'text/javascript', array('content' => $this->definitionList(false)));

                foreach ($this->_JsFiles as $Index => $JsInfo) {
                    $JsFile = $JsInfo['FileName'];

                    if (isset($Cdns[$JsFile])) {
                        $JsFile = $Cdns[$JsFile];
                    }

                    if (strpos($JsFile, '//') !== false) {
                        // This is a link to an external file.
                        $this->Head->addScript($JsFile, 'text/javascript', val('Options', $JsInfo, array()));
                        continue;
                    } elseif (strpos($JsFile, '/') !== false) {
                        // A direct path to the file was given.
                        $JsPaths = array(combinePaths(array(PATH_ROOT, str_replace('/', DS, $JsFile)), DS));
                    } else {
                        $AppFolder = $JsInfo['AppFolder'];
                        if ($AppFolder == '') {
                            $AppFolder = $this->ApplicationFolder;
                        }

                        // JS can come from a theme, an any of the application folder, or it can come from the global js folder:
                        $JsPaths = array();
                        if ($this->Theme) {
                            // 1. Application-specific js. eg. root/themes/theme_name/app_name/design/
                            $JsPaths[] = PATH_THEMES.DS.$this->Theme.DS.$AppFolder.DS.'js'.DS.$JsFile;
                            // 2. Garden-wide theme view. eg. root/themes/theme_name/design/
                            $JsPaths[] = PATH_THEMES.DS.$this->Theme.DS.'js'.DS.$JsFile;
                        }

                        // 3. The application or plugin folder.
                        if (stringBeginsWith(trim($AppFolder, '/'), 'plugins/')) {
                            $JsPaths[] = PATH_PLUGINS.strstr($AppFolder, '/')."/js/$JsFile";
                            $JsPaths[] = PATH_PLUGINS.strstr($AppFolder, '/')."/$JsFile";
                        } else {
                            $JsPaths[] = PATH_APPLICATIONS."/$AppFolder/js/$JsFile";
                        }

                        // 4. Global JS folder. eg. root/js/
                        $JsPaths[] = PATH_ROOT.DS.'js'.DS.$JsFile;
                        // 5. Global JS library folder. eg. root/js/library/
                        $JsPaths[] = PATH_ROOT.DS.'js'.DS.'library'.DS.$JsFile;
                    }

                    // Find the first file that matches the path.
                    $JsPath = false;
                    foreach ($JsPaths as $Glob) {
                        $Paths = safeGlob($Glob);
                        if (is_array($Paths) && count($Paths) > 0) {
                            $JsPath = $Paths[0];
                            break;
                        }
                    }

                    if ($JsPath !== false) {
                        $JsSrc = str_replace(
                            array(PATH_ROOT, DS),
                            array('', '/'),
                            $JsPath
                        );

                        $Options = (array)$JsInfo['Options'];
                        $Options['path'] = $JsPath;
                        $Version = val('Version', $JsInfo);
                        if ($Version) {
                            touchValue('version', $Options, $Version);
                        }

                        $this->Head->addScript($JsSrc, 'text/javascript', $Options);
                    }
                }
            }
            // Add the favicon.
            $Favicon = C('Garden.FavIcon');
            if ($Favicon) {
                $this->Head->setFavIcon(Gdn_Upload::url($Favicon));
            }

            // Make sure the head module gets passed into the assets collection.
            $this->addModule('Head');
        }

        // Master views come from one of four places:
        $MasterViewPaths = array();

        $MasterViewPath2 = viewLocation($this->masterView().'.master', '', $this->ApplicationFolder);

        if (strpos($this->MasterView, '/') !== false) {
            $MasterViewPaths[] = combinePaths(array(PATH_ROOT, str_replace('/', DS, $this->MasterView).'.master*'));
        } else {
            if ($this->Theme) {
                // 1. Application-specific theme view. eg. root/themes/theme_name/app_name/views/
                $MasterViewPaths[] = combinePaths(array(PATH_THEMES, $this->Theme, $this->ApplicationFolder, 'views', $this->MasterView.'.master*'));
                // 2. Garden-wide theme view. eg. /path/to/application/themes/theme_name/views/
                $MasterViewPaths[] = combinePaths(array(PATH_THEMES, $this->Theme, 'views', $this->MasterView.'.master*'));
            }
            // 3. Application default. eg. root/app_name/views/
            $MasterViewPaths[] = combinePaths(array(PATH_APPLICATIONS, $this->ApplicationFolder, 'views', $this->MasterView.'.master*'));
            // 4. Garden default. eg. root/dashboard/views/
            $MasterViewPaths[] = combinePaths(array(PATH_APPLICATIONS, 'dashboard', 'views', $this->MasterView.'.master*'));
        }

        // Find the first file that matches the path.
        $MasterViewPath = false;
        foreach ($MasterViewPaths as $Glob) {
            $Paths = safeGlob($Glob);
            if (is_array($Paths) && count($Paths) > 0) {
                $MasterViewPath = $Paths[0];
                break;
            }
        }

        if ($MasterViewPath != $MasterViewPath2) {
            trace("Master views differ. Controller: $MasterViewPath, ViewLocation(): $MasterViewPath2", TRACE_WARNING);
        }

        $this->EventArguments['MasterViewPath'] = &$MasterViewPath;
        $this->fireEvent('BeforeFetchMaster');

        if ($MasterViewPath === false) {
            trigger_error(errorMessage("Could not find master view: {$this->MasterView}.master*", $this->ClassName, '_FetchController'), E_USER_ERROR);
        }

        /// A unique identifier that can be used in the body tag of the master view if needed.
        $ControllerName = $this->ClassName;
        // Strip "Controller" from the body identifier.
        if (substr($ControllerName, -10) == 'Controller') {
            $ControllerName = substr($ControllerName, 0, -10);
        }

        // Strip "Gdn_" from the body identifier.
        if (substr($ControllerName, 0, 4) == 'Gdn_') {
            $ControllerName = substr($ControllerName, 4);
        }

        $this->setData('CssClass', $this->Application.' '.$ControllerName.' '.$this->RequestMethod.' '.$this->CssClass, true);

        // Check to see if there is a handler for this particular extension.
        $ViewHandler = Gdn::factory('ViewHandler'.strtolower(strrchr($MasterViewPath, '.')));
        if (is_null($ViewHandler)) {
            $BodyIdentifier = strtolower($this->ApplicationFolder.'_'.$ControllerName.'_'.Gdn_Format::alphaNumeric(strtolower($this->RequestMethod)));
            include($MasterViewPath);
        } else {
            $ViewHandler->render($MasterViewPath, $this);
        }
    }

    /**
     * Sends all headers in $this->_Headers (defined with $this->SetHeader()) to the browser.
     */
    public function sendHeaders() {
        // TODO: ALWAYS RENDER OR REDIRECT FROM THE CONTROLLER OR HEADERS WILL NOT BE SENT!! PUT THIS IN DOCS!!!
        foreach ($this->_Headers as $Name => $Value) {
            if ($Name != 'Status') {
                safeHeader($Name.': '.$Value, true);
            } else {
                $Code = array_shift($Shift = explode(' ', $Value));
                safeHeader($Name.': '.$Value, true, $Code);
            }
        }
        // Empty the collection after sending
        $this->_Headers = array();
    }

    /**
     * Allows the adding of page header information that will be delivered to
     * the browser before rendering.
     *
     * @param string $Name The name of the header to send to the browser.
     * @param string $Value The value of the header to send to the browser.
     */
    public function setHeader($Name, $Value) {
        $this->_Headers[$Name] = $Value;
    }

    /**
     * Set data from a method call.
     *
     * @param string $Key The key that identifies the data.
     * @param mixed $Value The data.
     * @param mixed $AddProperty Whether or not to also set the data as a property of this object.
     * @return mixed The $Value that was set.
     */
    public function setData($Key, $Value = null, $AddProperty = false) {
        if (is_array($Key)) {
            $this->Data = array_merge($this->Data, $Key);

            if ($AddProperty === true) {
                foreach ($Key as $Name => $Value) {
                    $this->$Name = $Value;
                }
            }
            return;
        }

        $this->Data[$Key] = $Value;
        if ($AddProperty === true) {
            $this->$Key = $Value;
        }
        return $Value;
    }

    /**
     * Set $this->_FormSaved for JSON Renders.
     *
     * @param bool $Saved Whether form data was successfully saved.
     */
    public function setFormSaved($Saved = true) {
        if ($Saved === '') { // Allow reset
            $this->_FormSaved = '';
        } else { // Force true/false
            $this->_FormSaved = ($Saved) ? true : false;
        }
    }

    /**
     * Looks for a Last-Modified header from the browser and compares it to the
     * supplied date. If the Last-Modified date is after the supplied date, the
     * controller will send a "304 Not Modified" response code to the web
     * browser and stop all execution. Otherwise it sets the Last-Modified
     * header for this page and continues processing.
     *
     * @param string $LastModifiedDate A unix timestamp representing the date that the current page was last
     *  modified.
     */
    public function setLastModified($LastModifiedDate) {
        $GMD = gmdate('D, d M Y H:i:s', $LastModifiedDate).' GMT';
        $this->setHeader('Etag', '"'.$GMD.'"');
        $this->setHeader('Last-Modified', $GMD);
        $IncomingHeaders = getallheaders();
        if (isset($IncomingHeaders['If-Modified-Since'])
            && isset ($IncomingHeaders['If-None-Match'])
        ) {
            $IfNoneMatch = $IncomingHeaders['If-None-Match'];
            $IfModifiedSince = $IncomingHeaders['If-Modified-Since'];
            if ($GMD == $IfNoneMatch && $IfModifiedSince == $GMD) {
                $Database = Gdn::database();
                if (!is_null($Database)) {
                    $Database->closeConnection();
                }

                $this->setHeader('Content-Length', '0');
                $this->sendHeaders();
                safeHeader('HTTP/1.1 304 Not Modified');
                exit("\n\n"); // Send two linefeeds so that the client knows the response is complete
            }
        }
    }

    /**
     * If JSON is going to be sent to the client, this method allows you to add
     * extra values to the JSON array.
     *
     * @param string $Key The name of the array key to add.
     * @param string $Value The value to be added. If empty, nothing will be added.
     */
    public function setJson($Key, $Value = '') {
        $this->_Json[$Key] = $Value;
    }

    /**
     *
     *
     * @param $StatusCode
     * @param null $Message
     * @param bool $SetHeader
     * @return null|string
     */
    public function statusCode($StatusCode, $Message = null, $SetHeader = true) {
        if (is_null($Message)) {
            $Message = self::getStatusMessage($StatusCode);
        }

        if ($SetHeader) {
            $this->setHeader('Status', "{$StatusCode} {$Message}");
        }
        return $Message;
    }

    /**
     *
     *
     * @param $StatusCode
     * @return string
     */
    public static function getStatusMessage($StatusCode) {
        switch ($StatusCode) {
            case 100:
                $Message = 'Continue';
                break;
            case 101:
                $Message = 'Switching Protocols';
                break;

            case 200:
                $Message = 'OK';
                break;
            case 201:
                $Message = 'Created';
                break;
            case 202:
                $Message = 'Accepted';
                break;
            case 203:
                $Message = 'Non-Authoritative Information';
                break;
            case 204:
                $Message = 'No Content';
                break;
            case 205:
                $Message = 'Reset Content';
                break;

            case 300:
                $Message = 'Multiple Choices';
                break;
            case 301:
                $Message = 'Moved Permanently';
                break;
            case 302:
                $Message = 'Found';
                break;
            case 303:
                $Message = 'See Other';
                break;
            case 304:
                $Message = 'Not Modified';
                break;
            case 305:
                $Message = 'Use Proxy';
                break;
            case 307:
                $Message = 'Temporary Redirect';
                break;

            case 400:
                $Message = 'Bad Request';
                break;
            case 401:
                $Message = 'Not Authorized';
                break;
            case 402:
                $Message = 'Payment Required';
                break;
            case 403:
                $Message = 'Forbidden';
                break;
            case 404:
                $Message = 'Not Found';
                break;
            case 405:
                $Message = 'Method Not Allowed';
                break;
            case 406:
                $Message = 'Not Acceptable';
                break;
            case 407:
                $Message = 'Proxy Authentication Required';
                break;
            case 408:
                $Message = 'Request Timeout';
                break;
            case 409:
                $Message = 'Conflict';
                break;
            case 410:
                $Message = 'Gone';
                break;
            case 411:
                $Message = 'Length Required';
                break;
            case 412:
                $Message = 'Precondition Failed';
                break;
            case 413:
                $Message = 'Request Entity Too Large';
                break;
            case 414:
                $Message = 'Request-URI Too Long';
                break;
            case 415:
                $Message = 'Unsupported Media Type';
                break;
            case 416:
                $Message = 'Requested Range Not Satisfiable';
                break;
            case 417:
                $Message = 'Expectation Failed';
                break;

            case 500:
                $Message = 'Internal Server Error';
                break;
            case 501:
                $Message = 'Not Implemented';
                break;
            case 502:
                $Message = 'Bad Gateway';
                break;
            case 503:
                $Message = 'Service Unavailable';
                break;
            case 504:
                $Message = 'Gateway Timeout';
                break;
            case 505:
                $Message = 'HTTP Version Not Supported';
                break;

            default:
                $Message = 'Unknown';
                break;
        }
        return $Message;
    }

    /**
     * If this object has a "Head" object as a property, this will set it's Title value.
     *
     * @param string $Title The value to pass to $this->Head->Title().
     */
    public function title($Title = null, $Subtitle = null) {
        if (!is_null($Title)) {
            $this->setData('Title', $Title);
        }

        if (!is_null($Subtitle)) {
            $this->setData('_Subtitle', $Subtitle);
        }

        return $this->data('Title');
    }
}
