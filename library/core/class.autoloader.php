<?php if (!defined('APPLICATION')) exit();

/**
 * Vanilla framework autoloader.
 *
 * Handles indexing of class files across the entire framework, as well as bringing those
 * classes into scope as needed.
 *
 * This is a static class that hooks into the SPL autoloader.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0.16
 */

class Gdn_Autoloader {

   /**
    * Array of registered maps to search during load requests
    *
    * @var array
    */
   protected static $Maps;
      
   /**
    * Array of pathname prefixes used to namespace similar libraries
    *
    * @var array
    */
   protected static $Prefixes;
   
   /**
    * Array of contexts used to establish search order
    *
    * @var array
    */
   protected static $ContextOrder;
   
   /**
    * Array of maps that pertain to the same CONTEXT+Extension
    *
    * @var array
    */
   protected static $MapGroups;
   
   /**
    * List of priority/preferred CONTEXT+Extension[+MapType] groups for the next lookup 
    *
    * @var array
    */
   protected static $Priorities;
   
   const CONTEXT_CORE            = 'core';
   const CONTEXT_APPLICATION     = 'application';
   const CONTEXT_PLUGIN          = 'plugin';
   const CONTEXT_LOCALE          = 'locale';
   const CONTEXT_THEME           = 'theme';
   
   const MAP_LIBRARY             = 'library';
   const MAP_CONTROLLER          = 'controller';
   const MAP_PLUGIN              = 'plugin';
   const MAP_VENDORS             = 'vendors';
   
   const PRIORITY_TYPE_PREFER    = 'prefer';
   const PRIORITY_TYPE_RESTRICT  = 'restrict';
   
   const PRIORITY_ONCE           = 'once';
   const PRIORITY_PERSIST        = 'persist';
   
   /**
    * Attach mappings for vanilla extension folders
    *
    * @param string $ExtensionType type of extension to map. one of: CONTEXT_THEME, CONTEXT_PLUGIN, CONTEXT_APPLICATION
    */
   public static function Attach($ExtensionType) {
   
      switch ($ExtensionType) {
         case self::CONTEXT_APPLICATION:
         
            if (Gdn::ApplicationManager() instanceof Gdn_ApplicationManager) {
               $EnabledApplications = Gdn::ApplicationManager()->EnabledApplicationFolders();
               
               foreach ($EnabledApplications as $EnabledApplication) {
                  self::AttachApplication($EnabledApplication);
               }
            }
            
         break;
         
         case self::CONTEXT_PLUGIN:

            if (Gdn::PluginManager() instanceof Gdn_PluginManager) {
            
               foreach (Gdn::PluginManager()->SearchPaths() as $SearchPath => $SearchPathName) {
               
                  if ($SearchPathName === TRUE || $SearchPathName == 1)
                     $SearchPathName = md5($SearchPath);
               
                  // If we have already loaded the plugin manager, use its internal folder list
                  if (Gdn::PluginManager()->Started()) {
                     $Folders = Gdn::PluginManager()->EnabledPluginFolders($SearchPath);
                     foreach ($Folders as $PluginFolder) {
                        $FullPluginPath = CombinePaths(array($SearchPath, $PluginFolder));
                        self::RegisterMap(self::MAP_LIBRARY, self::CONTEXT_PLUGIN, $FullPluginPath, array(
                           'SearchSubfolders'      => TRUE,
                           'Extension'             => $SearchPathName,
                           'Structure'             => Gdn_Autoloader_Map::STRUCTURE_SPLIT,
                           'SplitTopic'            => strtolower($PluginFolder),
                           'PreWarm'               => TRUE
                        ));
                     }
                     
                     $PluginMap = self::GetMap(self::MAP_LIBRARY, self::CONTEXT_PLUGIN);
                     if ($PluginMap && !$PluginMap->MapIsOnDisk())
                        Gdn::PluginManager()->ForceAutoloaderIndex();
                  }
               }
            }

         break;
         
         case self::CONTEXT_THEME:
         
         break;
      }
   
   }
   
