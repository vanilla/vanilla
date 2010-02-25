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
 * Garden.Core
 */

/**
 * A singleton class used to identify extensions, register them in a central
 * location, and instantiate/call them when necessary.
 */
class Gdn_PluginManager {
   
   const ACTION_ENABLE  = 1;
   const ACTION_DISABLE = 2;
   const ACTION_REMOVE  = 3;
   
   /**
    * An associative array of arrays containing information about each
    * enabled plugin. This value is assigned in the garden bootstrap.php.
    */
   public $EnabledPlugins = array();

   /**
    * An associative array of EventHandlerName => PluginName pairs.
    */
   private $_EventHandlerCollection = array();

   /**
    * An associative array of MethodOverrideName => PluginName pairs.
    */
   private $_MethodOverrideCollection = array();
   
   /**
    * An associative array of NewMethodName => PluginName pairs.
    */
   private $_NewMethodCollection = array();
   
   /**
    * An array of available plugins. Never access this directly, instead use
    * $this->AvailablePlugins();
    */
   private $_AvailablePlugins = NULL;
   
   /**
    * Register all enabled plugins
    *
    * Examines all declared classes, identifying which ones implement
    * Gdn_IPlugin and registers all of their event handlers and method
    * overrides. It recognizes them because Handlers end with _Handler,
    * _Before, and _After and overrides end with "_Override". They are prefixed
    * with the name of the class and method (or event) to be handled or
    * overridden. For example:
    *  class MyPlugin implements Gdn_IPlugin {
    *   public function MyController_SignIn_After($Sender) {
    *      // Do something neato
    *   }
    *   public function Url_AppRoot_Override($WithDomain) {
    *      return "MyCustomAppRoot!";
    *   }
    *  }
    */
   public function RegisterPlugins() {
      // Loop through all declared classes looking for ones that implement iPlugin.
      // print_r(get_declared_classes());
      foreach(get_declared_classes() as $ClassName) {
         // Only implement the plugin if it implements the Gdn_IPlugin interface and
         // it has it's properties defined in $this->EnabledPlugins.
         if (in_array('Gdn_IPlugin', class_implements($ClassName))) {
            $ClassMethods = get_class_methods($ClassName);
            foreach ($ClassMethods as $Method) {
               $MethodName = strtolower($Method);
               // Loop through their individual methods looking for event handlers and method overrides.
               if (isset($MethodName[9])) {
                  if (substr($MethodName, -8) == '_handler' || substr($MethodName, -7) == '_before' || substr($MethodName, -6) == '_after') {
                     $this->RegisterHandler($ClassName, $MethodName);
                  } else if (substr($MethodName, -9) == '_override') {
                     $this->RegisterOverride($ClassName, $MethodName);
                  } else if (substr($MethodName, -7) == '_create') {
                     $this->RegisterNewMethod($ClassName, $MethodName);
                  }
               }
            }
         }
      }
   }
   
   /**
    * Registers a plugin method name as a handler.
    * @param string $HandlerClassName The name of the plugin class that will handle the event.
    * @param string $HandlerMethodName The name of the plugin method being registered to handle the event.
    * @param string $EventClassName The name of the class that will fire the event.
    * @param string $EventName The name of the event that will fire.
    * @param string $EventHandlerType The type of event handler.
    */
   public function RegisterHandler($HandlerClassName, $HandlerMethodName, $EventClassName = '', $EventName = '', $EventHandlerType = '') {
      $HandlerKey = $HandlerClassName.'.'.$HandlerMethodName;
      $EventKey = strtolower($EventClassName == '' ? $HandlerMethodName : $EventClassName.'_'.$EventName.'_'.$EventHandlerType);

      // Create a new array of handler class names if it doesn't exist yet.
      if (array_key_exists($EventKey, $this->_EventHandlerCollection) === FALSE)
         $this->_EventHandlerCollection[$EventKey] = array();

      // Specify this class as a handler for this method if it hasn't been done yet.
      if (in_array($HandlerKey, $this->_EventHandlerCollection[$EventKey]) === FALSE)
         $this->_EventHandlerCollection[$EventKey][] = $HandlerKey;
   }
   
