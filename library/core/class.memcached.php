<?php
/**
 * Gdn_Memcached & MemcachedShard.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Cache Layer: Memcached
 *
 * A cache layer that stores its items in memcached and uses libmemcached to interact with the daemons.
 */
class Gdn_Memcached extends Gdn_Cache {

    /** Memcached option. */
    const OPT_MOD_SPLIT = 65000;

    /** Memcached option. */
    const OPT_PASSTHRU_CONTAINER = 'passthru';

    /** O_CREATE. */
    const O_CREATE = 1;

    /** @var Memcached  */
    private $memcache;

    /** @var array */
    protected $weightedContainers;

    /**
     * Setup our Memcached configuration.
     */
    public function __construct() {
        parent::__construct();
        $this->cacheType = Gdn_Cache::CACHE_TYPE_MEMORY;

        // Allow persistent connections

        /**
         * EXTREMELY IMPORTANT NOTE!!
         * There is a bug in libmemcached which causes persistent connections not
         * to be recycled, thereby initiating a spiral of memory loss. DO NOT USE
         * THIS UNLESS YOU ARE QUITE CERTAIN THIS IS SOLVED!
         */

        $persist = $this->config(Gdn_Cache::CONTAINER_PERSISTENT);
        if ($persist) {
            $poolSize = $this->config(Gdn_Cache::CONTAINER_POOLSIZE, 10);
            $poolKeyFormat = $this->config(Gdn_Cache::CONTAINER_POOLKEY, "cachekey-%d");
            $poolIndex = mt_rand(1, $poolSize);
            $poolKey = sprintf($poolKeyFormat, $poolIndex);
            $this->memcache = new Memcached($poolKey);
        } else {
            $this->memcache = new Memcached;
        }

        $this->registerFeature(Gdn_Cache::FEATURE_COMPRESS, Memcached::OPT_COMPRESSION);
        $this->registerFeature(Gdn_Cache::FEATURE_EXPIRY);
        $this->registerFeature(Gdn_Cache::FEATURE_TIMEOUT);
        $this->registerFeature(Gdn_Cache::FEATURE_NOPREFIX);
        $this->registerFeature(Gdn_Cache::FEATURE_FORCEPREFIX);
        $this->registerFeature(Gdn_Cache::FEATURE_SHARD);

        if (c('Garden.Cache.Local', true)) {
            $this->registerFeature(Gdn_Cache::FEATURE_LOCAL);
        }

        $this->StoreDefaults = [
            Gdn_Cache::FEATURE_COMPRESS => false,
            Gdn_Cache::FEATURE_TIMEOUT => false,
            Gdn_Cache::FEATURE_EXPIRY => false,
            Gdn_Cache::FEATURE_NOPREFIX => false,
            Gdn_Cache::FEATURE_FORCEPREFIX => null,
            Gdn_Cache::FEATURE_SHARD => false,
            Gdn_Cache::FEATURE_LOCAL => true
        ];

        $defaultOptions = [
            Memcached::OPT_COMPRESSION => true,
            Memcached::OPT_DISTRIBUTION => Memcached::DISTRIBUTION_CONSISTENT,
            Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
            Memcached::OPT_NO_BLOCK => true,
            Memcached::OPT_TCP_NODELAY => true,
            Memcached::OPT_CONNECT_TIMEOUT => 2000,
            Memcached::OPT_SERVER_FAILURE_LIMIT => 2
        ];

        $options = $this->option(null, []);
        $options = array_replace($defaultOptions, $options);

        foreach ($options as $option => $optValue) {
            $this->memcache->setOption($option, $optValue);
        }

    }