   public static function AttachApplication($Application) {
      $ApplicationPath = CombinePaths(array(PATH_APPLICATIONS."/{$Application}"));

      $AppControllers = CombinePaths(array($ApplicationPath."/controllers"));
      self::RegisterMap(self::MAP_CONTROLLER, self::CONTEXT_APPLICATION, $AppControllers, array(
         'SearchSubfolders'      => FALSE,
         'Extension'             => $Application
      ));

      $AppModels = CombinePaths(array($ApplicationPath."/models"));
      self::RegisterMap(self::MAP_LIBRARY, self::CONTEXT_APPLICATION, $AppModels, array(
         'SearchSubfolders'      => FALSE,
         'Extension'             => $Application,
         'ClassFilter'           => '*model'
      ));

      $AppModules = CombinePaths(array($ApplicationPath."/modules"));
      self::RegisterMap(self::MAP_LIBRARY, self::CONTEXT_APPLICATION, $AppModules, array(
         'SearchSubfolders'      => FALSE,
         'Extension'             => $Application,
         'ClassFilter'           => '*module'
      ));

      $AppLibrary = CombinePaths(array($ApplicationPath."/library"));
      self::RegisterMap(self::MAP_LIBRARY, self::CONTEXT_APPLICATION, $AppLibrary, array(
         'SearchSubfolders'      => FALSE,
         'Extension'             => $Application,
         'ClassFilter'           => '*'
      ));
   }
   
   protected static function DoLookup($ClassName, $MapType) {
      // We loop over the caches twice. First, hit only their cached data.
      // If all cache hits miss, search filesystem.
      
      if (!is_array(self::$Maps))
         return FALSE;
      
      $Priorities = array();
      
      // Binary flip - cacheonly or cache+fs
      foreach (array(TRUE, FALSE) as $MapOnly) {
      
         $SkipMaps = array(); $ContextType = NULL;
         $SkipTillNextContext = FALSE;
         
         // Drill to the caches associated with this map type
         foreach (self::$Maps as $MapHash => &$Map) {
            if ($Map->MapType() != $MapType) continue;
            
            $MapContext = self::GetContextType($MapHash);
            if ($MapContext != $ContextType) {
               // Hit new context
               $SkipMaps = array();
               $ContextType = $MapContext;
               
               if (!array_key_exists($ContextType, $Priorities))
                  $Priorities[$ContextType] = self::Priorities($ContextType, $MapType);
               
               if (array_key_exists($ContextType, $Priorities) && is_array($Priorities[$ContextType])) {
                  foreach ($Priorities[$ContextType] as $PriorityMapHash => $PriorityInfo) {
                  
                     // If we're in a RESTRICT priority and we come to the end, wait till we hit the next context before looking further
                     if ($PriorityMapHash == 'FAIL_CONTEXT_IF_NOT_FOUND') {
                        $SkipTillNextContext = TRUE;
                        break;
                     }
                     $PriorityMap = self::Map($PriorityMapHash);
                     
                     $File = $PriorityMap->Lookup($ClassName, $MapOnly);
                     if ($File !== FALSE) return $File;
                     
                     // Don't check this map again
                     array_push($SkipMaps, $PriorityMapHash);
                  }
               }
            }
            
            // If this map was already checked by a priority, or if we've exhausted a RESTRICT priority, skip maps until the next
            // context level is reached.
            if (in_array($MapHash, $SkipMaps) || $SkipTillNextContext === TRUE) continue;
            
            // Finally, search this map.
            $File = $Map->Lookup($ClassName, $MapOnly);
            if ($File !== FALSE) return $File;
         }
      }
      
      return FALSE;
   }
   
   public static function GetContextType($MapHash) {
      $Matched = preg_match('/^context:(\d+)_.*/', $MapHash, $Matches);
      if ($Matched && isset($Matches[1]))
         $ContextIdentifier = $Matches[1];
      else
         return FALSE;
      
      if (isset($ContextIdentifier, self::$ContextOrder))
         return self::$ContextOrder[$ContextIdentifier];
      return FALSE;
   }
   
   public static function GetMapType($ClassName) {
      // Strip leading 'Gdn_'
      if (substr($ClassName, 0, 4) == 'Gdn_')
         $ClassName = substr($ClassName, 4);
      
      $ClassName = strtolower($ClassName);
      $Length = strlen($ClassName);
      
      if (substr($ClassName, -10) == 'controller' && $Length > 10)
         return self::MAP_CONTROLLER;
      
      return self::MAP_LIBRARY;
   }
   
