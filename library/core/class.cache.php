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
 *
 * @author Tim Gunter
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */
 
abstract class Gdn_Cache {
   
   /**
   * List of cache containers
   * @var array
   */
   protected $Containers;
   
   /**
   * List of features this cache system supports
   * @var array
   */
   protected $Features;
   
   /**
   * Type of cache this this: one of CACHE_TYPE_MEMORY, CACHE_TYPE_FILE, CACHE_TYPE_NULL
   * @var string
   */
   protected $CacheType;
   
   // Allows items to be internally compressed/decompressed
   const FEATURE_COMPRESS     = 'f_compress';
   // Allows items to autoexpire
   const FEATURE_EXPIRY       = 'f_expiry';
   // Allows set/get timeouts
   const FEATURE_TIMEOUT      = 'f_timeout';
   // Allows disabling usage of key prefix
   const FEATURE_NOPREFIX     = 'f_noprefix';
   // Allows forcing alternate key prefix
   const FEATURE_FORCEPREFIX  = 'f_forceprefix';
   // Allows querying DB for missing keys, or firing a callback
   const FEATURE_FALLBACK     = 'f_fallback';
   
   /**
    * Location - SERVER:IP, Filepath, etc
    */
   const CONTAINER_LOCATION   = 'c_location';
   
   /**
    * Persistent - Whether to use connect() or pconnect() where applicable
    */
   const CONTAINER_PERSISTENT = 'c_persistent';
   
   /**
    * Pool Size - When using pconnect(), how many connections should we use in the pool?
    */
   const CONTAINER_POOLSIZE = 'c_poolsize';
   
   /**
    * Pool Key - When using pconnect(), what should the pool key look like?
    */
   const CONTAINER_POOLKEY = 'c_poolkey';
   
   /**
    * Weight - Allows for differently weighted storage locations
    */
   const CONTAINER_WEIGHT     = 'c_weight';
   
   /**
    * Persistent - Retry delay inverval in seconds
    */
   const CONTAINER_RETRYINT = 'c_retryint';
   
   /**
    * Timeout - How long to wait before timing out while connecting
    */
   const CONTAINER_TIMEOUT    = 'c_timeout';
   
   /**
    * Online - If this container is available for requests
    */
   const CONTAINER_ONLINE     = 'c_online';
   
   /**
    * Callback - Method to call if the location fails to be added
    */
   const CONTAINER_CALLBACK   = 'c_callback';
   
   const CACHEOP_FAILURE = FALSE;
   const CACHEOP_SUCCESS = TRUE;
   
   const CACHE_TYPE_MEMORY = 'ct_memory';
   const CACHE_TYPE_FILE = 'ct_file';
   const CACHE_TYPE_NULL = 'ct_null';

   public function __construct() {
      $this->Containers = array();
      $this->Features = array();
   }
   
   /**
   * Determines the currently installed cache solution and returns a fresh instance of its object
   * 
   * @return Gdn_Cache
   */
   public static function Initialize($ForceEnable = FALSE, $ForceMethod = FALSE) {
      $AllowCaching = self::ActiveEnabled($ForceEnable); 
      $ActiveCache = Gdn_Cache::ActiveCache();
      
      if ($ForceMethod !== FALSE) $ActiveCache = $ForceMethod;
      $ActiveCacheClass = 'Gdn_'.ucfirst($ActiveCache);
      
      if (!$AllowCaching || !$ActiveCache || !class_exists($ActiveCacheClass)) {
         
         $CacheObject = new Gdn_Dirtycache();
      } else
         $CacheObject = new $ActiveCacheClass();
      
      if (method_exists($CacheObject,'Autorun'))
         $CacheObject->Autorun();
      
      // This should only fire when cache is loading automatically
      if (!func_num_args() && Gdn::PluginManager() instanceof Gdn_PluginManager)
         Gdn::PluginManager()->FireEvent('AfterActiveCache');
      
      return $CacheObject;
   }
   
   /**
   * Gets the shortname of the currently active cache
   * 
   * This method retrieves the name of the active cache according to the config file.
   * It fires an event thereafter, allowing that value to be overridden 
   * by loaded plugins.
   * 
   * @return string shortname of current auto active cache
   */
   public static function ActiveCache() {
      /*
       * There is a catch 22 with caching the config file. We need
       * an external way to define the cache layer before needing it 
       * in the config.
       */
      
      if (defined('CACHE_METHOD_OVERRIDE'))
         $ActiveCache = CACHE_METHOD_OVERRIDE;
      else
         $ActiveCache = C('Cache.Method', FALSE);
      
      // This should only fire when cache is loading automatically
      if (!func_num_args() && Gdn::PluginManager() instanceof Gdn_PluginManager) {
         Gdn::PluginManager()->EventArguments['ActiveCache'] = &$ActiveCache;
         Gdn::PluginManager()->FireEvent('BeforeActiveCache');
      }
      
      return $ActiveCache;
   }
   
