<?php
/**
 * Cache layer base class
 *
 * All cache objects should extend this to ensure a consistent public api for
 * caching.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0.10
 * @abstract
 */
abstract class Gdn_Cache {

    /** @var array List of cache containers. */
    protected $containers;

    /** @var array List of features this cache system supports. */
    protected $features;

    /** @var string Type of cache this this: one of CACHE_TYPE_MEMORY, CACHE_TYPE_FILE, CACHE_TYPE_NULL. */
    protected $cacheType;

    /** @var array Memory copy of store containers. */
    protected static $stores = array();

    /** Allows items to be internally compressed/decompressed. */
    const FEATURE_COMPRESS = 'f_compress';

    /** Allows items to auto-expire (seconds). */
    const FEATURE_EXPIRY = 'f_expiry';

    /** Allows set/get timeouts (seconds). */
    const FEATURE_TIMEOUT = 'f_timeout';

    /** Allows disabling usage of key prefix. */
    const FEATURE_NOPREFIX = 'f_noprefix';

    /** Allows forcing alternate key prefix. */
    const FEATURE_FORCEPREFIX = 'f_forceprefix';

    /** Allows querying DB for missing keys, or firing a callback. */
    const FEATURE_FALLBACK = 'f_fallback';

    /** In incr/decr ops, what should the initial value be. */
    const FEATURE_INITIAL = 'f_initial';

    /** Allows sharding large keys across all servers [Add,Store,Get,Replace,Remove]. */
    const FEATURE_SHARD = 'f_shard';

    /** Allows control over localcache usage. */
    const FEATURE_LOCAL = 'f_local';

    /** Location - SERVER:IP, Filepath, etc. */
    const CONTAINER_LOCATION = 'c_location';

    /** Persistent - Whether to use connect() or pconnect() where applicable. */
    const CONTAINER_PERSISTENT = 'c_persistent';

    /** Pool Size - When using pconnect(), how many connections should we use in the pool? */
    const CONTAINER_POOLSIZE = 'c_poolsize';

    /** Pool Key - When using pconnect(), what should the pool key look like? */
    const CONTAINER_POOLKEY = 'c_poolkey';

    /** Weight - Allows for differently weighted storage locations. */
    const CONTAINER_WEIGHT = 'c_weight';

    /** Persistent - Retry delay inverval in seconds. */
    const CONTAINER_RETRYINT = 'c_retryint';

    /** Timeout - How long to wait before timing out while connecting. */
    const CONTAINER_TIMEOUT = 'c_timeout';

    /** Online - If this container is available for requests. */
    const CONTAINER_ONLINE = 'c_online';

    /** Callback - Method to call if the location fails to be added. */
    const CONTAINER_CALLBACK = 'c_callback';

    /** Cache status. */
    const CACHEOP_FAILURE = false;

    /** Cache status. */
    const CACHEOP_SUCCESS = true;

    /** Cache type. */
    const CACHE_TYPE_MEMORY = 'ct_memory';

    /** Cache type. */
    const CACHE_TYPE_FILE = 'ct_file';

    /** Cache type. */
    const CACHE_TYPE_NULL = 'ct_null';

    /** Seconds. */
    const CACHE_EJECT_DURATION = 60;

    /** Seconds. */
    const APC_CACHE_DURATION = 300;

    /**  @var array Local in-memory cache of fetched data. This prevents duplicate gets to memcache. */
    protected static $localCache = array();

    /** @var bool  */
    public static $trace = true;

    /** @var array  */
    public static $trackGet = array();

    /** @var int  */
    public static $trackGets = 0;

    /** @var array  */
    public static $trackSet = array();

    /** @var int  */
    public static $trackSets = 0;

    /** @var int  */
    public static $trackTime = 0;

    /**
     *
     */
    public function __construct() {
        $this->containers = array();
        $this->features = array();
    }