   public static function Lookup($ClassName, $Options = array()) {
      if (!preg_match("/^[a-zA-Z0-9_\x7f-\xff]*$/", $ClassName))
         return;
      
      $MapType = self::GetMapType($ClassName);
      
      $DefaultOptions = array(
         'Quiet'              => FALSE,
         'RespectPriorities'  => TRUE
      );
      $Options = array_merge($DefaultOptions, $Options);
      
      $File = self::DoLookup($ClassName, $MapType);
      
      if ($File !== FALSE) {
         if (!isset($Options['Quiet']) || !$Options['Quiet'])
            include_once($File);
      }
      return $File;
   }
   
   /**
    * Get an Autoloader Map by hash
    * 
    * @param type $MapHash
    * @return Gdn_Autoloader_Map
    */
   public static function Map($MapHash) {
      if (array_key_exists($MapHash, self::$Maps))
         return self::$Maps[$MapHash];
      
      if (is_null($MapHash)) return self::$Maps;
      return FALSE;
   }
   
   /**
    * Lookup and return an Autoloader Map
    * 
    * @param type $MapType
    * @param type $ContextType
    * @param type $Extension
    * @param type $MapRootLocation
    * @return Gdn_Autoloader_Map
    */
   public static function GetMap($MapType, $ContextType, $Extension = self::CONTEXT_CORE, $MapRootLocation = PATH_CACHE) {
      $MapHash = self::MakeMapHash($MapType, $ContextType, $Extension, $MapRootLocation);
      return self::Map($MapHash);
   }
   
   public static function Priority($ContextType, $Extension, $MapType = NULL, $PriorityType = self::PRIORITY_TYPE_PREFER, $PriorityDuration = self::PRIORITY_ONCE) {
      $MapGroupIdentifier = implode('|',array(
         $ContextType,
         $Extension
      ));
      
      $MapGroupHashes = GetValue($MapGroupIdentifier, self::$MapGroups, array());
      $PriorityHashes = array();
      $PriorityHashes = array();
      foreach ($MapGroupHashes as $MapHash => $Trash) {
         $ThisMapType = self::Map($MapHash)->MapType();
         // We're restricting this priority to a certain maptype, so exclude non matchers
         if (!is_null($MapType) && $ThisMapType != $MapType) continue;
         
         $PriorityHashes[$MapHash] = array(
            'maptype'      => $ThisMapType,
            'duration'     => $PriorityDuration,
            'prioritytype' => $PriorityType
         );
      }
      
      if (!sizeof($PriorityHashes)) return FALSE;
      
      if (!is_array(self::$Priorities)) 
         self::$Priorities = array();
      
      if (!array_key_exists($ContextType,self::$Priorities))
         self::$Priorities[$ContextType] = array(
            self::PRIORITY_TYPE_RESTRICT  => array(),
            self::PRIORITY_TYPE_PREFER    => array()
         );
      
      // Add new priorities to list
      self::$Priorities[$ContextType][$PriorityType] = array_merge(self::$Priorities[$ContextType][$PriorityType], $PriorityHashes);
      
      return TRUE;
   }
   
