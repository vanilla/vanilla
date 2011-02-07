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
 * Vanilla framework autoloader.
 *
 * Handles indexing of class files across the entire framework, as well as bringing those
 * classes into scope as needed.
 *
 * This is a static class that hooks into the SPL autoloader.
 *
 * @author Tim Gunter
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

class Gdn_Autoloader {

   /**
    * Array of registered maps to search during load requests
    *
    * @var array
    */
   protected static $RegisteredMaps;
   
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
   
   const CONTEXT_CORE         = 'core';
   const CONTEXT_APPLICATION  = 'application';
   const CONTEXT_PLUGIN       = 'plugin';
   const CONTEXT_LOCALE       = 'locale';
   const CONTEXT_THEME        = 'theme';
   
   const MAP_LIBRARY          = 'library';
   const MAP_CONTROLLER       = 'controller';
   const MAP_PLUGIN           = 'plugin';
   
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
               
               echo "\nAdding applications folders...\n";
               
               foreach ($EnabledApplications as $EnabledApplication) {
                  $ApplicationPath = CombinePaths(array(PATH_APPLICATIONS."/{$EnabledApplication}"));
                  
                  $AppControllers = CombinePaths(array($ApplicationPath."/controllers"));
                  self::RegisterMap(self::MAP_CONTROLLER, self::CONTEXT_APPLICATION, $AppControllers, array(
                     'SearchSubfolders'      => FALSE,
                     'Extension'             => $EnabledApplication
                  ));
                  
                  $AppModels = CombinePaths(array($ApplicationPath."/models"));
                  self::RegisterMap(self::MAP_LIBRARY, self::CONTEXT_APPLICATION, $AppModels, array(
                     'SearchSubfolders'      => FALSE,
                     'Extension'             => $EnabledApplication,
                     'ClassFilter'           => '*model'
                  ));
                  
                  $AppModules = CombinePaths(array($ApplicationPath."/modules"));
                  self::RegisterMap(self::MAP_LIBRARY, self::CONTEXT_APPLICATION, $AppModules, array(
                     'SearchSubfolders'      => FALSE,
                     'Extension'             => $EnabledApplication,
                     'ClassFilter'           => '*module'
                  ));
               }
            }
            
         break;
         
         case self::CONTEXT_PLUGIN:

            if (Gdn::PluginManager() instanceof Gdn_PluginManager) {
            
               echo "\nAdding plugin folders...\n";

               foreach (Gdn::PluginManager()->SearchPaths() as $SearchPath => $SearchPathName) {
               
                  if ($SearchPathName === TRUE || $SearchPathName == 1)
                     $SearchPathName = md5($SearchPath);
               
                  // If we have already loaded the plugin manager, use its internal folder list, otherwise scan all subfolders during search
                  if (Gdn::PluginManager()->Started()) {
                     $Folders = Gdn::PluginManager()->EnabledPluginFolders($SearchPath);
                     foreach ($Folders as $PluginFolder) {
                        $FullPluginPath = CombinePaths(array($SearchPath, $PluginFolder));
                        self::RegisterMap(self::MAP_LIBRARY, self::CONTEXT_PLUGIN, $FullPluginPath, array(
                           'SearchSubfolders'      => TRUE,
                           'Extension'             => $SearchPathName
                        ));
                     }
                  } else {
                     self::RegisterMap(self::MAP_LIBRARY, self::CONTEXT_PLUGIN, $SearchPath, array(
                        'SearchSubfolders'      => TRUE,
                        'Extension'             => $SearchPathName
                     ));
                  }
               }
               
            }

         break;
         
         case self::CONTEXT_THEME:
         
         break;
      }
   
   }
   
   protected static function DoLookup($ClassName, $MapType) {
      // We loop over the caches twice. First, hit only their cached data.
      // If all cache hits miss, search filesystem.
      
      echo '  '.__METHOD__."\n";
      
      if (!is_array(self::$RegisteredMaps[$MapType]))
         self::$RegisteredMaps[$MapType] = array();
      
      // Binary flip - cacheonly or cache+fs
      foreach (array(TRUE, FALSE) as $CacheOnly) {
      
         // Drill to the caches associated with this map type
         foreach (self::$ContextOrder as $Context) {
            if (!array_key_exists($Context, self::$RegisteredMaps[$MapType]) || !sizeof(self::$RegisteredMaps[$MapType][$Context])) continue;
            
            // Search each registered cache at this level
            foreach (self::$RegisteredMaps[$MapType][$Context] as $CacheHash => &$Cache) {
               $File = $Cache->Lookup($ClassName, $CacheOnly);
               if ($File !== FALSE) return $File;
            }
         }
      }
      
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
   
   public static function Lookup($ClassName) {
      echo __METHOD__."({$ClassName})\n";
      
      $MapType = self::GetMapType($ClassName);
      echo "  map type: {$MapType}\n";
      
      $File = self::DoLookup($ClassName, $MapType);
      
      if ($File !== FALSE)
         include_once($File);
   }
   
   public static function RegisterMap($MapType, $ContextType, $SearchPath, $Options = array()) {
   
      echo __METHOD__."({$MapType}, {$ContextType}, {$SearchPath})\n";
   
      $DefaultOptions = array(
         'SearchSubfolders'      => TRUE,
         'Extension'             => NULL,
         'ContextPrefix'         => NULL,
         'ClassFilter'           => '*'
      );
      if (array_key_exists($ContextType, self::$Prefixes))
         $DefaultOptions['ContextPrefix'] = GetValue($ContextType, self::$Prefixes);
      
      $Options = array_merge($DefaultOptions, $Options);
      
      // Determine cache root disk location
      $Hits = 0; str_replace(PATH_LOCAL_ROOT, '', $SearchPath, $Hits);
      if ($Hits) $CacheRootLocation = PATH_LOCAL_CACHE;
      else $CacheRootLocation = PATH_CACHE;
      
      $CacheIdentifier = implode('|',array(
         $MapType,
         $ContextType,
         GetValue('Extension',$Options),
         $CacheRootLocation
      ));
      $CacheHash = md5($CacheIdentifier);
      
      if (!is_array(self::$RegisteredMaps))
         self::$RegisteredMaps = array();
      
      if (!is_array(self::$RegisteredMaps[$ContextType]))
         self::$RegisteredMaps[$ContextType] = array();
         
      if (!array_key_exists($CacheHash, self::$RegisteredMaps[$MapType][$ContextType])) {
         $Cache = Gdn_Autoloader_Cache::Load($MapType, $CacheRootLocation, $Options);
         self::$RegisteredMaps[$MapType][$ContextType][$CacheHash] = $Cache;
      } else {
         echo "  appended path to existing cache\n";
      }
      
      return self::$RegisteredMaps[$MapType][$ContextType][$CacheHash]->AddPath($SearchPath, $Options);
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
      self::$RegisteredMaps = array();
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
   
      // Register autoloader with the SPL
      spl_autoload_register(array('Gdn_Autoloader', 'Lookup'));
      
      // Configure core lookups
      self::RegisterMap(self::MAP_LIBRARY, self::CONTEXT_CORE, PATH_LIBRARY);
      
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
      foreach (self::$RegisteredMaps as $MapType => $Contexts)
         foreach ($Contexts as $ContextLevel => $Caches)
            foreach ($Caches as &$Cache)
               $Cache->Shutdown();
   }
   
}

