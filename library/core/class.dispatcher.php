<?php if (!defined('APPLICATION')) exit();

/**
 * Framework dispatcher
 * 
 * Handles all requests and routing.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Dispatcher extends Gdn_Pluggable {

   /**
    * An array of folders within the application that are OK to search through
    * for controllers. This property is filled by the applications array
    * located in /conf/applications.php and included in /bootstrap.php
    *
    * @var array
    */
   private $_EnabledApplicationFolders;

   /**
    * An associative array of ApplicationName => ApplicationFolder. This
    * property is filled by the applications array located in
    * /conf/applications.php and included in /bootstrap.php
    *
    * @var array
    */
   private $_EnabledApplications;

   /**
    * The currently requested url (defined in _AnalyzeRequest)
    *
    * @var string
    */
   public $Request;

   /**
    * The name of the application folder that contains the controller that has
    * been requested.
    *
    * @var string
    */
   private $_ApplicationFolder;

   /**
    * An associative collection of AssetName => Strings that will get passed
    * into the controller once it has been instantiated.
    *
    * @var array
    */
   private $_AssetCollection;

   /**
    * The name of the controller folder that contains the controller that has
    * been requested.
    *
    * @var string
    */
   public $ControllerFolder;

   /**
    * The name of the controller to be dispatched.
    *
    * @var string
    */
   public $ControllerName;

   /**
    * The method of the controller to be called.
    *
    * @var string
    */
   public $ControllerMethod;

   /**
    * Any query string arguments supplied to the controller method.
    *
    * @var string
    */
   private $_ControllerMethodArgs = array();

   /**
    * @var string|FALSE The delivery method to set on the controller.
    */
   private $_DeliveryMethod = FALSE;


   /**
    * @var string|FALSE The delivery type to set on the controller.
    */
   private $_DeliveryType = FALSE;

   /**
    * An associative collection of variables that will get passed into the
    * controller as properties once it has been instantiated.
    *
    * @var array
    */
   private $_PropertyCollection;

   /**
    * Defined by the url of the request: SYNDICATION_RSS, SYNDICATION_ATOM, or
    * SYNDICATION_NONE (default).
    *
    * @var string
    */
   private $_SyndicationMethod;
   
   const BLOCK_NEVER = 0;
   const BLOCK_PERMISSION = 1;
   const BLOCK_ANY = 2;

   /**
    * Class constructor.
    */
   public function __construct() {
      parent::__construct();
      $this->_EnabledApplicationFolders = array();
      $this->Request = '';
      $this->_ApplicationFolder = '';
      $this->_AssetCollection = array();
      $this->ControllerFolder = '';
      $this->ControllerName = '';
      $this->ControllerMethod = '';
      $this->_ControllerMethodArgs = array();
      $this->_PropertyCollection = array();
      $this->_Data = array();
   }
   
   public function Cleanup() {
      $this->FireEvent('Cleanup');
   }

   /**
    * Return the properly formatted controller class name.
    */
   public function ControllerName() {
      return $this->ControllerName.'Controller';
   }
   
   public function Application() {
      return $this->_ApplicationFolder;
   }
   
   public function Controller() {
      return $this->ControllerName;
   }
   
   public function ControllerMethod() {
      return $this->ControllerMethod;
   }
   
   public function ControllerArguments() {
      return $this->_ControllerMethodArgs;
   }

   public function Start() {
      $this->FireEvent('AppStartup');
   }
   
   /**
    * Analyzes the supplied query string and decides how to dispatch the request.
    */
   public function Dispatch($ImportRequest = NULL, $Permanent = TRUE) {
      
      if ($ImportRequest && is_string($ImportRequest))
         $ImportRequest = Gdn_Request::Create()->FromEnvironment()->WithURI($ImportRequest);
      
      if (is_a($ImportRequest, 'Gdn_Request') && $Permanent) {
         Gdn::Request($ImportRequest);
      }
      
      $Request = is_a($ImportRequest, 'Gdn_Request') ? $ImportRequest : Gdn::Request();
      
      if (Gdn::Session()->NewVisit()) {
         Gdn::UserModel()->FireEvent('Visit');
      }
      
      // Move this up to allow pre-routing
      $this->FireEvent('BeforeDispatch');
      
      // By default, all requests can be blocked by UpdateMode/PrivateCommunity
      $CanBlock = self::BLOCK_ANY;
      
      try {
         $BlockExceptions = array(
             '/^utility(\/.*)?$/'                   => self::BLOCK_NEVER,
             '/^plugin(\/.*)?$/'                    => self::BLOCK_NEVER,
             '/^sso(\/.*)?$/'                       => self::BLOCK_NEVER,
             '/^discussions\/getcommentcounts/'     => self::BLOCK_NEVER,
             '/^entry(\/.*)?$/'                     => self::BLOCK_PERMISSION,
             '/^user\/usernameavailable(\/.*)?$/'   => self::BLOCK_PERMISSION,
             '/^user\/emailavailable(\/.*)?$/'      => self::BLOCK_PERMISSION,
             '/^home\/termsofservice(\/.*)?$/'      => self::BLOCK_PERMISSION
         );
         
         $this->EventArguments['BlockExceptions'] = &$BlockExceptions;
         $this->FireEvent('BeforeBlockDetect');
         
         $PathRequest = Gdn::Request()->Path();
         foreach ($BlockExceptions as $BlockException => $BlockLevel) {
            if (preg_match($BlockException, $PathRequest))
               throw new Exception("Block detected - {$BlockException}", $BlockLevel);
         }
         
         // Never block an admin
         if (Gdn::Session()->CheckPermission('Garden.Settings.Manage'))
            throw new Exception("Block detected", self::BLOCK_NEVER);
         
         if (Gdn::Session()->IsValid())
            throw new Exception("Block detected", self::BLOCK_PERMISSION);
         
      } catch (Exception $e) {
         // BlockLevel
         //  TRUE = Block any time
         //  FALSE = Absolutely no blocking
         //  NULL = Block for permissions (e.g. PrivateCommunity)
         $CanBlock = $e->getCode();
      }
      
      // If we're in updatemode and arent explicitly prevented from blocking, block
      if (Gdn::Config('Garden.UpdateMode', FALSE) && $CanBlock > self::BLOCK_NEVER) {
         $Request->WithURI(Gdn::Router()->GetDestination('UpdateMode'));
      }
      
      // Analze the request AFTER checking for update mode.
      $this->AnalyzeRequest($Request);
      $this->FireEvent('AfterAnalyzeRequest');
      
      // If we're in updatemode and can block, redirect to signin
      if (C('Garden.PrivateCommunity') && $CanBlock > self::BLOCK_PERMISSION) {
         Redirect('/entry/signin?Target='.urlencode($this->Request));
         exit();
      }
      
      $ControllerName = $this->ControllerName();
      if ($ControllerName != '' && class_exists($ControllerName)) {
         // Create it and call the appropriate method/action
         $Controller = new $ControllerName();
         Gdn::Controller($Controller);
         
         $this->EventArguments['Controller'] =& $Controller;
         $this->FireEvent('AfterControllerCreate');

         // Pass along any assets
         if (is_array($this->_AssetCollection)) {
            foreach ($this->_AssetCollection as $AssetName => $Assets) {
               foreach ($Assets as $Asset) {
                  $Controller->AddAsset($AssetName, $Asset);
               }
            }
         }

         // Instantiate Imported & Uses classes
         $Controller->GetImports();

         // Pass in the syndication method
         $Controller->SyndicationMethod = $this->_SyndicationMethod;

         // Pass along the request
         $Controller->SelfUrl = $this->Request;

         // Pass along any objects
         foreach ($this->_PropertyCollection as $Name => $Mixed) {
            $Controller->$Name = $Mixed;
         }

         // Pass along any data.
         if (is_array($this->_Data))
            $Controller->Data = $this->_Data;

         // Set up a default controller method in case one isn't defined.
         $ControllerMethod = str_replace('_', '', $this->ControllerMethod);
         $Controller->OriginalRequestMethod = $ControllerMethod;
         
         // Take enabled plugins into account, as well
         $PluginReplacement = Gdn::PluginManager()->HasNewMethod($this->ControllerName(), $this->ControllerMethod);
         if (!$PluginReplacement && ($this->ControllerMethod == '' || !method_exists($Controller, $ControllerMethod))) {
            // Check to see if there is an 'x' version of the method.
            if (method_exists($Controller, 'x' . $ControllerMethod)) {
               // $PluginManagerHasReplacementMethod = TRUE;
               $ControllerMethod = 'x' . $ControllerMethod;
            } else {
               if ($this->ControllerMethod != '')
                  array_unshift($this->_ControllerMethodArgs, $this->ControllerMethod);

               $this->ControllerMethod = 'Index';
               $ControllerMethod = 'Index';

               $PluginReplacement = Gdn::PluginManager()->HasNewMethod($this->ControllerName(), $this->ControllerMethod);
            }
         }

         // Pass in the querystring values
         $Controller->ApplicationFolder = $this->_ApplicationFolder;
         $Controller->Application = $this->EnabledApplication();
         $Controller->ControllerFolder = $this->ControllerFolder;
         $Controller->RequestMethod = $this->ControllerMethod;
         $Controller->RequestArgs = $this->_ControllerMethodArgs;
         $Controller->Request = $Request;
         $Controller->DeliveryType($Request->GetValue('DeliveryType', $this->_DeliveryType));
         $Controller->DeliveryMethod($Request->GetValue('DeliveryMethod', $this->_DeliveryMethod));
         
         // Set special controller method options for REST APIs.
         $Controller->Initialize();
         
         $this->EventArguments['Controller'] = &$Controller;
         $this->FireEvent('AfterControllerInit');

         // Call the requested method on the controller - error out if not defined.
         if ($PluginReplacement) {
            // Set the application folder to the plugin's key.
//            $PluginInfo = Gdn::PluginManager()->GetPluginInfo($PluginReplacement, Gdn_PluginManager::ACCESS_CLASSNAME);
//            if ($PluginInfo) {
//               $Controller->ApplicationFolder = 'plugins/'.GetValue('Index', $PluginInfo);
//            }
            
            // Reflect the args for the method.
            $Callback = Gdn::PluginManager()->GetCallback($Controller->ControllerName, $ControllerMethod);
            // Augment the arguments to the plugin with the sender and these arguments.
            $InputArgs = array_merge(array($Controller), $this->_ControllerMethodArgs, array('Sender' => $Controller, 'Args' => $this->_ControllerMethodArgs));
//            decho(array_keys($InputArgs), 'InputArgs');
            $Args = ReflectArgs($Callback, $InputArgs, $Request->Get());
            $Controller->ReflectArgs = $Args;
            
            try {
               $this->FireEvent('BeforeControllerMethod');
               Gdn::PluginManager()->CallEventHandlers($Controller, $Controller->ControllerName, $ControllerMethod, 'Before');
               
               call_user_func_array($Callback, $Args);
            } catch (Exception $Ex) {
               $Controller->RenderException($Ex);
            }
         } elseif (method_exists($Controller, $ControllerMethod)) {
            $Args = ReflectArgs(array($Controller, $ControllerMethod), $this->_ControllerMethodArgs, $Request->Get());
            $this->_ControllerMethodArgs = $Args;
            $Controller->ReflectArgs = $Args;
            
            try {
               $this->FireEvent('BeforeControllerMethod');
               Gdn::PluginManager()->CallEventHandlers($Controller, $Controller->ControllerName, $ControllerMethod, 'Before');
               
               call_user_func_array(array($Controller, $ControllerMethod), $Args);
            } catch (Exception $Ex) {
               $Controller->RenderException($Ex);
               exit();
            }
         } else {
            $this->EventArguments['Handled'] = FALSE;
            $Handled =& $this->EventArguments['Handled'];
            $this->FireEvent('NotFound');
            
            if (!$Handled) {
               Gdn::Request()->WithRoute('Default404');
               return $this->Dispatch();
            } else {
               return $Handled;
            }
         }
      }
   }
   
   /**
    * Undocumented method.
    *
    * @param string $EnabledApplications
    * @todo Method EnabledApplicationFolders() and $EnabledApplications needs descriptions.
    */
   public function EnabledApplicationFolders($EnabledApplications = '') {
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
   public function EnabledApplication($ApplicationFolder = '') {
      if ($ApplicationFolder == '')
         $ApplicationFolder = $this->_ApplicationFolder;

      foreach (Gdn::ApplicationManager()->AvailableApplications() as $ApplicationName => $ApplicationInfo) {
         if (GetValue('Folder', $ApplicationInfo, FALSE) === $ApplicationFolder) {
            $EnabledApplication = $ApplicationName;
            $this->EventArguments['EnabledApplication'] = $EnabledApplication;
            $this->FireEvent('AfterEnabledApplication');
            return $EnabledApplication;
         }
      }
      return FALSE;
   }

   /**
    * Allows the passing of a string to the controller's asset collection.
    *
    * @param string $AssetName The name of the asset collection to add the string to.
    * @param mixed $Asset The string asset to be added. The asset can be one of two things.
    * - <b>string</b>: The string will be rendered to the page.
    * - <b>Gdn_IModule</b>: The Gdn_IModule::Render() method will be called when the asset is rendered.
    */
   public function PassAsset($AssetName, $Asset) {
      $this->_AssetCollection[$AssetName][] = $Asset;
      return $this;
   }

   public function PassData($Name, $Value) {
      $this->_Data[$Name] = $Value;
      return $this;
   }

   /**
    * Allows the passing of any variable to the controller as a property.
    *
    * @param string $Name The name of the property to assign the variable to.
    * @param mixed $Mixed The variable to be passed as a property of the controller.
    */
   public function PassProperty($Name, $Mixed) {
      $this->_PropertyCollection[$Name] = $Mixed;
      return $this;
   }

   /**
    * Parses the query string looking for supplied request parameters. Places
    * anything useful into this object's Controller properties.
    *
    * @param int $FolderDepth
    */
   protected function AnalyzeRequest(&$Request) {
   
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
      $this->ControllerFolder = '';
      $this->ControllerName = '';
      $this->ControllerMethod = 'index';
      $this->_ControllerMethodArgs = array();
      $this->Request = $Request->Path(FALSE);

      $PathAndQuery = $Request->PathAndQuery();
      $MatchRoute = Gdn::Router()->MatchRoute($PathAndQuery);
      
      // We have a route. Take action.
      if ($MatchRoute !== FALSE) {
         switch ($MatchRoute['Type']) {
            case 'Internal':
               $Request->PathAndQuery($MatchRoute['FinalDestination']);
               $this->Request = $Request->Path(FALSE);
               break;

            case 'Temporary':
               header("HTTP/1.1 302 Moved Temporarily" );
               header("Location: ".Url($MatchRoute['FinalDestination']));
               exit();
               break;

            case 'Permanent':
               header("HTTP/1.1 301 Moved Permanently" );
               header("Location: ".Url($MatchRoute['FinalDestination']));
               exit();
               break;

            case 'NotAuthorized':
               header("HTTP/1.1 401 Not Authorized" );
               $this->Request = $MatchRoute['FinalDestination'];
               break;

            case 'NotFound':
               header("HTTP/1.1 404 Not Found" );
               $this->Request = $MatchRoute['FinalDestination'];
               break;
            case 'Test':
               $Request->PathAndQuery($MatchRoute['FinalDestination']);
               $this->Request = $Request->Path(FALSE);
               decho($MatchRoute, 'Route');
               decho(array(
                  'Path' => $Request->Path(),
                  'Get' => $Request->Get()
                  ), 'Request');
               die();
         }
      }
      
      switch ($Request->OutputFormat()) {
         case 'rss':
            $this->_SyndicationMethod = SYNDICATION_RSS;
            break;
         case 'atom':
            $this->_SyndicationMethod = SYNDICATION_ATOM;
            break;
         case 'default':
         default:
            $this->_SyndicationMethod = SYNDICATION_NONE;
            break;
      }

      if ($this->Request == '') {
         $DefaultController = Gdn::Router()->GetRoute('DefaultController');
         $this->Request = $DefaultController['Destination'];
      }

      $Parts = explode('/', str_replace('\\', '/', $this->Request));

      /**
       * The application folder is either the first argument or is not provided. The controller is therefore
       * either the second argument or the first, depending on the result of the previous statement. Check that.
       */
      try {
         // if the 1st argument is a valid application, check if it has a controller matching the 2nd argument
         if (in_array($Parts[0], $this->EnabledApplicationFolders()))
            $this->FindController(1, $Parts);

         // if no match, see if the first argument is a controller
         $this->FindController(0, $Parts);

         // 3] See if there is a plugin trying to create a root method.
         list($MethodName, $DeliveryMethod) = $this->_SplitDeliveryMethod(GetValue(0, $Parts), TRUE);
         if ($MethodName && Gdn::PluginManager()->HasNewMethod('RootController', $MethodName, TRUE)) {
            $this->_DeliveryMethod = $DeliveryMethod;
            $Parts[0] = $MethodName;
            $Parts = array_merge(array('root'), $Parts);
            $this->FindController(0, $Parts);
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
               break;
            default:
               $this->_DeliveryMethod = DELIVERY_METHOD_XHTML;
               break;
         }

         return TRUE;
      } catch (GdnDispatcherControllerNotFoundException $e) {
         $this->EventArguments['Handled'] = FALSE;
         $Handled =& $this->EventArguments['Handled'];
         $this->FireEvent('NotFound');
         
         if (!$Handled) {
            header("HTTP/1.1 404 Not Found");
            $Request->WithRoute('Default404');
            return $this->AnalyzeRequest($Request);
         }
      }
   }

   protected function FindController($ControllerKey, $Parts) {
      $Controller = GetValue($ControllerKey, $Parts, NULL);
      $Controller = ucfirst(strtolower($Controller));
      $Application = GetValue($ControllerKey-1, $Parts, NULL);

      // Check for a file extension on the controller.
      list($Controller, $this->_DeliveryMethod) = $this->_SplitDeliveryMethod($Controller, FALSE);
      
      // If we're loading from a fully qualified path, prioritize this app's library
      if (!is_null($Application)) {
         Gdn_Autoloader::Priority(
            Gdn_Autoloader::CONTEXT_APPLICATION, 
            $Application,
            Gdn_Autoloader::MAP_CONTROLLER, 
            Gdn_Autoloader::PRIORITY_TYPE_RESTRICT,
            Gdn_Autoloader::PRIORITY_ONCE);
      }
      
      $ControllerName = $Controller.'Controller';
      $ControllerPath = Gdn_Autoloader::Lookup($ControllerName, array('Quiet' => TRUE));
      if ($ControllerPath) {
         // This was a guess search with no specified application. Look up
         // the application folder from the controller path.
         if (is_null($Application)) {
            $InterimPath = explode('/controllers/', $ControllerPath);
            array_pop($InterimPath); // Get rid of the end. Useless;
            $InterimPath = explode('/', trim(array_pop($InterimPath)));
            $Application = array_pop($InterimPath);
            if (!in_array($Application, $this->EnabledApplicationFolders()))
               return FALSE;
         }
      
         Gdn_Autoloader::Priority(
            Gdn_Autoloader::CONTEXT_APPLICATION, 
            $Application,
            Gdn_Autoloader::MAP_CONTROLLER, 
            Gdn_Autoloader::PRIORITY_TYPE_PREFER,
            Gdn_Autoloader::PRIORITY_PERSIST);
      
         $this->ControllerName = $Controller;
         $this->_ApplicationFolder = (is_null($Application) ? '' : $Application);
         $this->ControllerFolder = '';
         
         $Length = sizeof($Parts);
         if ($Length > $ControllerKey + 1)
            list($this->ControllerMethod, $this->_DeliveryMethod) = $this->_SplitDeliveryMethod($Parts[$ControllerKey + 1], FALSE);
   
         if ($Length > $ControllerKey + 2) {
            for ($i = $ControllerKey + 2; $i < $Length; ++$i) {
               if ($Parts[$i] != '')
                  $this->_ControllerMethodArgs[] = $Parts[$i];
            }
         }
         
         require_once($ControllerPath);
         
         throw new GdnDispatcherControllerFoundException();
      }
      
      return FALSE;
   }
   
   /**
    * An internal method used to map parts of the request to various properties
    * of this object that represent the controller, controller method, and
    * controller method arguments.
    *
    * @param array $Parts An array of parts of the request.
    * @param int $ControllerKey An integer representing the key of the controller in the $Parts array.
    */
   private function _MapParts($Parts, $ControllerKey) {
      $Length = count($Parts);
      if ($Length > $ControllerKey)
         $this->ControllerName = ucfirst(strtolower($Parts[$ControllerKey]));

      if ($Length > $ControllerKey + 1)
         list($this->ControllerMethod, $this->_DeliveryMethod) = $this->_SplitDeliveryMethod($Parts[$ControllerKey + 1]);

      if ($Length > $ControllerKey + 2) {
         for ($i = $ControllerKey + 2; $i < $Length; ++$i) {
            if ($Parts[$i] != '')
               $this->_ControllerMethodArgs[] = $Parts[$i];
         }
      }
   }

   protected function _ReflectControllerArgs($Controller) {
      // Reflect the controller arguments based on the get.
      if (count($Controller->Request->Get()) == 0)
         return;

      if (!method_exists($Controller, $this->ControllerMethod))
         return;

      $Meth = new ReflectionMethod($Controller, $this->ControllerMethod);
      $MethArgs = $Meth->getParameters();
      $Args = array();
      $Get = array_change_key_case($Controller->Request->Get());
      $MissingArgs = array();
      
      if (count($MethArgs) == 0) {
         // The method has no arguments so just pass all of the arguments in.
         return;
      }

      // Set all of the parameters.
      foreach ($MethArgs as $Index => $MethParam) {
         $ParamName = strtolower($MethParam->getName());

         if (isset($this->_ControllerMethodArgs[$Index]))
            $Args[] = $this->_ControllerMethodArgs[$Index];
         elseif (isset($Get[$ParamName]))
            $Args[] = $Get[$ParamName];
         elseif ($MethParam->isDefaultValueAvailable())
            $Args[] = $MethParam->getDefaultValue();
         else {
            $Args[] = NULL;
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
   protected function _SplitDeliveryMethod($Name, $AllowAll = FALSE) {
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

class GdnDispatcherControllerNotFoundException extends Exception {}
class GdnDispatcherControllerFoundException extends Exception {}