   public static function Priorities($ContextType, $MapType = NULL) {
      if (!is_array(self::$Priorities) || !array_key_exists($ContextType,self::$Priorities)) 
         return FALSE;
      
      /**
       * First, gather the RESTRICT requirements. If these exist, they are the only hashes that will be sent, and a 'FAIL_IF_NOT_FOUND' 
       * flag will be appended to the list to halt lookups.
       *
       * If there are no RESTRICT priorities, check for PREFER priorities and send those.
       *
       * Always optionally filter on $MapType if provided.
       */
      foreach (array(self::PRIORITY_TYPE_RESTRICT, self::PRIORITY_TYPE_PREFER) as $PriorityType) {
         if (!sizeof(self::$Priorities[$ContextType][$PriorityType])) continue;
         
         $ResultMapHashes = self::$Priorities[$ContextType][$PriorityType];
         $ResponseHashes = array();
         foreach ($ResultMapHashes as $MapHash => $PriorityInfo) {
         
            if (GetValue('duration', $PriorityInfo) == self::PRIORITY_ONCE)
               unset(self::$Priorities[$ContextType][$PriorityType][$MapHash]);
         
            // If this request is being specific about the required maptype, reject anything that doesnt match
            if (!is_null($MapType) && GetValue('maptype', $PriorityInfo) != $MapType)
               continue;
            
            $ResponseHashes[$MapHash] = $PriorityInfo;
         }
         
         if ($PriorityType == self::PRIORITY_TYPE_RESTRICT)
            $ResponseHashes['FAIL_CONTEXT_IF_NOT_FOUND'] = TRUE;
         
         return $ResponseHashes;
      }
      
      return FALSE;
   }
   
   public static function RegisterMap($MapType, $ContextType, $SearchPath, $Options = array()) {
      $DefaultOptions = array(
         'SearchSubfolders'      => TRUE,
         'Extension'             => NULL,
         'ContextPrefix'         => NULL,
         'ClassFilter'           => '*',
         'SaveToDisk'            => TRUE
      );
      if (array_key_exists($ContextType, self::$Prefixes))
         $DefaultOptions['ContextPrefix'] = GetValue($ContextType, self::$Prefixes);
      
      $Options = array_merge($DefaultOptions, $Options);
      
      $Extension = GetValue('Extension', $Options, NULL);
      
      // Determine cache root on-disk location
      $Hits = 0; str_replace(PATH_ROOT, '', $SearchPath, $Hits);
      $MapRootLocation = PATH_CACHE;
      
      // Build a unique identifier that refers to this map (same map type, context, extension, and cachefile location)
      $MapIdentifier = implode('|',array(
         $MapType,
         $ContextType,
         $Extension,
         $MapRootLocation
      ));
      $Options['MapIdentifier'] = $MapIdentifier;
      $MapHash = md5($MapIdentifier);
      
      // Allow intrinsic ordering / layering of contexts by prefixing them with a context number
      $MapHash = 'context:'.GetValue($ContextType, array_flip(self::$ContextOrder)).'_'.$Extension.'_'.$MapHash;
      $MapHash = self::MakeMapHash($MapType, $ContextType, $Extension, $MapRootLocation);
      
      if (!is_array(self::$Maps))
         self::$Maps = array();
         
      if (!array_key_exists($MapHash, self::$Maps)) {
         $Map = Gdn_Autoloader_Map::Load($MapType, $ContextType, $MapRootLocation, $Options);
         self::$Maps[$MapHash] = $Map;
      }
      
      ksort(self::$Maps, SORT_REGULAR);
      
      $AddPathResult = self::$Maps[$MapHash]->AddPath($SearchPath, $Options);
         
      /*
       * Build a unique identifier that refers to this cached list (context and extension)
       *
       * For example, CONTEXT_APPLICATION and 'dashboard' would refer to all maps that store
       * information about the dashboard application: its controllers, models, modules, etc.
       */
      $MapGroupIdentifier = implode('|',array(
         $ContextType,
         $Extension
      ));
      
      if (!is_array(self::$MapGroups))
         self::$MapGroups = array();
      
      if (!array_key_exists($MapGroupIdentifier,self::$MapGroups))
         self::$MapGroups[$MapGroupIdentifier] = array();

      self::$MapGroups[$MapGroupIdentifier][$MapHash] = TRUE;
      
      return $AddPathResult;
   }

   public static function MakeMapHash($MapType,$ContextType,$Extension,$MapRootLocation) {
      $MapIdentifier = implode('|',array(
         $MapType,
         $ContextType,
         $Extension,
         $MapRootLocation
      ));

      $MapHash = md5($MapIdentifier);

      // Allow intrinsic ordering / layering of contexts by prefixing them with a context number
      $MapHash = 'context:'.GetValue($ContextType, array_flip(self::$ContextOrder)).'_'.$Extension.'_'.$MapHash;

      return $MapHash;
   }
   