    /**
     * Determines the currently installed cache solution and returns a fresh instance of its object.
     *
     * @return Gdn_Cache
     */
    public static function initialize($ForceEnable = false, $ForceMethod = false) {
        $AllowCaching = self::activeEnabled($ForceEnable);
        $ActiveCache = Gdn_Cache::activeCache();

        if ($ForceMethod !== false) {
            $ActiveCache = $ForceMethod;
        }
        $ActiveCacheClass = 'Gdn_'.ucfirst($ActiveCache);

        if (!$AllowCaching || !$ActiveCache || !class_exists($ActiveCacheClass)) {
            $CacheObject = new Gdn_Dirtycache();
        } else {
            $CacheObject = new $ActiveCacheClass();
        }

        // Null caches should not acount as enabled.
        if (!$ForceEnable && $CacheObject->type() === Gdn_Cache::CACHE_TYPE_NULL) {
            SaveToConfig('Cache.Enabled', false, false);
        }

        if (method_exists($CacheObject, 'Autorun')) {
            $CacheObject->autorun();
        }

        // This should only fire when cache is loading automatically
        if (!func_num_args() && Gdn::pluginManager() instanceof Gdn_PluginManager) {
            Gdn::pluginManager()->fireEvent('AfterActiveCache');
        }

        return $CacheObject;
    }

    /**
     * Gets the short name of the currently active cache.
     *
     * This method retrieves the name of the active cache according to the config file.
     * It fires an event thereafter, allowing that value to be overridden
     * by loaded plugins.
     *
     * @return string shortname of current auto active cache
     */
    public static function activeCache() {
        /*
         * There is a catch 22 with caching the config file. We need
         * an external way to define the cache layer before needing it
         * in the config.
         */

        if (defined('CACHE_METHOD_OVERRIDE')) {
            $ActiveCache = CACHE_METHOD_OVERRIDE;
        } else {
            $ActiveCache = C('Cache.Method', false);
        }

        // This should only fire when cache is loading automatically
        if (!func_num_args() && Gdn::pluginManager() instanceof Gdn_PluginManager) {
            Gdn::pluginManager()->EventArguments['ActiveCache'] = &$ActiveCache;
            Gdn::pluginManager()->fireEvent('BeforeActiveCache');
        }

        return $ActiveCache;
    }

    /**
     * Get the status of the active cache.
     *
     * Return whether or not the current cache method is enabled.
     *
     * @param type $ForceEnable
     * @return bool status of active cache
     */
    public static function activeEnabled($ForceEnable = false) {
        $AllowCaching = false;

        if (defined('CACHE_ENABLED_OVERRIDE')) {
            $AllowCaching |= CACHE_ENABLED_OVERRIDE;
        }

        $AllowCaching |= C('Cache.Enabled', false);
        $AllowCaching |= $ForceEnable;

        return (bool)$AllowCaching;
    }

