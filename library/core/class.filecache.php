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
   
   protected static $_Caches;
   
   public static function PrepareCache($CacheName, $DiskCache, $ExistingCacheArray=NULL) {
      // Onetime initialization of in-memory file cache
      if (!is_array(self::$_Caches)) 
         self::$_Caches = array();
         
      if (!array_key_exists($CacheName,self::$_Caches)) {
         self::$_Caches[$CacheName] = array(
            'ondisk'    => $DiskCache,
            'cache'     => array()
         );
         
         // Loading cache for the first time by name+path only... import data now.
         if ($ExistingCacheArray === NULL && file_exists(PATH_CACHE.DS.$DiskCache))
            require_once(PATH_CACHE.DS.$DiskCache);
      }
      
      // If cache data array is passed in, merge it with our existing cache
      if (is_array($ExistingCacheArray))
         self::Import($CacheName, $ExistingCacheArray);
   }
   
   public static function Import($CacheName, $CacheContents) {
      if (!array_key_exists($CacheName,self::$_Caches))
         return FALSE;
         
      self::$_Caches[$CacheName]['cache'] = array_merge(self::$_Caches[$CacheName]['cache'], $CacheContents);
      self::SaveCache($CacheName);
   }
   
   public static function ClearCache($CacheName) {
      if (!array_key_exists($CacheName,self::$_Caches))
         return FALSE;
         
      self::$_Caches[$CacheName]['cache'] = array();
      @unlink(PATH_CACHE.DS.self::$_Caches[$CacheName]['ondisk']);
   }
   
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
    * @param string $CacheName 
    * @param string $CacheKey 
    * @param string $CacheContents 
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
   
   public static function GetCache($CacheName, $CacheKey) {
      if (array_key_exists($CacheKey,self::$_Caches[$CacheName]['cache']))
         return self::$_Caches[$CacheName]['cache'][$CacheKey];
         
      return NULL;
   }
   
   public static function SaveCache($CacheName) {
      if (!array_key_exists($CacheName,self::$_Caches)) 
         return FALSE;
      
      $FileName = self::$_Caches[$CacheName]['ondisk'];
      $CacheContents = "<?php if (!defined('APPLICATION')) exit();\n".
                        "Gdn_FileCache::PrepareCache('{$CacheName}','{$FileName}',";
                        
      self::RecurseArrayStr(NULL, self::$_Caches[$CacheName]['cache'], $CacheContents);

      $CacheContents .= ");";

      try {
         Gdn_FileSystem::SaveFile(PATH_CACHE.DS.$FileName, $CacheContents);
      }
      catch (Exception $e) {}
   }
   
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
            $CacheStr .= str_repeat('   ',$FormatIndentLevel+1)."'{$CacheValue}'";
         }
         else {
            self::RecurseArrayStr($CacheKey, $CacheValue, $CacheStr, $FormatIndentLevel+1);
         }
      }
      if (is_array($Cache))
         $CacheStr .= "\n".str_repeat('   ',$FormatIndentLevel).")";
   }
   
}