   public static function ForceIndex($MapType, $ContextType, $Extension = self::CONTEXT_CORE) {
      $Map = self::GetMap($MapType, $ContextType, $Extension);
      $Map->Index();
   }

   /**
    * This method frees the map storing information about the specified resource
    *
    * Takes a maptype, and an array of information about the resource in question:
    * a plugininfo array, in the case of a plugin, or
    *
    * @param string $ContextType type of map to consider (one of the MAP_ constants)
    * @param array $MapResourceArray array of information about the mapped resource
    */
   public static function SmartFree($ContextType = NULL, $MapResourceArray = NULL) {

      $CacheFolder = @opendir(PATH_CACHE);
      if (!$CacheFolder) return TRUE;
      while ($File = readdir($CacheFolder)) {
         $Extension = pathinfo($File, PATHINFO_EXTENSION);
         if ($Extension == 'ini' && $File != 'locale_map.ini')
            @unlink(CombinePaths(array(PATH_CACHE, $File)));
      }
      
   }
   
   /**
    * Register core mappings
    *
    * Set up the autoloader with known searchg directories, hook into the SPL autoloader
    * and load existing caches.
    *
    * @param void
    */
   public static function Start() {
      
      self::$Prefixes = array(
         self::CONTEXT_CORE            => 'c',
         self::CONTEXT_APPLICATION     => 'a',
         self::CONTEXT_PLUGIN          => 'p',
         self::CONTEXT_THEME           => 't'
      );
      self::$ContextOrder = array(
         self::CONTEXT_THEME,
         self::CONTEXT_LOCALE,
         self::CONTEXT_PLUGIN,
         self::CONTEXT_APPLICATION,
         self::CONTEXT_CORE
      );
      
      self::$Maps = array();
      self::$MapGroups = array();
   
      // Register autoloader with the SPL
      spl_autoload_register(array('Gdn_Autoloader', 'Lookup'));
      
      // Configure library/core and library/database
      self::RegisterMap(self::MAP_LIBRARY, self::CONTEXT_CORE, PATH_LIBRARY.'/core');
      self::RegisterMap(self::MAP_LIBRARY, self::CONTEXT_CORE, PATH_LIBRARY.'/database');
      self::RegisterMap(self::MAP_LIBRARY, self::CONTEXT_CORE, PATH_LIBRARY.'/vendors');
      
      // Register shutdown function to auto save changed cache files
      register_shutdown_function(array('Gdn_Autoloader', 'Shutdown'));
   }
   
   /**
    * Save current caches
    *
    * This method executes once, just as the framework is shutting down. Its purpose
    * is to save the library maps to disk if they've changed.
    *
    * @param void
    */
   public static function Shutdown() {
      foreach (self::$Maps as $MapHash => &$Map)
         $Map->Shutdown();
   }
   
}

class Gdn_Autoloader_Map {
   
   /**
    * Sprintf format string that describes the on-disk name of the mapping caches
    * 
    * @const string
    */
   const DISK_MAP_NAME_FORMAT = '%s/%s_map.ini';
   
   const LOOKUP_CLASS_MASK = 'class.%s.php';
   const LOOKUP_INTERFACE_MASK = 'interface.%s.php';
   
   /**
    * Map file structure type
    * 
    *  flat - file contains one topic, 'cache', with all classname -> file entries in a single list
    *  split - file contains a topic for each path, with only that path's files in the list
    * 
    * @const string
    */
   const STRUCTURE_FLAT = 'flat';
   const STRUCTURE_SPLIT = 'split';
   
   const TOPIC_DEFAULT = 'cache';
   
   protected $BuildOptions;
   protected $MapInfo;
   protected $Map;
   protected $Ignore;
   protected $Paths;
   
