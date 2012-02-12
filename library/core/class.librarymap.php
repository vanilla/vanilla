<?php if (!defined('APPLICATION')) exit();

/**
 * Handle the creation, usage, and deletion of file cache entries which map paths
 * to locale files.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 * @deprecated
 */

class Gdn_LibraryMap {
   
   /**
    * Sprintf format string that describes the on-disk name of the mapping caches
    * 
    * @const string
    */
   const DISK_CACHE_NAME_FORMAT = '%s_map.ini';
   const CACHE_CACHE_NAME_FORMAT = 'garden.librarymap.%s';
   
   /**
    * Holds the in-memory array of cache entries
    * 
    * @var array
    */
   public static $Caches;
   
   /**
    * Prepare a cache library for use, either by loading it from file, filling it with
    * pre existing data in array form, or leaving it empty and waiting for new entries.
    * 
    * @param string $CacheName name of cache library
    * @param array $ExistingCacheArray optional array containing an initial seed cache
    * @param string $CacheMode optional mode of the cache... defaults to flat
    * @return void
    */
   public static function PrepareCache($CacheName, $ExistingCacheArray = NULL, $CacheMode = 'flat') {
      // Onetime initialization of in-memory file cache
      if (!is_array(self::$Caches)) 
         self::$Caches = array();
      
      if ($CacheName != 'locale') return;

      if (!array_key_exists($CacheName,self::$Caches)) {
         
         self::$Caches[$CacheName] = array(
            'cache'     => array(),
            'mode'      => $CacheMode
         );
         
         $UseCache = (Gdn::Cache()->Type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::Cache()->ActiveEnabled());
         if ($UseCache) {
            
            $CacheKey = sprintf(Gdn_LibraryMap::CACHE_CACHE_NAME_FORMAT, $CacheName);
            $CacheContents = Gdn::Cache()->Get($CacheKey);
            $LoadedFromCache = ($CacheContents !== Gdn_Cache::CACHEOP_FAILURE);
            if ($LoadedFromCache && is_array($CacheContents))
               self::Import($CacheName, $CacheContents);
            
         } else {
            
            $OnDiskCacheName = sprintf(self::DISK_CACHE_NAME_FORMAT,strtolower($CacheName));
            self::$Caches[$CacheName]['ondisk'] = $OnDiskCacheName;

            // Loading cache for the first time by name+path only... import data now.
            if (file_exists(PATH_CACHE.DS.$OnDiskCacheName)) {
               $CacheContents = parse_ini_file(PATH_CACHE.DS.$OnDiskCacheName, TRUE);
               if ($CacheContents != FALSE && is_array($CacheContents)) {
                  self::Import($CacheName, $CacheContents);
               } else
                  @unlink(PATH_CACHE.DS.$OnDiskCacheName);
            }
            
         }
      }
      
      // If cache data array is passed in, merge it with our existing cache
      if (is_array($ExistingCacheArray))
         self::Import($CacheName, $ExistingCacheArray, TRUE);
   }
   
   /**
    * Import an existing well formed cache chunk into the supplied library
    * 
    * @param string $CacheName name of cache library
    * @param array $CacheContents well formed cache array
    * @return void
    */
   protected static function Import($CacheName, $CacheContents, $AutoSave = FALSE) {
      if (!array_key_exists($CacheName,self::$Caches))
         return FALSE;
         
      self::$Caches[$CacheName]['cache'] = array_merge(self::$Caches[$CacheName]['cache'], $CacheContents);
      self::$Caches[$CacheName]['mode'] = (sizeof($CacheContents) == 1 && array_key_exists($CacheName, $CacheContents)) ? 'flat' : 'tree';
      if ($AutoSave)
         self::SaveCache($CacheName);
   }
   