   /**
    * Registers a plugin override method.
    * @param string $OverrideClassName The name of the plugin class that will override the existing method.
    * @param string $OverrideMethodName The name of the plugin method being registered to override the existing method.
    * @param string $EventClassName The name of the class that will fire the event.
    * @param string $EventName The name of the event that will fire.
    */
   public function RegisterOverride($OverrideClassName, $OverrideMethodName, $EventClassName = '', $EventName = '') {
      $OverrideKey = $OverrideClassName.'.'.$OverrideMethodName;
      $EventKey = strtolower($EventClassName == '' ? $OverrideMethodName : $EventClassName.'_'.$EventName.'_Override');

      // Throw an error if this method has already been overridden.
      if (array_key_exists($EventKey, $this->_MethodOverrideCollection) === TRUE)
         trigger_error(ErrorMessage('Any object method can only be overridden by a single plugin. The "'.$EventKey.'" override has already been assigned by the "'.$this->_MethodOverrideCollection[$EventKey].'" plugin. It cannot also be overridden by the "'.$OverrideClassName.'" plugin.', 'PluginManager', 'RegisterOverride'), E_USER_ERROR);

      // Otherwise, specify this class as the source for the override.
      $this->_MethodOverrideCollection[$EventKey] = $OverrideKey;
   }
   
   /**
    * Registers a plugin new method.
    * @param string $NewMethodClassName The name of the plugin class that will add a new method.
    * @param string $NewMethodName The name of the plugin method being added.
    * @param string $EventClassName The name of the class that will fire the event.
    * @param string $EventName The name of the event that will fire.
    */
   public function RegisterNewMethod($NewMethodClassName, $NewMethodName, $EventClassName = '', $EventName = '') {
      $NewMethodKey = $NewMethodClassName.'.'.$NewMethodName;
      $EventKey = strtolower($EventClassName == '' ? $NewMethodName : $EventClassName.'_'.$EventName.'_Create');

      // Throw an error if this method has already been created.
      if (array_key_exists($EventKey, $this->_NewMethodCollection) === TRUE)
         trigger_error(ErrorMessage('New object methods must be unique. The new "'.$EventKey.'" method has already been assigned by the "'.$this->_NewMethodCollection[$EventKey].'" plugin. It cannot also be assigned by the "'.$NewMethodClassName.'" plugin.', 'PluginManager', 'RegisterNewMethod'), E_USER_ERROR);

      // Otherwise, specify this class as the source for the new method.
      $this->_NewMethodCollection[$EventKey] = $NewMethodKey;
   }
   
   /**
    * Transfer control to the plugins
    *
    * Looks through $this->_EventHandlerCollection for matching event
    * signatures to handle. If it finds any, it executes them in the order it
    * found them. It instantiates any plugins and adds them as properties to
    * this class (unless they were previously instantiated), and then calls
    * the handler in question.
    *
    * @param object The object that fired the event being handled.
    * @param string The name of the class that fired the event being handled.
    * @param string The name of the event being fired.
    * @param string The type of handler being fired (Handler, Before, After).
    * @return bool True if an event was executed.
    */
   public function CallEventHandlers(&$Sender, $EventClassName, $EventName, $EventHandlerType = 'Handler') {
      $Return = FALSE;
      
      // Look through $this->_EventHandlerCollection for relevant handlers
      if ($this->CallEventHandler($Sender, $EventClassName, $EventName, $EventHandlerType))
         $Return = TRUE;

      // Look for "Base" (aka any class that has $EventName)
      if ($this->CallEventHandler($Sender, 'Base', $EventName, $EventHandlerType))
         $Return = TRUE;
         
      return $Return;
   }
   
   public function CallEventHandler(&$Sender, $EventClassName, $EventName, $EventHandlerType) {
      $Return = FALSE;
      $EventKey = strtolower($EventClassName.'_'.$EventName.'_'.$EventHandlerType);
      if (array_key_exists($EventKey, $this->_EventHandlerCollection)) {
         // Loop through the handlers and execute them
         foreach ($this->_EventHandlerCollection[$EventKey] as $PluginKey) {
            $PluginKeyParts = explode('.', $PluginKey);
            if (count($PluginKeyParts) == 2) {
               list($PluginClassName, $PluginEventHandlerName) = $PluginKeyParts;
               if (property_exists($this, $PluginClassName) === FALSE)
                  $this->$PluginClassName = new $PluginClassName();
                  
               if (array_key_exists($EventKey, $Sender->Returns) === FALSE || is_array($Sender->Returns[$EventKey]) === FALSE)
                  $Sender->Returns[$EventKey] = array();
               
               $Sender->Returns[$EventKey][$PluginKey] = $this->$PluginClassName->$PluginEventHandlerName($Sender, $Sender->EventArguments);
               $Return = TRUE;
            }
         }
      }
      return $Return;
   }
   