   private function __construct($MapType, $ContextType, $MapRootLocation, $Options) {
      $this->Map = NULL;
      $this->Ignore = array('.','..');
      $this->Paths = array();
      $this->BuildOptions = $Options;
      
      $ExtensionName = GetValue('Extension', $Options, NULL);
      $Recursive = GetValue('SearchSubfolders', $Options, TRUE);
      $ContextPrefix = GetValue('ContextPrefix', $Options, NULL);
      $MapIdentifier = GetValue('MapIdentifier', $Options, NULL);
      $SaveToDisk = GetValue('SaveToDisk', $Options, TRUE);
      $FileStructure = GetValue('Structure', $Options, self::STRUCTURE_FLAT);
      
      $MapName = $MapType;
      if (!is_null($ExtensionName))
         $MapName = $ExtensionName.'_'.$MapName;
         
      if (!is_null($ContextPrefix))
         $MapName = $ContextPrefix.'_'.$MapName;
      
      $OnDiskMapFile = sprintf(self::DISK_MAP_NAME_FORMAT, $MapRootLocation, strtolower($MapName));
      
      $this->MapInfo = array(
         'ondisk'       => $OnDiskMapFile,
         'identifier'   => $MapIdentifier,
         'root'         => $MapRootLocation,
         'name'         => $MapName,
         'maptype'      => $MapType,
         'contexttype'  => $ContextType,
         'extension'    => $ExtensionName,
         'dirty'        => FALSE,
         'save'         => $SaveToDisk,
         'structure'    => $FileStructure
      );
   }
   
   public function MapIsOnDisk() {
      return file_exists($this->MapInfo['ondisk']);
   }
   
   public function AddPath($SearchPath, $Options) {
      $PathOptions = array(
         'path'         => $SearchPath,
         'recursive'    => (bool)GetValue('SearchSubfolders', $Options),
         'filter'       => GetValue('ClassFilter', $Options),
         'topic'        => self::TOPIC_DEFAULT
      );
      
      if ($this->MapInfo['structure'] == self::STRUCTURE_SPLIT) {
         $SplitTopic = GetValue('SplitTopic', $Options, NULL);
         if (is_null($SplitTopic))
            throw new Exception("Trying to use 'split' structure but no SplitTopic provided. Path: {$SearchPath}");
         $PathOptions['topic'] = $SplitTopic;
      }
      
      $this->Paths[$SearchPath] = $PathOptions;
   }
   
   public function ContextType() {
      return $this->MapInfo['contexttype']; //GetValue('contexttype', $this->MapInfo);
   }
   
   public function Extension() {
      return $this->MapInfo['extension']; //GetValue('extension', $this->MapInfo);
   }
   
   protected function FindFile($Path, $SearchFiles, $Recursive) {
      if (!is_array($SearchFiles))
         $SearchFiles = array($SearchFiles);
      
      if (!is_dir($Path)) return FALSE;

      $Files = scandir($Path);
      foreach ($Files as $FileName) {
         if (in_array($FileName, $this->Ignore)) continue;
         $FullPath = CombinePaths(array($Path, $FileName));
         
         // If this is a folder...
         if (is_dir($FullPath)) {
            if ($Recursive) {
               $File = $this->FindFile($FullPath, $SearchFiles, $Recursive);
               if ($File !== FALSE) return $File;
               continue;
            }
            else {
               continue;
            }
         }
         
         if (in_array($FileName, $SearchFiles)) return $FullPath;
      }
      return FALSE;
   }
   
   protected function FindFiles($Path, $FileMasks, $Recursive) {
      if (!is_array($FileMasks))
         $FileMasks = array($FileMasks);
      
      if (!is_dir($Path)) return FALSE;

      $FoundFiles = array();
      $Files = scandir($Path);
      foreach ($Files as $FileName) {
         if (in_array($FileName, $this->Ignore)) continue;
         $FullPath = CombinePaths(array($Path, $FileName));
         
         // If this is a folder, maybe recurse it eh?
         if (is_dir($FullPath)) {
            if ($Recursive) {
               $Recurse = $this->FindFiles($FullPath, $FileMasks, $Recursive);
               if ($Recurse !== FALSE) 
                  $FoundFiles = array_merge($FoundFiles, $Recurse);
               continue;
            }
            else {
               continue;
            }
         } else {
            foreach ($FileMasks as $FileMask) {
               // If this file matches one of the masks, add it to this loops's found files
               if (fnmatch($FileMask, $FileName)) {
                  $FoundFiles[] = $FullPath;
                  break;
               }
            }
         }
      }
      
      return $FoundFiles;
   }
   