    /**
     * Returns the storage data for the active cache.
     *
     * For FileCache, the folder. For Memcache, the server(s).
     *
     * @param type $ForceMethod
     * @return mixed Active Store Location
     */
    public static function activeStore($ForceMethod = null) {
        // Get the active cache name
        $ActiveCache = self::activeCache();
        if (!is_null($ForceMethod)) {
            $ActiveCache = $ForceMethod;
        }
        $ActiveCache = ucfirst($ActiveCache);

        // Overrides
        if (defined('CACHE_STORE_OVERRIDE') && defined('CACHE_METHOD_OVERRIDE') && CACHE_METHOD_OVERRIDE == $ActiveCache) {
            return unserialize(CACHE_STORE_OVERRIDE);
        }

        // Use APC cache?
        $apc = false;
        if (C('Garden.Apc', false) && C('Garden.Cache.ApcPrecache', false) && function_exists('apc_fetch')) {
            $apc = true;
        }

        $LocalStore = null;
        $ActiveStore = null;
        $ActiveStoreKey = "Cache.{$ActiveCache}.Store";

        // Check memory
        if (is_null($LocalStore)) {
            if (array_key_exists($ActiveCache, Gdn_Cache::$stores)) {
                $LocalStore = Gdn_Cache::$stores[$ActiveCache];
            }
        }

        // Check APC cache
        if (is_null($LocalStore) && $apc) {
            $LocalStore = apc_fetch($ActiveStoreKey);
            if ($LocalStore) {
                Gdn_Cache::$stores[$ActiveCache] = $LocalStore;
            }
        }

        if (is_array($LocalStore)) {
            // Convert to ActiveStore format (with 'Active' key)
            $Save = false;
            $ActiveStore = array();
            foreach ($LocalStore as $StoreServerName => &$StoreServer) {
                $IsDelayed = &$StoreServer['Delay'];
                $IsActive = &$StoreServer['Active'];

                if (is_numeric($IsDelayed)) {
                    if ($IsDelayed < time()) {
                        $IsActive = true;
                        $IsDelayed = false;
                        $StoreServer['Fails'] = 0;
                        $Save = true;
                    } else {
                        if ($IsActive) {
                            $IsActive = false;
                            $Save = true;
                        }
                    }
                }

                // Add active servers to ActiveStore array
                if ($IsActive) {
                    $ActiveStore[] = $StoreServer['Server'];
                }
            }

        }

        // No local copy, get from config
        if (is_null($ActiveStore)) {
            $ActiveStore = c($ActiveStoreKey, false);

            // Convert to LocalStore format
            $LocalStore = array();
            $ActiveStore = (array)$ActiveStore;
            foreach ($ActiveStore as $StoreServer) {
                $StoreServerName = md5($StoreServer);
                $LocalStore[$StoreServerName] = array(
                    'Server' => $StoreServer,
                    'Active' => true,
                    'Delay' => false,
                    'Fails' => 0
                );
            }

            $Save = true;
        }

        if ($Save) {
            // Save to memory
            Gdn_Cache::$stores[$ActiveCache] = $LocalStore;

            // Save back to APC for later
            if ($apc) {
                apc_store($ActiveStoreKey, $LocalStore, Gdn_Cache::APC_CACHE_DURATION);
            }
        }

        return $ActiveStore;
    }

    /**
     * Register a temporary server connection failure.
     *
     * This method will attempt to temporarily excise the offending server from
     * the connect roster for a period of time.
     *
     * @param string $server
     */
    public function fail($server) {

        // Use APC?
        $apc = false;
        if (C('Garden.Apc', false) && function_exists('apc_fetch')) {
            $apc = true;
        }

        // Get the active cache name
        $activeCache = Gdn_Cache::activeCache();
        $activeCache = ucfirst($activeCache);
        $activeStoreKey = "Cache.{$activeCache}.Store";

        // Get the local store.
        $localStore = val($activeCache, Gdn_Cache::$stores, null);
        if (is_null($localStore)) {
            Gdn_Cache::activeStore();
            $localStore = val($activeCache, Gdn_Cache::$stores, null);
            if (is_null($localStore)) {
                return false;
            }
        }

        $storeServerName = md5($server);
        if (!array_key_exists($storeServerName, $localStore)) {
            return false;
        }

        $storeServer = &$localStore[$storeServerName];
        $isActive = &$storeServer['Active'];
        if (!$isActive) {
            return false;
        }

        $fails = &$storeServer['Fails'];
        $fails++;
        $active = $isActive ? 'active' : 'inactive';

        // Check if we need to deactivate for 5 minutes
        if ($isActive && $storeServer['Fails'] > 3) {
            $isActive = false;
            $storeServer['Delay'] = time() + Gdn_Cache::CACHE_EJECT_DURATION;
        }

        // Save
        Gdn_Cache::$stores[$activeCache] = $localStore;

        // Save to APC
        if ($apc) {
            apc_store($activeStoreKey, $localStore, Gdn_Cache::APC_CACHE_DURATION);
        }

        return true;
    }

    /**
     * Returns a constant describing the type of cache implementation this object represents.
     *
     * @return string Type of cache. One of CACHE_TYPE_MEMORY, CACHE_TYPE_FILE, CACHE_TYPE_NULL
     */
    public function type() {
        return $this->cacheType;
    }

    /**
     * Add a value to the cache.
     *
     * This fails if the item already exists in the cache.
     *
     * @param string $Key Cache key used for storage
     * @param mixed $Value Value to be cached
     * @param array $Options
     * @return boolean true on success or false on failure.
     */
    abstract public function add($Key, $Value, $Options = array());