   /**
    * Get the status of the active cache
    * 
    * Return whether or not the current cache method is enabled.
    * 
    * @param type $ForceEnable
    * @return bool status of active cache
    */
   public static function ActiveEnabled($ForceEnable = FALSE) {
      $AllowCaching = FALSE;
      
      if (defined('CACHE_ENABLED_OVERRIDE'))
         $AllowCaching |= CACHE_ENABLED_OVERRIDE;
         
      $AllowCaching |= C('Cache.Enabled', FALSE);
      $AllowCaching |= $ForceEnable;
      
      return (bool)$AllowCaching;
   }
   
   /**
    * Returns the storage data for the active cache
    * 
    * For FileCache, the folder. For Memcache, the server(s).
    * 
    * @param type $ForceMethod
    * @return mixed Active Store Location
    */
   public static function ActiveStore($ForceMethod = NULL) {
      $ActiveCache = self::ActiveCache();
      if (!is_null($ForceMethod))
         $ActiveCache = $ForceMethod;
      
      $ActiveCache = ucfirst($ActiveCache);
      
      if (defined('CACHE_STORE_OVERRIDE') && defined('CACHE_METHOD_OVERRIDE') && CACHE_METHOD_OVERRIDE == $ActiveCache) {
         $ActiveStore = unserialize(CACHE_STORE_OVERRIDE);
      } else
         $ActiveStore = C("Cache.{$ActiveCache}.Store", FALSE);
      
      return $ActiveStore;
   }
   
   /**
   * Returns a constant describing the type of cache implementation this object represents.
   *
   * @return string Type of cache. One of CACHE_TYPE_MEMORY, CACHE_TYPE_FILE, CACHE_TYPE_NULL
   */
   public function Type() {
      return $this->CacheType;
   }
   
   /**
   * Add a value to the cache
   * 
   * This fails if the item already exists in the cache.
   * 
   * @param string $Key Cache key used for storage
   * @param mixed $Value Value to be cached
   * @param array $Options
   * @return boolean TRUE on success or FALSE on failure.
   */
   abstract public function Add($Key, $Value, $Options = array());
   
   public function StripKey($Key, $Options) {
      $UsePrefix = !GetValue(Gdn_Cache::FEATURE_NOPREFIX, $Options, FALSE);
      $ForcePrefix = GetValue(Gdn_Cache::FEATURE_FORCEPREFIX, $Options, NULL);
      
      if ($UsePrefix) {
         $Key = substr($Key, strlen($this->GetPrefix($ForcePrefix)) + 1);
      }
      return $Key;
      
   }
   
   /**
   * Store a value in the cache
   * 
   * This works regardless of whether the item already exists in the cache.
   * 
   * @param string $Key Cache key used for storage
   * @param mixed $Value Value to be cached
   * @param array $Options
   *   - FEATURE_COMPRESS: Allows items to be internally compressed/decompressed (bool)
   *   - FEATURE_EXPIRY: Allows items to autoexpire (seconds)
   *   - FEATURE_NOPREFIX: Allows disabling usage of key prefix (bool)
   *   - FEATURE_FORCEPREFIX: Allows forcing alternate key prefix (string)
   *   - FEATURE_FALLBACK: Allows querying DB for missing keys, or firing a callback (see Gdn_Cache->Fallback)
   * @return boolean TRUE on success or FALSE on failure.
   */
   abstract public function Store($Key, $Value, $Options = array());
   
   /**
   * Check if a value exists in the cache
   *
   * @param string $Key Cache key used for storage
   * @return array array(key => value) for existing key or FALSE if not found.
   */
   abstract public function Exists($Key);
   
   /**
   * Retrieve a key's value from the cache
   * 
   * @param string $Key Cache key used for storage
   * @param array $Options
   * @return mixed key value or FALSE on failure or not found.
   */
   abstract public function Get($Key, $Options = array());
   
   /**
   * Remove a key/value pair from the cache.
   * 
   * @param string $Key Cache key used for storage
   * @param array $Options
   * @return boolean TRUE on success or FALSE on failure.
   */
   abstract public function Remove($Key, $Options = array());
   
   /**
   * Replace an existing key's value with the provided value
   * 
   * This will fail if the provided key does not already exist.
   * 
   * @param string $Key Cache key used for storage
   * @param mixed $Value Value to be cached
   * @param array $Options
   * @return boolean TRUE on success or FALSE on failure.
   */
   abstract public function Replace($Key, $Value, $Options = array());
   