    /**
     * Reads in known/config servers and adds them to the instance.
     *
     * This method is called when the cache object is invoked by the framework
     * automatically, and needs to configure itself from the values in the global
     * config file.
     */
    public function autorun() {
        $servers = Gdn_Cache::activeStore('memcached');
        if (!is_array($servers)) {
            $servers = explode(',', $servers);
        }

        // No servers, cache temporarily offline
        if (!sizeof($servers)) {
            saveToConfig('Cache.Enabled', false, false);
            return false;
        }

        // Persistent, and already have servers. Short circuit adding.
        if ($this->config(Gdn_Cache::CONTAINER_PERSISTENT) && count($this->servers())) {
            return true;
        }

        $keys = [
            Gdn_Cache::CONTAINER_LOCATION,
            Gdn_Cache::CONTAINER_PERSISTENT,
            Gdn_Cache::CONTAINER_WEIGHT,
            Gdn_Cache::CONTAINER_TIMEOUT,
            Gdn_Cache::CONTAINER_ONLINE,
            Gdn_Cache::CONTAINER_CALLBACK
        ];
        foreach ($servers as $cacheServer) {
            $cacheServer = explode(' ', $cacheServer);
            $cacheServer = array_pad($cacheServer, count($keys), null);
            $cacheServer = array_combine($keys, $cacheServer);

            foreach ($keys as $keyName) {
                $value = val($keyName, $cacheServer, null);
                if (is_null($value)) {
                    unset($cacheServer[$keyName]);
                }
            }

            $this->addContainer($cacheServer);
        }
    }

