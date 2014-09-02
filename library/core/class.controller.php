<?php if (!defined('APPLICATION')) exit();

/**
 * Controller base class
 * 
 * A base class that all controllers can inherit for common controller
 * properties and methods.
 * 
 * @method void Render() Render the controller's view.
 * @param string $View
 * @param string $ControllerName
 * @param string $ApplicationFolder
 * @param string $AssetName The name of the asset container that the content should be rendered in.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 * @abstract
 */

class Gdn_Controller extends Gdn_Pluggable {

   /**
    * The name of the application that this controller can be found in
    * (ie. Vanilla, Garden, etc).
    *
    * @var string
    */
   public $Application;

   /**
    * The name of the application folder that this controller can be found in
    * (ie. vanilla, dashboard, etc).
    *
    * @var string
    */
   public $ApplicationFolder;

   /**
    * An associative array that contains content to be inserted into the
    * master view. All assets are placed in this array before being passed to
    * the master view. If an asset's key is not called by the master view,
    * that asset will not be rendered.
    *
    * @var array
    */
   public $Assets;


   protected $_CanonicalUrl;

   /**
    * The controllers subfolder that this controller is placed in (if present).
    * This is defined by the dispatcher.
    *
    * @var string
    */
   public $ControllerFolder;

   /**
    * The name of the controller that holds the view (used by $this->FetchView
    * when retrieving the view). Default value is $this->ClassName.
    *
    * @var string
    */
   public $ControllerName;

   /**
    * A CSS class to apply to the body tag of the page. Note: you can only
    * assume that the master view will use this property (ie. a custom theme
    * may not decide to implement this property).
    *
    * @var string
    */
   public $CssClass;
   
   /**
    * The data that a controller method has built up from models and other calcualtions.
    *
    * @var array The data from method calls.
    */
   public $Data = array();

   /**
    * The Head module that this controller should use to add CSS files.
    *
    * @var HeadModule
    */
   public $Head;

   /**
    * The name of the master view that has been requested. Typically this is
    * part of the master view's file name. ie. $this->MasterView.'.master.tpl'
    *
    * @var string
    */
   public $MasterView;
   
   /**
    * A Menu module for rendering the main menu on each page.
    *
    * @var object
    */
   public $Menu;

   /**
    * An associative array of assets and what order their modules should be rendered in.
    * You can set module sort orders in the config using Modules.ModuleSortContainer.AssetName.
    *
    * @example $Configuration['Modules']['Vanilla']['Panel'] = array('CategoryModule', 'NewDiscussionModule');
    * @var string
    */
   public $ModuleSortContainer;

   /**
    * The method that was requested before the dispatcher did any re-routing.
    *
    * @var string
    */
   public $OriginalRequestMethod;

   /**
    * The url to redirect the user to by ajax'd forms after the form is
    * successfully saved.
    *
    * @var string
    */
   public $RedirectUrl;
   
   /**
    * @var string Fully resolved path to the application/controller/method
    * @since 2.1
    */
   public $ResolvedPath;
   
   /**
    * @var array The arguments passed into the controller mapped to their proper argument names.
    * @since 2.1
    */
   public $ReflectArgs;

   /**
    * This is typically an array of arguments passed after the controller
    * name and controller method in the query string. Additional arguments are
    * parsed out by the @@Dispatcher and sent to $this->RequestArgs as an
    * array. If there are no additional arguments specified, this value will
    * remain FALSE.
    * ie. http://localhost/index.php?/controller_name/controller_method/arg1/arg2/arg3
    * translates to: array('arg1', 'arg2', 'arg3');
    *
    * @var mixed
    */
   public $RequestArgs;

   /**
    * The method that has been requested. The request method is defined by the
    * @@Dispatcher as the second parameter passed in the query string. In the
    * following example it would be "controller_method" and it relates
    * directly to the method that will be called in the controller. This value
    * is also used as $this->View unless $this->View has already been
    * hard-coded to be something else.
    * ie. http://localhost/index.php?/controller_name/controller_method/
    *
    * @var string
    */
   public $RequestMethod;
   
   /**
    * Reference to the Request object that spawned this controller
    * 
    * @var Gdn_Request
    */
   public $Request;

   /**
    * The requested url to this controller.
    *
    * @var string
    */
   public $SelfUrl;

   /**
    * The message to be displayed on the screen by ajax'd forms after the form
    * is successfully saved.
    *
    * @deprecated since 2.0.18; $this->ErrorMessage() and $this->InformMessage()
    * are to be used going forward.
    * 
    * @var string
    */
   public $StatusMessage;

   /**
    * Defined by the dispatcher: SYNDICATION_RSS, SYNDICATION_ATOM, or
    * SYNDICATION_NONE (default).
    *
    * @var string
    */
   public $SyndicationMethod;

   /**
    * The name of the folder containing the views to be used by this
    * controller. This value is retrieved from the $Configuration array when
    * this class is instantiated. Any controller can then override the property
    * before render if there is a need.
    *
    * @var string
    */
   public $Theme;

   /**
    * Specific options on the currently selected theme.
    * @var array
    */
   public $ThemeOptions;

   /**
    * The name of the view that has been requested. Typically this is part of
    * the view's file name. ie. $this->View.'.php'
    *
    * @var string
    */
   public $View;

   /**
    * An array of CSS file names to search for in theme folders & include in
    * the page.
    *
    * @var array
    */
   protected $_CssFiles;

   /**
    * A collection of definitions that will be written to the screen in a
    * hidden unordered list so that JavaScript has access to them (ie. for
    * language translations, web root, etc).
    *
    * @var array
    */
   protected $_Definitions;

   /**
    * An enumerator indicating how the response should be delivered to the
    * output buffer. Options are:
    *    DELIVERY_METHOD_XHTML: page contents are delivered as normal.
    *    DELIVERY_METHOD_JSON: page contents and extra information delivered as JSON.
    * The default value is DELIVERY_METHOD_XHTML.
    *
    * @var string
    */
   protected $_DeliveryMethod;

   /**
    * An enumerator indicating what should be delivered to the screen. Options
    * are:
    *    DELIVERY_TYPE_ALL: The master view and everything in the requested asset.
    *    DELIVERY_TYPE_ASSET: Everything in the requested asset.
    *    DELIVERY_TYPE_VIEW: Only the requested view.
    *    DELIVERY_TYPE_BOOL: Deliver only the success status (or error) of the request
    *    DELIVERY_TYPE_NONE: Deliver nothing
    * The default value is DELIVERY_TYPE_ALL.
    *
    * @var string
    */
   protected $_DeliveryType;
   
   /**
    * A string of html containing error messages to be displayed to the user.
    *
    * @since 2.0.18
    * @var string
    */
   protected $_ErrorMessages;

   /**
    * @var bool Allows overriding 'FormSaved' property to send with DELIVERY_METHOD_JSON.
    */
   protected $_FormSaved;

   /**
    * An associative array of header values to be sent to the browser before
    * the page is rendered.
    *
    * @var array
    */
   protected $_Headers;

   /**
    * A collection of "inform" messages to be displayed to the user.
    *
    * @since 2.0.18
    * @var array
    */
   protected $_InformMessages;

   /**
    * An array of JS file names to search for in app folders & include in
    * the page.
    *
    * @var array
    */
   protected $_JsFiles;
   
   /**
    * 
    * @var array
    */
   protected $_Staches;

   /**
    * If JSON is going to be delivered to the client (see the render method),
    * this property will hold the values being sent.
    *
    * @var array
    */
   protected $_Json;

   /**
    * A collection of view locations that have already been found. Used to
    * prevent re-finding views.
    *
    * @var array
    */
   protected $_ViewLocations;