   /**
   * Increment the value of the provided key by $Amount
   * 
   * This will fail if the key does not already exist. Cannot take the value 
   * of $Key below 0.
   * 
   * @param string $Key Cache key used for storage
   * @param int $Amount Amount to shift value up
   * @return int new value or FALSE on failure.
   */
   abstract public function Increment($Key, $Amount = 1, $Options = array());
   
   /**
   * Decrement the value of the provided key by $Amount
   * 
   * This will fail if the key does not already exist. Cannot take the value
   * of $Key below 0.
   * 
   * @param string $Key Cache key used for storage
   * @param int $Amount Amount to shift value down
   * @return int new value or FALSE on failure.
   */
   abstract public function Decrement($Key, $Amount = 1, $Options = array());
   
   /**
   * Add a container to the cache pool
   * 
   * @param array $Options
   *  - CONTAINER_LOCATION: required. the location of the container. SERVER:IP, Filepath, etc
   *  - CONTAINER_PERSISTENT: optional (default true). whether to use connect() or pconnect() where applicable
   *  - CONTAINER_WEIGHT: optional (default 1). number of buckets to create for this server which in turn control its probability of it being selected
   *  - CONTAINER_RETRYINT: optional (default 15s). controls how often a failed container will be retried, the default value is 15 seconds
   *  - CONTAINER_TIMEOUT: optional (default 1s). amount of time to wait for connection to container before timing ou.
   *  - CONTAINER_CALLBACK: optional (default NULL). callback to execute if container fails to open/connect
   * @return boolean TRUE on success or FALSE on failure.
   */
   abstract public function AddContainer($Options);
   
   /**
    * 
    * 
    * @param type $Key Cache key
    * @param type $Options
    * @return mixed
    */
   protected function Fallback($Key, $Options) {
      $Fallback = GetValue(Gdn_Cache::FEATURE_FALLBACK, $Options, NULL);
      if (is_null($Fallback))
         return Gdn_Cache::CACHEOP_FAILURE;
      
      $FallbackType = array_shift($Fallback);
      switch ($FallbackType) {
         case 'query':
            $QueryFallbackField = array_shift($Fallback);
            $QueryFallbackCode = array_shift($Fallback);
            $FallbackResult = Gdn::Database()->Query($QueryFallbackCode);
            if ($FallbackResult->NumRows()) {
               if (!is_null($QueryFallbackField))
                  $FallbackResult = GetValue($QueryFallbackField, $FallbackResult->FirstRow(DATASET_TYPE_ARRAY));
               else
                  $FallbackResult = $FallbackResult->ResultArray();
            }
            break;
         case 'callback':
            $CallbackFallbackMethod = array_shift($Fallback);
            $CallbackFallbackArgs = $Fallback;
            $FallbackResult = call_user_func_array($CallbackFallbackMethod, $CallbackFallbackArgs);
            break;
      }
      Gdn::Cache()->Store($Key, $FallbackResult);
      return $FallbackResult;
   }
   
   public function GetPrefix($ForcePrefix = NULL, $WithRevision = TRUE) {
      static $ConfigPrefix = FALSE;
      
      // Allow overriding the prefix
      if (!is_null($ForcePrefix))
         return $ForcePrefix;
       
      // Keep searching for the prefix until it is defined
      if ($ConfigPrefix === FALSE) {
         
         // Allow vfcom-infrastructure to set the prefix automatically
         if (defined('FORCE_CACHE_PREFIX'))
            $ConfigPrefix = FORCE_CACHE_PREFIX;
         
         if ($ConfigPrefix === FALSE)
            $ConfigPrefix = C('Cache.Prefix', FALSE);
         
      }
      
      // Lookup Revision if we have a prefix.
      $RevisionNumber = FALSE;
      if ($WithRevision && $ConfigPrefix !== FALSE) {
         $CacheRevision = $this->GetRevision($ConfigPrefix);
         if (!is_null($CacheRevision))
            $RevisionNumber = $CacheRevision;
      }
      
      $Response = $ConfigPrefix;
      if ($WithRevision && $RevisionNumber !== FALSE && $ConfigPrefix !== FALSE)
         $Response .= ".rev{$RevisionNumber}";
         
      return ($ConfigPrefix === FALSE) ? NULL : $Response;
   }
   
