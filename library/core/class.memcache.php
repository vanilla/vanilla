<?php
/**
 * Gdn_Memcache.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
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

        $this->StoreDefaults = array(
            Gdn_Cache::FEATURE_COMPRESS => false,
            Gdn_Cache::FEATURE_TIMEOUT => false,
            Gdn_Cache::FEATURE_EXPIRY => false,
            Gdn_Cache::FEATURE_NOPREFIX => false,
            Gdn_Cache::FEATURE_FORCEPREFIX => null
        );
    }

    /**
     * Reads in known/config servers and adds them to the instance.
     *
     * This method is called when the cache object is invoked by the framework
     * automatically, and needs to configure itself from the values in the global
     * config file.
     */
    public function autorun() {
        $Servers = Gdn_Cache::activeStore('memcache');
        if (!is_array($Servers)) {
            $Servers = explode(',', $Servers);
        }

        $Keys = array(
            Gdn_Cache::CONTAINER_LOCATION,
            Gdn_Cache::CONTAINER_PERSISTENT,
            Gdn_Cache::CONTAINER_WEIGHT,
            Gdn_Cache::CONTAINER_TIMEOUT,
            Gdn_Cache::CONTAINER_ONLINE,
            Gdn_Cache::CONTAINER_CALLBACK
        );
        foreach ($Servers as $CacheServer) {
            $CacheServer = explode(' ', $CacheServer);
            $CacheServer = array_pad($CacheServer, count($Keys), null);
            $CacheServer = array_combine($Keys, $CacheServer);

            foreach ($Keys as $KeyName) {
                $Value = GetValue($KeyName, $CacheServer, null);
                if (is_null($Value)) {
                    unset($CacheServer[$KeyName]);
                }
            }

            $this->addContainer($CacheServer);
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
    public function addContainer($Options) {

        $Required = array(
            Gdn_Cache::CONTAINER_LOCATION
        );

        $KeyedRequirements = array_fill_keys($Required, 1);
        if (sizeof(array_intersect_key($Options, $KeyedRequirements)) != sizeof($Required)) {
            $Missing = implode(", ", array_keys(array_diff_key($KeyedRequirements, $Options)));
            return $this->failure("Required parameters not supplied. Missing: {$Missing}");
        }

        $CacheLocation = GetValue(Gdn_Cache::CONTAINER_LOCATION, $Options);

        // Merge the options array with our local defaults
        $Defaults = array(
            Gdn_Cache::CONTAINER_PERSISTENT => true,
            Gdn_Cache::CONTAINER_WEIGHT => 1,
            Gdn_Cache::CONTAINER_TIMEOUT => 1,
            Gdn_Cache::CONTAINER_RETRYINT => 15,
            Gdn_Cache::CONTAINER_ONLINE => true,
            Gdn_Cache::CONTAINER_CALLBACK => null
        );

        $FinalContainer = array_merge($Defaults, $Options);
        $this->containers[$CacheLocation] = $FinalContainer;
        $PathInfo = explode(':', $CacheLocation);

        $this->memcache->addServer(
            GetValue(0, $PathInfo),
            GetValue(1, $PathInfo, 11211),
            GetValue(Gdn_Cache::CONTAINER_PERSISTENT, $FinalContainer),
            GetValue(Gdn_Cache::CONTAINER_WEIGHT, $FinalContainer),
            GetValue(Gdn_Cache::CONTAINER_TIMEOUT, $FinalContainer),
            GetValue(Gdn_Cache::CONTAINER_RETRYINT, $FinalContainer),
            GetValue(Gdn_Cache::CONTAINER_ONLINE, $FinalContainer),
            GetValue(Gdn_Cache::CONTAINER_CALLBACK, $FinalContainer)
        );

        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     *
     *
     * @param string $Key
     * @param mixed $Value
     * @param array $Options
     * @return bool
     */
    public function add($Key, $Value, $Options = array()) {
        $FinalOptions = array_merge($this->StoreDefaults, $Options);

        $Flags = 0;
        $Compress = 0;
        if (GetValue(Gdn_Cache::FEATURE_COMPRESS, $FinalOptions)) {
            $Compress = (int)$this->hasFeature(Gdn_Cache::FEATURE_COMPRESS);
        }

        $Flags |= $Compress;

        $Expiry = GetValue(Gdn_Cache::FEATURE_EXPIRY, $FinalOptions, 0);

        $RealKey = $this->makeKey($Key, $FinalOptions);
        $Stored = $this->memcache->add($RealKey, $Value, $Flags, $Expiry);
        return ($Stored) ? Gdn_Cache::CACHEOP_SUCCESS : Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param string $Key
     * @param mixed $Value
     * @param array $Options
     * @return bool
     */
    public function store($Key, $Value, $Options = array()) {
        $FinalOptions = array_merge($this->StoreDefaults, $Options);

        $Flags = 0;
        $Compress = 0;
        if (GetValue(Gdn_Cache::FEATURE_COMPRESS, $FinalOptions)) {
            $Compress = (int)$this->hasFeature(Gdn_Cache::FEATURE_COMPRESS);
        }

        $Flags |= $Compress;

        $Expiry = (int)GetValue(Gdn_Cache::FEATURE_EXPIRY, $FinalOptions, 0);

        $RealKey = $this->makeKey($Key, $FinalOptions);
        $Stored = $this->memcache->set($RealKey, $Value, $Flags, $Expiry);
        return ($Stored) ? Gdn_Cache::CACHEOP_SUCCESS : Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param string $Key
     * @param array $Options
     * @return array|mixed|string
     */
    public function get($Key, $Options = array()) {
        $FinalOptions = array_merge($this->StoreDefaults, $Options);

        $Flags = 0;
        $Compress = 0;
        if (GetValue(Gdn_Cache::FEATURE_COMPRESS, $FinalOptions)) {
            $Compress = (int)$this->hasFeature(Gdn_Cache::FEATURE_COMPRESS);
        }

        $Flags |= $Compress;

        $RealKey = $this->makeKey($Key, $FinalOptions);
        $Data = $this->memcache->get($RealKey, $Flags);
        return ($Data === false) ? $this->Fallback($Key, $Options) : $Data;
    }

    /**
     *
     *
     * @param string $Key
     * @param array $Options
     * @return bool
     */
    public function exists($Key, $Options = array()) {
        return ($this->Get($Key, $Options) === Gdn_Cache::CACHEOP_FAILURE) ? Gdn_Cache::CACHEOP_FAILURE : Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     *
     *
     * @param string $Key
     * @param array $Options
     * @return bool
     */
    public function remove($Key, $Options = array()) {
        $FinalOptions = array_merge($this->StoreDefaults, $Options);

        $RealKey = $this->makeKey($Key, $FinalOptions);
        $Deleted = $this->memcache->delete($RealKey);
        return ($Deleted) ? Gdn_Cache::CACHEOP_SUCCESS : Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param string $Key
     * @param mixed $Value
     * @param array $Options
     * @return bool
     */
    public function replace($Key, $Value, $Options = array()) {
        return $this->Store($Key, $Value, $Options);
    }

    /**
     *
     *
     * @param string $Key
     * @param int $Amount
     * @param array $Options
     * @return bool
     */
    public function increment($Key, $Amount = 1, $Options = array()) {
        $FinalOptions = array_merge($this->StoreDefaults, $Options);

        $RealKey = $this->MakeKey($Key, $FinalOptions);
        $Incremented = $this->memcache->increment($RealKey, $Amount);
        return ($Incremented !== false) ? $Incremented : Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param string $Key
     * @param int $Amount
     * @param array $Options
     * @return int
     */
    public function decrement($Key, $Amount = 1, $Options = array()) {
        $FinalOptions = array_merge($this->StoreDefaults, $Options);

        $RealKey = $this->makeKey($Key, $FinalOptions);
        return $this->memcache->decrement($RealKey, $Amount);
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