   /**
    * Undocumented method.
    *
    * @todo Method __construct() needs a description.
    */
   public function __construct() {
      $this->Application = '';
      $this->ApplicationFolder = '';
      $this->Assets = array();
      $this->ControllerFolder = '';
      $this->CssClass = '';
      $this->Head = Gdn::Factory('Dummy');
      $this->MasterView = '';
      $this->ModuleSortContainer = '';
      $this->OriginalRequestMethod = '';
      $this->RedirectUrl = '';
      $this->RequestMethod = '';
      $this->RequestArgs = FALSE;
      $this->Request = FALSE;
      $this->SelfUrl = '';
      $this->SyndicationMethod = SYNDICATION_NONE;
      $this->Theme = Theme();
      $this->ThemeOptions = Gdn::Config('Garden.ThemeOptions', array());
      $this->View = '';
      $this->_CssFiles = array();
      $this->_JsFiles = array();
      $this->_Definitions = array();
      $this->_DeliveryMethod = DELIVERY_METHOD_XHTML;
      $this->_DeliveryType = DELIVERY_TYPE_ALL;
      $this->_FormSaved = '';
      $this->_Json = array();
      $this->_Headers = array(
         'X-Garden-Version'   => APPLICATION.' '.APPLICATION_VERSION,
         'Content-Type'       => Gdn::Config('Garden.ContentType', '').'; charset='.Gdn::Config('Garden.Charset', '') // PROPERLY ENCODE THE CONTENT
//         'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT', // PREVENT PAGE CACHING: always modified (this can be overridden by specific controllers)
      );
      
      if (Gdn::Session()->IsValid()) {
         $this->_Headers = array_merge($this->_Headers, array(
            'Cache-Control'   => 'private, no-cache, no-store, max-age=0, must-revalidate', // PREVENT PAGE CACHING: HTTP/1.1 
            'Expires'         => 'Sat, 01 Jan 2000 00:00:00 GMT', // Make sure the client always checks at the server before using it's cached copy.
            'Pragma'          => 'no-cache', // PREVENT PAGE CACHING: HTTP/1.0
         ));
      }
      
      $this->_ErrorMessages = '';
      $this->_InformMessages = array();
      $this->StatusMessage = '';
      
      parent::__construct();
      $this->ControllerName = strtolower($this->ClassName);
   }

   /**
    * Add a breadcrumb to the list
    * 
    * @param string $Name Translation code
    * @param string $Link Optional. Hyperlink this breadcrumb somewhere.
    * @param string $Position Optional. Where in the list to add it? 'front', 'back'
    */
   public function AddBreadcrumb($Name, $Link = NULL, $Position = 'back') {
      $Breadcrumb = array(
         'Name'   => T($Name),
         'Url'    => $Link
      );
      
      $Breadcrumbs = $this->Data('Breadcrumbs', array());
      switch ($Position) {
         case 'back':
            $Breadcrumbs = array_merge($Breadcrumbs, array($Breadcrumb));
            break;
         case 'front':
            $Breadcrumbs = array_merge(array($Breadcrumb), $Breadcrumbs);
            break;
      }
      $this->SetData('Breadcrumbs', $Breadcrumbs);
   }
   