    public function stripKey($Key, $Options) {
        $UsePrefix = !val(Gdn_Cache::FEATURE_NOPREFIX, $Options, false);
        $ForcePrefix = val(Gdn_Cache::FEATURE_FORCEPREFIX, $Options, null);

        if ($UsePrefix) {
            $Key = substr($Key, strlen($this->getPrefix($ForcePrefix)) + 1);
        }
        return $Key;

    }

    /**
     * Store a value in the cache.
     *
     * This works regardless of whether the item already exists in the cache.
     *
     * @param string $Key Cache key used for storage.
     * @param mixed $Value Value to be cached.
     * @param array $Options An array of cache feature constants.
     *   - FEATURE_COMPRESS: Allows items to be internally compressed/decompressed (bool).
     *   - FEATURE_EXPIRY: Allows items to autoexpire (seconds).
     *   - FEATURE_NOPREFIX: Allows disabling usage of key prefix (bool).
     *   - FEATURE_FORCEPREFIX: Allows forcing alternate key prefix (string).
     *   - FEATURE_FALLBACK: Allows querying DB for missing keys, or firing a callback (see Gdn_Cache->Fallback).
     * @return boolean true on success or false on failure.
     */
    abstract public function store($Key, $Value, $Options = array());

    /**
     * Check if a value exists in the cache.
     *
     * @param string $Key Cache key used for storage.
     * @return array array(key => value) for existing key or false if not found.
     */
    abstract public function exists($Key);

    /**
     * Retrieve a key's value from the cache.
     *
     * @param string $Key Cache key used for storage.
     * @param array $Options
     * @return mixed key value or false on failure or not found.
     */
    abstract public function get($Key, $Options = array());

    /**
     * Remove a key/value pair from the cache.
     *
     * @param string $Key Cache key used for storage.
     * @param array $Options
     * @return boolean true on success or false on failure.
     */
    abstract public function remove($Key, $Options = array());

    /**
     * Replace an existing key's value with the provided value.
     *
     * This will fail if the provided key does not already exist.
     *
     * @param string $Key Cache key used for storage.
     * @param mixed $Value Value to be cached.
     * @param array $Options
     * @return boolean true on success or false on failure.
     */
    abstract public function replace($Key, $Value, $Options = array());

    /**
     * Increment the value of the provided key by {@link $Amount}.
     *
     * This will fail if the key does not already exist. Cannot take the value
     * of $Key below 0.
     *
     * @param string $Key Cache key used for storage.
     * @param int $Amount Amount to shift value up.
     * @return int new value or false on failure.
     */
    abstract public function increment($Key, $Amount = 1, $Options = array());

    /**
     * Decrement the value of the provided key by {@link $Amount}.
     *
     * This will fail if the key does not already exist. Cannot take the value
     * of $Key below 0.
     *
     * @param string $Key Cache key used for storage.
     * @param int $Amount Amount to shift value down.
     * @return int new value or false on failure.
     */
    abstract public function decrement($Key, $Amount = 1, $Options = array());

    /**
     * Add a container to the cache pool.
     *
     * @param array $Options An array of options with container constants as keys.
     *  - CONTAINER_LOCATION: required. the location of the container. SERVER:IP, Filepath, etc.
     *  - CONTAINER_PERSISTENT: optional (default true). whether to use connect() or pconnect() where applicable.
     *  - CONTAINER_WEIGHT: optional (default 1). number of buckets to create for this server which in turn control its probability of it being selected.
     *  - CONTAINER_RETRYINT: optional (default 15s). controls how often a failed container will be retried, the default value is 15 seconds.
     *  - CONTAINER_TIMEOUT: optional (default 1s). amount of time to wait for connection to container before timing out.
     *  - CONTAINER_CALLBACK: optional (default null). callback to execute if container fails to open/connect.
     * @return boolean true on success or false on failure.
     */
    abstract public function addContainer($Options);