   /**
    * Autoloader cache static constructor
    *
    * @return Gdn_Autoloader_Map
    */
   public static function Load($MapType, $ContextType, $MapRootLocation, $Options) {
      return new Gdn_Autoloader_Map($MapType, $ContextType, $MapRootLocation, $Options);
   }
   
   public function Lookup($ClassName, $MapOnly = TRUE) {
      $MapName = $this->MapInfo['name']; //GetValue('name', $this->MapInfo);
      
      // Lazyload cache data
      if (is_null($this->Map)) {
         $this->Map = array();
         $OnDiskMapFile = $this->MapInfo['ondisk']; //GetValue('ondisk', $this->MapInfo);
         
         // Loading cache data from disk
         if (file_exists($OnDiskMapFile)) {
            $Structure = $this->MapInfo['structure']; //GetValue('structure', $this->MapInfo);
            $MapContents = parse_ini_file($OnDiskMapFile, TRUE);
            
            try {
               // Detect legacy flat files which are now stored split
               if ($Structure == self::STRUCTURE_SPLIT && array_key_exists(self::TOPIC_DEFAULT, $MapContents))
                  throw new Exception();
               
               // Bad file?
               if ($MapContents == FALSE || !is_array($MapContents))
                  throw new Exception();
               
               // All's well that ends well. Load the cache.
               $this->Map = $MapContents;
            } catch (Exception $Ex) {
               @unlink($OnDiskMapFile);
            }
         }
      }
   
      // Always look by lowercase classname
      $ClassName = strtolower($ClassName);
      
      switch ($this->MapInfo['structure']) {
         case 'split':
            // This file stored split, so look for this class in each virtual sub-path, by topic
            foreach ($this->Paths as $Path => $PathOptions) {
               $LookupSplitTopic = GetValue('topic', $PathOptions);
               if (array_key_exists($LookupSplitTopic, $this->Map) && array_key_exists($ClassName, $this->Map[$LookupSplitTopic])) {
                  return GetValue($ClassName, $this->Map[$LookupSplitTopic]);
               }
            }
            break;
         
         default:
         case 'flat':
            // This file is stored flat, so just look in the DEFAULT_TOPIC
            $LookupSplitTopic = self::TOPIC_DEFAULT;
            if (array_key_exists($LookupSplitTopic, $this->Map) && array_key_exists($ClassName, $this->Map[$LookupSplitTopic])) {
               return GetValue($ClassName, $this->Map[$LookupSplitTopic]);
            }
            break;
      }
      
      // Look at the filesystem, too
      if (!$MapOnly) {
         if (substr($ClassName, 0, 4) == 'gdn_')
            $FSClassName = substr($ClassName, 4);
         else
            $FSClassName = $ClassName;
         
         $Files = array(
            sprintf(self::LOOKUP_CLASS_MASK, $FSClassName),
            sprintf(self::LOOKUP_INTERFACE_MASK, $FSClassName)
         );
                  
         foreach ($this->Paths as $Path => $PathOptions) {
            $ClassFilter = GetValue('filter', $PathOptions);
            if (!fnmatch($ClassFilter, $ClassName)) continue;
            
            $Recursive = GetValue('recursive', $PathOptions);
            $File = $this->FindFile($Path, $Files, $Recursive);
            
            if ($File !== FALSE) {
               $SplitTopic = GetValue('topic', $PathOptions, self::TOPIC_DEFAULT);
               $this->Map[$SplitTopic][$ClassName] = $File;
               $this->MapInfo['dirty'] = TRUE;
               
               return $File;
            }
         }
      }
      
      return FALSE;
   }
   
