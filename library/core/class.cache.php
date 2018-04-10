<?php
/**
 * Cache layer base class
 *
 * All cache objects should extend this to ensure a consistent public api for
 * caching.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
    protected static $stores = [];

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

    /** Max number of shards. 0 = no limit */
    const CACHE_SHARD_MAX_SHARDS = 0;

    /** Min size for a shard, in bytes. 0 = no limit */
    const CACHE_SHARD_MIN_SIZE = 10000;

    /** Auto shard keys that are larger than this, in bytes. */
    const CACHE_SHARD_AUTO_SIZE = 100000;

    /**  @var array Local in-memory cache of fetched data. This prevents duplicate gets to memcache. */
    protected static $localCache = [];

    /** @var bool  */
    public static $trace = true;

    /** @var array  */
    public static $trackGet = [];

    /** @var int  */
    public static $trackGets = 0;

    /** @var array  */
    public static $trackSet = [];

    /** @var int  */
    public static $trackSets = 0;

    /** @var int  */
    public static $trackTime = 0;

    /**
     *
     */
    public function __construct() {
        $this->containers = [];
        $this->features = [];
    }

    /**
     * Determines the currently installed cache solution and returns a fresh instance of its object.
     *
     * @return Gdn_Cache
     */
    public static function initialize($forceEnable = false, $forceMethod = false) {
        $allowCaching = self::activeEnabled($forceEnable);
        $activeCache = Gdn_Cache::activeCache();

        if ($forceMethod !== false) {
            $activeCache = $forceMethod;
        }
        $activeCacheClass = 'Gdn_'.ucfirst($activeCache);

        if (!$allowCaching || !$activeCache || !class_exists($activeCacheClass)) {
            $cacheObject = new Gdn_Dirtycache();
        } else {
            $cacheObject = new $activeCacheClass();
        }

        // Null caches should not acount as enabled.
        if (!$forceEnable && $cacheObject->type() === Gdn_Cache::CACHE_TYPE_NULL) {
            saveToConfig('Cache.Enabled', false, false);
        }

        if (method_exists($cacheObject, 'Autorun')) {
            $cacheObject->autorun();
        }

        return $cacheObject;
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
            $activeCache = CACHE_METHOD_OVERRIDE;
        } else {
            $activeCache = c('Cache.Method', false);
        }

        return $activeCache;
    }

    /**
     * Get the status of the active cache.
     *
     * Return whether or not the current cache method is enabled.
     *
     * @param type $forceEnable
     * @return bool status of active cache
     */
    public static function activeEnabled($forceEnable = false) {
        $allowCaching = false;

        if (defined('CACHE_ENABLED_OVERRIDE')) {
            $allowCaching |= CACHE_ENABLED_OVERRIDE;
        }

        $allowCaching |= c('Cache.Enabled', false);
        $allowCaching |= $forceEnable;

        return (bool)$allowCaching;
    }

    /**
     * Returns the storage data for the active cache.
     *
     * For FileCache, the folder. For Memcache, the server(s).
     *
     * @param type $forceMethod
     * @return mixed Active Store Location
     */
    public static function activeStore($forceMethod = null) {
        // Get the active cache name
        $activeCache = self::activeCache();
        if (!is_null($forceMethod)) {
            $activeCache = $forceMethod;
        }
        $activeCache = ucfirst($activeCache);

        // Overrides
        if (defined('CACHE_STORE_OVERRIDE') && defined('CACHE_METHOD_OVERRIDE') && CACHE_METHOD_OVERRIDE == $activeCache) {
            return unserialize(CACHE_STORE_OVERRIDE);
        }

        // Use APC cache?
        $apc = false;
        if (c('Garden.Apc', false) && c('Garden.Cache.ApcPrecache', false) && function_exists('apc_fetch')) {
            $apc = true;
        }

        $localStore = null;
        $activeStore = null;
        $activeStoreKey = "Cache.{$activeCache}.Store";

        // Check memory
        if (is_null($localStore)) {
            if (array_key_exists($activeCache, Gdn_Cache::$stores)) {
                $localStore = Gdn_Cache::$stores[$activeCache];
            }
        }

        // Check APC cache
        if (is_null($localStore) && $apc) {
            $localStore = apc_fetch($activeStoreKey);
            if ($localStore) {
                Gdn_Cache::$stores[$activeCache] = $localStore;
            }
        }

        if (is_array($localStore)) {
            // Convert to ActiveStore format (with 'Active' key)
            $save = false;
            $activeStore = [];
            foreach ($localStore as $storeServerName => &$storeServer) {
                $isDelayed = &$storeServer['Delay'];
                $isActive = &$storeServer['Active'];

                if (is_numeric($isDelayed)) {
                    if ($isDelayed < time()) {
                        $isActive = true;
                        $isDelayed = false;
                        $storeServer['Fails'] = 0;
                        $save = true;
                    } else {
                        if ($isActive) {
                            $isActive = false;
                            $save = true;
                        }
                    }
                }

                // Add active servers to ActiveStore array
                if ($isActive) {
                    $activeStore[] = $storeServer['Server'];
                }
            }

        }

        // No local copy, get from config
        if (is_null($activeStore)) {
            $activeStore = c($activeStoreKey, false);

            // Convert to LocalStore format
            $localStore = [];
            $activeStore = (array)$activeStore;
            foreach ($activeStore as $storeServer) {
                $storeServerName = md5($storeServer);
                $localStore[$storeServerName] = [
                    'Server' => $storeServer,
                    'Active' => true,
                    'Delay' => false,
                    'Fails' => 0
                ];
            }

            $save = true;
        }

        if ($save) {
            // Save to memory
            Gdn_Cache::$stores[$activeCache] = $localStore;

            // Save back to APC for later
            if ($apc) {
                apc_store($activeStoreKey, $localStore, Gdn_Cache::APC_CACHE_DURATION);
            }
        }

        return $activeStore;
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
        if (c('Garden.Apc', false) && function_exists('apc_fetch')) {
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
     * @param string $key Cache key used for storage
     * @param mixed $value Value to be cached
     * @param array $options
     * @return boolean true on success or false on failure.
     */
    abstract public function add($key, $value, $options = []);

    public function stripKey($key, $options) {
        $usePrefix = !val(Gdn_Cache::FEATURE_NOPREFIX, $options, false);
        $forcePrefix = val(Gdn_Cache::FEATURE_FORCEPREFIX, $options, null);

        if ($usePrefix) {
            $key = substr($key, strlen($this->getPrefix($forcePrefix)) + 1);
        }
        return $key;

    }

    /**
     * Store a value in the cache.
     *
     * This works regardless of whether the item already exists in the cache.
     *
     * @param string $key Cache key used for storage.
     * @param mixed $value Value to be cached.
     * @param array $options An array of cache feature constants.
     *   - FEATURE_COMPRESS: Allows items to be internally compressed/decompressed (bool).
     *   - FEATURE_EXPIRY: Allows items to autoexpire (seconds).
     *   - FEATURE_NOPREFIX: Allows disabling usage of key prefix (bool).
     *   - FEATURE_FORCEPREFIX: Allows forcing alternate key prefix (string).
     *   - FEATURE_FALLBACK: Allows querying DB for missing keys, or firing a callback (see Gdn_Cache->Fallback).
     * @return boolean true on success or false on failure.
     */
    abstract public function store($key, $value, $options = []);

    /**
     * Check if a value exists in the cache.
     *
     * @param string $Key Cache key used for storage.
     * @return array array(key => value) for existing key or false if not found.
     */
    abstract public function exists($key);

    /**
     * Retrieve a key's value from the cache.
     *
     * @param string $Key Cache key used for storage.
     * @param array $Options
     * @return mixed key value or false on failure or not found.
     */
    abstract public function get($key, $options = []);

    /**
     * Remove a key/value pair from the cache.
     *
     * @param string $Key Cache key used for storage.
     * @param array $Options
     * @return boolean true on success or false on failure.
     */
    abstract public function remove($key, $options = []);

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
    abstract public function replace($key, $value, $options = []);

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
    abstract public function increment($key, $amount = 1, $options = []);

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
    abstract public function decrement($key, $amount = 1, $options = []);

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
    abstract public function addContainer($options);

    /**
     * Invalidate all items in the cache.
     *
     * Gdn_Cache::flush() invalidates all existing cache items immediately.
     * After invalidation none of the items will be returned in response to a
     * retrieval command (unless it's stored again under the same key after
     * Gdn_Cache::flush() has invalidated the items).
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
    protected function fallback($key, $options) {
        $fallback = val(Gdn_Cache::FEATURE_FALLBACK, $options, null);
        if (is_null($fallback)) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $fallbackType = array_shift($fallback);
        switch ($fallbackType) {
            case 'query':
                $queryFallbackField = array_shift($fallback);
                $queryFallbackCode = array_shift($fallback);
                $fallbackResult = Gdn::database()->query($queryFallbackCode);
                if ($fallbackResult->numRows()) {
                    if (!is_null($queryFallbackField)) {
                        $fallbackResult = val($queryFallbackField, $fallbackResult->firstRow(DATASET_TYPE_ARRAY));
                    } else {
                        $fallbackResult = $fallbackResult->resultArray();
                    }
                }
                break;
            case 'callback':
                $callbackFallbackMethod = array_shift($fallback);
                $callbackFallbackArgs = $fallback;
                $fallbackResult = call_user_func_array($callbackFallbackMethod, $callbackFallbackArgs);
                break;
        }
        Gdn::cache()->store($key, $fallbackResult);
        return $fallbackResult;
    }

    public function getPrefix($forcePrefix = null, $withRevision = true) {
        static $configPrefix = false;

        // Allow overriding the prefix
        if (!is_null($forcePrefix)) {
            return $forcePrefix;
        }

        // Keep searching for the prefix until it is defined
        if ($configPrefix === false) {
            // Allow vfcom-infrastructure to set the prefix automatically
            if (defined('FORCE_CACHE_PREFIX')) {
                $configPrefix = FORCE_CACHE_PREFIX;
            }

            if ($configPrefix === false) {
                $configPrefix = c('Cache.Prefix', false);
            }
        }

        // Lookup Revision if we have a prefix.
        $revisionNumber = false;
        if ($withRevision && $configPrefix !== false) {
            $cacheRevision = $this->getRevision($configPrefix);
            if (!is_null($cacheRevision)) {
                $revisionNumber = $cacheRevision;
            }
        }

        $response = $configPrefix;
        if ($withRevision && $revisionNumber !== false && $configPrefix !== false) {
            $response .= ".rev{$revisionNumber}";
        }

        return ($configPrefix === false) ? null : $response;
    }

    public function getRevision($forcePrefix = null, $force = false) {
        static $cacheRevision = false;

        if ($cacheRevision === false || $force) {
            $configPrefix = $forcePrefix;
            if (is_null($configPrefix)) {
                $configPrefix = $this->getPrefix(null, false);
            }

            $cacheRevisionKey = "{$configPrefix}.Revision";
            $cacheRevision = $this->get($cacheRevisionKey, [
                Gdn_Cache::FEATURE_NOPREFIX => true
            ]);

            if ($cacheRevision === Gdn_Cache::CACHEOP_FAILURE) {
                $cacheRevision = 1;
            }
        }

        return $cacheRevision;
    }

    public function incrementRevision() {
        $cachePrefix = $this->getPrefix(null, false);
        if ($cachePrefix === false) {
            return false;
        }

        $cacheRevisionKey = "{$cachePrefix}.Revision";
        $incremented = $this->increment($cacheRevisionKey, 1, [
            Gdn_Cache::FEATURE_NOPREFIX => true
        ]);

        if (!$incremented) {
            return $this->store($cacheRevisionKey, 2, [
                Gdn_Cache::FEATURE_NOPREFIX => true
            ]);
        }

        return true;
    }

    public function makeKey($key, $options) {
        $usePrefix = !val(Gdn_Cache::FEATURE_NOPREFIX, $options, false);
        $forcePrefix = val(Gdn_Cache::FEATURE_FORCEPREFIX, $options, null);

        $prefix = '';
        if ($usePrefix) {
            $prefix = $this->getPrefix($forcePrefix).'!';
        }

        if (is_array($key)) {
            $result = [];
            foreach ($key as $i => $v) {
                $result[$i] = $prefix.$v;
            }
        } else {
            $result = $prefix.$key;
        }

        // Make sure key is valid: no control characters or whitespace and no more than 250 characters.
        // See https://github.com/memcached/memcached/blob/master/doc/protocol.txt
        $result = trim($result);
        if (unicodeRegexSupport()) {
            // No whitespace or control characters.
            $result = preg_replace('/[\p{Z}\p{C}]+/u', '-', $result);
        } else {
            // No whitespace.
            $result = preg_replace('/\s+/', '-', $result);
        }

        if (strlen($result) > 250) {
            $result = substr($result, 0, 250);
        }

        return $result;
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
    public function option($option = null, $default = null) {
        static $activeOptions = null;

        if (is_null($activeOptions)) {
            $activeCacheShortName = ucfirst($this->activeCache());
            $optionKey = "Cache.{$activeCacheShortName}.Option";
            $activeOptions = c($optionKey, []);
        }

        if (is_null($option)) {
            return $activeOptions;
        }

        return val($option, $activeOptions, $default);
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
    public function config($key = null, $default = null) {
        static $activeConfig = null;

        if (is_null($activeConfig)) {
            $activeCacheShortName = ucfirst($this->activeCache());
            $configKey = "Cache.{$activeCacheShortName}.Config";
            $activeConfig = c($configKey, []);
        }

        if (is_null($key)) {
            return $activeConfig;
        }

        if (!array_key_exists($key, $activeConfig)) {
            return $default;
        }

        return val($key, $activeConfig, $default);
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
            $key = [$key => $value];
        }
        self::$localCache = array_merge(self::$localCache, $key);
    }

    /**
     * Clear local cache (process memory cache).
     */
    protected static function localClear() {
        self::$localCache = [];
    }

    /**
     * Flag this cache as being capable of performing a feature.
     *
     *  FEATURE_COMPRESS: this cache can compress and decompress values on the fly
     *  FEATURE_TIMEOUT: this cache can timeout while reading / writing
     *  FEATURE_EXPIRY: this cache can expire keys
     *
     * @param int $feature One of the feature constants.
     * @param mixed $meta An optional data to return when calling HasFeature. default true.
     */
    public function registerFeature($feature, $meta = true) {
        $this->features[$feature] = $meta;
    }

    /**
     * Remove feature flag from this cache, for the specific feature.
     *
     * @param int $feature One of the feature constants.
     */
    public function unregisterFeature($feature) {
        if (isset($this->features[$feature])) {
            unset($this->features[$feature]);
        }
    }

    /**
     * Check whether this cache supports the specified feature.
     *
     * @param int $feature One of the feature constants.
     * @return mixed $Meta returns the meta data supplied during registerFeature().
     */
    public function hasFeature($feature) {
        return isset($this->features[$feature]) ? $this->features[$feature] : Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     * Is the current cache available?
     *
     * @return boolean
     */
    public function online() {
        return true;
    }

    protected function failure($message) {
        if (debug()) {
            throw new Exception($message);
        } else {
            return Gdn_Cache::CACHEOP_FAILURE;
        }
    }
}