class Gdn_Autoloader_Cache {
   
   /**
    * Sprintf format string that describes the on-disk name of the mapping caches
    * 
    * @const string
    */
   const DISK_CACHE_NAME_FORMAT = '%s/%s_map.ini';
   
   const LOOKUP_CLASS_MASK = 'class.%s.php';
   const LOOKUP_INTERFACE_MASK = 'interface.%s.php';
   
   protected $CacheInfo;
   protected $Cache;
   protected $Ignore;
   protected $Paths;
   
   private function __construct($MapType, $CacheRootLocation, $Options) {
      $this->Cache = NULL;
      $this->Ignore = array('.','..');
      $this->Paths = array();
      
      $ExtensionName = GetValue('Extension', $Options, NULL);
      $Recursive = GetValue('SearchSubfolders', $Options, TRUE);
      $ContextPrefix = GetValue('ContextPrefix', $Options, NULL);
      
      $CacheName = $MapType;
      if (!is_null($ExtensionName))
         $CacheName = $ExtensionName.'_'.$CacheName;
         
      if (!is_null($ContextPrefix))
         $CacheName = $ContextPrefix.'_'.$CacheName;
      
      $OnDiskCacheFile = sprintf(self::DISK_CACHE_NAME_FORMAT, $CacheRootLocation, strtolower($CacheName));
      
      echo "  cache started: {$CacheName} - {$OnDiskCacheFile}\n";
      
      $this->CacheInfo = array(
         'ondisk'    => $OnDiskCacheFile,
         'name'      => $CacheName,
         'dirty'     => FALSE
      );
   }
   