   /**
    * Clear the contents of the supplied cache, and remove it from disk
    *
    * @param string $CacheName name of cache library
    * @return void
    */
   public static function ClearCache($CacheName = FALSE) {
      Gdn_Autoloader::SmartFree();
      if ($CacheName != 'locale') return;
      
      if (!array_key_exists($CacheName,self::$Caches))
         return self::PrepareCache($CacheName);
      
      $UseCache = (Gdn::Cache()->Type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::Cache()->ActiveEnabled());
      if ($UseCache) {
         $CacheKey = sprintf(Gdn_LibraryMap::CACHE_CACHE_NAME_FORMAT, $CacheName);
         $Deleted = Gdn::Cache()->Remove($CacheKey);
      } else {
         @unlink(PATH_CACHE.DS.self::$Caches[$CacheName]['ondisk']);
      }
      self::$Caches[$CacheName]['cache'] = array();
   }
   
   /**
    * Detect whether the cache has any items in it
    *
    * @param string $CacheName name of cache library
    * @return bool ready state of cache
    */
   public static function CacheReady($CacheName) {
      if (!array_key_exists($CacheName,self::$Caches))
         return FALSE;
         
      if (!sizeof(self::$Caches[$CacheName]['cache']))
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
      if ($CacheName != 'locale') return;
      
      if (!array_key_exists($CacheName,self::$Caches)) 
         return FALSE;
      
      // Set and save cache data to memory and disk
      if (self::$Caches[$CacheName]['mode'] == 'flat') 
         $Target = &self::$Caches[$CacheName]['cache'][$CacheName];
      else
         $Target = &self::$Caches[$CacheName]['cache'];
      
      $Target[$CacheKey] = $CacheContents;
      if ($CacheWrite === TRUE)
         self::SaveCache($CacheName);
         
      return $CacheContents;
   }
   
   public static function SafeCache($CacheName, $CacheKey, $CacheContents, $CacheWrite=TRUE) {
      if ($CacheName != 'locale') return;
      
      self::PrepareCache($CacheName);
      return self::Cache($CacheName, str_replace('.','__',$CacheKey), $CacheContents, $CacheWrite);
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
      if ($CacheName != 'locale') return;
      
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
      if ($CacheName != 'locale') return;
      
      if (!array_key_exists($CacheName,self::$Caches)) 
         self::PrepareCache($CacheName);
         
      if (self::$Caches[$CacheName]['mode'] == 'flat') 
         $Target = &self::$Caches[$CacheName]['cache'][$CacheName];
      else
         $Target = &self::$Caches[$CacheName]['cache'];
      $Target = (array)$Target;
      
      if (array_key_exists($CacheKey,$Target))
         return $Target[$CacheKey];
         
      return NULL;
   }
   
   /**
    * Save the provided library's data to the on disk location.
    *
    * @param string $CacheName name of cache library
    * @return void
    */
   public static function SaveCache($CacheName) {
      if ($CacheName != 'locale') return;
      
      if (!array_key_exists($CacheName,self::$Caches)) 
         return FALSE;
      
      $UseCache = (Gdn::Cache()->Type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::Cache()->ActiveEnabled());
      if ($UseCache) {
         $CacheKey = sprintf(Gdn_LibraryMap::CACHE_CACHE_NAME_FORMAT, $CacheName);
         $Stored = Gdn::Cache()->Store($CacheKey, self::$Caches[$CacheName]['cache']);
      } else {
         $FileName = self::$Caches[$CacheName]['ondisk'];
         $CacheContents = "";
         foreach (self::$Caches[$CacheName]['cache'] as $SectionTitle => $SectionData) {
            $CacheContents .= "[{$SectionTitle}]\n";
            foreach ($SectionData as $StoreKey => $StoreValue) {
               $CacheContents .= "{$StoreKey} = \"{$StoreValue}\"\n";
            }
         }
         try {
            Gdn_FileSystem::SaveFile(PATH_CACHE.DS.$FileName, $CacheContents, LOCK_EX);
         }
         catch (Exception $e) {}
      }
   }
   
}