   public function GetRevision($ForcePrefix = NULL, $Force = FALSE) {
      static $CacheRevision = FALSE;
      
      if ($CacheRevision === FALSE || $Force) {
         $ConfigPrefix = $ForcePrefix;
         if (is_null($ConfigPrefix))
            $ConfigPrefix = $this->GetPrefix(NULL, FALSE);

         $CacheRevisionKey = "{$ConfigPrefix}.Revision";
         $CacheRevision = $this->Get($CacheRevisionKey, array(
            Gdn_Cache::FEATURE_NOPREFIX   => TRUE
         ));
         
         if ($CacheRevision === Gdn_Cache::CACHEOP_FAILURE)
            $CacheRevision = 1;
      }
      
      return $CacheRevision;
   }
   
   public function IncrementRevision() {
      $CachePrefix = $this->GetPrefix(NULL, FALSE);
      if ($CachePrefix === FALSE) return FALSE;
      
      $CacheRevisionKey = "{$CachePrefix}.Revision";
      $Incremented = $this->Increment($CacheRevisionKey, 1, array(
         Gdn_Cache::FEATURE_NOPREFIX   => TRUE
      ));
      
      if (!$Incremented) {
         return $this->Store($CacheRevisionKey, 2, array(
            Gdn_Cache::FEATURE_NOPREFIX   => TRUE
         ));
      }
      
      return TRUE;
   }
   
   public function MakeKey($Key, $Options) {
      $UsePrefix = !GetValue(Gdn_Cache::FEATURE_NOPREFIX, $Options, FALSE);
      $ForcePrefix = GetValue(Gdn_Cache::FEATURE_FORCEPREFIX, $Options, NULL);
      
      if ($UsePrefix)
         $Key = $this->GetPrefix($ForcePrefix).'!'.$Key;
      
      return $Key;
   }
   
   /*
    * Get the value of a store-specific option
    * 
    * The option keys are specific to the active cache type, but are always
    * stored under $Configuration['Cache'][ActiveCacheName]['Option'][*].
    * 
    * @param string|integer $Option The option key to retrieve
    * @return mixed The value associated with the given option key
    */
   public function Option($Option = NULL, $Default = NULL) {
      static $ActiveOptions = NULL;
      
      if (is_null($ActiveOptions)) {
         $ActiveCacheShortName = ucfirst($this->ActiveCache());
         $OptionKey = "Cache.{$ActiveCacheShortName}.Option";
         $ActiveOptions = C($OptionKey, array());
      }
      
      if (is_null($Option) || !array_key_exists($Option, $ActiveOptions)) {
         return $ActiveOptions;
      }
      
      return GetValue($Option, $ActiveOptions, $Default);
   }
   
   /*
    * Get the value of a store-specific config
    * 
    * The option keys are generic and cross-cache, but are always
    * stored under $Configuration['Cache'][ActiveCacheName]['Config'][*].
    * 
    * @param string|integer $Key The config key to retrieve
    * @return mixed The value associated with the given config key
    */
   public function Config($Key = NULL, $Default = NULL) {
      static $ActiveConfig = NULL;
      
      if (is_null($ActiveConfig)) {
         $ActiveCacheShortName = ucfirst($this->ActiveCache());
         $ConfigKey = "Cache.{$ActiveCacheShortName}.Config";
         $ActiveConfig = C($ConfigKey, array());
      }
      
      if (is_null($Key) || !array_key_exists($Key, $ActiveConfig)) {
         return $ActiveConfig;
      }
      
      return GetValue($Key, $ActiveConfig, $Default);
   }
   
   /**
   * Flag this cache as being capable of perfoming a feature
   * 
   *  FEATURE_COMPRESS: this cache can compress and decompress values on the fly
   *  FEATURE_TIMEOUT: this cache can timeout while reading / writing
   *  FEATURE_EXPIRY: this cache can expire keys
   * 
   * @param int $Feature feature constant
   * @param mixed $Meta optional data to return when calling HasFeature. default TRUE
   */
   public function RegisterFeature($Feature, $Meta = TRUE) {
      $this->Features[$Feature] = $Meta;
   }
   
   /**
   * Remove feature flag from this cache, for the specific feature
   * 
   * @param int $Feature feature contant
   */
   public function UnregisterFeature($Feature) {
      if (isset($this->Features[$Features]))
         unset($this->Features[$Feature]);
   }
   
   /**
   * Check whether this cache supports the specified feature
   * 
   * @param int $Feature feature constant
   * @return mixed $Meta returns the meta data supplied during RegisterFeature()
   */
   public function HasFeature($Feature) {
      return isset($this->Features[$Feature]) ? $this->Features[$Feature] : Gdn_Cache::CACHEOP_FAILURE;
   }
   
   protected function Failure($Message) {
      if (Debug())
         throw new Exception($Message);
      else
         return Gdn_Cache::CACHEOP_FAILURE;
   }
}