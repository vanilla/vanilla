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
 * Dispatcher handles all requests.
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
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
   private $_ControllerFolder;

   /**
    * The name of the controller to be dispatched.
    *
    * @var string
    */
   private $_ControllerName;

   /**
    * The method of the controller to be called.
    *
    * @var string
    */
   private $_ControllerMethod;

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
      $this->_ControllerFolder = '';
      $this->_ControllerName = '';
      $this->_ControllerMethod = '';
      $this->_ControllerMethodArgs = array();
      $this->_PropertyCollection = array();
   }
   
   public function Cleanup() {
      // Destruct the db connection;
      $Database = Gdn::Database();
      if($Database != null)
         $Database->CloseConnection();
   }


   /**
    * Return the properly formatted controller class name.
    */
   public function ControllerName() {
      return $this->_ControllerName.'Controller';
   }
   
   public function Application() {
      return $this->_ApplicationFolder;
   }
   
   public function Controller() {
      return $this->_ControllerName;
   }
   
   public function ControllerMethod() {
      return $this->_ControllerMethod;
   }
   
   public function ControllerArguments() {
      return $this->_ControllerMethodArgs;
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
      
      // By default, all requests can be blocked by UpdateMode/PrivateCommunity
      $CanBlock = self::BLOCK_ANY;
      
      try {
         $BlockExceptions = array(
             '/utility(\/.*)?$/'                   => self::BLOCK_NEVER,
             '/plugin(\/.*)?$/'                    => self::BLOCK_NEVER,
             '/entry(\/.*)?$/'                     => self::BLOCK_PERMISSION,
             '/user\/usernameavailable(\/.*)?$/'   => self::BLOCK_PERMISSION,
             '/user\/emailavailable(\/.*)?$/'      => self::BLOCK_PERMISSION,
             '/home\/termsofservice(\/.*)?$/'      => self::BLOCK_PERMISSION
         );
         
         $this->EventArguments['BlockExceptions'] = &$BlockExceptions;
         $this->FireEvent('BeforeBlockDetect');
         
         $PathRequest = Gdn::Request()->Path();
         foreach ($BlockExceptions as $BlockException => $BlockLevel)
            if (preg_match($BlockException, $PathRequest))
               throw new Exception("Block detected", $BlockLevel);
         
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
      if (Gdn::Config('Garden.UpdateMode', FALSE) && $CanBlock > self::BLOCK_NEVER)
         $Request->WithURI(Gdn::Router()->GetDestination('UpdateMode'));
      
      // If we're in updatemode and can block, redirect to signin
      if (C('Garden.PrivateCommunity') && $CanBlock > self::BLOCK_PERMISSION) {
         Redirect('/entry/signin?Target='.urlencode($this->Request));
         exit();
      }
      
      $this->FireEvent('BeforeDispatch');
      $this->AnalyzeRequest($Request);
      
      $ControllerName = $this->ControllerName();
      if ($ControllerName != '' && class_exists($ControllerName)) {
         // Create it and call the appropriate method/action
         $Controller = new $ControllerName();
         
         $this->EventArguments['Controller'] =& $Controller;

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
         foreach($this->_PropertyCollection as $Name => $Mixed) {
            $Controller->$Name = $Mixed;
         }

         // Set up a default controller method in case one isn't defined.
         $ControllerMethod = str_replace('_', '', $this->_ControllerMethod);
         $Controller->OriginalRequestMethod = $ControllerMethod;
         
         $this->FireEvent('AfterAnalyzeRequest');
         
         // Take enabled plugins into account, as well
         $PluginManagerHasReplacementMethod = Gdn::PluginManager()->HasNewMethod($this->ControllerName(), $this->_ControllerMethod);
         if (!$PluginManagerHasReplacementMethod && ($this->_ControllerMethod == '' || !method_exists($Controller, $ControllerMethod))) {
            // Check to see if there is an 'x' version of the method.
            if (method_exists($Controller, 'x'.$ControllerMethod)) {
               // $PluginManagerHasReplacementMethod = TRUE;
               $ControllerMethod = 'x'.$ControllerMethod;
            } else {
               if ($this->_ControllerMethod != '')
                  array_unshift($this->_ControllerMethodArgs, $this->_ControllerMethod);
               
               $this->_ControllerMethod = 'Index';
               $ControllerMethod = 'Index';
               
               $PluginManagerHasReplacementMethod = Gdn::PluginManager()->HasNewMethod($this->ControllerName(), $this->_ControllerMethod);
            }
         }
         
         // Pass in the querystring values
         $Controller->ApplicationFolder = $this->_ApplicationFolder;
         $Controller->Application = $this->EnabledApplication();
         $Controller->ControllerFolder = $this->_ControllerFolder;
         $Controller->RequestMethod = $this->_ControllerMethod;
         $Controller->RequestArgs = $this->_ControllerMethodArgs;
         $Controller->Request = $Request;
         $Controller->DeliveryType($Request->GetValue('DeliveryType', $this->_DeliveryType));
         $Controller->DeliveryMethod($Request->GetValue('DeliveryMethod', $this->_DeliveryMethod));

         // Set special controller method options for REST APIs.
         $this->_ReflectControllerArgs($Controller);
         Gdn::Controller($Controller);
         
         $this->FireEvent('BeforeControllerMethod');
         
         $Controller->Initialize();

         // Call the requested method on the controller - error out if not defined.
         if ($PluginManagerHasReplacementMethod || method_exists($Controller, $ControllerMethod)) {
            // call_user_func_array is too slow!!
            //call_user_func_array(array($Controller, $ControllerMethod), $this->_ControllerMethodArgs);
            
            if ($PluginManagerHasReplacementMethod) {
              try {
                 Gdn::PluginManager()->CallNewMethod($Controller, $Controller->ControllerName, $ControllerMethod);
              } catch (Exception $Ex) {
                 $Controller->RenderException($Ex);
              }
            } else { 
              $Args = $this->_ControllerMethodArgs;
              $Count = count($Args);

              try {
                 call_user_func_array(array($Controller, $ControllerMethod), $Args);
              } catch (Exception $Ex) {
                 $Controller->RenderException($Ex);
                 exit();
              }
            }
         } else {
            Gdn::Request()->WithRoute('Default404');
            return $this->Dispatch();
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
   }

   /**
    * Allows the passing of any variable to the controller as a property.
    *
    * @param string $Name The name of the property to assign the variable to.
    * @param mixed $Mixed The variable to be passed as a property of the controller.
    */
   public function PassProperty($Name, $Mixed) {
      $this->_PropertyCollection[$Name] = $Mixed;
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
      $this->_ControllerFolder = '';
      $this->_ControllerName = '';
      $this->_ControllerMethod = 'index';
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
      
         // 1] if the 1st argument is a valid application, check if it has a controller matching the 2nd argument
         if (in_array($Parts[0], $this->EnabledApplicationFolders()))
            $this->FindController(1, $Parts);
         
         // 2] if no match, see if the first argument is a controller
         $this->FindController(0, $Parts);
         
         throw new GdnDispatcherControllerNotFoundException();
         
      } catch (GdnDispatcherControllerFoundException $e) {

         // Success!
         if (in_array($this->_DeliveryMethod, array(DELIVERY_METHOD_JSON, DELIVERY_METHOD_XML)))
            $this->_DeliveryType = DELIVERY_TYPE_DATA;
         
         return TRUE;
      } catch (GdnDispatcherControllerNotFoundException $e) {
         header("HTTP/1.1 404 Not Found" );
         $Request->WithRoute('Default404');
         return $this->AnalyzeRequest($Request);
      }
   }
   
   protected function FindController($ControllerKey, $Parts) {
      
      $Application = GetValue($ControllerKey-1, $Parts, NULL);
      $Controller = GetValue($ControllerKey, $Parts, NULL);
      $Controller = ucfirst(strtolower($Controller));

      // Check for a file extension on the controller.
      $Ext = strrchr($Controller, '.');
      if ($Ext) {
         $Controller = substr($Controller, 0, -strlen($Ext));
         $Ext = strtoupper(trim($Ext, '.'));
         if (in_array($Ext, array(DELIVERY_METHOD_JSON, DELIVERY_METHOD_XHTML, DELIVERY_METHOD_XML))) {
            $this->_DeliveryMethod = strtoupper($Ext);
         }
      }
      
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
      
      if ($ControllerPath !== FALSE) {
         
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
      
         $this->_ControllerName = $Controller;
         $this->_ApplicationFolder = (is_null($Application) ? '' : $Application);
         $this->_ControllerFolder = '';
         
         $Length = sizeof($Parts);
         if ($Length > $ControllerKey + 1)
            list($this->_ControllerMethod, $this->_DeliveryMethod) = $this->_SplitDeliveryMethod($Parts[$ControllerKey + 1]);
   
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
         $this->_ControllerName = ucfirst(strtolower($Parts[$ControllerKey]));

      if ($Length > $ControllerKey + 1)
         list($this->_ControllerMethod, $this->_DeliveryMethod) = $this->_SplitDeliveryMethod($Parts[$ControllerKey + 1]);

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

      if (!method_exists($Controller, $this->_ControllerMethod))
         return;

      $Meth = new ReflectionMethod($Controller, $this->_ControllerMethod);
      $MethArgs = $Meth->getParameters();
      $Args = array();
      $Get = array_change_key_case($Controller->Request->Get());
      $MissingArgs = array();

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

   protected function _SplitDeliveryMethod($Name) {
      $Parts = explode('.', $Name, 2);
      if (count($Parts) >= 2) {
         if (in_array(strtoupper($Parts[1]), array(DELIVERY_METHOD_JSON, DELIVERY_METHOD_XHTML, DELIVERY_METHOD_XML))) {
            return array($Parts[0], strtoupper($Parts[1]));
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