   /**
    * Looks through $this->_MethodOverrideCollection for a matching method
    * signature to override. It instantiates any plugins and adds them as
    * properties to this class (unless they were previously instantiated), then
    * calls the method in question.
    *
    * @param object The object being worked on.
    * @param string The name of the class that called the method being overridden.
    * @param string The name of the method that is being overridden.
    * @return mixed Return value of overridden method.
    */
   public function CallMethodOverride(&$Sender, $ClassName, $MethodName) {
      $EventKey = strtolower($ClassName.'_'.$MethodName.'_Override');
      $OverrideKey = ArrayValue($EventKey, $this->_MethodOverrideCollection, '');
      $OverrideKeyParts = explode('.', $OverrideKey);
      if (count($PluginKeyParts) != 2)
         return FALSE;
      
      list($OverrideClassName, $OverrideMethodName) = $OverrideKeyParts;
      if (property_exists($this, $OverrideClassName) === FALSE)
         $this->$OverrideClassName = new $OverrideClassName($Sender);
         
      return $this->$OverrideClassName->$OverrideMethodName($Sender, $Sender->EventArguments);
   }
   
   /**
    * Checks to see if there are any plugins that override the method being
    * executed.
    *
    * @param string The name of the class that called the method being overridden.
    * @param string The name of the method that is being overridden.
    * @return bool True if an override exists.
    */
   public function HasMethodOverride($ClassName, $MethodName) {
      return array_key_exists(strtolower($ClassName.'_'.$MethodName.'_Override'), $this->_MethodOverrideCollection) ? TRUE : FALSE;
   }
   
   /**
    * Looks through $this->_NewMethodCollection for a matching method signature
    * to call. It instantiates any plugins and adds them as properties to this
    * class (unless they were previously instantiated), then calls the method
    * in question.
    *
    * @param object The object being worked on.
    * @param string The name of the class that called the method being created.
    * @param string The name of the method that is being created.
    * @return mixed Return value of new method.
    */
   public function CallNewMethod(&$Sender, $ClassName, $MethodName) {
      $Return = FALSE;
      $EventKey = strtolower($ClassName.'_'.$MethodName.'_Create');
      $NewMethodKey = ArrayValue($EventKey, $this->_NewMethodCollection, '');
      $NewMethodKeyParts = explode('.', $NewMethodKey);
      if (count($NewMethodKeyParts) != 2)
         return FALSE;

      list($NewMethodClassName, $NewMethodName) = $NewMethodKeyParts;

      if (property_exists($this, $NewMethodClassName) === FALSE)
         $this->$NewMethodClassName = new $NewMethodClassName($Sender);
         
      return $this->$NewMethodClassName->$NewMethodName($Sender, $Sender->RequestArgs);
   }
   
   /**
    * Checks to see if there are any plugins that create the method being
    * executed.
    *
    * @param string The name of the class that called the method being created.
    * @param string The name of the method that is being created.
    * @return True if method exists.
    */
   public function HasNewMethod($ClassName, $MethodName) {
      return array_key_exists(strtolower($ClassName.'_'.$MethodName.'_Create'), $this->_NewMethodCollection) ? TRUE : FALSE;
   }
   
   /**
    * Looks through the plugins directory for valid plugins and returns them
    * as an associative array of "PluginName" => "Plugin Info Array". It also
    * adds "Folder", and "ClassName" definitions to the Plugin Info Array for
    * each plugin.
    */
   public function AvailablePlugins() {
      if (!is_array($this->_AvailablePlugins)) {
         $Info = array();
         if ($FolderHandle = opendir(PATH_PLUGINS)) {
            if ($FolderHandle === FALSE)
               return $Info;
            
            // Loop through subfolders (ie. the actual plugin folders)
            while (($Item = readdir($FolderHandle)) !== FALSE) {
               if(in_array($Item, array('.', '..'))) {
                  continue;
               }
               
               $PluginPaths = SafeGlob(PATH_PLUGINS . DS . $Item . DS . '*plugin.php');
               $PluginPaths[] = PATH_PLUGINS . DS . $Item . DS . 'default.php';
               
               foreach($PluginPaths as $i => $PluginFile) {
                  if (file_exists($PluginFile)) {
                     // Find the $PluginInfo array
                     $Lines = file($PluginFile);
                     $InfoBuffer = FALSE;
                     $ClassBuffer = FALSE;
                     $ClassName = '';
                     $PluginInfoString = '';
                     $PluginInfo = FALSE;
                     foreach ($Lines as $Line) {
                        if ($InfoBuffer && substr(trim($Line), -2) == ');') {
                           $PluginInfoString .= $Line;
                           $ClassBuffer = TRUE;
                           $InfoBuffer = FALSE;
                        }
                        
                        if (substr(trim($Line), 0, 11) == '$PluginInfo')
                           $InfoBuffer = TRUE;
                           
                        if ($InfoBuffer)
                           $PluginInfoString .= $Line;
                           
                        if ($ClassBuffer && strtolower(substr(trim($Line), 0, 6)) == 'class ') {
                           $Parts = explode(' ', $Line);
                           if (count($Parts) > 2)
                              $ClassName = $Parts[1];
                              
                           break;
                        }
                        
                     }
                     unset($Lines);
                     if ($PluginInfoString != '')
                        @eval($PluginInfoString);
                        
                     // Define the folder name and assign the class name for the newly added item
                     if (is_array($PluginInfo)) {
                        $Info = array_merge($Info, $PluginInfo);
                        foreach ($Info as $PluginName => $Plugin) {
                           if (array_key_exists('Folder', $Info[$PluginName]) === FALSE) {
                              $Info[$PluginName]['Folder'] = $Item;
                              $Info[$PluginName]['ClassName'] = $ClassName;
                           }
                        }
                     }
                  }
               }
            }
            closedir($FolderHandle);
         }
         $this->_AvailablePlugins = $Info;
      }
      return $this->_AvailablePlugins;
   }
   