    /**
     * Invalidate all items in the cache.
     *
     * Gdn_Cache::Flush() invalidates all existing cache items immediately.
     * After invalidation none of the items will be returned in response to a
     * retrieval command (unless it's stored again under the same key after
     * Gdn_Cache::Flush() has invalidated the items).
     *
     * @return boolean true on success of false on failure.
     */
    abstract public function flush();

    /**
     *
     *
     * @param string $Key Cache key.
     * @param array $Options
     * @return mixed
     */
    protected function fallback($Key, $Options) {
        $Fallback = val(Gdn_Cache::FEATURE_FALLBACK, $Options, null);
        if (is_null($Fallback)) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $FallbackType = array_shift($Fallback);
        switch ($FallbackType) {
            case 'query':
                $QueryFallbackField = array_shift($Fallback);
                $QueryFallbackCode = array_shift($Fallback);
                $FallbackResult = Gdn::database()->query($QueryFallbackCode);
                if ($FallbackResult->numRows()) {
                    if (!is_null($QueryFallbackField)) {
                        $FallbackResult = val($QueryFallbackField, $FallbackResult->firstRow(DATASET_TYPE_ARRAY));
                    } else {
                        $FallbackResult = $FallbackResult->resultArray();
                    }
                }
                break;
            case 'callback':
                $CallbackFallbackMethod = array_shift($Fallback);
                $CallbackFallbackArgs = $Fallback;
                $FallbackResult = call_user_func_array($CallbackFallbackMethod, $CallbackFallbackArgs);
                break;
        }
        Gdn::cache()->store($Key, $FallbackResult);
        return $FallbackResult;
    }

    public function getPrefix($ForcePrefix = null, $WithRevision = true) {
        static $ConfigPrefix = false;

        // Allow overriding the prefix
        if (!is_null($ForcePrefix)) {
            return $ForcePrefix;
        }

        // Keep searching for the prefix until it is defined
        if ($ConfigPrefix === false) {
            // Allow vfcom-infrastructure to set the prefix automatically
            if (defined('FORCE_CACHE_PREFIX')) {
                $ConfigPrefix = FORCE_CACHE_PREFIX;
            }

            if ($ConfigPrefix === false) {
                $ConfigPrefix = C('Cache.Prefix', false);
            }
        }

        // Lookup Revision if we have a prefix.
        $RevisionNumber = false;
        if ($WithRevision && $ConfigPrefix !== false) {
            $CacheRevision = $this->getRevision($ConfigPrefix);
            if (!is_null($CacheRevision)) {
                $RevisionNumber = $CacheRevision;
            }
        }

        $Response = $ConfigPrefix;
        if ($WithRevision && $RevisionNumber !== false && $ConfigPrefix !== false) {
            $Response .= ".rev{$RevisionNumber}";
        }

        return ($ConfigPrefix === false) ? null : $Response;
    }

    public function getRevision($ForcePrefix = null, $Force = false) {
        static $CacheRevision = false;

        if ($CacheRevision === false || $Force) {
            $ConfigPrefix = $ForcePrefix;
            if (is_null($ConfigPrefix)) {
                $ConfigPrefix = $this->getPrefix(null, false);
            }

            $CacheRevisionKey = "{$ConfigPrefix}.Revision";
            $CacheRevision = $this->get($CacheRevisionKey, array(
                Gdn_Cache::FEATURE_NOPREFIX => true
            ));

            if ($CacheRevision === Gdn_Cache::CACHEOP_FAILURE) {
                $CacheRevision = 1;
            }
        }

        return $CacheRevision;
    }

    public function incrementRevision() {
        $CachePrefix = $this->getPrefix(null, false);
        if ($CachePrefix === false) {
            return false;
        }

        $CacheRevisionKey = "{$CachePrefix}.Revision";
        $Incremented = $this->increment($CacheRevisionKey, 1, array(
            Gdn_Cache::FEATURE_NOPREFIX => true
        ));

        if (!$Incremented) {
            return $this->store($CacheRevisionKey, 2, array(
                Gdn_Cache::FEATURE_NOPREFIX => true
            ));
        }

        return true;
    }

