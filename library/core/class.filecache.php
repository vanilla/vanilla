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
 * Handle the creation, usage, and deletion of file cache entries
 *
 * @author Tim Gunter
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

class Gdn_FileCache {
   
   /**
    * Sprintf format string that describes the on-disk name of the mapping caches
    * 
    * @const string
    */
   const DISK_CACHE_NAME_FORMAT = '%s_mappings.php';
   
   /**
    * Holds the in-memory array of cache entries
    * 
    * @var array
    */
   protected static $_Caches;
   
   /**
    * Prepare a cache library for use, either by loading it from file, filling it with
    * pre existing data in array form, or leaving it empty an waiting for new entries.
    * 
    * @param string $CacheName name of cache library
    * @param array $ExistingCacheArray optional array containing an initial seed cache
    * @return void
    */
   public static function PrepareCache($CacheName, $ExistingCacheArray=NULL) {
      // Onetime initialization of in-memory file cache
      if (!is_array(self::$_Caches)) 
         self::$_Caches = array();
      
      if (!array_key_exists($CacheName,self::$_Caches)) {
         $OnDiskCacheName = sprintf(self::DISK_CACHE_NAME_FORMAT,strtolower($CacheName));
      
         self::$_Caches[$CacheName] = array(
            'ondisk'    => $OnDiskCacheName,
            'cache'     => array()
         );
         
         // Loading cache for the first time by name+path only... import data now.
         if (file_exists(PATH_CACHE.DS.$OnDiskCacheName)) {
            require_once(PATH_CACHE.DS.$OnDiskCacheName);
         }
      }
      
      // If cache data array is passed in, merge it with our existing cache
      if (is_array($ExistingCacheArray))
         self::Import($CacheName, $ExistingCacheArray);
   }
   
   /**
    * Import an existing well formed cache chunk into the supplied library
    * 
    * @param string $CacheName name of cache library
    * @param array $CacheContents well formed cache array
    * @return void
    */
   public static function Import($CacheName, $CacheContents) {
      if (!array_key_exists($CacheName,self::$_Caches))
         return FALSE;
      
      self::$_Caches[$CacheName]['cache'] = array_merge(self::$_Caches[$CacheName]['cache'], $CacheContents);
      self::SaveCache($CacheName);
   }
   
   /**
    * Clear the contents of the supplied cache, and remove it from disk
    *
    * @param string $CacheName name of cache library
    * @return void
    */
   public static function ClearCache($CacheName) {
      if (!array_key_exists($CacheName,self::$_Caches))
         return self::PrepareCache($CacheName);
         
      self::$_Caches[$CacheName]['cache'] = array();
      @unlink(PATH_CACHE.DS.self::$_Caches[$CacheName]['ondisk']);
   }
   
   /**
    * Detect whether the cache has any items in it
    *
    * @param string $CacheName name of cache library
    * @return bool ready state of cache
    */
   public static function CacheReady($CacheName) {
      if (!array_key_exists($CacheName,self::$_Caches))
         return FALSE;
         
      if (!sizeof(self::$_Caches[$CacheName]['cache']))
         return FALSE;
         
      return TRUE;
   }

   /**
    * Store the provided resource in the appropriate (named) cache
    *
    * @param string $CacheName name of cache library
    * @param string $CacheKey name of cache entry
    * @param mixed $CacheContents contents of cache entry
    * @param bool $CacheWrite optional, whether or not to perform a disk write after this set. default yes
    * @return mixed cache contents
    */
   public static function Cache($CacheName, $CacheKey, $CacheContents, $CacheWrite=TRUE) {
      if (!array_key_exists($CacheName,self::$_Caches)) 
         return FALSE;
      
      // Set and save cache data to memory and disk
      self::$_Caches[$CacheName]['cache'][$CacheKey] = $CacheContents;
      if ($CacheWrite === TRUE)
         self::SaveCache($CacheName);
         
      return $CacheContents;
   }
   
   /**
    * Append the provided resource in the appropriate (named) cache under the named cache key. 
    * If the entry is not already an array, convert it to one... then append the new data.
    * 
    * @param string $CacheName name of cache library
    * @param string $CacheKey name of cache entry
    * @param mixed $CacheContents contents of cache entry
    * @param bool $CacheWrite optional, whether or not to perform a disk write after this set. default yes
    * @return array cache contents
    */
   public static function CacheArray($CacheName, $CacheKey, $CacheContents, $CacheWrite=TRUE) {
      $ExistingCacheData = self::GetCache($CacheName, $CacheKey);
      
      if ($ExistingCacheData === NULL) 
         $ExistingCacheData = array();
         
      if (!is_array($ExistingCacheData)) 
         $ExistingCacheData = array($ExistingCacheData);
      
      $ExistingCacheData[] = $CacheContents;
      
      // Save cache data to memory
      return self::Cache($CacheName, $CacheKey, $ExistingCacheData, $CacheWrite);
   }
   
   /**
    * Retrieve an item from the cache
    *
    * @param string $CacheName name of cache library
    * @param string $CacheKey name of cache entry
    * @return mixed cache entry or null on failure
    */
   public static function GetCache($CacheName, $CacheKey) {
      if (array_key_exists($CacheKey,self::$_Caches[$CacheName]['cache']))
         return self::$_Caches[$CacheName]['cache'][$CacheKey];
         
      return NULL;
   }
   
   /**
    * Save the provided library's data to the on disk location.
    *
    * @param string $CacheName name of cache library
    * @return void
    */
   public static function SaveCache($CacheName) {
      if (!array_key_exists($CacheName,self::$_Caches)) 
         return FALSE;
      
      $FileName = self::$_Caches[$CacheName]['ondisk'];
      
      $CacheContents = "<?php if (!defined('APPLICATION')) exit();\n".
                        "Gdn_FileCache::PrepareCache('{$CacheName}',";
      self::RecurseArrayStr(NULL, self::$_Caches[$CacheName]['cache'], $CacheContents);
      $CacheContents .= ");";

      try {
         Gdn_FileSystem::SaveFile(PATH_CACHE.DS.$FileName, $CacheContents);
      }
      catch (Exception $e) {}
   }
   
   /**
    * Recursively convert the provided array to a string, suitable for storage on disk
    *
    * @param string $RootCacheKey if not null, the name of the key fr this iteration
    * @param array $Cache cache data
    * @param ref $CacheStr reference to the destination string
    * @param int $FormatIndentLevel depth of indentation for pretty data files
    * @return string innards of cache data array
    */
   private static function RecurseArrayStr($RootCacheKey, $Cache, &$CacheStr, $FormatIndentLevel=0) {
      if ($RootCacheKey !== NULL)
         $CacheStr .= str_repeat('   ',$FormatIndentLevel)."'{$RootCacheKey}'   => ";
      
      if (is_array($Cache))
         $CacheStr .= "array(\n";
         
      $First = TRUE;
      foreach ($Cache as $CacheKey => $CacheValue) {
         if (!$First) { $CacheStr .= ",\n"; }
         if ($First) { $First = FALSE; }
         
         if (!is_array($CacheValue)) {
            $CacheStr .= str_repeat('   ',$FormatIndentLevel+1);
            if (!is_numeric($CacheKey))
               $CacheStr .= "'{$CacheKey}' => ";
            $CacheStr .= "'{$CacheValue}'";
         }
         else {
            self::RecurseArrayStr($CacheKey, $CacheValue, $CacheStr, $FormatIndentLevel+1);
         }
      }
      if (is_array($Cache))
         $CacheStr .= "\n".str_repeat('   ',$FormatIndentLevel).")";
   }
   
}