   public function EnabledPluginFolders() {
      $EnabledPlugins = Gdn::Config('EnabledPlugins', array());
      return array_values($EnabledPlugins);
   }
   
   public function EnablePlugin($PluginName, $Validation, $Setup = FALSE) {
      // 1. Make sure that the plugin's requirements are met
      // Required Plugins
      $AvailablePlugins = $this->AvailablePlugins();
      $RequiredPlugins = ArrayValue('RequiredPlugins', ArrayValue($PluginName, $AvailablePlugins, array()), FALSE);
      CheckRequirements($PluginName, $RequiredPlugins, $this->EnabledPlugins, 'plugin');
      
      // Required Themes
      $ThemeManager = new Gdn_ThemeManager();
      $EnabledThemes = $ThemeManager->EnabledThemeInfo();
      $RequiredThemes = ArrayValue('RequiredTheme', ArrayValue($PluginName, $AvailablePlugins, array()), FALSE);
      CheckRequirements($PluginName, $RequiredThemes, $EnabledThemes, 'theme');
      
      // Required Applications
      $ApplicationManager = new Gdn_ApplicationManager();
      $EnabledApplications = $ApplicationManager->EnabledApplications();
      $RequiredApplications = ArrayValue('RequiredApplications', ArrayValue($PluginName, $AvailablePlugins, array()), FALSE);
      CheckRequirements($PluginName, $RequiredApplications, $EnabledApplications, 'application');

      // 2. Include the plugin, instantiate it, and call its setup method
      $PluginInfo = ArrayValue($PluginName, $AvailablePlugins, FALSE);
      $PluginFolder = ArrayValue('Folder', $PluginInfo, FALSE);
      if ($PluginFolder == '')
         throw new Exception(Gdn::Translate('The plugin folder was not properly defined.'));
      
      $this->_PluginHook($PluginName, self::ACTION_ENABLE, $Setup);
      
      // 3. If setup succeeded, register any specified permissions
      $PermissionName = ArrayValue('RegisterPermissions', $PluginInfo, FALSE);
      if ($PermissionName != FALSE) {
         $PermissionModel = Gdn::PermissionModel();
         $PermissionModel->Define($PermissionName);
      }

      if (is_object($Validation) && count($Validation->Results()) > 0)
         return FALSE;

      // 4. If everything succeeded, add the plugin to the
      // $EnabledPlugins array in conf/plugins.php
      // $EnabledPlugins['PluginClassName'] = 'Plugin Folder Name';
      SaveToConfig('EnabledPlugins'.'.'.$PluginName, $PluginFolder);
      
      $ApplicationManager = new Gdn_ApplicationManager();
      $Locale = Gdn::Locale();
      $Locale->Set($Locale->Current(), $ApplicationManager->EnabledApplicationFolders(), $this->EnabledPluginFolders(), TRUE);
      return TRUE;
   }
   