    public function makeKey($Key, $Options) {
        $UsePrefix = !val(Gdn_Cache::FEATURE_NOPREFIX, $Options, false);
        $ForcePrefix = val(Gdn_Cache::FEATURE_FORCEPREFIX, $Options, null);

        $Prefix = '';
        if ($UsePrefix) {
            $Prefix = $this->getPrefix($ForcePrefix).'!';
        }

        if (is_array($Key)) {
            $Result = array();
            foreach ($Key as $i => $v) {
                $Result[$i] = $Prefix.$v;
            }
        } else {
            $Result = $Prefix.$Key;
        }

        return $Result;
    }

    /*
     * Get the value of a store-specific option.
     *
     * The option keys are specific to the active cache type, but are always
     * stored under $Configuration['Cache'][ActiveCacheName]['Option'][*].
     *
     * @param string|integer $Option The option key to retrieve
     * @return mixed The value associated with the given option key
     */
    public function option($Option = null, $Default = null) {
        static $ActiveOptions = null;

        if (is_null($ActiveOptions)) {
            $ActiveCacheShortName = ucfirst($this->activeCache());
            $OptionKey = "Cache.{$ActiveCacheShortName}.Option";
            $ActiveOptions = c($OptionKey, array());
        }

        if (is_null($Option) || !array_key_exists($Option, $ActiveOptions)) {
            return $ActiveOptions;
        }

        return val($Option, $ActiveOptions, $Default);
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
    public function config($Key = null, $Default = null) {
        static $ActiveConfig = null;

        if (is_null($ActiveConfig)) {
            $ActiveCacheShortName = ucfirst($this->activeCache());
            $ConfigKey = "Cache.{$ActiveCacheShortName}.Config";
            $ActiveConfig = c($ConfigKey, array());
        }

        if (is_null($Key)) {
            return $ActiveConfig;
        }

        if (!array_key_exists($Key, $ActiveConfig)) {
            return $Default;
        }

        return val($Key, $ActiveConfig, $Default);
    }

    /**
     *
     */
    public static function trace($trace = null) {
        if (!is_null($trace)) {
            Gdn_Cache::$trace = (bool)$trace;
        }
        return Gdn_Cache::$trace;
    }

    protected function localGet($key) {
        if (!$this->hasFeature(Gdn_Cache::FEATURE_LOCAL)) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        if (!array_key_exists($key, self::$localCache)) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }
        return self::$localCache[$key];
    }

    protected function localSet($key, $value = null) {
        if (!$this->hasFeature(Gdn_Cache::FEATURE_LOCAL)) {
            return;
        }

        if (!is_array($key)) {
            $key = array($key => $value);
        }
        self::$localCache = array_merge(self::$localCache, $key);
    }

    /**
     * Clear local cache (process memory cache).
     */
    protected static function localClear() {
        self::$localCache = array();
    }

    /**
     * Flag this cache as being capable of performing a feature.
     *
     *  FEATURE_COMPRESS: this cache can compress and decompress values on the fly
     *  FEATURE_TIMEOUT: this cache can timeout while reading / writing
     *  FEATURE_EXPIRY: this cache can expire keys
     *
     * @param int $Feature One of the feature constants.
     * @param mixed $Meta An optional data to return when calling HasFeature. default true.
     */
    public function registerFeature($Feature, $Meta = true) {
        $this->features[$Feature] = $Meta;
    }

    /**
     * Remove feature flag from this cache, for the specific feature.
     *
     * @param int $Feature One of the feature constants.
     */
    public function unregisterFeature($Feature) {
        if (isset($this->features[$Feature])) {
            unset($this->features[$Feature]);
        }
    }

    /**
     * Check whether this cache supports the specified feature.
     *
     * @param int $Feature One of the feature constants.
     * @return mixed $Meta returns the meta data supplied during RegisterFeature().
     */
    public function hasFeature($Feature) {
        return isset($this->features[$Feature]) ? $this->features[$Feature] : Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     * Is the current cache available?
     *
     * @return boolean
     */
    public function online() {
        return true;
    }

    protected function failure($Message) {
        if (debug()) {
            throw new Exception($Message);
        } else {
            return Gdn_Cache::CACHEOP_FAILURE;
        }
    }
}
