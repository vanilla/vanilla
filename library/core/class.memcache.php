<?php
/**
 * Gdn_Memcache.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Cache Layer: Memcache
 *
 * A cache layer that stores its items in memcached and uses libmemcache to interact with the daemons.
 */
class Gdn_Memcache extends Gdn_Cache {

    /** Memcache option. */
    const OPT_MOD_SPLIT = 65000;

    /** Memcache option. */
    const OPT_PASSTHRU_CONTAINER = 'passthru';

    /** O_CREATE. */
    const O_CREATE = 1;

    /** @var Memcache Our Memcache object. */
    private $memcache;

    /** @var array */
    protected $weightedContainers;

    /**
     * Setup our caching configuration.
     */
    public function __construct() {
        parent::__construct();
        $this->cacheType = Gdn_Cache::CACHE_TYPE_MEMORY;

        $this->memcache = new Memcache;

        $this->registerFeature(Gdn_Cache::FEATURE_COMPRESS, MEMCACHE_COMPRESSED);
        $this->registerFeature(Gdn_Cache::FEATURE_EXPIRY);
        $this->registerFeature(Gdn_Cache::FEATURE_TIMEOUT);
        $this->registerFeature(Gdn_Cache::FEATURE_NOPREFIX);
        $this->registerFeature(Gdn_Cache::FEATURE_FORCEPREFIX);

        $this->StoreDefaults = [
            Gdn_Cache::FEATURE_COMPRESS => false,
            Gdn_Cache::FEATURE_TIMEOUT => false,
            Gdn_Cache::FEATURE_EXPIRY => false,
            Gdn_Cache::FEATURE_NOPREFIX => false,
            Gdn_Cache::FEATURE_FORCEPREFIX => null
        ];
    }

    /**
     * Reads in known/config servers and adds them to the instance.
     *
     * This method is called when the cache object is invoked by the framework
     * automatically, and needs to configure itself from the values in the global
     * config file.
     */
    public function autorun() {
        $servers = Gdn_Cache::activeStore('memcache');
        if (!is_array($servers)) {
            $servers = explode(',', $servers);
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
                $value = getValue($keyName, $cacheServer, null);
                if (is_null($value)) {
                    unset($cacheServer[$keyName]);
                }
            }

            $this->addContainer($cacheServer);
        }
    }

    /**
     *
     *
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

        $cacheLocation = getValue(Gdn_Cache::CONTAINER_LOCATION, $options);

        // Merge the options array with our local defaults
        $defaults = [
            Gdn_Cache::CONTAINER_PERSISTENT => true,
            Gdn_Cache::CONTAINER_WEIGHT => 1,
            Gdn_Cache::CONTAINER_TIMEOUT => 1,
            Gdn_Cache::CONTAINER_RETRYINT => 15,
            Gdn_Cache::CONTAINER_ONLINE => true,
            Gdn_Cache::CONTAINER_CALLBACK => null
        ];

        $finalContainer = array_merge($defaults, $options);
        $this->containers[$cacheLocation] = $finalContainer;
        $pathInfo = explode(':', $cacheLocation);

        $this->memcache->addServer(
            getValue(0, $pathInfo),
            getValue(1, $pathInfo, 11211),
            getValue(Gdn_Cache::CONTAINER_PERSISTENT, $finalContainer),
            getValue(Gdn_Cache::CONTAINER_WEIGHT, $finalContainer),
            getValue(Gdn_Cache::CONTAINER_TIMEOUT, $finalContainer),
            getValue(Gdn_Cache::CONTAINER_RETRYINT, $finalContainer),
            getValue(Gdn_Cache::CONTAINER_ONLINE, $finalContainer),
            getValue(Gdn_Cache::CONTAINER_CALLBACK, $finalContainer)
        );

        return Gdn_Cache::CACHEOP_SUCCESS;
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
        $finalOptions = array_merge($this->StoreDefaults, $options);

        $flags = 0;
        $compress = 0;
        if (getValue(Gdn_Cache::FEATURE_COMPRESS, $finalOptions)) {
            $compress = (int)$this->hasFeature(Gdn_Cache::FEATURE_COMPRESS);
        }

        $flags |= $compress;

        $expiry = getValue(Gdn_Cache::FEATURE_EXPIRY, $finalOptions, 0);

        $realKey = $this->makeKey($key, $finalOptions);
        $stored = $this->memcache->add($realKey, $value, $flags, $expiry);
        return ($stored) ? Gdn_Cache::CACHEOP_SUCCESS : Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param string $key
     * @param mixed $value
     * @param array $options
     * @return bool
     */
    public function store($key, $value, $options = []) {
        $finalOptions = array_merge($this->StoreDefaults, $options);

        $flags = 0;
        $compress = 0;
        if (getValue(Gdn_Cache::FEATURE_COMPRESS, $finalOptions)) {
            $compress = (int)$this->hasFeature(Gdn_Cache::FEATURE_COMPRESS);
        }

        $flags |= $compress;

        $expiry = (int)getValue(Gdn_Cache::FEATURE_EXPIRY, $finalOptions, 0);

        $realKey = $this->makeKey($key, $finalOptions);
        $stored = $this->memcache->set($realKey, $value, $flags, $expiry);
        return ($stored) ? Gdn_Cache::CACHEOP_SUCCESS : Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param string $key
     * @param array $options
     * @return array|mixed|string
     */
    public function get($key, $options = []) {
        $finalOptions = array_merge($this->StoreDefaults, $options);

        $flags = 0;
        $compress = 0;
        if (getValue(Gdn_Cache::FEATURE_COMPRESS, $finalOptions)) {
            $compress = (int)$this->hasFeature(Gdn_Cache::FEATURE_COMPRESS);
        }

        $flags |= $compress;

        $realKey = $this->makeKey($key, $finalOptions);
        $data = $this->memcache->get($realKey, $flags);
        return ($data === false) ? $this->fallback($key, $options) : $data;
    }

    /**
     *
     *
     * @param string $key
     * @param array $options
     * @return bool
     */
    public function exists($key, $options = []) {
        return ($this->get($key, $options) === Gdn_Cache::CACHEOP_FAILURE) ? Gdn_Cache::CACHEOP_FAILURE : Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     *
     *
     * @param string $key
     * @param array $options
     * @return bool
     */
    public function remove($key, $options = []) {
        $finalOptions = array_merge($this->StoreDefaults, $options);

        $realKey = $this->makeKey($key, $finalOptions);
        $deleted = $this->memcache->delete($realKey);
        return ($deleted) ? Gdn_Cache::CACHEOP_SUCCESS : Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param string $key
     * @param mixed $value
     * @param array $options
     * @return bool
     */
    public function replace($key, $value, $options = []) {
        return $this->store($key, $value, $options);
    }

    /**
     *
     *
     * @param string $key
     * @param int $amount
     * @param array $options
     * @return bool
     */
    public function increment($key, $amount = 1, $options = []) {
        $finalOptions = array_merge($this->StoreDefaults, $options);

        $realKey = $this->makeKey($key, $finalOptions);
        $incremented = $this->memcache->increment($realKey, $amount);
        return ($incremented !== false) ? $incremented : Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param string $key
     * @param int $amount
     * @param array $options
     * @return int
     */
    public function decrement($key, $amount = 1, $options = []) {
        $finalOptions = array_merge($this->StoreDefaults, $options);

        $realKey = $this->makeKey($key, $finalOptions);
        return $this->memcache->decrement($realKey, $amount);
    }

    /**
     *
     *
     * @return bool
     */
    public function flush() {
        return $this->memcache->flush();
    }
}