   /**
    * Try to index the entire map
    * 
    * @return void
    */
   public function Index($ExtraPaths = NULL) {
      
      $FileMasks = array(
         sprintf(self::LOOKUP_CLASS_MASK, '*'),
         sprintf(self::LOOKUP_INTERFACE_MASK, '*')
      );
      
      $ExtraPathsRemove = NULL;
      if (!is_null($ExtraPaths)) {
         $ExtraPathsRemove = array();
         foreach ($ExtraPaths as $PathOpts) {
            $ExtraPath = GetValue('path', $PathOpts);
            if (array_key_exists($ExtraPath, $this->Paths)) continue;
            
            $ExtraPathsRemove[] = $ExtraPath;
            $ExtraOptions = $this->BuildOptions;
            $ExtraOptions['SplitTopic'] = GetValue('topic', $PathOpts);
            $this->AddPath($ExtraPath, $ExtraOptions);
         }
      }
      
      foreach ($this->Paths as $Path => $PathOptions) {

         $Recursive = GetValue('recursive', $PathOptions);
         $Files = $this->FindFiles($Path, $FileMasks, $Recursive);
         if ($Files === FALSE) continue;
         
         foreach ($Files as $File) {
            $SplitTopic = GetValue('topic', $PathOptions, self::TOPIC_DEFAULT);
            
            $ProvidedClass = $this->GetClassNameFromFile($File);
            if ($ProvidedClass) {
               $this->Map[$SplitTopic][$ProvidedClass] = $File;
               $this->MapInfo['dirty'] = TRUE;
            }
               
//            $ProvidesClasses = $this->Investigate($File);
//            if ($ProvidesClasses === FALSE) continue;
//            
//            foreach ($ProvidesClasses as $ProvidedClass) {
//               $ProvidedClass = strtolower($ProvidedClass);
//               $this->Map[$SplitTopic][$ProvidedClass] = $File;
//               $this->MapInfo['dirty'] = TRUE;
//            }
         }
      }
      
      // Save
      $this->Shutdown();
      
      if (!is_null($ExtraPathsRemove))
         foreach ($ExtraPathsRemove as $RemPath)
            unset($this->Paths[$RemPath]);
   }
   
   protected function Investigate($File) {
      if (!file_exists($File)) return;
      $Lines = file($File);
      $ClassesFound = array();
      foreach ($Lines as $Line) {
         //strtolower(substr(trim($Line), 0, 6)) == 'class '
         if (preg_match('/^class[\s]+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\s]+(?:.*)?{/',$Line,$Matches)) {
            $ClassesFound[] = $Matches[1];
            continue;
         }
         
         if (preg_match('/^interface[\s]+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\s]+(?:.*)?{/',$Line,$Matches)) {
            $ClassesFound[] = $Matches[1];
            continue;
         }
      }
      return $ClassesFound;
   }
   
   protected function GetClassNameFromFile($File) {
      if (StringEndsWith($File, '.plugin.php')) return FALSE;
      
      $FileName = basename($File);
      $Modes = array(
         'class'     => self::LOOKUP_CLASS_MASK,
         'interface' => self::LOOKUP_INTERFACE_MASK
      );
      foreach ($Modes as $ModeType => $ModeMask) {
         $MatchModeMask = sprintf($ModeMask, '*');
         if (fnmatch($MatchModeMask, $FileName)) {
            $ModeRE = '/^'.str_replace('.','\.',$ModeMask).'$/';
            $ModeRE = sprintf($ModeRE, '(.*)');
            $Matched = preg_match($ModeRE, $FileName, $Matches);
            if ($Matched)
               return str_replace('.','',strtolower($Matches[1]));
         }
      }
      return FALSE;
   }
   
   public function MapType() {
      return $this->MapInfo['maptype']; //GetValue('maptype', $this->MapInfo);
   }
   
   public function Shutdown() {
      
      if (!GetValue('dirty', $this->MapInfo)) return FALSE;
      if (!GetValue('save', $this->MapInfo)) return FALSE;
      
      if (!sizeof($this->Map))
         return FALSE;
         
      $MapName = GetValue('name', $this->MapInfo);
      $FileName = GetValue('ondisk', $this->MapInfo);
      
      $MapContents = '';
      foreach ($this->Map as $SplitTopic => $TopicFiles) {
         $MapContents .= "[{$SplitTopic}]\n";
         foreach ($TopicFiles as $ClassName => $Location)
            $MapContents .= "{$ClassName} = \"{$Location}\"\n";
      }
      
      try {
         Gdn_FileSystem::SaveFile($FileName, $MapContents, LOCK_EX);
         $this->MapInfo['dirty'] = FALSE;
      }
      catch (Exception $e) { return FALSE; }
      
      return TRUE;
   }
   
}
