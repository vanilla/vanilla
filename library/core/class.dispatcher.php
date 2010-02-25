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
    * located in /conf/applications.php and included in /garden/bootstrap.php
    *
    * @var array
    */
   private $_EnabledApplicationFolders;

   /**
    * An associative array of ApplicationName => ApplicationFolder. This
    * property is filled by the applications array located in
    * /conf/applications.php and included in /garden/bootstrap.php
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
    * An array of routes and where they should be redirected to (assigned in
    * the main bootstrap).
    *
    * @var array
    */
   public $Routes;

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

   /**
    * Class constructor.
    */
   public function __construct() {
      $this->_EnabledApplicationFolders = array();
      $this->Request = '';
      $this->Routes = array();
      $this->_ApplicationFolder = '';
      $this->_AssetCollection = array();
      $this->_ControllerFolder = '';
      $this->_ControllerName = '';
      $this->_ControllerMethod = '';
      $this->_ControllerMethodArgs = array();
      $this->_PropertyCollection = array();
   }

   /**
    * Return the properly formatted controller class name.
    */
   public function ControllerName() {
      return $this->_ControllerName.'Controller';
   }

   /**
    * Analyzes the supplied query string and decides how to dispatch the
    * request.
    */
   public function Dispatch() {
      $this->_AnalyzeRequest();

      //echo '<br />App folder: '.$this->_ApplicationFolder;
      //echo '<br />Controller folder: '.$this->_ControllerFolder;
      //echo '<br />ControllerName: '.$this->_ControllerName;
      //echo '<br />ControllerMethod: '.$this->_ControllerMethod;

      $ControllerName = $this->ControllerName();
      if ($ControllerName != '' && class_exists($ControllerName)) {
         // Create it and call the appropriate method/action
         $Controller = new $ControllerName();

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

         // Pass in the routes
         $Controller->Routes = $this->Routes;

         // Set up a default controller method in case one isn't defined.
         $ControllerMethod = str_replace('_', '', $this->_ControllerMethod);
         $Controller->OriginalRequestMethod = $ControllerMethod;
         // Take enabled plugins into account, as well
         $PluginManager = Gdn::Factory('PluginManager');
         $PluginManagerHasReplacementMethod = $PluginManager->HasNewMethod($this->ControllerName(), $this->_ControllerMethod);
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
               
               $PluginManagerHasReplacementMethod = $PluginManager->HasNewMethod($this->ControllerName(), $this->_ControllerMethod);
            }
         }
         // Pass in the querystring values
         $Controller->ApplicationFolder = $this->_ApplicationFolder;
         $Controller->Application = $this->EnabledApplication();
         $Controller->ControllerFolder = $this->_ControllerFolder;
         $Controller->RequestMethod = $this->_ControllerMethod;
         $Controller->RequestArgs = $this->_ControllerMethodArgs;

         $Controller->Initialize();

         // Call the requested method on the controller - error out if not defined.
         if ($PluginManagerHasReplacementMethod || method_exists($Controller, $ControllerMethod)) {
            // call_user_func_array is too slow!!
            //call_user_func_array(array($Controller, $ControllerMethod), $this->_ControllerMethodArgs);
            
            if ($PluginManagerHasReplacementMethod) {
              $PluginManager->CallNewMethod($Controller, $Controller->ControllerName, $ControllerMethod);
            } else {
              
              $Args = $this->_ControllerMethodArgs;
              $Count = count($Args);
              
              if ($Count == 0) {
                 $Controller->$ControllerMethod();
              } else if ($Count == 1) {
                 $Controller->$ControllerMethod($Args[0]);
              } else if ($Count == 2) {
                 $Controller->$ControllerMethod($Args[0], $Args[1]);
              } else if ($Count == 3) {
                 $Controller->$ControllerMethod($Args[0], $Args[1], $Args[2]);
              } else if ($Count == 4) {
                 $Controller->$ControllerMethod($Args[0], $Args[1], $Args[2], $Args[3]);
              } else if ($Count == 5) {
                 $Controller->$ControllerMethod($Args[0], $Args[1], $Args[2], $Args[3], $Args[4]);
              } else if ($Count == 6) {
                 $Controller->$ControllerMethod($Args[0], $Args[1], $Args[2], $Args[3], $Args[4], $Args[5]);
              } else if ($Count == 7) {
                 $Controller->$ControllerMethod($Args[0], $Args[1], $Args[2], $Args[3], $Args[4], $Args[5], $Args[6]);
              } else if ($Count == 8) {
                 $Controller->$ControllerMethod($Args[0], $Args[1], $Args[2], $Args[3], $Args[4], $Args[5], $Args[6], $Args[7]);
              } else if ($Count == 9) {
                 $Controller->$ControllerMethod($Args[0], $Args[1], $Args[2], $Args[3], $Args[4], $Args[5], $Args[6], $Args[7], $Args[8]);
              } else {
                 $Controller->$ControllerMethod($Args[0], $Args[1], $Args[2], $Args[3], $Args[4], $Args[5], $Args[6], $Args[7], $Args[8], $Args[9]);
              }
            }
         } else {
            trigger_error(ErrorMessage('Controller method missing: '.$this->_ControllerName.'.'.$ControllerMethod.'();', 'Dispatcher', 'Dispatch'), E_USER_ERROR);
         }
      }

      // Destruct the db connection;
      $Database = Gdn::Database();
      if($Database != null)
         $Database->CloseConnection();
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

      $EnabledApplication = array_keys($this->_EnabledApplications, $ApplicationFolder);
      $EnabledApplication = count($EnabledApplication) > 0 ? $EnabledApplication[0] : '';
      $this->EventArguments['EnabledApplication'] = $EnabledApplication;
      $this->FireEvent('AfterEnabledApplication');
      return $EnabledApplication;
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
    * @todo $folderDepth needs a description.
    */
   protected function _AnalyzeRequest($FolderDepth = 2) {
      // Here are some examples of what this method could/would receive:
      // /application/controllergroup/controller/method/argn
      // /controllergroup/controller/method/argn
      // /application/controllergroup/controller/argn
      // /controllergroup/controller/argn
      // /controllergroup/controller
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

      // Retrieve and parse the request
      if ($this->Request == '') {
         $this->Request = Gdn_Url::Request();
         $Prefix = strtolower(substr($this->Request, 0, strpos($this->Request, '/')));
         switch ($Prefix) {
            case 'rss':
               $this->_SyndicationMethod = SYNDICATION_RSS;
               $this->Request = substr($this->Request, 4);
               break;
            case 'atom':
               $this->_SyndicationMethod = SYNDICATION_ATOM;
               $this->Request = substr($this->Request, 5);
               break;
            default:
               $this->_SyndicationMethod = SYNDICATION_NONE;
               break;
         }
      }

      if ($this->Request == '')
         $this->Request = $this->Routes['DefaultController'];

      // Check for re-routing
      // Is there a literal match?
      if (isset($this->Routes[$this->Request])) {
         $this->Request = $this->Routes[$this->Request];
      } else {
         // Check for other matching custom routes
         foreach ($this->Routes as $Route => $Destination) {
            // Check for wild-cards
            $Route = str_replace(
               array(':alphanum', ':num'),
               array('([0-9a-zA-Z-_]+)', '([0-9]+)'),
               $Route
            );

            // Check for a match
            if (preg_match('#^'.$Route.'$#', $this->Request)) {
               // Do we have a back-reference?
               if (strpos($Destination, '$') !== FALSE && strpos($Route, '(') !== FALSE)
                  $Destination = preg_replace('#^'.$Route.'$#', $Destination, $this->Request);

               $this->Request = $Destination;
            }
         }
      }

      $Parts = explode('/', $this->Request);
      $Length = count($Parts);
      if ($Length == 1 || $FolderDepth <= 0) {
         $FolderDepth = 0;
         $this->_ControllerName = $Parts[0];
         $this->_MapParts($Parts, 0);
         $this->_FetchController(TRUE); // Throw an error if this fails because there's nothing else to check
      } else if ($Length == 2) {
         // Force a depth of 1 because only one of the two url parts can be a folder.
         $FolderDepth = 1;
      }
      if ($FolderDepth == 2) {
         $this->_ApplicationFolder = $Parts[0];
         $this->_ControllerFolder = $Parts[1];
         $this->_MapParts($Parts, 2);

         if (!$this->_FetchController()) {
            // echo '<div>Failed. AppFolder: '.$this->_ApplicationFolder.'; Cont Folder: '.$this->_ControllerFolder.'; Cont: '.$this->_ControllerName.';</div>';
            $this->_AnalyzeRequest(1);
         }

      } else if ($FolderDepth == 1) {
         // Try the application folder first
         $Found = FALSE;
         if (in_array($Parts[0], $this->EnabledApplicationFolders())) {
            // Check to see if the first part is an application
            $this->_ApplicationFolder = $Parts[0];
            $this->_MapParts($Parts, 1);
            $Found = $this->_FetchController();
         }
         if (!$Found) {
            // echo '<div>Failed. AppFolder: '.$this->_ApplicationFolder.'; Cont Folder: '.$this->_ControllerFolder.'; Cont: '.$this->_ControllerName.';</div>';
            // Check to see if the first part is a controller folder
            $this->_ApplicationFolder = '';
            $this->_ControllerFolder = $Parts[0];
            $this->_MapParts($Parts, 1);
            if (!$this->_FetchController()) {
               // echo '<div>Failed. AppFolder: '.$this->_ApplicationFolder.'; Cont Folder: '.$this->_ControllerFolder.'; Cont: '.$this->_ControllerName.';</div>';
               $this->_AnalyzeRequest(0);
            }
         }
      }
   }

   /**
    * Searches through the /cache/controller_mappings.php file for the requested
    * controller. If it doesn't find it, it searches through the entire
    * application's folders for the requested controller. If it finds the
    * controller, it adds the mapping to /cache/controller_mappings.php so it
    * won't need to search again. If it doesn't find the controller file
    * anywhere, it throws a fatal error.
    *
    * @param boolean $ThrowErrorOnFailure
    * @todo $ThrowErrorOnFailure needs a description.
    */
   private function _FetchController($ThrowErrorOnFailure = FALSE) {
      $ControllerWhiteList = $this->EnabledApplicationFolders();
      // Don't include it if it's already been included
      if (!class_exists($this->ControllerName())) {
         $PathParts = array('controllers');
         if ($this->_ControllerFolder != '')
            $PathParts[] = $this->_ControllerFolder;

         $PathParts[] = strtolower($this->_ControllerName).'.php';
         $ControllerFileName = CombinePaths($PathParts);

         // Force the mapping to search in the app folder if it was in the request
         if ($this->_ApplicationFolder != '' && InArrayI($this->_ApplicationFolder, $ControllerWhiteList)) {
            // Limit the white list to the specified application folder
            $ControllerWhiteList = array($this->_ApplicationFolder);
         }

         $ControllerPath = Gdn_FileSystem::FindByMapping('controller_mappings.php', 'Controller', PATH_APPLICATIONS, $ControllerWhiteList, $ControllerFileName);
         if ($ControllerPath !== FALSE) {
            // Strip the "Application Folder" from the controller path (this is
            // used by the controller for various purposes. ie. knowing which
            // application to search in for a view file).
            $this->_ApplicationFolder = explode(DS, str_replace(PATH_APPLICATIONS . DS, '', $ControllerPath));
            $this->_ApplicationFolder = $this->_ApplicationFolder[0];

            // Load the application's master controller
            if (!class_exists($this->_ApplicationFolder.'Controller'))
               include(CombinePaths(array(PATH_APPLICATIONS, $this->_ApplicationFolder, 'controllers', 'appcontroller.php')));

            // Now load the library (no need to check for existence - couldn't
            // have made it here if it didn't exist).
            include($ControllerPath);
         }
      }
      if (!class_exists($this->ControllerName())) {
         if ($ThrowErrorOnFailure === TRUE) {
            if (ForceBool(Gdn::Config('Garden.Debug'))) {
               trigger_error(ErrorMessage('Controller not found: '.$this->ControllerName(), 'Dispatcher', '_FetchController'), E_USER_ERROR);
            } else {
               // Return a 404 message
               list($this->_ApplicationFolder, $this->_ControllerName, $this->_ControllerMethod) = explode('/', $this->Routes['Default404']);
               $ControllerFileName = CombinePaths(array('controllers', strtolower($this->_ControllerName) . '.php'));
               $ControllerPath = Gdn_FileSystem::FindByMapping('controller_mappings.php', 'Controller', PATH_APPLICATIONS, $ControllerWhiteList, $ControllerFileName);
               include(CombinePaths(array(PATH_APPLICATIONS, $this->_ApplicationFolder, 'controllers', 'appcontroller.php')));
               include($ControllerPath);
            }
         }
         return FALSE;
      } else {
         return TRUE;
      }
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
         $this->_ControllerName = $Parts[$ControllerKey];

      if ($Length > $ControllerKey + 1)
         $this->_ControllerMethod = $Parts[$ControllerKey + 1];

      if ($Length > $ControllerKey + 2) {
         for ($i = $ControllerKey + 2; $i < $Length; ++$i) {
            if ($Parts[$i] != '')
               $this->_ControllerMethodArgs[] = $Parts[$i];

         }
      }
   }
}