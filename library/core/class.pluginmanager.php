<?php if (!defined('APPLICATION')) exit();

/**
 * Plugin Manager 
 * 
 * A singleton class used to identify extensions, register them in a central
 * location, and instantiate/call them when necessary.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_PluginManager extends Gdn_Pluggable {

   const ACTION_ENABLE  = 1;
   const ACTION_DISABLE = 2;
   //const ACTION_REMOVE  = 3;

   const ACCESS_CLASSNAME = 'classname';
   const ACCESS_PLUGINNAME = 'pluginname';

   /**
    * An array of available plugins. Never access this directly, instead use
    * $this->AvailablePlugins();
    */
   protected $PluginCache = NULL;
   protected $PluginsByClass = NULL;
   protected $PluginFoldersByPath = NULL;
   
   /**
    * A simple list of enabled plugins
    */
   protected $EnabledPlugins = NULL;

   /**
    * An array of search paths for plugins and their files
    */
   protected $PluginSearchPaths = NULL;
   protected $AlternatePluginSearchPaths = NULL;

   /**
    * A simple list of plugins that have already been registered
    *
    */
   protected $RegisteredPlugins = array();

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
    * An array of the instances of plugins
    */
   protected $Instances = array();

   protected $Started = FALSE;
   
   /**
    *
    * @var bool Whether or not to trace some event information
    * @since 2.1 
    */
   public $Trace = FALSE;
   
   /**
    * Whether to use APC for plugin cache storage
    * @var type 
    */
   protected $Apc = FALSE;

   public function __construct() {
      parent::__construct();
   }

   /**
    * Sets up the plugin framework
    *
    * This method indexes all available plugins and extracts their information.
    * It then determines which plugins have been enabled, and includes them.
    * Finally, it parses all plugin files and extracts their events and plugged
    * methods.
    */
   public function Start($Force = FALSE) {
      
      if (function_exists('apc_fetch') && C('Garden.Apc', FALSE))
         $this->Apc = TRUE;

      // Build list of all available plugins
      $this->AvailablePlugins($Force);

      // Build list of all enabled plugins
      $this->EnabledPlugins($Force);

      // Include enabled plugin source files
      $this->IncludePlugins();

      // Register hooked methods
      $this->RegisterPlugins();
      
      $this->Started = TRUE;
      $this->FireEvent('AfterStart');
   }

   public function Started() {
      return (bool)$this->Started;
   }

   public function AvailablePlugins($Force = FALSE) {

      if (is_null($this->PluginCache) || is_null($this->PluginsByClass) || $Force) {

         $this->PluginCache = array();
         $this->PluginsByClass = array();
         $this->PluginFoldersByPath = array();

         // Check cache freshness
         foreach ($this->SearchPaths() as $SearchPath => $Trash) {
            unset($SearchPathCache);

            // Check Cache
            $SearchPathCacheKey = 'Garden.Plugins.PathCache.'.$SearchPath;
            if ($this->Apc) {
               $SearchPathCache = apc_fetch($SearchPathCacheKey);
            } else {
               $SearchPathCache = Gdn::Cache()->Get($SearchPathCacheKey, array(Gdn_Cache::FEATURE_NOPREFIX => TRUE));
            }

            $CacheHit = ($SearchPathCache !== Gdn_Cache::CACHEOP_FAILURE);
            $CacheIntegrityCheck = FALSE;
            if ($CacheHit && is_array($SearchPathCache))
               $CacheIntegrityCheck = (sizeof(array_intersect(array_keys($SearchPathCache), array('CacheIntegrityHash', 'PluginInfo', 'ClassInfo'))) == 3);

            if (!$CacheIntegrityCheck) {
               $SearchPathCache = array(
                  'CacheIntegrityHash'    => NULL,
                  'PluginInfo'            => array(),
                  'ClassInfo'             => array()
               );
            }

            $CachePluginInfo = &$SearchPathCache['PluginInfo'];
            if (!is_array($CachePluginInfo))
               $CachePluginInfo = array();

            $CacheClassInfo = &$SearchPathCache['ClassInfo'];
            if (!is_array($CacheClassInfo))
               $CacheClassInfo = array();

            $PathListing = scandir($SearchPath, 0);
            sort($PathListing);

            $PathIntegrityHash = md5(serialize($PathListing));
            $CacheIntegrityHash = GetValue('CacheIntegrityHash',$SearchPathCache);
            if ($CacheIntegrityHash != $PathIntegrityHash) {
               Trace('Need to re-index plugin cache');
               // Need to re-index this folder
               
               // Since we're re-indexing this folder, need to unset all the plugins it was previously responsible for 
               // so that the merge below does what was intended
               $this->PluginCache = array_diff_key($this->PluginCache, $CachePluginInfo);
               $this->PluginsByClass = array_diff_key($this->PluginsByClass, $CacheClassInfo);
               
               $CachePluginInfo = array();
               $CacheClassInfo = array();
               $PathIntegrityHash = $this->IndexSearchPath($SearchPath, $CachePluginInfo, $CacheClassInfo, $PathListing);
               if ($PathIntegrityHash === FALSE)
                  continue;

               $SearchPathCache['CacheIntegrityHash'] = $PathIntegrityHash;
               if ($this->Apc) {
                  apc_store($SearchPathCacheKey, $SearchPathCache);
               } else {
                  Gdn::Cache()->Store($SearchPathCacheKey, $SearchPathCache, array(Gdn_Cache::FEATURE_NOPREFIX => TRUE));
               }
            }

            $this->PluginCache = array_merge($this->PluginCache, $CachePluginInfo);
            $this->PluginsByClass = array_merge($this->PluginsByClass, $CacheClassInfo);
            $this->PluginFoldersByPath[$SearchPath] = array_keys($CachePluginInfo);
         }
      }

      return $this->PluginCache;
   }
   
   public function ForceAutoloaderIndex() {
      $AutoloaderMap = Gdn_Autoloader::GetMap(Gdn_Autoloader::MAP_LIBRARY, Gdn_Autoloader::CONTEXT_PLUGIN);
      if (!$AutoloaderMap) return;
      
      $ExtraPaths = array();
      foreach ($this->AvailablePlugins() as $AvailablePlugin)
         $ExtraPaths[] = array(
            'path'  => GetValue('RealRoot', $AvailablePlugin),
            'topic' => strtolower(GetValue('Folder', $AvailablePlugin))
         );
      
      $AutoloaderMap->Index($ExtraPaths);
   }
   
   public function ClearPluginCache($SearchPaths = NULL) {
      if (!is_null($SearchPaths)) {
         if (!is_array($SearchPaths))
            $SearchPaths = array($SearchPaths);
      } else {
         $SearchPaths = $this->SearchPaths();
      }
      
      foreach ($SearchPaths as $SearchPath => $SearchPathName) {
         $SearchPathCacheKey = "Garden.Plugins.PathCache.{$SearchPath}";
         if ($this->Apc) {
            apc_delete($SearchPathCacheKey);
         } else {
            Gdn::Cache()->Remove($SearchPathCacheKey, array(Gdn_Cache::FEATURE_NOPREFIX => TRUE));
         }
      }
   }

   public function EnabledPlugins($Force = FALSE) {

      if (!is_array($this->EnabledPlugins) || $Force) {

         // Make sure all known plugins are cached
         $this->AvailablePlugins($Force);

         $this->EnabledPlugins = array();
         $EnabledPlugins = C('EnabledPlugins', array());

         foreach ($EnabledPlugins as $PluginName => $PluginStatus) {
            // Plugins can be explicitly disabled
            if ($PluginStatus === FALSE) continue;

            // Check that the plugin is in AvailablePlugins...
            $Plugin = $this->GetPluginInfo($PluginName);
            if ($Plugin === FALSE) continue;

            $this->EnabledPlugins[$PluginName] = TRUE;
         }
      }

      return array_intersect_key($this->AvailablePlugins(), $this->EnabledPlugins);
   }

   /**
    * Includes all of the plugin files for enabled plugins.
    *
    * Files are included in from the roots of each plugin directory if they have the following names.
    * - default.php
    * - *plugin.php
    *
    * @param array $EnabledPlugins An array of plugins that should be included.
    * If this argument is null then all enabled plugins will be included.
    * @return array The plugin info array for all included plugins.
    */
   public function IncludePlugins($EnabledPlugins = NULL) {

      // Include all of the plugins.
      if (is_null($EnabledPlugins))
         $EnabledPlugins = $this->EnabledPlugins();

      $PluginManager = &$this;
      // Get a list of files to include.
      foreach ($EnabledPlugins as $PluginName => $Trash) {
         $PluginInfo = $this->GetPluginInfo($PluginName);

         $ClassName = GetValue('ClassName', $PluginInfo, FALSE);
         $ClassFile = GetValue('RealFile', $PluginInfo, FALSE);

         if ($ClassName !== FALSE && !class_exists($ClassName, FALSE))
            if (file_exists($ClassFile))
               include_once($ClassFile);

      }
   }

   public function IndexSearchPath($SearchPath, &$PluginInfo, &$ClassInfo, $PathListing = NULL) {
      if (is_null($PathListing) || !is_array($PathListing)) {
         $PathListing = scandir($SearchPath, 0);
         sort($PathListing);
      }

      if ($PathListing === FALSE)
         return FALSE;

      foreach ($PathListing as $PluginFolderName) {
         if (substr($PluginFolderName, 0, 1) == '.')
            continue;

         $PluginPath = CombinePaths(array($SearchPath,$PluginFolderName));
         $PluginFile = $this->FindPluginFile($PluginPath);

         if ($PluginFile === FALSE)
            continue;

         $SearchPluginInfo = $this->ScanPluginFile($PluginFile);

         if ($SearchPluginInfo === FALSE)
            continue;

         $RealPluginFile = realpath($PluginFile);
         $SearchPluginInfo['RealFile'] = $RealPluginFile;
         $SearchPluginInfo['RealRoot'] = dirname($RealPluginFile);
         $SearchPluginInfo['SearchPath'] = $SearchPath;
         $PluginInfo[$PluginFolderName] = $SearchPluginInfo;

         $PluginClassName = GetValue('ClassName', $SearchPluginInfo);
         $ClassInfo[$PluginClassName] = $PluginFolderName;
      }

      return md5(serialize($PathListing));
   }

   public function AddSearchPath($SearchPath, $SearchPathName = NULL) {
      $AlternateSearchPaths = $this->SearchPaths(TRUE);
      $SearchPath = rtrim($SearchPath, '/');
      if (array_key_exists($SearchPath, $AlternateSearchPaths)) return TRUE;

      $this->AlternateSearchPaths[$SearchPath] = $SearchPathName;
      SaveToConfig('Garden.PluginManager.Search', $this->AlternateSearchPaths);
      return TRUE;
   }

   public function RemoveSearchPath($SearchPath) {
      $AlternateSearchPaths = $this->SearchPaths(TRUE);
      $SearchPath = rtrim($SearchPath, '/');
      if (!array_key_exists($SearchPath, $AlternateSearchPaths)) return TRUE;

      unset($this->AlternateSearchPaths[$SearchPath]);
      SaveToConfig('Garden.PluginManager.Search', $this->AlternateSearchPaths);
      return TRUE;
   }

   public function FindPluginFile($PluginPath) {
      if (!is_dir($PluginPath)) return FALSE;
      $PluginFiles = scandir($PluginPath);
      $TestPatterns = array(
         'default.php', '*plugin.php'
      );
      foreach ($PluginFiles as $PluginFile) {
         foreach ($TestPatterns as $Test)
            if (fnmatch($Test, $PluginFile)) return CombinePaths(array($PluginPath, $PluginFile));
      }

      return FALSE;
   }

   /**
    * Register all enabled plugins' event handlers and overrides
    *
    * Examines all declared classes, identifying which ones implement
    * Gdn_IPlugin and registers all of their event handlers and method
    * overrides. It recognizes them because Handlers end with _Handler,
    * _Before, and _After and overrides end with "_Override". They are prefixed
    * with the name of the class and method (or event) to be handled or
    * overridden.
    *
    * For example:
    *
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

      // Loop through all declared classes looking for ones that implement Gdn_iPlugin.
      foreach (get_declared_classes() as $ClassName) {

         // Only register the plugin if it implements the Gdn_IPlugin interface
         if (in_array('Gdn_IPlugin', class_implements($ClassName))) {

            // If this plugin was already indexed, skip it.
            if (array_key_exists($ClassName, $this->RegisteredPlugins))
               continue;

            // Register this plugin's methods
            $this->RegisterPlugin($ClassName);

         }
      }
   }

   public function RegisterPlugin($ClassName) {
      $ClassMethods = get_class_methods($ClassName);
      if ($ClassMethods === NULL) {
         throw new Exception("There was an error getting the $ClassName class methods.", 401);
      }
      
      foreach ($ClassMethods as $Method) {
         $MethodName = strtolower($Method);
         // Loop through their individual methods looking for event handlers and method overrides.
         if (isset($MethodName[9])) {
            $Suffix = array_pop(explode('_',$MethodName));
            switch ($Suffix) {
               case 'handler':
               case 'before':
               case 'after':
                  $this->RegisterHandler($ClassName, $MethodName);
               break;
               case 'override':
                  $this->RegisterOverride($ClassName, $MethodName);
               break;
               case 'create':
                  $this->RegisterNewMethod($ClassName, $MethodName);
               break;
            }
         }
      }

      $this->RegisteredPlugins[$ClassName] = TRUE;
   }

   public function UnRegisterPlugin($PluginClassName) {
      $this->_RemoveFromCollectionByPrefix($PluginClassName, $this->_EventHandlerCollection);
      $this->_RemoveFromCollectionByPrefix($PluginClassName, $this->_MethodOverrideCollection);
      $this->_RemoveFromCollectionByPrefix($PluginClassName, $this->_NewMethodCollection);
      if (array_key_exists($PluginClassName, $this->RegisteredPlugins))
         unset($this->RegisteredPlugins[$PluginClassName]);

      return TRUE;
   }

   private function _RemoveFromCollectionByPrefix($Prefix, &$Collection) {
      foreach ($Collection as $Event => $Hooks) {
         if (is_array($Hooks)) {
            foreach ($Hooks as $Index => $Hook) {
               if (strpos($Hook, $Prefix.'.') === 0)
                  unset($Collection[$Event][$Index]);
            }
         } elseif (is_string($Hooks)) {
            if (strpos($Hooks, $Prefix.'.') === 0)
               unset($Collection[$Event]);
         }
      }
   }

   public function RemoveMobileUnfriendlyPlugins() {
      foreach ($this->EnabledPlugins() as $PluginName => $Trash) {
         $PluginInfo = $this->GetPluginInfo($PluginName);

         // Remove plugin hooks from plugins that dont explicitly claim to be friendly with mobile themes
         if (!GetValue('MobileFriendly', $PluginInfo))
            $this->UnRegisterPlugin($PluginName.'Plugin');
      }
   }

   /**
    * Check whether a plugin is enabled
    * 
    * @param string $PluginName
    * @return bool
    */
   public function CheckPlugin($PluginName) {
      if (array_key_exists($PluginName, $this->EnabledPlugins()))
         return TRUE;

      return FALSE;
   }

   public function GetEventHandlers($Sender, $EventName, $HandlerType = 'Handler', $Options = array()) {
      // Figure out the classname.
      if (isset($Options['ClassName']))
         $ClassName = $Options['ClassName'];
      elseif (property_exists($Sender, 'ClassName') && $Sender->ClassName)
         $ClassName = $Sender->ClassName;
      else
         $ClassName = get_class($Sender);

      // Build the list of event handler names.
      $Names = array(
         "{$ClassName}_{$EventName}_{$HandlerType}",
         "Base_{$EventName}_{$HandlerType}",
         "{$ClassName}_{$EventName}_All",
         "Base_{$EventName}_All");

      // Grab the event handlers.
      $Handlers = array();
      foreach ($Names as $Name) {
         $Name = strtolower($Name);
         if (isset($this->_EventHandlerCollection[$Name])) {
            $Handlers = array_merge($Handlers, $this->_EventHandlerCollection[$Name]);
         }
      }

      return $Handlers;
   }

   public function GetPluginInfo($AccessName, $AccessType = self::ACCESS_PLUGINNAME) {
      $PluginName = FALSE;

      switch ($AccessType) {
         case self::ACCESS_PLUGINNAME:
            $PluginName = $AccessName;
         break;

         case self::ACCESS_CLASSNAME:
         default:
            $PluginName = GetValue($AccessName, $this->PluginsByClass, FALSE);
         break;
      }
      $Available = $this->AvailablePlugins();
      return ($PluginName !== FALSE) ? GetValue($PluginName, $Available, FALSE) : FALSE;
   }

   /**
    * Gets an instance of a given plugin.
    *
    * @param string $AccessName The key of the plugin.
    * @param string $AccessType The type of key for the plugin which must be one of the following:
    *  - Gdn_PluginManager::ACCESS_PLUGINNAME
    *  - Gdn_PluginManager::ACCESS_CLASSNAME
    * @param mixed $Sender An object to pass to a new plugin instantiation.
    * @return Gdn_IPlugin The plugin instance.
    */
   public function GetPluginInstance($AccessName, $AccessType = self::ACCESS_CLASSNAME, $Sender = NULL) {
      $ClassName = NULL;
      switch ($AccessType) {
         case self::ACCESS_PLUGINNAME:
            $ClassName = GetValue('ClassName', $this->GetPluginInfo($AccessName), FALSE);
         break;

         case self::ACCESS_CLASSNAME:
         default:
            $ClassName = $AccessName;
         break;
      }

      if (!class_exists($ClassName))
         throw new Exception("Tried to load plugin '{$ClassName}' from access name '{$AccessName}:{$AccessType}', but it doesn't exist.");

      if (!array_key_exists($ClassName, $this->Instances)) {
         $this->Instances[$ClassName] = (is_null($Sender)) ? new $ClassName() : new $ClassName($Sender);
         $this->Instances[$ClassName]->PluginInfo = $this->GetPluginInfo($AccessName, $AccessType);
      }

      return $this->Instances[$ClassName];
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
      if (array_key_exists($EventKey, $this->_NewMethodCollection) === TRUE) {
         trigger_error('New object methods must be unique. The new "'.$EventKey.'" method has already been assigned by the "'.$this->_NewMethodCollection[$EventKey].'" plugin. It cannot also be assigned by the "'.$NewMethodClassName.'" plugin.', E_USER_NOTICE);
         return;
      }

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
   public function CallEventHandlers($Sender, $EventClassName, $EventName, $EventHandlerType = 'Handler', $Options = array()) {
      $Return = FALSE;

      // Look through $this->_EventHandlerCollection for relevant handlers
      if ($this->CallEventHandler($Sender, $EventClassName, $EventName, $EventHandlerType))
         $Return = TRUE;

      // Look for "Base" (aka any class that has $EventName)
      if ($this->CallEventHandler($Sender, 'Base', $EventName, $EventHandlerType))
         $Return = TRUE;

      // Look for Wildcard event handlers
      $WildEventKey = $EventClassName.'_'.$EventName.'_'.$EventHandlerType;
      if ($this->CallEventHandler($Sender, 'Base', 'All', $EventHandlerType, $WildEventKey))
         $Return = TRUE;
      if ($this->CallEventHandler($Sender, $EventClassName, 'All', $EventHandlerType, $WildEventKey))
         $Return = TRUE;

      return $Return;
   }
   
   public function Trace($Message, $Type = TRACE_INFO) {
      if ($this->Trace)
         Trace($Message, $Type);
   }

   public function CallEventHandler($Sender, $EventClassName, $EventName, $EventHandlerType, $Options = array()) {
      $this->Trace("CallEventHandler $EventClassName $EventName $EventHandlerType");
      $Return = FALSE;

      // Backwards compatible for event key.
      if (is_string($Options)) {
         $PassedEventKey = $Options;
         $Options = array();
      } else {
         $PassedEventKey = GetValue('EventKey', $Options, NULL);
      }

      $EventKey = strtolower($EventClassName.'_'.$EventName.'_'.$EventHandlerType);
      if (!array_key_exists($EventKey, $this->_EventHandlerCollection))
         return FALSE;
      
      if (is_null($PassedEventKey))
         $PassedEventKey = $EventKey;

      // For "All" events, calculate the stack
      if ($EventName == 'All') {
         $Stack = debug_backtrace();
         // this call
         array_shift($Stack);

         // plural call
         array_shift($Stack);

         $EventCaller = array_shift($Stack);
         $Sender->EventArguments['WildEventStack'] = $EventCaller;
      }
      
      $this->Trace($this->_EventHandlerCollection[$EventKey], 'Event Handlers');
      
      // Loop through the handlers and execute them
      foreach ($this->_EventHandlerCollection[$EventKey] as $PluginKey) {
         $PluginKeyParts = explode('.', $PluginKey);
         if (count($PluginKeyParts) == 2) {
            list($PluginClassName, $PluginEventHandlerName) = $PluginKeyParts;

            
            if (isset($Sender->Returns)) {
               if (array_key_exists($EventKey, $Sender->Returns) === FALSE || is_array($Sender->Returns[$EventKey]) === FALSE)
                  $Sender->Returns[$EventKey] = array();

               $Return = $this->GetPluginInstance($PluginClassName)->$PluginEventHandlerName($Sender, $Sender->EventArguments, $PassedEventKey);
               
               $Sender->Returns[$EventKey][$PluginKey] = $Return;
               $Return = TRUE;
            } else {
               $this->GetPluginInstance($PluginClassName)->$PluginEventHandlerName($Sender, array(), $PassedEventKey);
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
   public function CallMethodOverride($Sender, $ClassName, $MethodName) {
      $EventKey = strtolower($ClassName.'_'.$MethodName.'_Override');
      $OverrideKey = ArrayValue($EventKey, $this->_MethodOverrideCollection, '');
      $OverrideKeyParts = explode('.', $OverrideKey);
      if (count($OverrideKeyParts) != 2)
         return FALSE;

      list($OverrideClassName, $OverrideMethodName) = $OverrideKeyParts;

      return $this->GetPluginInstance($OverrideClassName, self::ACCESS_CLASSNAME, $Sender)->$OverrideMethodName($Sender, $Sender->EventArguments);
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
   public function CallNewMethod($Sender, $ClassName, $MethodName) {
      $Return = FALSE;
      $EventKey = strtolower($ClassName.'_'.$MethodName.'_Create');
      $NewMethodKey = ArrayValue($EventKey, $this->_NewMethodCollection, '');
      $NewMethodKeyParts = explode('.', $NewMethodKey);
      if (count($NewMethodKeyParts) != 2)
         return FALSE;

      list($NewMethodClassName, $NewMethodName) = $NewMethodKeyParts;

      return $this->GetPluginInstance($NewMethodClassName, self::ACCESS_CLASSNAME, $Sender)->$NewMethodName($Sender, GetValue('RequestArgs', $Sender, array()));
   }
   /**
    * Get the callback for an event handler.
    * @param string $ClassName The name of the class throwing the event.
    * @param string $MethodName The name of the event.
    * @param string $Type The type of event handler.
    *  - Create: A new method creation.
    *  - Override: A method override.
    * @return callback 
    * @since 2.1
    */
   public function GetCallback($ClassName, $MethodName, $Type = 'Create') {
      $EventKey = strtolower("{$ClassName}_{$MethodName}_{$Type}");
      
      switch ($Type) {
         case 'Create':
            $MethodKey = GetValue($EventKey, $this->_NewMethodCollection);
            break;
         case 'Override':
            $MethodKey = GetValue($EventKey, $this->_MethodOverrideCollection);
            break;
      }
      $Parts = explode('.', $MethodKey, 2);
      if (count($Parts) != 2)
         return FALSE;
      
      list($ClassName, $MethodName) = $Parts;
      $Instance = $this->GetPluginInstance($ClassName, self::ACCESS_CLASSNAME);
      return array($Instance, $MethodName);
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
      $Key = strtolower($ClassName.'_'.$MethodName.'_Create');
      if (array_key_exists($Key, $this->_NewMethodCollection)) {
         $Result = explode('.', $this->_NewMethodCollection[$Key]);
         return $Result[0];
      } else {
         return FALSE;
      }
   }

   public function ScanPluginFile($PluginFile, $VariableName = NULL) {
      // Find the $PluginInfo array
      if (!file_exists($PluginFile)) return;
      $Lines = file($PluginFile);
      $InfoBuffer = FALSE;
      $ClassBuffer = FALSE;
      $ClassName = '';
      $PluginInfoString = '';
      if (!$VariableName)
         $VariableName = 'PluginInfo';

      $ParseVariableName = '$'.$VariableName;
      ${$VariableName} = array();

      foreach ($Lines as $Line) {
         if ($InfoBuffer && substr(trim($Line), -2) == ');') {
            $PluginInfoString .= $Line;
            $ClassBuffer = TRUE;
            $InfoBuffer = FALSE;
         }

         if (StringBeginsWith(trim($Line), $ParseVariableName))
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
         eval($PluginInfoString);

      // Define the folder name and assign the class name for the newly added item
      if (isset(${$VariableName}) && is_array(${$VariableName})) {
         $Item = array_pop($Trash = array_keys(${$VariableName}));

         ${$VariableName}[$Item]['Index'] = $Item;
         ${$VariableName}[$Item]['ClassName'] = $ClassName;
         ${$VariableName}[$Item]['PluginFilePath'] = $PluginFile;
         ${$VariableName}[$Item]['PluginRoot'] = dirname($PluginFile);

         if (!array_key_exists('Name', ${$VariableName}[$Item]))
            ${$VariableName}[$Item]['Name'] = $Item;

         if (!array_key_exists('Folder', ${$VariableName}[$Item]))
            ${$VariableName}[$Item]['Folder'] = $Item;

         return ${$VariableName}[$Item];
      } elseif ($VariableName !== NULL) {
         if (isset(${$VariableName}))
            return $$VariableName;
      }

      return NULL;
   }

   /**
    * Get the current search paths
    *
    * By default, get all the paths as built by the constructor. Includes the two (or one) default plugin paths
    * of PATH_PLUGINS and PATH_LOCAL_PLUGINS, as well as any extra paths defined in the config variable.
    *
    * @param boolean $OnlyCustom whether or not to exclude the two default paths and return only config paths
    * @return array Search paths
    */
   public function SearchPaths($OnlyCustom = FALSE) {
      if (is_null($this->PluginSearchPaths) || is_null($this->AlternatePluginSearchPaths)) {
         $this->PluginSearchPaths = array();
         $this->AlternatePluginSearchPaths = array();

         // Add default search path(s) to list
         $this->PluginSearchPaths[rtrim(PATH_PLUGINS,'/')] = 'core';

         // Check for, and load, alternate search paths from config
         $RawAlternatePaths = C('Garden.PluginManager.Search', NULL);
         if (!is_null($RawAlternatePaths)) {

/*
            // Handle serialized and unserialized alternate path arrays
            $AlternatePaths = unserialize($RawAlternatePaths);
            if ($AlternatePaths === FALSE && is_array($RawAlternatePaths))
*/
               $AlternatePaths = $RawAlternatePaths;

            if (!is_array($AlternatePaths))
               $AlternatePaths = array($AlternatePaths   => 'alternate');

            foreach ($AlternatePaths as $AltPath => $AltName) {
               $this->AlternatePluginSearchPaths[rtrim($AltPath, '/')] = $AltName;
               if (is_dir($AltPath))
                  $this->PluginSearchPaths[rtrim($AltPath, '/')] = $AltName;
            }
         }
      }

      if (!$OnlyCustom)
         return $this->PluginSearchPaths;

      return $this->AlternatePluginSearchPaths;
   }

   public function EnabledPluginFolders($SearchPath = NULL) {
      if (is_null($SearchPath)) {
         return array_keys($this->EnabledPlugins());
      } else {
         $Folders = array_flip(GetValue($SearchPath, $this->PluginFoldersByPath, array()));
         return array_keys(array_intersect_key($Folders,$this->EnabledPlugins()));
      }
   }
   
   public function AvailablePluginFolders($SearchPath = NULL) {
      if (is_null($SearchPath)) {
         return array_keys($this->AvailablePlugins());
      } else {
         return GetValue($SearchPath, $this->PluginFoldersByPath, array());
      }
   }

   /**
    * Test to see if a plugin throws fatal errors.
    */
   public function TestPlugin($PluginName, &$Validation, $Setup = FALSE) {
      // Make sure that the plugin's requirements are met
      // Required Plugins
      $PluginInfo = $this->GetPluginInfo($PluginName);
      $RequiredPlugins = GetValue('RequiredPlugins', $PluginInfo, FALSE);
      CheckRequirements($PluginName, $RequiredPlugins, $this->EnabledPlugins(), 'plugin');

      // Required Themes
      $EnabledThemes = Gdn::ThemeManager()->EnabledThemeInfo();
      $RequiredThemes = ArrayValue('RequiredTheme', $PluginInfo, FALSE);
      CheckRequirements($PluginName, $RequiredThemes, $EnabledThemes, 'theme');

      // Required Applications
      $EnabledApplications = Gdn::ApplicationManager()->EnabledApplications();
      $RequiredApplications = ArrayValue('RequiredApplications', $PluginInfo, FALSE);
      CheckRequirements($PluginName, $RequiredApplications, $EnabledApplications, 'application');

      // Include the plugin, instantiate it, and call its setup method
      $PluginClassName = ArrayValue('ClassName', $PluginInfo, FALSE);
      $PluginFolder = ArrayValue('Folder', $PluginInfo, FALSE);
      if ($PluginFolder == '')
         throw new Exception(T('The plugin folder was not properly defined.'));

      $this->_PluginHook($PluginName, self::ACTION_ENABLE, $Setup);

      // If setup succeeded, register any specified permissions
      $PermissionName = GetValue('RegisterPermissions', $PluginInfo, FALSE);
      if ($PermissionName != FALSE) {
         $PermissionModel = Gdn::PermissionModel();
         $PermissionModel->Define($PermissionName);
      }

      return TRUE;
   }

   public function EnablePlugin($PluginName, $Validation, $Setup = FALSE, $EnabledPluginValueIndex = 'Folder') {

      // Check that the plugin is in AvailablePlugins...
      $PluginInfo = $this->GetPluginInfo($PluginName);

      // Couldn't load the plugin info.
      if (!$PluginInfo) return FALSE;

      // Check to see if the plugin is already enabled.
      if (array_key_exists($PluginName, $this->EnabledPlugins()))
         throw new Gdn_UserException(T('The plugin is already enabled.'));

      $this->TestPlugin($PluginName, $Validation, $Setup);

      if (is_object($Validation) && count($Validation->Results()) > 0)
         return FALSE;

      // Write enabled state to config
      SaveToConfig("EnabledPlugins.{$PluginName}", TRUE);
      
      $this->EnabledPlugins[$PluginName] = TRUE;

      $PluginClassName = GetValue('ClassName', $PluginInfo);
      $this->RegisterPlugin($PluginClassName);

      Gdn::Locale()->Set(Gdn::Locale()->Current(), Gdn::ApplicationManager()->EnabledApplicationFolders(), $this->EnabledPluginFolders(), TRUE);
      return TRUE;
   }
   
   public function DisablePlugin($PluginName) {
      // Get the plugin and make sure its name is the correct case.
      $Plugin = $this->GetPluginInfo($PluginName);
      if ($Plugin)
         $PluginName = $Plugin['Index'];

      Gdn_Autoloader::SmartFree(Gdn_Autoloader::CONTEXT_PLUGIN, $Plugin);

      // 1. Check to make sure that no other enabled plugins rely on this one
      // Get all available plugins and compile their requirements
      foreach ($this->EnabledPlugins() as $CheckingName => $Trash) {
         $CheckingInfo = $this->GetPluginInfo($CheckingName);
         $RequiredPlugins = ArrayValue('RequiredPlugins', $CheckingInfo, FALSE);
         if (is_array($RequiredPlugins) && array_key_exists($PluginName, $RequiredPlugins) === TRUE) {
            throw new Exception(sprintf(T('You cannot disable the %1$s plugin because the %2$s plugin requires it in order to function.'), $PluginName, $CheckingName));
         }
      }

      // 2. Perform necessary hook action
      $this->_PluginHook($PluginName, self::ACTION_DISABLE, TRUE);

      // 3. Disable it
      RemoveFromConfig("EnabledPlugins.{$PluginName}");
      unset($this->EnabledPlugins[$PluginName]);

      // Redefine the locale manager's settings $Locale->Set($CurrentLocale, $EnabledApps, $EnabledPlugins, TRUE);
      Gdn::Locale()->Refresh();
      return TRUE;
   }

   public static function SplitAuthors($AuthorsString, $Format = 'html') {
      $Authors = explode(';', $AuthorsString);
      $Result = array();
      foreach ($Authors as $AuthorString) {
         $Parts = explode(',', $AuthorString, 3);
         $Author = array();
         $Author['Name'] = trim($Author[0]);
         for ($i = 1; $i < count($Parts); $i++) {
            if (strpos($Parts[$i], '@') !== FALSE)
               $Author['Email'] = $Parts[$i];
            elseif (preg_match('`^https?://`', $Parts[$i]))
               $Author['Url'] = $Parts[$i];
         }
         $Result[] = $Author;
      }

      if (strtolower($Format) == 'html') {
         // Build the html for the authors.
         $Htmls = array();
         foreach ($Result as $Author) {
            $Name = $Author['Name'];
            if (isset($Author['Url']))
               $Url = $Author['Url'];
            elseif (isset($Author['Email']))
               $Url = "mailto:{$Author['Email']}";

            if (isset($Url))
               $Htmls[] = '<a href="'.htmlspecialchars($Url).'">'.htmlspecialchars($Name).'</a>';
         }
         $Result = implode(', ', $Htmls);
      }
      return $Result;
      
   }

   /**
    * Hooks to the various actions, i.e. enable, disable and remove.
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
         //case self::ACTION_REMOVE:  $HookMethod = 'CleanUp'; break;
         case self::ACTION_ONLOAD:  $HookMethod = 'OnLoad'; break;
      }

      $PluginInfo      = ArrayValue($PluginName, $this->AvailablePlugins(), FALSE);
      $PluginFolder    = ArrayValue('Folder', $PluginInfo, FALSE);
      $PluginClassName = ArrayValue('ClassName', $PluginInfo, FALSE);

      if ($PluginFolder !== FALSE && $PluginClassName !== FALSE && class_exists($PluginClassName) === FALSE) {
         if ($ForAction !== self::ACTION_DISABLE) {
            $this->IncludePlugins(array($PluginName => TRUE));
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