   public function AddPath($SearchPath, $Options) {
      $this->Paths[$SearchPath] = array(
         'path'      => $SearchPath,
         'recursive' => (bool)GetValue('SearchSubfolders', $Options),
         'filter'    => GetValue('ClassFilter', $Options)
      );
   }
   
   public static function Load($MapType, $CacheRootLocation, $Options) {
      return new Gdn_Autoloader_Cache($MapType, $CacheRootLocation, $Options);
   }
   
   public function Lookup($ClassName, $CacheOnly = TRUE) {
      $CacheName = GetValue('name', $this->CacheInfo);
      echo "    ".__METHOD__." [{$CacheName}] ({$ClassName}, ".(($CacheOnly) ? 'cache': 'cache+fs').")\n";
      
      // Lazyload cache data
      if (is_null($this->Cache)) {
         $this->Cache = array();
         $OnDiskCacheFile = GetValue('ondisk', $this->CacheInfo);
         
         echo "      load from disk: {$OnDiskCacheFile}... ";
         // Loading cache data from disk
         if (file_exists($OnDiskCacheFile)) {
            echo "exists\n";
            $CacheContents = parse_ini_file($OnDiskCacheFile, FALSE);
            if ($CacheContents != FALSE && is_array($CacheContents)) {
               $this->Cache = $CacheContents;
            } else
               @unlink($OnDiskCacheFile);
         } else {
            echo "missing\n";
         }
      }
   
      $ClassName = strtolower($ClassName);
      if (array_key_exists($ClassName, $this->Cache)) {
         echo "      cache hit\n";
         return GetValue($ClassName, $this->Cache);
      }
      // Look at the filesystem, too
      if (!$CacheOnly) {
         if (substr($ClassName, 0, 4) == 'gdn_')
            $FSClassName = substr($ClassName, 4);
         else
            $FSClassName = $ClassName;
         
         $Files = array(
            sprintf(self::LOOKUP_CLASS_MASK, $FSClassName),
            sprintf(self::LOOKUP_INTERFACE_MASK, $FSClassName)
         );
         echo "      find: {$Files[0]}\n";
         echo "      find: {$Files[1]}\n";
         
         foreach ($this->Paths as $Path => $PathOptions) {
            $ClassFilter = GetValue('filter', $PathOptions);
            if (!fnmatch($ClassFilter, $ClassName)) continue;
            
            $Recursive = GetValue('recursive', $PathOptions);
            echo "      scan: '{$Path}' recurse: ".(($Recursive) ? 'y': 'n')."\n";
   
            $File = $this->FindFile($Path, $Files, $Recursive);
            
            if ($File !== FALSE) {
               $this->Cache[$ClassName] = $File;
               $this->CacheInfo['dirty'] = TRUE;
               
               echo "      found {$ClassName} @ {$File}. added back to cache {$CacheName}\n";
               return $File;
            }
         }
      }
      
      return FALSE;
   }
   
   protected function FindFile($Path, $SearchFiles, $Recursive) {
      if (!is_array($SearchFiles))
         $SearchFiles = array($SearchFiles);
      
      if (!is_dir($Path)) return FALSE;
      echo "        - findfile: {$Path}\n";
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
   
   public function Shutdown() {
      
      if (!GetValue('dirty', $this->CacheInfo)) return FALSE;
      
      if (!sizeof($this->Cache))
         return FALSE;
         
      echo __METHOD__."\n";
      $CacheName = GetValue('name', $this->CacheInfo);
      $OnDisk = GetValue('ondisk', $this->CacheInfo);
      echo "  saving cache [{$CacheName}] @ {$OnDisk}\n";
      
      $FileName = GetValue('ondisk', $this->CacheInfo);
      
      $CacheContents = "[cache]\n";
      foreach ($this->Cache as $ClassName => $Location) {
         $CacheContents .= "{$ClassName} = \"{$Location}\"\n";
      }
      try {
         Gdn_FileSystem::SaveFile($FileName, $CacheContents, LOCK_EX);
      }
      catch (Exception $e) { return FALSE; }
      
      return TRUE;
   }
   
}