   /**
    * Adds as asset (string) to the $this->Assets collection. The assets will
    * later be added to the view if their $AssetName is called by
    * $this->RenderAsset($AssetName) within the view.
    *
    * @param string $AssetContainer The name of the asset container to add $Asset to.
    * @param mixed $Asset The asset to be rendered in the view. This can be one of:
    * - <b>string</b>: The string will be rendered.
    * - </b>Gdn_IModule</b>: Gdn_IModule::Render() will be called.
    * @param string $AssetName The name of the asset being added. This can be
    * used later to sort assets before rendering.
    */
   public function AddAsset($AssetContainer, $Asset, $AssetName = '') {
      if (is_object($AssetName)) {
         return FALSE;
      } else if ($AssetName == '') {
         $this->Assets[$AssetContainer][] = $Asset;
      } else {
         if (isset($this->Assets[$AssetContainer][$AssetName])) {
            if (!is_string($Asset))
               $Asset = $Asset->ToString();
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
   public function AddCssFile($FileName, $AppFolder = '', $Options = NULL) {
      $this->_CssFiles[] = array('FileName' => $FileName, 'AppFolder' => $AppFolder, 'Options' => $Options);
   }
   
   /**
    * Undocumented method.
    *
    * @param string $Term
    * @param string $Definition
    * @todo Method AddDefinition(), $Term and $Definition need descriptions.
    */
   public function AddDefinition($Term, $Definition = NULL) {
      if(!is_null($Definition)) {
         // Make sure the term is a valid id.
         if (!preg_match('/[a-z][0-9a-z_\-]*/i', $Term))
            throw new Exception('Definition term must start with a letter or an underscore and consist of alphanumeric characters.');
         $this->_Definitions[$Term] = $Definition;
      }
      return ArrayValue($Term, $this->_Definitions);
   }

   /**
    * Adds a JS file to search for in the application or global js folder(s).
    *
    * @param string $FileName The js file to search for.
    * @param string $AppFolder The application folder that should contain the JS file. Default is to use the application folder that this controller belongs to.
    */
   public function AddJsFile($FileName, $AppFolder = '', $Options = NULL) {
      $JsInfo = array('FileName' => $FileName, 'AppFolder' => $AppFolder, 'Options' => $Options);
      
      if (StringBeginsWith($AppFolder, 'plugins/')) {
         $Name = StringBeginsWith($AppFolder, 'plugins/', TRUE, TRUE);
         $Info = Gdn::PluginManager()->GetPluginInfo($Name, Gdn_PluginManager::ACCESS_PLUGINNAME);
         if ($Info) {
            $JsInfo['Version'] = GetValue('Version', $Info);
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
    * @todo $AssetTarget need the correct variable type and description.
    */
   public function AddModule($Module, $AssetTarget = '') {
      $this->FireEvent('BeforeAddModule');
      $AssetModule = $Module;
      
      if (!is_object($AssetModule)) {
         if (property_exists($this, $Module) && is_object($this->$Module)) {
            $AssetModule = $this->$Module;
         } else {
            $ModuleClassExists = class_exists($Module);

            if ($ModuleClassExists) {
               // Make sure that the class implements Gdn_IModule
               $ReflectionClass = new ReflectionClass($Module);
               if ($ReflectionClass->implementsInterface("Gdn_IModule"))
                  $AssetModule = new $Module($this);
            }
         }
      }
      
      if (is_object($AssetModule)) {
         $AssetTarget = ($AssetTarget == '' ? $AssetModule->AssetTarget() : $AssetTarget);
         // echo '<div>adding: '.get_class($AssetModule).' ('.(property_exists($AssetModule, 'HtmlId') ? $AssetModule->HtmlId : '').') to '.$AssetTarget.' <textarea>'.$AssetModule->ToString().'</textarea></div>';
         $this->AddAsset($AssetTarget, $AssetModule, $AssetModule->Name());
      }

      $this->FireEvent('AfterAddModule');
   }
   
   
   /**
    * Add a Mustache template to the output
    * 
    * @param string $Template
    * @param string $ControllerName Optional.
    * @param string $ApplicationFolder Optional.
    * @return boolean
    */
   public function AddStache($Template = '', $ControllerName = FALSE, $ApplicationFolder = FALSE) {
      
      $Template = StringEndsWith($Template, '.stache', TRUE, TRUE);
      $StacheTemplate = "{$Template}.stache";
      $TemplateData = $this->FetchView($StacheTemplate, $ControllerName, $ApplicationFolder);
      
      if ($TemplateData === FALSE) return FALSE;
      $this->_Staches[$Template] = $TemplateData;
   }
   
   public function AllowJSONP($Value = NULL) {
      static $_Value;
      
      if (isset($Value))
         $_Value = $Value;
      
      if (isset($_Value))
         return $_Value;
      else
         return C('Garden.AllowJSONP');
   }

   public function CanonicalUrl($Value = NULL) {
      if ($Value === NULL) {
         if ($this->_CanonicalUrl) {
            return $this->_CanonicalUrl;
         } else {
            $Parts = array();
            
            $Controller = strtolower(StringEndsWith($this->ControllerName, 'Controller', TRUE, TRUE));
            
            if ($Controller == 'settings')
               $Parts[] = strtolower($this->ApplicationFolder);

            $Parts[] = $Controller;

            if (strcasecmp($this->RequestMethod, 'index') != 0)
               $Parts[] = strtolower($this->RequestMethod);

            // The default canonical url is the fully-qualified url.
            if (is_array($this->RequestArgs))
               $Parts = array_merge($Parts, $this->RequestArgs);
            elseif (is_string($this->RequestArgs))
               $Parts = trim($this->RequestArgs, '/');

            $Path = implode('/', $Parts);
            $Result = Url($Path, TRUE);
            return $Result;
         }
      } else {
         $this->_CanonicalUrl = $Value;
         return $Value;
      }
   }
   
   public function ClearCssFiles() {
      $this->_CssFiles = array();
   }
   
   /**
    * Clear all js files from the collection.
    */
   public function ClearJsFiles() {
      $this->_JsFiles = array();
   }
   
   public function ContentType($ContentType) {
      $this->SetHeader("Content-Type", $ContentType);
   }
   
   public function CssFiles() {
      return $this->_CssFiles;
   }

   /** Get a value out of the controller's data array.
    *
    * @param string $Path The path to the data.
    * @param mixed $Default The default value if the data array doesn't contain the path.
    * @return mixed
    * @see GetValueR()
    */
   public function Data($Path, $Default = '' ) {
      $Result = GetValueR($Path, $this->Data, $Default);
      return $Result;
   }

   /**
    * Undocumented method.
    *
    * @todo Method DefinitionList() needs a description.
    */
   public function DefinitionList() {
      $Session = Gdn::Session();
      if (!array_key_exists('TransportError', $this->_Definitions))
         $this->_Definitions['TransportError'] = T('Transport error: %s', 'A fatal error occurred while processing the request.<br />The server returned the following response: %s');

      if (!array_key_exists('TransientKey', $this->_Definitions))
         $this->_Definitions['TransientKey'] = $Session->TransientKey();

      if (!array_key_exists('WebRoot', $this->_Definitions))
         $this->_Definitions['WebRoot'] = CombinePaths(array(Gdn::Request()->Domain(), Gdn::Request()->WebRoot()), '/');

      if (!array_key_exists('UrlFormat', $this->_Definitions))
         $this->_Definitions['UrlFormat'] = Url('{Path}');

      if (!array_key_exists('Path', $this->_Definitions))
         $this->_Definitions['Path'] = Gdn::Request()->Path();
      
      if (!array_key_exists('Args', $this->_Definitions))
         $this->_Definitions['Args'] = http_build_query (Gdn::Request()->Get());
      
      if (!array_key_exists('ResolvedPath', $this->_Definitions))
         $this->_Definitions['ResolvedPath'] = $this->ResolvedPath;
      
      if (!array_key_exists('ResolvedArgs', $this->_Definitions)) {
         if (sizeof($this->ReflectArgs) && (
                 (isset($this->ReflectArgs[0]) && $this->ReflectArgs[0] instanceof Gdn_Pluggable) ||
                 (isset($this->ReflectArgs['Sender']) && $this->ReflectArgs['Sender'] instanceof Gdn_Pluggable)
               ))
            $ReflectArgs = json_encode(array_slice($this->ReflectArgs, 1));
         else
            $ReflectArgs = json_encode($this->ReflectArgs);
         
         $this->_Definitions['ResolvedArgs'] = $ReflectArgs;
      }

      if (!array_key_exists('SignedIn', $this->_Definitions)) {
         if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
            $SignedIn = 2;
         } else {
            $SignedIn = (int)Gdn::Session()->IsValid();
         }
         $this->_Definitions['SignedIn'] = $SignedIn;
      }
      
      if (Gdn::Session()->IsValid()) {
         // Tell the client what our hour offset is so it can compare it to the user's real offset.
         TouchValue('SetHourOffset', $this->_Definitions, Gdn::Session()->User->HourOffset);
      }

      if (!array_key_exists('ConfirmHeading', $this->_Definitions))
         $this->_Definitions['ConfirmHeading'] = T('Confirm');

      if (!array_key_exists('ConfirmText', $this->_Definitions))
         $this->_Definitions['ConfirmText'] = T('Are you sure you want to do that?');

      if (!array_key_exists('Okay', $this->_Definitions))
         $this->_Definitions['Okay'] = T('Okay');

      if (!array_key_exists('Cancel', $this->_Definitions))
         $this->_Definitions['Cancel'] = T('Cancel');

      if (!array_key_exists('Search', $this->_Definitions))
         $this->_Definitions['Search'] = T('Search');

      // Output a JavaScript object with all the definitions
      $Definitions = array();
      foreach($this->_Definitions as $Term => $Definition) {
         $Definitions[] = "'{$Term}' : " . json_encode($Definition);
      }

      $Result = array();
      $Result[] = '<!-- Various definitions for Javascript //-->';
      $Result[] = '<script>';
      $Result[] = "var definitions = {\n" . implode(",\n", $Definitions) . "}";
      $Result[] = '</script>';

      return implode("\n", $Result);
   }

   /**
    * Returns the requested delivery type of the controller if $Default is not
    * provided. Sets and returns the delivery type otherwise.
    *
    * @param string $Default One of the DELIVERY_TYPE_* constants.
    */
   public function DeliveryType($Default = '') {
      if ($Default) {
         // Make sure we only set a defined delivery type.
         // Use constants' name pattern instead of a strict whitelist for forwards-compatibility.
         if (defined('DELIVERY_TYPE_'.$Default)) {
            $this->_DeliveryType = $Default;
         }
         else {
            throw new Exception(sprintf(T('Attempted to set invalid DeliveryType value (%s).'), $Default));
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
   public function DeliveryMethod($Default = '') {
      if ($Default != '')
         $this->_DeliveryMethod = $Default;

      return $this->_DeliveryMethod;
   }
   
   public function Description($Value = FALSE, $PlainText = FALSE) {
      if ($Value != FALSE) {
         if ($PlainText)
            $Value = Gdn_Format::PlainText($Value);
         $this->SetData('_Description', $Value);
      }
      return $this->Data('_Description');
   }

   /**
    * Add error messages to be displayed to the user.
    *
    * @since 2.0.18
    *
    * @param string $Messages The html of the errors to be display.
    */
   public function ErrorMessage($Messages) {
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
   public function FetchView($View = '', $ControllerName = FALSE, $ApplicationFolder = FALSE) {
      $ViewPath = $this->FetchViewLocation($View, $ControllerName, $ApplicationFolder);
      
      // Check to see if there is a handler for this particular extension.
      $ViewHandler = Gdn::Factory('ViewHandler' . strtolower(strrchr($ViewPath, '.')));
      
      $ViewContents = '';
      ob_start();
      if(is_null($ViewHandler)) {   
         // Parse the view and place it into the asset container if it was found.
         include($ViewPath);
      } else {
         // Use the view handler to parse the view.
         $ViewHandler->Render($ViewPath, $this);
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
   public function FetchViewLocation($View = '', $ControllerName = FALSE, $ApplicationFolder = FALSE, $ThrowError = TRUE) {
      // Accept an explicitly defined view, or look to the method that was called on this controller
      if ($View == '')
         $View = $this->View;

      if ($View == '')
         $View = $this->RequestMethod;

      if ($ControllerName === FALSE)
         $ControllerName = $this->ControllerName;

      // Munge the controller folder onto the controller name if it is present.
      if ($this->ControllerFolder != '')
         $ControllerName = $this->ControllerFolder . DS . $ControllerName;

      if (StringEndsWith($ControllerName, 'controller', TRUE))
         $ControllerName = substr($ControllerName, 0, -10);

      if (strtolower(substr($ControllerName, 0, 4)) == 'gdn_')
         $ControllerName = substr($ControllerName, 4);

      if (!$ApplicationFolder)
         $ApplicationFolder = $this->ApplicationFolder;

      //$ApplicationFolder = strtolower($ApplicationFolder);
      $ControllerName = strtolower($ControllerName);
      if(strpos($View, DS) === FALSE) // keep explicit paths as they are.
         $View = strtolower($View);

      // If this is a syndication request, append the method to the view
      if ($this->SyndicationMethod == SYNDICATION_ATOM)
         $View .= '_atom';
      else if ($this->SyndicationMethod == SYNDICATION_RSS)
         $View .= '_rss';
      
      $ViewPath2 = ViewLocation($View, $ControllerName, $ApplicationFolder);

      $LocationName = ConcatSep('/', strtolower($ApplicationFolder), $ControllerName, $View);
      $ViewPath = ArrayValue($LocationName, $this->_ViewLocations, FALSE);
      if ($ViewPath === FALSE) {
         // Define the search paths differently depending on whether or not we are in a plugin or application.
         $ApplicationFolder = trim($ApplicationFolder, '/');
         if (StringBeginsWith($ApplicationFolder, 'plugins/')) {
            $KeyExplode = explode('/',$ApplicationFolder);
            $PluginName = array_pop($KeyExplode);
            $PluginInfo = Gdn::PluginManager()->GetPluginInfo($PluginName);
            
            $BasePath = GetValue('SearchPath', $PluginInfo);
            $ApplicationFolder = GetValue('Folder', $PluginInfo);
         } else {
            $BasePath = PATH_APPLICATIONS;
            $ApplicationFolder = strtolower($ApplicationFolder);
         }

         $SubPaths = array();
         // Define the subpath for the view.
         // The $ControllerName used to default to '' instead of FALSE.
         // This extra search is added for backwards-compatibility.
         if (strlen($ControllerName) > 0)
            $SubPaths[] = "views/$ControllerName/$View";
         else {
            $SubPaths[] = "views/$View";
            
            $SubPaths[] = 'views/'.StringEndsWith($this->ControllerName, 'Controller', TRUE, TRUE)."/$View";
         }

         // Views come from one of four places:
         $ViewPaths = array();
         
         // 1. An explicitly defined path to a view
         if (strpos($View, DS) !== FALSE)
            $ViewPaths[] = $View;
         
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
         $ViewPath = FALSE;
         foreach($ViewPaths as $Glob) {
            $Paths = SafeGlob($Glob);
            if(is_array($Paths) && count($Paths) > 0) {
               $ViewPath = $Paths[0];
               break;
            }
         }
         //$ViewPath = Gdn_FileSystem::Exists($ViewPaths);
         
         $this->_ViewLocations[$LocationName] = $ViewPath;
      }
      // echo '<div>['.$LocationName.'] RETURNS ['.$ViewPath.']</div>';
      if ($ViewPath === FALSE && $ThrowError) {
         Gdn::Dispatcher()->PassData('ViewPaths', $ViewPaths);
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
   public function Finalize() {
      $this->FireAs('Gdn_Controller')->FireEvent('Finalize');
   }

   /**
    * Undocumented method.
    *
    * @param string $AssetName
    * @todo Method GetAsset() and $AssetName needs descriptions.
    */
   public function GetAsset($AssetName) {
      if (!array_key_exists($AssetName, $this->Assets))
         return '';
      if (!is_array($this->Assets[$AssetName]))
         return $this->Assets[$AssetName];
      
      // Include the module sort
      $Modules = Gdn::Config('Modules', array());
      if ($this->ModuleSortContainer === FALSE)
         $ModuleSort = FALSE; // no sort wanted
      elseif (array_key_exists($this->ModuleSortContainer, $Modules) && array_key_exists($AssetName, $Modules[$this->ModuleSortContainer]))
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
      foreach ($ThisAssets as $Name => $Asset)
         $Assets[] = $Asset;
         
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
    * Undocumented method.
    *
    * @todo Method GetImports() needs a description.
    */
   public function GetImports() {
      if(!isset($this->Uses) || !is_array($this->Uses))
         return;
      
      // Load any classes in the uses array and make them properties of this class
      foreach ($this->Uses as $Class) {
         if(strlen($Class) >= 4 && substr_compare($Class, 'Gdn_', 0, 4) == 0) {
            $Property = substr($Class, 4);
         } else {
            $Property = $Class;
         }
         
         // Find the class and instantiate an instance..
         if(Gdn::FactoryExists($Property)) {
            $this->$Property = Gdn::Factory($Property);
         } if(Gdn::FactoryExists($Class)) {
            // Instantiate from the factory.
            $this->$Property = Gdn::Factory($Class);
         } elseif(class_exists($Class)) {               
            // Instantiate as an object.
            $ReflectionClass = new ReflectionClass($Class);
            // Is this class a singleton?
            if ($ReflectionClass->implementsInterface("ISingleton")) {
               eval('$this->'.$Property.' = '.$Class.'::GetInstance();');
            } else {
               $this->$Property = new $Class();
            }
         } else {
            trigger_error(ErrorMessage('The "'.$Class.'" class could not be found.', $this->ClassName, '__construct'), E_USER_ERROR);
         }
      }
   }

   public function GetJson() {
      return $this->_Json;
   }

   /** 
    * Allows images to be specified for the page, to be used by the head module 
    * to add facebook open graph information.
    * @param mixed $Img An image or array of image urls.
    * @return array The array of image urls. 
    */
   public function Image($Img = FALSE) {
      if ($Img) {
         if (!is_array($Img))
            $Img = array($Img);

         $CurrentImages = $this->Data('_Images');
         if (!is_array($CurrentImages))
            $this->SetData('_Images', $Img);
         else {
            $Images = array_unique(array_merge($CurrentImages, $Img));
            $this->SetData('_Images', $Images);
         }
      }
      $Images = $this->Data('_Images');
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
   public function InformMessage($Message, $Options = 'Dismissable AutoDismiss') {
      // If $Options isn't an array of options, accept it as a string of css classes to be assigned to the message.
      if (!is_array($Options))
         $Options = array('CssClass' => $Options);
      
      if (!$Message && !array_key_exists('id', $Options))
         return;
      
      $Options['Message'] = $Message;
      $this->_InformMessages[] = $Options;
   }

   /**
    * The initialize method is called by the dispatcher after the constructor
    * has completed, objects have been passed along, assets have been
    * retrieved, and before the requested method fires. Use it in any extended
    * controller to do things like loading script and CSS into the head.
    */
   public function Initialize() {
      if (in_array($this->SyndicationMethod, array(SYNDICATION_ATOM, SYNDICATION_RSS))) {
         $this->_Headers['Content-Type'] = 'text/xml; charset='.C('Garden.Charset', '');
      }
      
      if (is_object($this->Menu))
         $this->Menu->Sort = Gdn::Config('Garden.Menu.Sort');
      
      $ResolvedPath = strtolower(CombinePaths(array(Gdn::Dispatcher()->Application(), Gdn::Dispatcher()->ControllerName, Gdn::Dispatcher()->ControllerMethod)));
      $this->ResolvedPath = $ResolvedPath;
      
      $this->FireEvent('Initialize');
   }
   
   public function JsFiles() {
      return $this->_JsFiles;
   }
   
   /**
    * If JSON is going to be sent to the client, this method allows you to add
    * extra values to the JSON array.
    *
    * @param string $Key The name of the array key to add.
    * @param mixed $Value The value to be added. If null, then it won't be set.
    * @return mixed The value at the key.
    */
   public function Json($Key, $Value = NULL) {
      if(!is_null($Value)) {
         $this->_Json[$Key] = $Value;
      }
      return ArrayValue($Key, $this->_Json, NULL);
   }
   
   public function JsonTarget($Target, $Data, $Type = 'Html') {
      $Item = array('Target' => $Target, 'Data' => $Data, 'Type' => $Type);
      
      if(!array_key_exists('Targets', $this->_Json))
         $this->_Json['Targets'] = array($Item);
      else
         $this->_Json['Targets'][] = $Item;
   }
   
   /**
    * Define & return the master view.
    */
   public function MasterView() {
      // Define some default master views unless one was explicitly defined
      if ($this->MasterView == '') {
         // If this is a syndication request, use the appropriate master view
         if ($this->SyndicationMethod == SYNDICATION_ATOM)
            $this->MasterView = 'atom';
         else if ($this->SyndicationMethod == SYNDICATION_RSS)
            $this->MasterView = 'rss';
         else
            $this->MasterView = 'default'; // Otherwise go with the default
      }
      return $this->MasterView;
   }

   protected $_PageName = NULL;

   /**
    * Gets or sets the name of the page for the controller.
    * The page name is meant to be a friendly name suitable to be consumed by developers.
    *
    * @param string|NULL $Value A new value to set.
    */
   public function PageName($Value = NULL) {
      if ($Value !== NULL) {
         $this->_PageName = $Value;
         return $Value;
      }

      if ($this->_PageName === NULL) {
         if ($this->ControllerName)
            $Name = $this->ControllerName;
         else
            $Name = get_class($this);
         $Name = strtolower($Name);
         
         if (StringEndsWith($Name, 'controller', FALSE))
            $Name = substr($Name, 0, -strlen('controller'));

         return $Name;
      } else {
         return $this->_PageName;
      }
   }
   
   /**
    * Checks that the user has the specified permissions. If the user does not, they are redirected to the DefaultPermission route.
    * @param mixed $Permission A permission or array of permission names required to access this resource.
    * @param bool $FullMatch If $Permission is an array, $FullMatch indicates if all permissions specified are required. If false, the user only needs one of the specified permissions.
	 * @param string $JunctionTable The name of the junction table for a junction permission.
	 * @param in $JunctionID The ID of the junction permission.
	 */
   public function Permission($Permission, $FullMatch = TRUE, $JunctionTable = '', $JunctionID = '') {
      $Session = Gdn::Session();

      if (!$Session->CheckPermission($Permission, $FullMatch, $JunctionTable, $JunctionID)) {
        if (!$Session->IsValid() && $this->DeliveryType() == DELIVERY_TYPE_ALL) {
           Redirect('/entry/signin?Target='.urlencode($this->SelfUrl));
        } else {
           Gdn::Dispatcher()->Dispatch('DefaultPermission');
           exit();
        }
      }
   }
   
   /**
    * Removes a CSS file from the collection.
    *
    * @param string $FileName The CSS file to search for.
    */
   public function RemoveCssFile($FileName) {
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
   public function RemoveJsFile($FileName) {
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
    * @todo $View, $ControllerName, and $ApplicationFolder need correct variable types and descriptions.
    */
   public function xRender($View = '', $ControllerName = FALSE, $ApplicationFolder = FALSE, $AssetName = 'Content') {
      if ($this->_DeliveryType == DELIVERY_TYPE_NONE)
         return;
      
      // Handle deprecated StatusMessage values that may have been added by plugins
      $this->InformMessage($this->StatusMessage);

      // If there were uncontrolled errors above the json data, wipe them out
      // before fetching it (otherwise the json will not be properly parsed
      // by javascript).
      if ($this->_DeliveryMethod == DELIVERY_METHOD_JSON) {
         ob_clean();
         $this->ContentType('application/json');
         $this->SetHeader('X-Content-Type-Options', 'nosniff');
      }
      
      if ($this->_DeliveryMethod == DELIVERY_METHOD_TEXT) {
         $this->ContentType('text/plain');
      }

      // Send headers to the browser
      $this->SendHeaders();

      // Make sure to clear out the content asset collection if this is a syndication request
      if ($this->SyndicationMethod !== SYNDICATION_NONE)
         $this->Assets['Content'] = '';

      // Define the view
      if (!in_array($this->_DeliveryType, array(DELIVERY_TYPE_BOOL, DELIVERY_TYPE_DATA))) {
         $View = $this->FetchView($View, $ControllerName, $ApplicationFolder);
         // Add the view to the asset container if necessary
         if ($this->_DeliveryType != DELIVERY_TYPE_VIEW)
            $this->AddAsset($AssetName, $View, 'Content');
      }

      // Redefine the view as the entire asset contents if necessary
      if ($this->_DeliveryType == DELIVERY_TYPE_ASSET) {
         $View = $this->GetAsset($AssetName);
      } else if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
         // Or as a boolean if necessary
         $View = TRUE;
         if (property_exists($this, 'Form') && is_object($this->Form))
            $View = $this->Form->ErrorCount() > 0 ? FALSE : TRUE;
      }
      
      if ($this->_DeliveryType == DELIVERY_TYPE_MESSAGE && $this->Form) {
         $View = $this->Form->Errors();
      }

      if ($this->_DeliveryType == DELIVERY_TYPE_DATA) {
         $ExitRender = $this->RenderData();
         if ($ExitRender) return;
      }

      if ($this->_DeliveryMethod == DELIVERY_METHOD_JSON) {
         // Format the view as JSON with some extra information about the
         // success status of the form so that jQuery knows what to do
         // with the result.
         if ($this->_FormSaved === '') // Allow for override
            $this->_FormSaved = (property_exists($this, 'Form') && $this->Form->ErrorCount() == 0) ? TRUE : FALSE;
         
         $this->SetJson('FormSaved', $this->_FormSaved);
         $this->SetJson('DeliveryType', $this->_DeliveryType);
         $this->SetJson('Data', base64_encode(($View instanceof Gdn_IModule) ? $View->ToString() : $View));
         $this->SetJson('InformMessages', $this->_InformMessages);
         $this->SetJson('ErrorMessages', $this->_ErrorMessages);
         $this->SetJson('RedirectUrl', $this->RedirectUrl);
         
         // Make sure the database connection is closed before exiting.
         $this->Finalize();
         
         if (!check_utf8($this->_Json['Data']))
            $this->_Json['Data'] = utf8_encode($this->_Json['Data']);

         $Json = json_encode($this->_Json);
         // Check for jsonp call.
         if (($Callback = $this->Request->Get('callback', FALSE)) && $this->AllowJSONP()) {
            $Json = $Callback.'('.$Json.')';
         }

         $this->_Json['Data'] = $Json;
         exit($this->_Json['Data']);
      } else {
         if (count($this->_InformMessages) > 0 && $this->SyndicationMethod === SYNDICATION_NONE)
            $this->AddDefinition('InformMessageStack', base64_encode(json_encode($this->_InformMessages)));

         if ($this->RedirectUrl != '' && $this->SyndicationMethod === SYNDICATION_NONE)
            $this->AddDefinition('RedirectUrl', $this->RedirectUrl);
         
         if (Debug()) {
            $this->AddModule('TraceModule');
         }

         // Render
         if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
            echo $View ? 'TRUE' : 'FALSE';
         } else if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            // Add definitions to the page
            if ($this->SyndicationMethod === SYNDICATION_NONE)
               $this->AddAsset('Foot', $this->DefinitionList());

            // Render
            $this->RenderMaster();
         } else {
            if($View instanceof Gdn_IModule) {
               $View->Render();
            } else {
               echo $View;
            }
         }
      }
   }

   /**
    * Undocumented method.
    *
    * @param string $AltAppFolder
    * @param string $AltController
    * @param string $AltMethod
    * @todo Method RenderAlternate() and $AltAppFolder, $AltController and $AltMethod needs descriptions.
    */
   public function RenderAlternate($AltAppFolder, $AltController, $AltMethod) {
      $this->AddAsset('Content', $this->FetchView($AltMethod, $AltController, $AltAppFolder));
      $this->RenderMaster();
      return;
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
   public function RenderAsset($AssetName) {
      $Asset = $this->GetAsset($AssetName);

      $this->EventArguments['AssetName'] = $AssetName;
      $this->FireEvent('BeforeRenderAsset');

      //$LengthBefore = ob_get_length();

      if(is_string($Asset)) {
         echo $Asset;
      } else {
         $Asset->AssetName = $AssetName;
         $Asset->Render();
      }

      $this->FireEvent('AfterRenderAsset');
   }

   // Render the data array.
   public function RenderData($Data = NULL) {
      if ($Data === NULL) {
         $Data = array();

         // Remove standard and "protected" data from the top level.
         foreach ($this->Data as $Key => $Value) {
            if ($Key && in_array($Key, array('Title', 'Breadcrumbs')))
               continue;
            if (isset($Key[0]) && $Key[0] === '_')
               continue; // protected
            
            $Data[$Key] = $Value;
         }
         unset($this->Data);
      }

      // Massage the data for better rendering.
      foreach ($Data as $Key => $Value) {
         if (is_a($Value, 'Gdn_DataSet')) {
            $Data[$Key] = $Value->ResultArray();
         }
      }
      
      $CleanOutut = C('Api.Clean', TRUE);
      if ($CleanOutut) {
         // Remove values that should not be transmitted via api
         $Remove = array('Password', 'HashMethod', 'TransientKey', 'Permissions', 'Attributes', 'AccessToken');
         if (!Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
            $Remove[] = 'InsertIPAddress';
            $Remove[] = 'UpdateIPAddress';
            $Remove[] = 'LastIPAddress';
            $Remove[] = 'AllIPAddresses';
            $Remove[] = 'Fingerprint';
            if (C('Api.Clean.Email', TRUE))
               $Remove[] = 'Email';
            $Remove[] = 'DateOfBirth';
         }
         $Data = RemoveKeysFromNestedArray($Data, $Remove);
      }
      
      // Make sure the database connection is closed before exiting.
      $this->EventArguments['Data'] = &$Data;
      $this->Finalize();
      
      // Add error information from the form.
      if (isset($this->Form) && sizeof($this->Form->ValidationResults())) {
         $this->StatusCode(400);
         $Data['Code'] = 400;
         $Data['Exception'] = Gdn_Validation::ResultsAsText($this->Form->ValidationResults());
      }
      
      
      $this->SendHeaders();

      // Check for a special view.
      $ViewLocation = $this->FetchViewLocation(($this->View ? $this->View : $this->RequestMethod).'_'.strtolower($this->DeliveryMethod()), FALSE, FALSE, FALSE);
      if (file_exists($ViewLocation)) {
         include $ViewLocation;
         return;
      }
      
      // Add schemes to to urls.
      $r = array_walk_recursive($Data, array('Gdn_Controller', '_FixUrlScheme'), Gdn::Request()->Scheme());
      
      switch ($this->DeliveryMethod()) {
         case DELIVERY_METHOD_XML:
            header('Content-Type: text/xml', TRUE);
            echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
            $this->_RenderXml($Data);
            return TRUE;
            break;
         case DELIVERY_METHOD_PLAIN:
            return TRUE;
            break;
         case DELIVERY_METHOD_JSON:
         default:
            if (($Callback = $this->Request->Get('callback', FALSE)) && $this->AllowJSONP()) {
               header('Content-Type: application/javascript', TRUE);
               // This is a jsonp request.
               echo $Callback.'('.json_encode($Data).');';
               return TRUE;
            } else {
               header('Content-Type: application/json', TRUE);
               // This is a regular json request.
               echo json_encode($Data);
               return TRUE;
            }
            break;
      }
      return FALSE;
   }
   
   protected static function _FixUrlScheme(&$Value, $Key, $Scheme) {
      if (!is_string($Value))
         return;
      
      if (substr($Value, 0, 2) == '//' && substr($Key, -3) == 'Url')
         $Value = $Scheme.':'.$Value;
   }

   /**
    * A simple default method for rendering xml.
    *
    * @param mixed $Data The data to render. This is usually $this->Data.
    * @param string $Node The name of the root node.
    * @param string $Indent The indent before the data for layout that is easier to read.
    */
   protected function _RenderXml($Data, $Node = 'Data', $Indent = '') {
      // Handle numeric arrays.
      if (is_numeric($Node))
         $Node = 'Item';

      if (!$Node)
         return;
      
      echo "$Indent<$Node>";

      if (is_scalar($Data)) {
         echo htmlspecialchars($Data);
      } else {
         $Data = (array)$Data;
         if (count($Data) > 0) {
            foreach ($Data as $Key => $Value) {
               echo "\n";
               $this->_RenderXml($Value, $Key, $Indent.' ');
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
   public function RenderException($Ex) {
      if ($this->DeliveryMethod() == DELIVERY_METHOD_XHTML) {
         try {
            if (is_a($Ex, 'Gdn_UserException')) {
               Gdn::Dispatcher()
                  ->PassData('Code', $Ex->getCode())
                  ->PassData('Exception', $Ex->getMessage())
                  ->PassData('Message', $Ex->getMessage())
                  ->PassData('Trace', $Ex->getTraceAsString())
                  ->PassData('Breadcrumbs', $this->Data('Breadcrumbs', array()))
                  ->Dispatch('/home/error');
            } else {
               switch ($Ex->getCode()) {
                  case 401:
                     Gdn::Dispatcher()
                        ->PassData('Message', $Ex->getMessage())
                        ->Dispatch('DefaultPermission');
                     break;
                  case 404:
                     Gdn::Dispatcher()
                        ->PassData('Message', $Ex->getMessage())
                        ->Dispatch('Default404');
                     break;
                 default:
                    Gdn_ExceptionHandler($Ex);
               }
            }
            
            
         } catch(Exception $Ex2) {
            Gdn_ExceptionHandler($Ex);
         }
         return;
      }

      // Make sure the database connection is closed before exiting.
      $this->Finalize();
      $this->SendHeaders();

      $Code = $Ex->getCode();
      $Data = array('Code' => $Code, 'Exception' => $Ex->getMessage(), 'Class' => get_class($Ex));
      
      if (Debug()) {
         if ($Trace = Trace()) {
            $Data['Trace'] = $Trace;
         }
         
         if (!is_a($Ex, 'Gdn_UserException'))
            $Data['StackTrace'] = $Ex->getTraceAsString();
         
         $Data['Data'] = $this->Data;
      }
      
      // Try cleaning out any notices or errors.
      @@ob_clean();
      

      if ($Code >= 400 && $Code <= 505)
         header("HTTP/1.0 $Code", TRUE, $Code);
      else
         header('HTTP/1.0 500', TRUE, 500);

      
      switch ($this->DeliveryMethod()) {
         case DELIVERY_METHOD_JSON:
            if (($Callback = $this->Request->GetValueFrom(Gdn_Request::INPUT_GET, 'callback', FALSE)) && $this->AllowJSONP()) {
               header('Content-Type: application/javascript', TRUE);
               // This is a jsonp request.
               exit($Callback.'('.json_encode($Data).');');
            } else {
               header('Content-Type: application/json', TRUE);
               // This is a regular json request.
               exit(json_encode($Data));
            }
            break;
//         case DELIVERY_METHOD_XHTML:
//            Gdn_ExceptionHandler($Ex);
//            break;
         case DELIVERY_METHOD_XML:
            header('Content-Type: text/xml', TRUE);
            array_map('htmlspecialchars', $Data);
            exit("<Exception><Code>{$Data['Code']}</Code><Class>{$Data['Class']}</Class><Message>{$Data['Exception']}</Message></Exception>");
            break;
         default:
            header('Content-Type: text/plain', TRUE);
            exit($Ex->getMessage());
      }
   }

   /**
    * Undocumented method.
    *
    * @todo Method RenderMaster() needs a description.
    */
   public function RenderMaster() {
      // Build the master view if necessary
      if (in_array($this->_DeliveryType, array(DELIVERY_TYPE_ALL))) {
         $this->MasterView = $this->MasterView();

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
            $this->FireEvent('BeforeAddCss');
            
            $ETag = AssetModel::ETag();
            $CombineAssets = C('Garden.CombineAssets');
            
            // And now search for/add all css files.
            foreach ($this->_CssFiles as $CssInfo) {
               $CssFile = $CssInfo['FileName'];
               
               // style.css and admin.css deserve some custom processing.
               if (in_array($CssFile, array('style.css', 'admin.css'))) {
                  if (!$CombineAssets) {
                     // Grab all of the css files from the asset model.
                     $AssetModel = new AssetModel();
                     $CssFiles = $AssetModel->GetCssFiles(ucfirst(substr($CssFile, 0, -4)), $ETag);
                     foreach ($CssFiles as $Info) {
                        $this->Head->AddCss($Info[1], 'all', TRUE, $CssInfo);
                     }
                  } else {
                     $Basename = substr($CssFile, 0, -4);
                     
                     $this->Head->AddCss(Url("/utility/css/$Basename/$Basename-$ETag.css", '//'), 'all', FALSE, $CssInfo['Options']);
                  }
                  continue;
               }
               
               if (StringBeginsWith($CssFile, 'http')) {
                  $this->Head->AddCss($CssFile, 'all', GetValue('AddVersion', $CssInfo, TRUE), $CssInfo['Options']);
                  continue;
               } elseif(strpos($CssFile, '/') !== FALSE) {
                  // A direct path to the file was given.
                  $CssPaths = array(CombinePaths(array(PATH_ROOT, str_replace('/', DS, $CssFile))));
               } else {
//                  $CssGlob = preg_replace('/(.*)(\.css)/', '\1*\2', $CssFile);
                  $AppFolder = $CssInfo['AppFolder'];
                  if ($AppFolder == '')
                     $AppFolder = $this->ApplicationFolder;
   
                  // CSS comes from one of four places:
                  $CssPaths = array();
                  if ($this->Theme) {
                     // Use the default filename.
                     $CssPaths[] = PATH_THEMES . DS . $this->Theme . DS . 'design' . DS . $CssFile;
                  }


                  // 3. Application or plugin.
                  if (StringBeginsWith($AppFolder, 'plugins/')) {
                     // The css is coming from a plugin.
                     $AppFolder = substr($AppFolder, strlen('plugins/'));
                     $CssPaths[] = PATH_PLUGINS . "/$AppFolder/design/$CssFile";
                     $CssPaths[] = PATH_PLUGINS . "/$AppFolder/$CssFile";
                  } else {
                     // Application default. eg. root/applications/app_name/design/
                     $CssPaths[] = PATH_APPLICATIONS . DS . $AppFolder . DS . 'design' . DS . $CssFile;
                  }

                  // 4. Garden default. eg. root/applications/dashboard/design/
                  $CssPaths[] = PATH_APPLICATIONS . DS . 'dashboard' . DS . 'design' . DS . $CssFile;
               }
               
               // Find the first file that matches the path.
               $CssPath = FALSE;
               foreach($CssPaths as $Glob) {
                  $Paths = SafeGlob($Glob);
                  if(is_array($Paths) && count($Paths) > 0) {
                     $CssPath = $Paths[0];
                     break;
                  }
               }
               
               // Check to see if there is a CSS cacher.
               $CssCacher = Gdn::Factory('CssCacher');
               if(!is_null($CssCacher)) {
                  $CssPath = $CssCacher->Get($CssPath, $AppFolder);
               }
               
               if ($CssPath !== FALSE) {
                  $CssPath = substr($CssPath, strlen(PATH_ROOT));
                  $CssPath = str_replace(DS, '/', $CssPath);
                  $this->Head->AddCss($CssPath, 'all', TRUE, $CssInfo['Options']);
               }
            }

            // Add a custom js file.
            if (ArrayHasValue($this->_CssFiles, 'style.css'))
               $this->AddJsFile('custom.js'); // only to non-admin pages.

            // And now search for/add all JS files.
            $Cdns = array();
            if (Gdn::Request()->Scheme() != 'https' && !C('Garden.Cdns.Disable', FALSE)) {
               $Cdns = array(
                  'jquery.js' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'
                  );
            }
            
            $this->EventArguments['Cdns'] = &$Cdns;
            $this->FireEvent('AfterJsCdns');
            
            foreach ($this->_JsFiles as $Index => $JsInfo) {
               $JsFile = $JsInfo['FileName'];
               
               if (isset($Cdns[$JsFile]))
                  $JsFile = $Cdns[$JsFile];

               if (strpos($JsFile, '//') !== FALSE) {
                  // This is a link to an external file.
                  $this->Head->AddScript($JsFile, 'text/javascript', GetValue('Options', $JsInfo, array()));
                  continue;
               } elseif (strpos($JsFile, '/') !== FALSE) {
                  // A direct path to the file was given.
                  $JsPaths = array(CombinePaths(array(PATH_ROOT, str_replace('/', DS, $JsFile)), DS));
               } else {
                  $AppFolder = $JsInfo['AppFolder'];
                  if ($AppFolder == '')
                     $AppFolder = $this->ApplicationFolder;
   
                  // JS can come from a theme, an any of the application folder, or it can come from the global js folder:
                  $JsPaths = array();
                  if ($this->Theme) {
                     // 1. Application-specific js. eg. root/themes/theme_name/app_name/design/
                     $JsPaths[] = PATH_THEMES . DS . $this->Theme . DS . $AppFolder . DS . 'js' . DS . $JsFile;
                     // 2. Garden-wide theme view. eg. root/themes/theme_name/design/
                     $JsPaths[] = PATH_THEMES . DS . $this->Theme . DS . 'js' . DS . $JsFile;
                  }

                  // 3. The application or plugin folder.
                  if (StringBeginsWith(trim($AppFolder, '/'), 'plugins/')) {
                     $JsPaths[] = PATH_PLUGINS.strstr($AppFolder, '/')."/js/$JsFile";
                     $JsPaths[] = PATH_PLUGINS.strstr($AppFolder, '/')."/$JsFile";
                  } else
                     $JsPaths[] = PATH_APPLICATIONS."/$AppFolder/js/$JsFile";

                  // 4. Global JS folder. eg. root/js/
                  $JsPaths[] = PATH_ROOT . DS . 'js' . DS . $JsFile;
                  // 5. Global JS library folder. eg. root/js/library/
                  $JsPaths[] = PATH_ROOT . DS . 'js' . DS . 'library' . DS . $JsFile;
               }

               // Find the first file that matches the path.
               $JsPath = FALSE;
               foreach($JsPaths as $Glob) {
                  $Paths = SafeGlob($Glob);
                  if(is_array($Paths) && count($Paths) > 0) {
                     $JsPath = $Paths[0];
                     break;
                  }
               }
               
               if ($JsPath !== FALSE) {
                  $JsSrc = str_replace(
                     array(PATH_ROOT, DS),
                     array('', '/'),
                     $JsPath
                  );

                  $Options = (array)$JsInfo['Options'];
                  $Options['path'] = $JsPath;
                  $Version = GetValue('Version', $JsInfo);
                  if ($Version)
                     TouchValue('version', $Options, $Version);

                  $this->Head->AddScript($JsSrc, 'text/javascript', $Options);
               }
            }
         }
         // Add the favicon.
         $Favicon = C('Garden.FavIcon');
         if ($Favicon)
            $this->Head->SetFavIcon(Gdn_Upload::Url($Favicon));
         
         // Make sure the head module gets passed into the assets collection.
         $this->AddModule('Head');
      }

      // Master views come from one of four places:
      $MasterViewPaths = array();
      
      $MasterViewPath2 = ViewLocation($this->MasterView().'.master', '', $this->ApplicationFolder);
      
      if(strpos($this->MasterView, '/') !== FALSE) {
         $MasterViewPaths[] = CombinePaths(array(PATH_ROOT, str_replace('/', DS, $this->MasterView).'.master*'));
      } else {
         if ($this->Theme) {
            // 1. Application-specific theme view. eg. root/themes/theme_name/app_name/views/
            $MasterViewPaths[] = CombinePaths(array(PATH_THEMES, $this->Theme, $this->ApplicationFolder, 'views', $this->MasterView . '.master*'));
            // 2. Garden-wide theme view. eg. /path/to/application/themes/theme_name/views/
            $MasterViewPaths[] = CombinePaths(array(PATH_THEMES, $this->Theme, 'views', $this->MasterView . '.master*'));
         }
         // 3. Application default. eg. root/app_name/views/
         $MasterViewPaths[] = CombinePaths(array(PATH_APPLICATIONS, $this->ApplicationFolder, 'views', $this->MasterView . '.master*'));
         // 4. Garden default. eg. root/dashboard/views/
         $MasterViewPaths[] = CombinePaths(array(PATH_APPLICATIONS, 'dashboard', 'views', $this->MasterView . '.master*'));
      }
      
      // Find the first file that matches the path.
      $MasterViewPath = FALSE;
      foreach($MasterViewPaths as $Glob) {
         $Paths = SafeGlob($Glob);
         if(is_array($Paths) && count($Paths) > 0) {
            $MasterViewPath = $Paths[0];
            break;
         }
      }
      
      if ($MasterViewPath != $MasterViewPath2)
         Trace("Master views differ. Controller: $MasterViewPath, ViewLocation(): $MasterViewPath2", TRACE_WARNING);
      
      $this->EventArguments['MasterViewPath'] = &$MasterViewPath;
      $this->FireEvent('BeforeFetchMaster');

      if ($MasterViewPath === FALSE)
         trigger_error(ErrorMessage("Could not find master view: {$this->MasterView}.master*", $this->ClassName, '_FetchController'), E_USER_ERROR);
      
      /// A unique identifier that can be used in the body tag of the master view if needed.
      $ControllerName = $this->ClassName;
      // Strip "Controller" from the body identifier.
      if (substr($ControllerName, -10) == 'Controller')
         $ControllerName = substr($ControllerName, 0, -10);
         
      // Strip "Gdn_" from the body identifier.
      if (substr($ControllerName, 0, 4) == 'Gdn_')
         $ControllerName = substr($ControllerName, 4); 

      $this->SetData('CssClass', $this->Application.' '.$ControllerName.' '.$this->RequestMethod.' '.$this->CssClass, TRUE);
     
      // Check to see if there is a handler for this particular extension.
      $ViewHandler = Gdn::Factory('ViewHandler' . strtolower(strrchr($MasterViewPath, '.')));
      if(is_null($ViewHandler)) {
         $BodyIdentifier = strtolower($this->ApplicationFolder.'_'.$ControllerName.'_'.Gdn_Format::AlphaNumeric(strtolower($this->RequestMethod)));
         include($MasterViewPath);
      } else {
         $ViewHandler->Render($MasterViewPath, $this);
      }
   }

   /**
    * Sends all headers in $this->_Headers (defined with $this->SetHeader()) to
    * the browser.
    */
   public function SendHeaders() {
      // TODO: ALWAYS RENDER OR REDIRECT FROM THE CONTROLLER OR HEADERS WILL NOT BE SENT!! PUT THIS IN DOCS!!!
      foreach ($this->_Headers as $Name => $Value) {
         if ($Name != 'Status')
            header($Name.': '.$Value, TRUE);
         else {
            $Code = array_shift($Shift = explode(' ', $Value));
            header($Name.': '.$Value, TRUE, $Code);
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
   public function SetHeader($Name, $Value) {
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
   public function SetData($Key, $Value = NULL, $AddProperty = FALSE) {
      if (is_array($Key)) {
         $this->Data = array_merge($this->Data, $Key);

         if ($AddProperty === TRUE) {
            foreach ($Key as $Name => $Value) {
               $this->$Name = $Value;
            }
         }
         return;
      }

      $this->Data[$Key] = $Value;
      if($AddProperty === TRUE) {
         $this->$Key = $Value;
      }
      return $Value;
   }
   
   /**
    * Set $this->_FormSaved for JSON Renders.
    *
    * @param bool $Saved Whether form data was successfully saved.
    */
   public function SetFormSaved($Saved = TRUE) {
      if ($Saved === '') // Allow reset
         $this->_FormSaved = '';
      else // Force true/false
         $this->_FormSaved = ($Saved) ? TRUE : FALSE;
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
   public function SetLastModified($LastModifiedDate) {
      $GMD = gmdate('D, d M Y H:i:s', $LastModifiedDate) . ' GMT';
      $this->SetHeader('Etag', '"'.$GMD.'"');
      $this->SetHeader('Last-Modified', $GMD);
      $IncomingHeaders = getallheaders();
      if (
         isset($IncomingHeaders['If-Modified-Since'])
         && isset ($IncomingHeaders['If-None-Match'])
      ) {
         $IfNoneMatch = $IncomingHeaders['If-None-Match'];
         $IfModifiedSince = $IncomingHeaders['If-Modified-Since'];
         if($GMD == $IfNoneMatch && $IfModifiedSince == $GMD) {
            $Database = Gdn::Database();
            if(!is_null($Database))
               $Database->CloseConnection();

            $this->SetHeader('Content-Length', '0');
            $this->SendHeaders();
            header('HTTP/1.1 304 Not Modified');
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
   public function SetJson($Key, $Value = '') {
      $this->_Json[$Key] = $Value;
   }
   
   public function StatusCode($StatusCode, $Message = NULL, $SetHeader = TRUE) {
      if (is_null($Message))
         $Message = self::GetStatusMessage($StatusCode);
      
      if ($SetHeader)
         $this->SetHeader('Status', "{$StatusCode} {$Message}");
      return $Message;
   }
   
   public static function GetStatusMessage($StatusCode) {
      switch ($StatusCode) {
         case 100: $Message = 'Continue'; break;
         case 101: $Message = 'Switching Protocols'; break;

         case 200: $Message = 'OK'; break;
         case 201: $Message = 'Created'; break;
         case 202: $Message = 'Accepted'; break;
         case 203: $Message = 'Non-Authoritative Information'; break;
         case 204: $Message = 'No Content'; break;
         case 205: $Message = 'Reset Content'; break;

         case 300: $Message = 'Multiple Choices'; break;
         case 301: $Message = 'Moved Permanently'; break;
         case 302: $Message = 'Found'; break;
         case 303: $Message = 'See Other'; break;
         case 304: $Message = 'Not Modified'; break;
         case 305: $Message = 'Use Proxy'; break;
         case 307: $Message = 'Temporary Redirect'; break;

         case 400: $Message = 'Bad Request'; break;
         case 401: $Message = 'Not Authorized'; break;
         case 402: $Message = 'Payment Required'; break;
         case 403: $Message = 'Forbidden'; break;
         case 404: $Message = 'Not Found'; break;
         case 405: $Message = 'Method Not Allowed'; break;
         case 406: $Message = 'Not Acceptable'; break;
         case 407: $Message = 'Proxy Authentication Required'; break;
         case 408: $Message = 'Request Timeout'; break;
         case 409: $Message = 'Conflict'; break;
         case 410: $Message = 'Gone'; break;
         case 411: $Message = 'Length Required'; break;
         case 412: $Message = 'Precondition Failed'; break;
         case 413: $Message = 'Request Entity Too Large'; break;
         case 414: $Message = 'Request-URI Too Long'; break;
         case 415: $Message = 'Unsupported Media Type'; break;
         case 416: $Message = 'Requested Range Not Satisfiable'; break;
         case 417: $Message = 'Expectation Failed'; break;

         case 500: $Message = 'Internal Server Error'; break;
         case 501: $Message = 'Not Implemented'; break;
         case 502: $Message = 'Bad Gateway'; break;
         case 503: $Message = 'Service Unavailable'; break;
         case 504: $Message = 'Gateway Timeout'; break;
         case 505: $Message = 'HTTP Version Not Supported'; break;

         default: $Message = 'Unknown'; break;
      }
      return $Message;
   }
   
   /**
    * If this object has a "Head" object as a property, this will set it's Title value.
    * 
    * @param string $Title The value to pass to $this->Head->Title().
    */
   public function Title($Title = NULL, $Subtitle = NULL) {
      if (!is_null($Title))
         $this->SetData('Title', $Title);
      
      if (!is_null($Subtitle))
         $this->SetData('_Subtitle', $Subtitle);
      
      return $this->Data('Title');
   }
   
}