    /**
     * const CONTAINER_LOCATION = 1;
     * const CONTAINER_PERSISTENT = 2;
     * const CONTAINER_WEIGHT = 3;
     * const CONTAINER_TIMEOUT = 4;
     * const CONTAINER_ONLINE = 5;
     * const CONTAINER_CALLBACK = 6;
     */
    public function addContainer($options) {

        $required = [
            Gdn_Cache::CONTAINER_LOCATION
        ];

        $keyedRequirements = array_fill_keys($required, 1);
        if (sizeof(array_intersect_key($options, $keyedRequirements)) != sizeof($required)) {
            $missing = implode(", ", array_keys(array_diff_key($keyedRequirements, $options)));
            return $this->failure("Required parameters not supplied. Missing: {$missing}");
        }

        $cacheLocation = val(Gdn_Cache::CONTAINER_LOCATION, $options);

        // Merge the options array with our local defaults
        $defaults = [
            Gdn_Cache::CONTAINER_WEIGHT => 1
        ];

        $finalContainer = array_merge($defaults, $options);
        $this->containers[$cacheLocation] = $finalContainer;
        $pathInfo = explode(':', $cacheLocation);

        $serverHostname = val(0, $pathInfo);
        $serverPort = val(1, $pathInfo, 11211);

        $addServerResult = $this->memcache->addServer(
            $serverHostname,
            $serverPort,
            val(Gdn_Cache::CONTAINER_WEIGHT, $finalContainer, 1)
        );

        if (!$addServerResult) {
            $callback = val(Gdn_Cache::CONTAINER_CALLBACK, $finalContainer, null);
            if (!is_null($callback)) {
                call_user_func($callback, $serverHostname, $serverPort);
            }

            return Gdn_Cache::CACHEOP_FAILURE;
        }

        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     * Get server list with mapping keys.
     */
    public function shardMap() {
        static $servers = null;
        if (is_null($servers)) {
            $serverList = $this->servers();
            $numShards = count($serverList);
            $servers = [];

            if ($this->canAutoShard()) {
                // Use getServerByKey to determine server keys

                // Here we loop until we have found keys for all the servers

                $i = $numShards * 10;
                do {
                    $i--; // limited iterations
                    $shardKey = betterRandomString(6, 'a0');
                    $shardServer = $this->memcache->getServerByKey($shardKey);
                    $shardServerName = $shardServer['host'];
                    if ($shardServerName) {
                        $servers[$shardServerName] = $shardKey;
                    }
                } while ($i > 0 && count($servers) < $numShards);

            }

            if (!count($servers)) {
                // Use random server keys and hope for the best

                foreach ($serverList as $server) {
                    $serverName = $server['host'];
                    $servers[$serverName] = betterRandomString(6, 'a0');
                }
            }

        }
        return $servers;
    }

    /**
     * Determine if we can auto distribute shards.
     *
     * Version of memcached prior to v2.2 could not be relied upon to return
     * reliable results for getServerByKey(), so we have to generate random
     * server keys and hope for the best.
     *
     * @static boolean $canAutoShard
     * @return boolean
     */
    public function canAutoShard() {
        static $canAutoShard = null;
        if (is_null($canAutoShard)) {
            $mcversion = phpversion('memcached');
            $canAutoShard = version_compare($mcversion, '2.2', '>=');
        }
        return $canAutoShard;
    }

    /**
     * Shard a key/value and create its manifest key.
     *
     *  array
     *    type     // 'shard'
     *    hash     // hash of serialized data
     *    size     // number of bytes in serialized data
     *    shards   // sharded data
     *      [...]
     *        server  // server key, for assigning a server
     *        key     // actual key
     *        data    // data shard
     *    keys     // list of shard keys grouped by server key
     *      [server key]
     *        [...]
     *          key
     *
     * @param string $key data key
     * @param mixed $value data value to shard
     * @param integer $keySize value size
     * @param int|boolean $shards number of shards, or simply bool true
     * @return array
     */
    public function shard($key, $value, $keySize, $shards = true) {

        $shardMap = $this->shardMap();
        $mapSize = count($shardMap);

        // Apply automated shard limits if shard allocation is automatic
        if (!is_numeric($shards)) {

            // By default, shard to all servers
            $shards = $mapSize;

            // Don't over-shard (make sure shards are large enough to warrant their own key)
            $shardSize = ceil($keySize / $shards);
            if (Gdn_Cache::CACHE_SHARD_MIN_SIZE > 0 && $shardSize < Gdn_Cache::CACHE_SHARD_MIN_SIZE) {
                $shards = ceil($keySize / Gdn_Cache::CACHE_SHARD_MIN_SIZE);
            }

            // Limit the number of shards to a sane value to reduce overhead (but only if $shards wasn't explicitly specified)
            if (Gdn_Cache::CACHE_SHARD_MAX_SHARDS > 0 && count($shardMap) > Gdn_Cache::CACHE_SHARD_MAX_SHARDS) {
                $shards = Gdn_Cache::CACHE_SHARD_MAX_SHARDS;
            }

        }

        // Don't shard to more servers than we know about
        if ($shards > $mapSize) {
            $shards = $mapSize;
        }

        // If we're sharding to less servers than we know about, pick some random ones
        if ($shards < $mapSize) {
            $shardSlices = array_rand($shardMap, $shards);
            $shardMap = array_intersect_key($shardMap, array_fill_keys($shardSlices, true));
        }
        $mapSize = count($shardMap);

        // If we're not precisely targeting keys, add a shard for safety
        if (!$this->canAutoShard()) {
            $shards = $mapSize + 1;
        }

        $shardMap = array_values($shardMap);

        // Prepare manifest
        $data = serialize($value);
        $hash = md5($data);
        $keySize = strlen($data);
        $manifest = new MemcachedShard();
        $manifest->hash = $hash;
        $manifest->size = $keySize;

        // Determine chunk size
        $chunk = ceil($keySize / $shards);

        // Write keys
        $chunks = str_split($data, $chunk);
        $j = 0;
        for ($i = 0; $i < $shards; $i++) {
            $shardKey = sprintf('%s-shard%d', $key, $i);
            $serverKey = $shardMap[$j];
            $manifest->shards[] = [
                'server' => $serverKey,
                'key' => $shardKey,
                'data' => $chunks[$i]
            ];
            if (!array_key_exists($serverKey, $manifest->keys)) {
                $manifest->keys[$serverKey] = [];
            }
            $manifest->keys[$serverKey][] = $shardKey;
            $j++;
            if ($j >= $mapSize) {
                $j = 0;
            }
        }

        return $manifest;
    }

    /**
     *
     *
     * @param string $key
     * @param mixed $value
     * @param array $options
     * @return bool
     */
    public function add($key, $value, $options = []) {
        if (!$this->online()) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $finalOptions = array_merge($this->StoreDefaults, $options);

        $expiry = val(Gdn_Cache::FEATURE_EXPIRY, $finalOptions, 0);
        $useLocal = (bool)$finalOptions[Gdn_Cache::FEATURE_LOCAL];

        $realKey = $this->makeKey($key, $finalOptions);

        // Sharding, write real keys
        if (array_key_exists(Gdn_Cache::FEATURE_SHARD, $finalOptions) && $shards = $finalOptions[Gdn_Cache::FEATURE_SHARD]) {
            $manifest = $this->shard($realKey, $value, $shards);
            $shards = $manifest->shards;
            unset($manifest->shards);

            // Attempt to write manifest
            $added = $this->memcache->add($realKey, $manifest, $expiry);

            // Check if things went ok
            $ok = $this->lastAction($realKey);
            if (!$ok || !$added) {
                return Gdn_Cache::CACHEOP_FAILURE;
            }

            // Write real keys
            foreach ($shards as $shard) {
                $this->memcache->setByKey($shard['server'], $shard['key'], $shard['data'], $expiry);
            }
            unset($shards);

            if ($useLocal) {
                $this->localSet($realKey, $value);
            }
            return Gdn_Cache::CACHEOP_SUCCESS;
        }

        $stored = $this->memcache->add($realKey, $value, $expiry);

        // Check if things went ok
        $ok = $this->lastAction($realKey);
        if (!$ok) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        if ($stored) {
            if ($useLocal) {
                $this->localSet($realKey, $value);
            }
            return Gdn_Cache::CACHEOP_SUCCESS;
        }
        return Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @param array $options
     * @return bool
     */
    public function store($key, $value, $options = []) {
        if (!$this->online()) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $finalOptions = array_merge($this->StoreDefaults, $options);

        $expiry = (int)val(Gdn_Cache::FEATURE_EXPIRY, $finalOptions, 0);
        $useLocal = (bool)$finalOptions[Gdn_Cache::FEATURE_LOCAL];

        $realKey = $this->makeKey($key, $finalOptions);

        // Should auto sharding be enabled?
        $keySize = strlen(serialize($value));
        if ($this->hasFeature(Gdn_Cache::FEATURE_SHARD) && $keySize > Gdn_Cache::CACHE_SHARD_AUTO_SIZE) {
            $finalOptions[Gdn_Cache::FEATURE_SHARD] = true;
        }

        // Sharding, write real keys and manifest
        if (array_key_exists(Gdn_Cache::FEATURE_SHARD, $finalOptions) && $shards = $finalOptions[Gdn_Cache::FEATURE_SHARD]) {
            $manifest = $this->shard($realKey, $value, $keySize, $shards);
            $shards = $manifest->shards;
            unset($manifest->shards);

            // Attempt to write manifest
            $added = $this->memcache->set($realKey, $manifest, $expiry);

            // Check if things went ok
            $ok = $this->lastAction($realKey);
            if (!$ok || !$added) {
                return Gdn_Cache::CACHEOP_FAILURE;
            }

            // Write real keys
            foreach ($shards as $shard) {
                $this->memcache->setByKey($shard['server'], $shard['key'], $shard['data'], $expiry);
            }
            unset($shards);

            if ($useLocal) {
                $this->localSet($realKey, $value);
            }
            return Gdn_Cache::CACHEOP_SUCCESS;
        }

        // Unsharded, write key
        $stored = $this->memcache->set($realKey, $value, $expiry);

        // Check if things went ok
        $ok = $this->lastAction($realKey);
        if (!$ok) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        if ($stored) {
            if ($useLocal) {
                $this->localSet($realKey, $value);
            }
            return Gdn_Cache::CACHEOP_SUCCESS;
        }
        return Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param string $key
     * @param array $options
     * @return array|bool|mixed
     */
    public function get($key, $options = []) {
        if (!$this->online()) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $startTime = microtime(true);

        $finalOptions = array_merge($this->StoreDefaults, $options);

        $useLocal = (bool)$finalOptions[Gdn_Cache::FEATURE_LOCAL];

        $localData = [];
        $realKeys = [];
        if (is_array($key)) {
            $multi = true;
            foreach ($key as $multiKey) {
                $realKey = $this->makeKey($multiKey, $finalOptions);

                // Skip this key if we already have it
                if ($useLocal) {
                    $local = $this->localGet($realKey);
                    if ($local !== Gdn_Cache::CACHEOP_FAILURE) {
                        $localData[$realKey] = $local;
                        continue;
                    }
                }
                $realKeys[] = $realKey;
            }
        } else {
            $multi = false;
            $realKey = $this->makeKey($key, $finalOptions);

            // Completely short circuit if we already have everything
            if ($useLocal) {
                $local = $this->localGet($realKey);
                if ($local !== false) {
                    return $local;
                }
            }

            $realKeys = [$realKey];
        }

        $data = [];
        $hitCache = false;
        $numKeys = sizeof($realKeys);
        if ($numKeys) {
            $hitCache = true;
            if ($numKeys > 1) {
                $data = $this->memcache->getMulti($realKeys);
                $ok = $this->lastAction();
            } else {
                $data = $this->memcache->get($realKey);
                // Check if things went ok
                $ok = $this->lastAction($realKey);
                $data = [$realKey => $data];
            }

            if (!$ok) {
                return Gdn_Cache::CACHEOP_FAILURE;
            }

            $storeData = [];
            foreach ($data as $localKey => &$localValue) {
                // Is this a sharded key manifest?
                if (is_object($localValue) && $localValue instanceof MemcachedShard) {
                    $manifest = $localValue;

                    // MultiGet sub-keys
                    $shardKeys = [];
                    foreach ($manifest->keys as $serverKey => $keys) {
                        $serverKeys = $this->memcache->getMultiByKey($serverKey, $keys);
                        $shardKeys = array_merge($shardKeys, $serverKeys);
                    }
                    ksort($shardKeys, SORT_NATURAL);

                    // Check subkeys for validity
                    $shardData = implode('', array_values($shardKeys));
                    unset($shardKeys);
                    $dataHash = md5($shardData);
                    if ($dataHash != $manifest->hash) {
                        continue;
                    }

                    $localValue = unserialize($shardData);
                }

                if ($localValue !== false) {
                    $storeData[$localKey] = $localValue;
                }

            }
            $data = $storeData;
            unset($storeData);

            // Cache in process memory
            if ($useLocal && sizeof($data)) {
                $this->localSet($data);
            }
        }

        // Merge in local data
        $data = array_merge($data, $localData);

        // Track debug stats
        $elapsedTime = microtime(true) - $startTime;
        if (Gdn_Cache::$trace) {
            Gdn_Cache::$trackTime += $elapsedTime;
            Gdn_Cache::$trackGets++;

            $keyTime = sizeof($realKeys) ? $elapsedTime / sizeof($realKeys) : $elapsedTime;
            foreach ($realKeys as $realKey) {
                touchValue($realKey, Gdn_Cache::$trackGet, [
                    'hits' => 0,
                    'time' => 0,
                    'keysize' => null,
                    'transfer' => 0,
                    'wasted' => 0
                ]);

                $keyData = val($realKey, $data, false);
                Gdn_Cache::$trackGet[$realKey]['hits']++;
                Gdn_Cache::$trackGet[$realKey]['time'] += $keyTime;

                if ($keyData !== false) {
                    $keyData = serialize($keyData);

                    $keySize = strlen($keyData);
                    if (is_null(Gdn_Cache::$trackGet[$realKey]['keysize'])) {
                        Gdn_Cache::$trackGet[$realKey]['keysize'] = $keySize;
                    } else {
                        Gdn_Cache::$trackGet[$realKey]['wasted'] += $keySize;
                    }

                    Gdn_Cache::$trackGet[$realKey]['transfer'] += Gdn_Cache::$trackGet[$realKey]['keysize'];
                }
            }
        }

        // Miss: return the fallback
        if ($data === false) {
            return $this->fallback($key, $options);
        }

        // Hit: Single key. Return the value
        if (!$multi) {
            $val = sizeof($data) ? array_pop($data) : false;
            return $val;
        }

        // Hit: Multi key. Return stripped array.
        $dataStripped = [];
        foreach ($data as $index => $value) {
            $dataStripped[$this->stripKey($index, $finalOptions)] = $value;
        }
        $data = $dataStripped;
        unset($dataStripped);
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key, $options = []) {
        if (!$this->online()) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        return ($this->get($key, $options) === Gdn_Cache::CACHEOP_FAILURE) ? Gdn_Cache::CACHEOP_FAILURE : Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key, $options = []) {
        if (!$this->online()) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $finalOptions = array_merge($this->StoreDefaults, $options);

        $realKey = $this->makeKey($key, $finalOptions);
        $deleted = $this->memcache->delete($realKey);

        // Check if things went ok
        $ok = $this->lastAction($realKey);
        if (!$ok) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        unset(Gdn_Cache::$localCache[$realKey]);
        return ($deleted) ? Gdn_Cache::CACHEOP_SUCCESS : Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     * {@inheritdoc}
     */
    public function replace($key, $value, $options = []) {
        if (!$this->online()) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        return $this->store($key, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function increment($key, $amount = 1, $options = []) {
        if (!$this->online()) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $finalOptions = array_merge($this->StoreDefaults, $options);

        $initial = val(Gdn_Cache::FEATURE_INITIAL, $finalOptions, 0);
        $expiry = val(Gdn_Cache::FEATURE_EXPIRY, $finalOptions, 0);
        $requireBinary = $initial || $expiry;
        $initial = !is_null($initial) ? $initial : 0;
        $expiry = !is_null($expiry) ? $expiry : 0;

        $tryBinary = $this->option(Memcached::OPT_BINARY_PROTOCOL, false) & $requireBinary;
        $realKey = $this->makeKey($key, $finalOptions);
        switch ($tryBinary) {
            case false:
                $incremented = $this->memcache->increment($realKey, $amount);
                if ($incremented === false && $initial) {
                    $incremented = $this->memcache->set($realKey, $initial);
                    if ($incremented) {
                        $incremented = $initial;
                    }
                }
                break;
            case true:
                $incremented = $this->memcache->increment($realKey, $amount, $initial, $expiry);
                break;
        }

        // Check if things went ok
        $ok = $this->lastAction($realKey);
        if (!$ok) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        if ($incremented !== false) {
            Gdn_Cache::localSet($realKey, $incremented);
            return $incremented;
        }
        return Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     * {@inheritdoc}
     */
    public function decrement($key, $amount = 1, $options = []) {
        if (!$this->online()) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $finalOptions = array_merge($this->StoreDefaults, $options);

        $initial = val(Gdn_Cache::FEATURE_INITIAL, $finalOptions, null);
        $expiry = val(Gdn_Cache::FEATURE_EXPIRY, $finalOptions, null);
        $requireBinary = $initial || $expiry;
        $initial = !is_null($initial) ? $initial : 0;
        $expiry = !is_null($expiry) ? $expiry : 0;

        $tryBinary = $this->option(Memcached::OPT_BINARY_PROTOCOL, false) & $requireBinary;
        $realKey = $this->makeKey($key, $finalOptions);
        switch ($tryBinary) {
            case false:
                $decremented = $this->memcache->decrement($realKey, $amount);
                if (is_null($decremented) && $initial) {
                    $decremented = $this->memcache->set($realKey, $initial);
                    if ($decremented) {
                        $decremented = $initial;
                    }
                }
                break;
            case true:
                $decremented = $this->memcache->decrement($realKey, $amount, $initial, $expiry);
                break;
        }

        // Check if things went ok
        $ok = $this->lastAction($realKey);
        if (!$ok) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        if ($decremented !== false) {
            Gdn_Cache::localSet($realKey, $decremented);
            return $decremented;
        }
        return Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     * {@inheritdoc}
     */
    public function flush() {
        return $this->memcache->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function lastAction($key = null) {
        $lastCode = $this->memcache->getResultCode();

        if ($lastCode == 47 || $lastCode == 35) {
            if ($key) {
                $server = $this->memcache->getServerByKey($key);
                $host = $server['host'];
                $port = $server['port'];
                $this->fail("{$host}:{$port}");
            }
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function online() {
        return (bool)sizeof($this->containers);
    }

    /**
     * {@inheritdoc}
     */
    public function servers() {
        return $this->memcache->getServerList();
    }

    /**
     * {@inheritdoc}
     */
    public function resultCode() {
        return $this->memcache->getResultCode();
    }

    /**
     * {@inheritdoc}
     */
    public function resultMessage() {
        return $this->memcache->getResultMessage();
    }
}

/**
 * Class MemcachedShard
 */
class MemcachedShard {

    /** @var   */
    public $hash;

    /** @var   */
    public $size;

    /** @var array  */
    public $shards = [];

    /** @var array  */
    public $keys = [];
}