   public function DisablePlugin($PluginName) {
      // 1. Check to make sure that no other enabled plugins rely on this one
      // Get all available plugins and compile their requirements
      foreach ($this->EnabledPlugins as $CheckingName => $CheckingInfo) {
         $RequiredPlugins = ArrayValue('RequiredPlugins', $CheckingInfo, FALSE);
         if (is_array($RequiredPlugins) && array_key_exists($PluginName, $RequiredPlugins) === TRUE) {
            throw new Exception(sprintf(Gdn::Translate('You cannot disable the %1$s plugin because the %2$s plugin requires it in order to function.'), $PluginName, $CheckingName));
         }
      }
      
      // 2. Perform necessary hook action
      $this->_PluginHook($PluginName, self::ACTION_DISABLE, TRUE);
      
      // 3. Disable it
      RemoveFromConfig('EnabledPlugins'.'.'.$PluginName);
      unset($this->EnabledPlugins[$PluginName]);
      
      // Redefine the locale manager's settings $Locale->Set($CurrentLocale, $EnabledApps, $EnabledPlugins, TRUE);
      $ApplicationManager = new Gdn_ApplicationManager();
      $Locale = Gdn::Locale();
      $Locale->Set($Locale->Current(), $ApplicationManager->EnabledApplicationFolders(), $this->EnabledPluginFolders(), TRUE);
   }
   
   /**
    * Remove the plugin.
    *
    * @param string $PluginName 
    * @return void
    */
   public function RemovePlugin($PluginName) {
      $this->_PluginHook($PluginName, self::ACTION_REMOVE, TRUE);
   }
   
   /**
    * Remove the plugin folder.
    *
    * @param string $PluginFolder 
    * @return void
    */
   private function _RemovePluginFolder($PluginFolder) {
      Gdn_FileSystem::RemoveFolder(PATH_PLUGINS . DS . $PluginFolder);
   }
   
   /**
    * Includes all of the plugin files for enabled plugins.
    *
    * Files are included in from the roots of each plugin directory of they have the following names.
    * - default.php
    * - *plugin.php
    *
    * @param array $EnabledPlugins An array of plugins that should be included.
    * If this argument is null then all enabled plugins will be included.
    * @return array The plugin info array for all included plugins.
    */
   public function IncludePlugins($EnabledPlugins = NULL) {
      // Include all of the plugins.
      if(is_null($EnabledPlugins))
         $EnabledPlugins = Gdn::Config('EnabledPlugins', array());
      
      // Get a list of files to include.
      $Paths = array();
      foreach ($EnabledPlugins as $PluginName => $PluginFolder) {
         $Paths[] = PATH_PLUGINS . DS . $PluginFolder . DS . 'default.php';
         $Paths = array_merge($Paths, SafeGlob(PATH_PLUGINS . DS . $PluginFolder . DS . '*plugin.php'));
      }
      if (!is_array($Paths))
         $Paths = array();
      
      // Include all of the paths.
      $PluginInfo = array();
      
      $PluginManager = &$this;
      $Paths = (array)$Paths;
      foreach($Paths as $Path) {
         if(file_exists($Path))
            include($Path);
      }
      
      return $PluginInfo;
   }
   
   /**
    * Hooks to the varies actions, i.e. enable, disable and remove.
    *
    * @param string $PluginName 
    * @param string $ForAction which action to hook it to, i.e. enable, disable or remove
    * @param boolean $Callback whether to perform the hook method
    * @return void
    */
   private function _PluginHook($PluginName, $ForAction, $Callback = FALSE) {
      
      switch ($ForAction) {
         case self::ACTION_ENABLE:  $HookMethod = 'Setup'; break;
         case self::ACTION_DISABLE: $HookMethod = 'OnDisable'; break;
         case self::ACTION_REMOVE:  $HookMethod = 'CleanUp'; break;
      }
      
      $PluginInfo      = ArrayValue($PluginName, $this->AvailablePlugins(), FALSE);
      $PluginFolder    = ArrayValue('Folder', $PluginInfo, FALSE);
      $PluginClassName = ArrayValue('ClassName', $PluginInfo, FALSE);
      
      if ($ForAction === self::ACTION_REMOVE) {
         $this->_RemovePluginFolder($PluginFolder);
      }
      
      if ($PluginFolder !== FALSE && $PluginClassName !== FALSE && class_exists($PluginClassName) === FALSE) {
         if ($ForAction !== self::ACTION_DISABLE) {
            $this->IncludePlugins(array($PluginName => $PluginFolder));
         }
         
         $this->_PluginCallbackExecution($PluginClassName, $HookMethod);
      } elseif ($Callback === TRUE) {
         $this->_PluginCallbackExecution($PluginClassName, $HookMethod);
      }
   }
   
   /**
    * Executes the plugin hook action if it exists.
    *
    * @param string $PluginClassName 
    * @param string $HookMethod 
    * @return void
    */
   private function _PluginCallbackExecution($PluginClassName, $HookMethod) {
      if (class_exists($PluginClassName)) {
         $Plugin = new $PluginClassName();
         if (method_exists($PluginClassName, $HookMethod)) {
            $Plugin->$HookMethod();
         }
      }
   }
}
