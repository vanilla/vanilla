<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * A base class that all controllers can inherit for common controller
 * properties and methods.
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
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
    * (ie. vanilla, garden, etc).
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
    * @var object
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
    * If specified, this string will be used to identify the sort collection
    * in conf/modules.php to use when organizing modules within page assets.
    * $Configuration['Modules']['ModuleSortContainer']['AssetName'] = array('Module1', 'Module2');
    *
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
    * This is typically an array of arguments passed after the controller
    * name and controller method in the query string. Additional arguments are
    * parsed out by the @@Dispatcher and sent to $this->RequestArgs as an
    * array. If there are no additional arguments specified, this value will
    * remain FALSE.
    * ie. http://localhost/index.php/controller_name/controller_method/arg1/arg2/arg3
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
    * ie. http://localhost/index.php/controller_name/controller_method/
    *
    * @var string
    */
   public $RequestMethod;

   /**
    * An array of routes and where they should be redirected to (assigned to
    * the dispatcher in the main bootstrap, and then assigned by reference
    * from the dispatcher.
    *
    * @var array
    */
   public $Routes;

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
    * An array of JS file names to search for in app folders & include in
    * the page.
    *
    * @var array
    */
   protected $_JsFiles;

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
    * An associative array of header values to be sent to the browser before
    * the page is rendered.
    *
    * @var array
    */
   protected $_Headers;

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
      $this->Routes = array();
      $this->SelfUrl = '';
      $this->StatusMessage = '';
      $this->SyndicationMethod = SYNDICATION_NONE;
      $this->Theme = Gdn::Config('Garden.Theme');
      $this->View = '';
      $this->_CssFiles = array();
      $this->_JsFiles = array();
      $this->_Definitions = array();
      $this->_DeliveryMethod = GetIncomingValue('DeliveryMethod', DELIVERY_METHOD_XHTML);
      $this->_DeliveryType = GetIncomingValue('DeliveryType', DELIVERY_TYPE_ALL);
      $this->_Json = array();
      $this->_Headers = array(
         'Expires' =>  'Mon, 26 Jul 1997 05:00:00 GMT', // Make sure the client always checks at the server before using it's cached copy.
         'X-Powered-By' => APPLICATION.' '.APPLICATION_VERSION,
         'Content-Type' => Gdn::Config('Garden.ContentType', '').'; charset='.Gdn::Config('Garden.Charset', ''), // PROPERLY ENCODE THE CONTENT
         'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT' // PREVENT PAGE CACHING: always modified (this can be overridden by specific controllers)
         // $Dispatcher->Header('Cache-Control', 'no-cache, must-revalidate'); // PREVENT PAGE CACHING: HTTP/1.1
         // $Dispatcher->Header('Pragma', 'no-cache'); // PREVENT PAGE CACHING: HTTP/1.0
      );

      parent::__construct();
      $this->ControllerName = strtolower($this->ClassName);
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
         if (isset($this->Assets[$AssetContainer][$AssetName]))
            $this->Assets[$AssetContainer][$AssetName] .= $Asset;
         else
            $this->Assets[$AssetContainer][$AssetName] = $Asset;
      }
   }

   /**
    * Adds a CSS file to search for in the theme folder(s).
    *
    * @param string $FileName The CSS file to search for.
    * @param string $AppFolder The application folder that should contain the CSS file. Default is to
    * use the application folder that this controller belongs to.
    */
   public function AddCssFile($FileName, $AppFolder = '') {
      $this->_CssFiles[] = array('FileName' => $FileName, 'AppFolder' => $AppFolder);
   }
   
   public function ClearCssFiles() {
      $this->_CssFiles = array();
   }
   
   /**
    * Undocumented method.
    *
    * @param string $Term
    * @param string $Definition
    * @todo Method AddDefinition(), $Term and $Definition need descriptions.
    */
   public function AddDefinition($Term, $Definition = NULL) {
      if(!is_null($Definition))
         $this->_Definitions[$Term] = $Definition;
      return ArrayValue($Term, $this->_Definitions);
   }

   /**
    * Adds a JS file to search for in the application or global js folder(s).
    *
    * @param string $FileName The CSS file to search for.
    * @param string $AppFolder The application folder that should contain the JS file. Default is to
    * use the application folder that this controller belongs to.
    */
   public function AddJsFile($FileName, $AppFolder = '') {
      $this->_JsFiles[] = array('FileName' => $FileName, 'AppFolder' => $AppFolder);
   }

   /**
    * Adds the specified module to the specified asset target. If no asset
    * target is defined, it will use the asset target defined by the module's
    * AssetTarget method.
    *
    * @param mixed $Module A module or the name of a module to add to the page.
    * @param string $AssetTarget
    * @todo $AssetTarget need the correct variable type and description.
    */
   public function AddModule($Module, $AssetTarget = '') {
      $this->FireEvent('BeforeAddModule');

      if (!is_object($Module)) {
         if (property_exists($this, $Module) && is_object($this->$Module)) {
            $Module = $this->$Module;
         } else {
            if (!class_exists($Module))
               __autoload($Module);

            if (class_exists($Module)) {
               // Make sure that the class implements Gdn_IModule
               $ReflectionClass = new ReflectionClass($Module);
               if ($ReflectionClass->implementsInterface("Gdn_IModule"))
                  $Module = new $Module($this);

            }
         }
      }
      if (is_object($Module)) {
         $AssetTarget = ($AssetTarget == '' ? $Module->AssetTarget() : $AssetTarget);
         // echo '<div>adding: '.$Module->Name().' ('.(property_exists($Module, 'HtmlId') ? $Module->HtmlId : '').') to '.$AssetTarget.' <textarea>'.$Module->ToString().'</textarea></div>';
         $this->AddAsset($AssetTarget, $Module->ToString(), $Module->Name());
      }

      $this->FireEvent('AfterAddModule');
   }

   /**
    * Undocumented method.
    *
    * @todo Method DefinitionList() needs a description.
    */
   public function DefinitionList() {
      $Session = Gdn::Session();
      if (!array_key_exists('TransportError', $this->_Definitions))
         $this->_Definitions['TransportError'] = T('A fatal error occurred while processing the request.<br />The server returned the following response: %s');

      if (!array_key_exists('TransientKey', $this->_Definitions))
         $this->_Definitions['TransientKey'] = $Session->TransientKey();

      if (!array_key_exists('WebRoot', $this->_Definitions))
         $this->_Definitions['WebRoot'] = Gdn_Url::WebRoot(TRUE);

      if (!array_key_exists('UrlRoot', $this->_Definitions))
         $this->_Definitions['UrlRoot'] = substr(Url(' '), 0, -2);

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

      $Return = '<!-- Various definitions for Javascript //-->
<div id="Definitions" style="display: none;">
';

      foreach ($this->_Definitions as $Term => $Definition) {
         $Return .= '<input type="hidden" id="'.$Term.'" value="'.$Definition.'" />'."\n";
      }

      return $Return .'</div>';
   }

   /**
    * Returns the requested delivery type of the controller if $Default is not
    * provided. Sets and returns the delivery type otherwise.
    *
    * @param string $Default One of the DELIVERY_TYPE_* constants.
    */
   public function DeliveryType($Default = '') {
      if ($Default != '')
         $this->_DeliveryType = $Default;

      return $this->_DeliveryType;
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
   public function FetchView($View = '', $ControllerName = '', $ApplicationFolder = '') {
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
      $ViewContents = ob_get_contents();
      @ob_end_clean();
      
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
    * @param string $ApplicationFolder The name of the application folder that contains the requested controller
    * if it is not $this->ApplicationFolder.
    */
   public function FetchViewLocation($View = '', $ControllerName = '', $ApplicationFolder = '') {
      // Accept an explicitly defined view, or look to the method that was called on this controller
      if ($View == '')
         $View = $this->View;

      if ($View == '')
         $View = $this->RequestMethod;

      if ($ControllerName == '')
         $ControllerName = $this->ControllerName;

      // Munge the controller folder onto the controller name if it is present.
      if ($this->ControllerFolder != '')
         $ControllerName = $this->ControllerFolder . DS . $ControllerName;

      if (strtolower(substr($ControllerName, -10, 10)) == 'controller')
         $ControllerName = substr($ControllerName, 0, -10);

      if ($ApplicationFolder == '')
         $ApplicationFolder = $this->ApplicationFolder;

      $ApplicationFolder = strtolower($ApplicationFolder);
      $ControllerName = strtolower($ControllerName);
      if(strpos($View, DS) === FALSE) // keep explicit paths as they are.
         $View = strtolower($View);

      // If this is a syndication request, append the method to the view
      if ($this->SyndicationMethod == SYNDICATION_ATOM)
         $View .= '_atom';
      else if ($this->SyndicationMethod == SYNDICATION_RSS)
         $View .= '_rss';

      $LocationName = $ApplicationFolder.'/'.$ControllerName.'/'.$View;
      $ViewPath = ArrayValue($LocationName, $this->_ViewLocations, FALSE);
      if ($ViewPath === FALSE) {
         // Views come from one of four places:
         $ViewPaths = array();
         // 1. An explicitly defined path to a view
         if (strpos($View, DS) !== FALSE)
            $ViewPaths[] = $View;

         if ($this->Theme) {
            // 2. Application-specific theme view. eg. /path/to/application/themes/theme_name/app_name/views/controller_name/
            $ViewPaths[] = CombinePaths(array(PATH_THEMES, $this->Theme, $ApplicationFolder, 'views', $ControllerName, $View . '.*'));
            // 3. Garden-wide theme view. eg. /path/to/application/themes/theme_name/views/controller_name/
            $ViewPaths[] = CombinePaths(array(PATH_THEMES, $this->Theme, 'views', $ControllerName, $View . '.*'));
         }
         // 4. Application default. eg. /path/to/application/app_name/views/controller_name/
         $ViewPaths[] = CombinePaths(array(PATH_APPLICATIONS, $ApplicationFolder, 'views', $ControllerName, $View . '.*'));

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
      if ($ViewPath === FALSE)
         trigger_error(ErrorMessage('Could not find a `'.$View.'` view for the `'.$ControllerName.'` controller in the `'.$ApplicationFolder.'` application.', $this->ClassName, 'FetchViewLocation'), E_USER_ERROR);

      return $ViewPath;
   }

   /**
    * Undocumented method.
    *
    * @param string $AssetName
    * @todo Method GetAsset() and $AssetName needs descriptions.
    */
   public function GetAsset($AssetName) {
      if(!array_key_exists($AssetName, $this->Assets))
         return '';
      if(!is_array($this->Assets[$AssetName]))
         return $this->Assets[$AssetName];
      
      // Include the module sort
      $Modules = Gdn::Config('Modules', array());
      if($this->ModuleSortContainer === FALSE)
         $ModuleSort = FALSE; // no sort wanted
      elseif(array_key_exists($this->ModuleSortContainer, $Modules) && array_key_exists($AssetName, $Modules[$this->ModuleSortContainer]))
         $ModuleSort = $Modules[$this->ModuleSortContainer][$AssetName]; // explicit sort
      elseif(array_key_exists($this->Application, $Modules) && array_key_exists($AssetName, $Modules[$this->Application]))
         $ModuleSort = $Modules[$this->Application][$AssetName]; // application default sort

      $ThisAssets = $this->Assets[$AssetName];
      $Assets = array();
      if(isset($ModuleSort) && is_array($ModuleSort)) {
         // There is a specified sort so sort by it.
         foreach($ModuleSort as $Name) {
            if(array_key_exists($Name, $ThisAssets)) {
               if(defined("DEBUG"))
                  $Assets[] = "\n<!-- Asset: $Name -->\n";
               $Assets[] = $ThisAssets[$Name];
               unset($ThisAssets[$Name]);
            }
         }
      }
      // Pick up any leftover assets
      foreach($ThisAssets as $Name => $Asset) {
         if(defined("DEBUG"))
            $Assets[] = "\n<!-- Asset: $Name -->\n";
         $Assets[] = $Asset;
      }
         
      if(count($Assets) == 0) {
         return '';
      } elseif(count($Assets) == 1) {
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
    * The initialize method is called by the dispatcher after the constructor
    * has completed, objects have been passed along, assets have been
    * retrieved, and before the requested method fires. Use it in any extended
    * controller to do things like loading script and CSS into the head.
    */
   public function Initialize() {
      if (is_object($this->Menu))
         $this->Menu->Sort = Gdn::Config('Garden.Menu.Sort');
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
   public function xRender($View = '', $ControllerName = '', $ApplicationFolder = '', $AssetName = 'Content') {
      if ($this->_DeliveryType == DELIVERY_TYPE_NONE)
         return;

      // If there were uncontrolled errors above the json data, wipe them out
      // before fetching it (otherwise the json will not be properly parsed
      // by javascript).
      if ($this->_DeliveryMethod == DELIVERY_METHOD_JSON)
         ob_clean(); 

      // Send headers to the browser
      $this->SendHeaders();

      // Make sure to clear out the content asset collection if this is a syndication request
      if ($this->SyndicationMethod !== SYNDICATION_NONE)
         $this->Assets['Content'] = '';

      // Define the view
      if ($this->_DeliveryType != DELIVERY_TYPE_BOOL) {
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

      if ($this->_DeliveryMethod == DELIVERY_METHOD_JSON) {
         // Format the view as JSON with some extra information about the
         // success status of the form so that jQuery knows what to do
         // with the result.
         $FormSaved = FALSE;
         if (property_exists($this, 'Form') && $this->Form->ErrorCount() == 0)
            $FormSaved = TRUE;

         $this->SetJson('FormSaved', $FormSaved);
         $this->SetJson('DeliveryType', $this->_DeliveryType);
         if($View instanceof Gdn_IModule) {
            $this->SetJson('Data', $View->ToString());
         } else {
            $this->SetJson('Data', $View);
         }
         $this->SetJson('StatusMessage', $this->StatusMessage);
         $this->SetJson('RedirectUrl', $this->RedirectUrl);

         // Make sure the database connection is closed before exiting.
         $Database = Gdn::Database();
         $Database->CloseConnection();
         exit(json_encode($this->_Json));
      } else {
         if ($this->StatusMessage != '' && $this->SyndicationMethod === SYNDICATION_NONE)
            $this->AddAsset($AssetName, '<div class="Messages Information"><ul><li>'.$this->StatusMessage.'</li></ul></div>');

         if ($this->RedirectUrl != '' && $this->SyndicationMethod === SYNDICATION_NONE)
            $this->AddDefinition('RedirectUrl', $this->RedirectUrl);

         // Render
         if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
            echo $View ? 'TRUE' : 'FALSE';
         } else if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            // Add definitions to the page
            if ($this->SyndicationMethod === SYNDICATION_NONE)
               $this->AddAsset($AssetName, $this->DefinitionList());

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
      if(is_string($Asset))
         echo $Asset;
      else
         $Asset->Render();
   }

   /**
    * Undocumented method.
    *
    * @todo Method RenderMaster() needs a description.
    */
   public function RenderMaster() {
      // Build the master view if necessary
      if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
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

         // Only get css & ui components if this is NOT a syndication request
         if ($this->SyndicationMethod == SYNDICATION_NONE && is_object($this->Head)) {
            // And now search for/add all css files
            foreach ($this->_CssFiles as $CssInfo) {
               $CssFile = $CssInfo['FileName'];
               
               if(strpos($CssFile, '/') !== FALSE) {
                  // A direct path to the file was given.
                  $CssPaths = array(PATH_ROOT.str_replace('/', DS, $CssFile));
               } else {
                  $CssGlob = preg_replace('/(.*)(\.css)/', '\1*\2', $CssFile);
                  $AppFolder = $CssInfo['AppFolder'];
                  if ($AppFolder == '')
                     $AppFolder = $this->ApplicationFolder;
   
                  // CSS comes from one of four places:
                  $CssPaths = array();
                  if ($this->Theme) {
                     // 1. Application-specific css. eg. root/themes/theme_name/app_name/design/
                     $CssPaths[] = PATH_THEMES . DS . $this->Theme . DS . $AppFolder . DS . 'design' . DS . $CssGlob;
                     // 2. Garden-wide theme view. eg. root/themes/theme_name/design/
                     $CssPaths[] = PATH_THEMES . DS . $this->Theme . DS . 'design' . DS . $CssGlob;
                  }
                  // 3. Application default. eg. root/applications/app_name/design/
                  $CssPaths[] = PATH_APPLICATIONS . DS . $AppFolder . DS . 'design' . DS . $CssGlob;
                  // 4. Garden default. eg. root/applications/garden/design/
                  $CssPaths[] = PATH_APPLICATIONS . DS . 'garden' . DS . 'design' . DS . $CssGlob;
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
                  $CssPath = str_replace(
                     array(PATH_ROOT, DS),
                     array('', '/'),
                     $CssPath
                  );
                  $this->Head->AddCss($CssPath, 'screen');
               }
            }
            
            // And now search for/add all JS files
            foreach ($this->_JsFiles as $JsInfo) {
               $JsFile = $JsInfo['FileName'];
               
               if (strpos($JsFile, '/') !== FALSE) {
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
                  // 3. This application folder
                  $JsPaths[] = PATH_APPLICATIONS . DS . $AppFolder . DS . 'js' . DS . $JsFile;
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
                  $JsPath = str_replace(
                     array(PATH_ROOT, DS),
                     array('', '/'),
                     $JsPath
                  );
                  $this->Head->AddScript($JsPath);
               }
            }
         }
         // Add the favicon
         $this->Head->SetFavIcon(Asset('themes/'.$this->Theme.'/design/'.Gdn::Config('Garden.FavIcon', 'favicon.png')));
         
         // Make sure the head module gets passed into the assets collection.
         $this->AddModule('Head');
      }

      // Master views come from one of four places:
      $MasterViewPaths = array();
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
         // 4. Garden default. eg. root/garden/views/
         $MasterViewPaths[] = CombinePaths(array(PATH_APPLICATIONS, 'garden', 'views', $this->MasterView . '.master*'));
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

      if ($MasterViewPath === FALSE)
         trigger_error(ErrorMessage('Could not find master view:'.$this->MasterView.'.master*', $this->ClassName, '_FetchController'), E_USER_ERROR);
      
      
      /// A unique identifier that can be used in the body tag of the master view if needed.
      $ControllerName = $this->ClassName;
      if (substr($ControllerName, -10) == 'Controller')
         $ControllerName = substr($ControllerName, 0, -10); // Strip "Controller" from the body identifier.
         
      $this->SetData('CssClass', $this->Application.' '.$ControllerName.' '.$this->RequestMethod.' '.$this->CssClass, TRUE);
     
      // Check to see if there is a handler for this particular extension.
      $ViewHandler = Gdn::Factory('ViewHandler' . strtolower(strrchr($MasterViewPath, '.')));
      if(is_null($ViewHandler)) {
         $BodyIdentifier = strtolower($this->ApplicationFolder.'_'.$ControllerName.'_'.Format::AlphaNumeric(strtolower($this->RequestMethod)));
         include($MasterViewPath);
      } else {
         $ViewHandler->Render($MasterViewPath, $this);
      }
   }
   
   /**
    * Checks that the user has the specified permissions. If the user does not,
    * they are redirected to the DefaultPermission route.
    * @param mixed $Permission A permission or array of permission names required to access this resource.
    * @param bool $FullMatch If $Permission is an array, $FullMatch indicates if all permissions specified are required. If false, the user only needs one of the specified permissions.
    */
   public function Permission($Permission, $JunctionID = '', $FullMatch = TRUE) {
      $Session = Gdn::Session();

      // TODO: Make this work with different delivery types.
      if (!$Session->CheckPermission($Permission, $JunctionID, $FullMatch)) {
        if (!$Session->IsValid()) {
           Redirect(Gdn::Authenticator()->SignInUrl($this->SelfUrl));
        } else {
          Redirect($this->Routes['DefaultPermission']);
        }
      }

   }

   /**
    * Sends all headers in $this->_Headers (defined with $this->SetHeader()) to
    * the browser.
    */
   public function SendHeaders() {
      // TODO: ALWAYS RENDER OR REDIRECT FROM THE CONTROLLER OR HEADERS WILL NOT BE SENT!! PUT THIS IN DOCS!!!
      foreach ($this->_Headers as $Name => $Value) {
         header($Name.': '.$Value, TRUE);
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
   public function SetData($Key, $Value, $AddProperty = FALSE) {
      $this->Data[$Key] = $Value;
      if($AddProperty === TRUE) {
         $this->$Key = $Value;
      }
      return $Value;
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
   
   /**
    * If this object has a "Head" object as a property, this will set it's Title value.
    * 
    * @param string $Title The value to pass to $this->Head->Title().
    */
   public function Title($Title) {
      if ($this->Head)
         $this->Head->Title($Title);
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
}