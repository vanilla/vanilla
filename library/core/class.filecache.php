<?php
/**
 * Gdn_Filecache.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

 /**
 * Cache Layer: Files
 *
 * A cache layer that stores its items as files on the filesystem.
 */
class Gdn_Filecache extends Gdn_Cache {

    /** Option. */
    const OPT_MOD_SPLIT = 65000;

    /** Option. */
    const OPT_PASSTHRU_CONTAINER = 'passthru';

    /** Op. */
    const O_CREATE = 1;

    /** Cache fuke, */
    const CONTAINER_CACHEFILE = 'c_cachefile';

    /** @var   */
    protected $weightedContainers;

    /**
     *
     */
    public function __construct() {
        parent::__construct();
        $this->cacheType = Gdn_Cache::CACHE_TYPE_FILE;

        $this->registerFeature(Gdn_Cache::FEATURE_COMPRESS, ['gzcompress', 'gzuncompress']);
        $this->registerFeature(Gdn_Cache::FEATURE_EXPIRY);
        $this->registerFeature(Gdn_Cache::FEATURE_TIMEOUT);
    }

    /**
     * Reads in known/config storage locations and adds them to the instance.
     *
     * This method is called when the cache object is invoked by the framework
     * automatically, and needs to configure itself from the values in the global
     * config file.
     */
    public function autorun() {
        $this->addContainer([
            Gdn_Cache::CONTAINER_LOCATION => c('Cache.Filecache.Store')
        ]);
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

        $cacheLocation = $options[Gdn_Cache::CONTAINER_LOCATION];
        $cacheLocationOK = Gdn_FileSystem::checkFolderR($cacheLocation, Gdn_FileSystem::O_CREATE | Gdn_FileSystem::O_WRITE);
        if (!$cacheLocationOK) {
            return $this->failure("Supplied cache folder '{$cacheLocation}' could not be found, or created.");
        }

        // Merge the options array with our local defaults
        $defaults = [
            Gdn_Cache::CONTAINER_ONLINE => true,
            Gdn_Cache::CONTAINER_TIMEOUT => 1
        ];
        $finalContainer = array_merge($defaults, $options);
        if ($finalContainer[Gdn_Cache::CONTAINER_ONLINE]) {
            $this->containers[] = $finalContainer;
        }

        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     *
     *
     * @param $keyHash
     * @return bool
     */
    protected function _getContainer($keyHash) {
        // Get a container based on the key. For now, loop through until we find one that is online.
        foreach ($this->containers as &$container) {
            if ($container[Gdn_Cache::CONTAINER_ONLINE]) {
                return $container;
            }
        }

        return Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param $key
     * @return string
     */
    protected function _hashKey($key) {
        return sha1($key);
    }

    /**
     *
     *
     * @param $key
     * @param int $flags
     * @return array|bool
     * @throws Exception
     */
    protected function _getKeyPath($key, $flags = 0) {
        $keyHash = $this->_HashKey($key);
        $splitValue = intval('0x'.substr($keyHash, 0, 8), 16);
        $targetFolder = (string)($splitValue % Gdn_Filecache::OPT_MOD_SPLIT);

        $container = $this->_getContainer($keyHash);
        if ($container === Gdn_Cache::CACHEOP_FAILURE) {
            return $this->failure("Trying to fetch a container for hash '{$keyHash}' but got back CACHEOP_FAILURE instead");
        }

        $cacheLocation = $container[Gdn_Cache::CONTAINER_LOCATION];
        $splitCacheLocation = combinePaths([$cacheLocation, $targetFolder]);

        $flags = ($flags & Gdn_Filecache::O_CREATE) ? Gdn_FileSystem::O_CREATE | Gdn_FileSystem::O_WRITE : 0;
        $cacheLocationOK = Gdn_FileSystem::checkFolderR($splitCacheLocation, $flags);
        if (!$cacheLocationOK && ($flags & Gdn_Filecache::O_CREATE)) {
            return $this->failure("Computed cache folder '{$splitCacheLocation}' could not be found, or created.");
        }

        $cacheFile = rtrim(combinePaths([$splitCacheLocation, $keyHash]), '/');

        return array_merge($container, [
            Gdn_Filecache::CONTAINER_CACHEFILE => $cacheFile
        ]);
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
        if ($this->exists($key) !== Gdn_Cache::CACHEOP_FAILURE) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        return $this->store($key, $value, $options);
    }

    /**
     * This method is deprecated, but since cache files call it there will be low-level crashes without it.
     */
    public static function prepareCache($cacheName, $existingCacheArray = null) {
        Gdn_LibraryMap::prepareCache($cacheName, $existingCacheArray);
    }

    /**
     *
     *
     * @param string $key
     * @param mixed $value
     * @param array $options
     * @return bool
     * @throws Exception
     */
    public function store($key, $value, $options = []) {
        $defaults = [
            Gdn_Cache::FEATURE_COMPRESS => false,
            Gdn_Cache::FEATURE_TIMEOUT => false,
            Gdn_Cache::FEATURE_EXPIRY => false
        ];
        $finalOptions = array_merge($defaults, $options);

        if (array_key_exists(Gdn_Filecache::OPT_PASSTHRU_CONTAINER, $finalOptions)) {
            $container = $finalOptions[Gdn_Filecache::OPT_PASSTHRU_CONTAINER];
        } else {
            $container = $this->_getKeyPath($key, Gdn_Filecache::O_CREATE);
            if ($container === Gdn_Cache::CACHEOP_FAILURE) {
                return Gdn_Cache::CACHEOP_FAILURE;
            }
        }
        $cacheFile = $container[Gdn_Filecache::CONTAINER_CACHEFILE];

        if ($finalOptions[Gdn_Cache::FEATURE_COMPRESS] && $compressionMethod = $this->hasFeature(Gdn_Cache::FEATURE_COMPRESS)) {
            $compressor = $compressionMethod[0];
            if (!function_exists($compressor)) {
                return $this->failure("Trying to compress a value, but method '{$compressor}' is not available.");
            }
            $value = call_user_func($compressor, $value);
        }

        $context = implode('|', [
            intval($finalOptions[Gdn_Cache::FEATURE_COMPRESS]),
            intval($finalOptions[Gdn_Cache::FEATURE_EXPIRY]),
            time()
        ]);
        $value = $context."\n\n".$value;
        try {
            $storeOp = file_put_contents($cacheFile, $value, LOCK_EX | LOCK_NB);
        } catch (Exception $e) {
            die("exp: ".$e->getMessage());
        }
        if ($storeOp === false) {
            return $this->failure("Trying to save cache value to file '{$cacheFile}' but file_put_contents returned FALSE.");
        }

        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     *
     *
     * @param string $key
     * @param array $options
     * @return bool|mixed|null|string
     * @throws Exception
     */
    public function get($key, $options = []) {
        if (array_key_exists(Gdn_Filecache::OPT_PASSTHRU_CONTAINER, $options)) {
            $container = $options[Gdn_Filecache::OPT_PASSTHRU_CONTAINER];
        } else {
            $container = $this->_getKeyPath($key, Gdn_Filecache::O_CREATE);
            if ($container === Gdn_Cache::CACHEOP_FAILURE) {
                return Gdn_Cache::CACHEOP_FAILURE;
            }
        }
        $cacheFile = $container[Gdn_Filecache::CONTAINER_CACHEFILE];

        $cache = @fopen($cacheFile, 'r');
        if (!$cache) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }
        $timeoutMS = $container[Gdn_Cache::CONTAINER_TIMEOUT] * 1000;
        $endTimeMS = microtime(true) + $timeoutMS;
        $data = null;
        do {
            flock($cache, LOCK_SH | LOCK_NB, $block);
            if (!$block) {
                // Read in here, assign $Data, then break;

                // First get the meta data array
                $context = fgets($cache);
                list($compressed, $expires, $set) = explode('|', $context);

                // Check Expiry
                if ($expires) {
                    // Expired
                    if ((intval($set) + intval($expires)) < time()) {
                        @fclose($cache);
                        $this->remove($key);
                        return Gdn_Cache::CACHEOP_FAILURE;
                    }
                }

                // Skip the newline
                $nL = fgetc($cache);

                // Do a block-wise buffered read
                $contents = '';
                while (!feof($cache) && ($buf = fread($cache, 8192)) != '') {
                    $contents .= $buf;
                }
                @fclose($cache);

                // Check Compression
                if ($compressed) {
                    if ($compressionMethod = $this->hasFeature(Gdn_Cache::FEATURE_COMPRESS)) {
                        $deCompressor = $compressionMethod[1];
                        if (!function_exists($deCompressor)) {
                            return $this->failure("Trying to decompress a value, but method '{$deCompressor}' is not available.");
                        }
                        $data = call_user_func($deCompressor, $contents);
                    }
                } else {
                    $data = $contents;
                }
                break;
            }
            usleep(50);
        } while (microtime(true) <= $endTimeMS);
        @fclose($cache);

        if (!is_null($data)) {
            return $data;
        }

        return Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param string $key
     * @return bool
     */
    public function exists($key) {
        return ($this->_exists($key) === Gdn_Cache::CACHEOP_FAILURE) ? Gdn_Cache::CACHEOP_FAILURE : Gdn_Cache::CACHEOP_SUCCESS;
        return Gdn_Cache::CACHEOP_FAILURE;

        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     *
     *
     * @param $key
     * @return array|bool
     */
    protected function _exists($key) {
        $container = $this->_getKeyPath($key);
        if ($container === Gdn_Cache::CACHEOP_FAILURE) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $cacheFile = $container[Gdn_Filecache::CONTAINER_CACHEFILE];
        if (!file_exists($cacheFile)) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        return $container;
    }

    /**
     *
     *
     * @param string $key
     * @param array $options
     * @return bool
     */
    public function remove($key, $options = []) {
        if (array_key_exists(Gdn_Filecache::OPT_PASSTHRU_CONTAINER, $options)) {
            $container = $options[Gdn_Filecache::OPT_PASSTHRU_CONTAINER];
        } else {
            $container = $this->_getKeyPath($key, Gdn_Filecache::O_CREATE);
            if ($container === Gdn_Cache::CACHEOP_FAILURE) {
                return Gdn_Cache::CACHEOP_FAILURE;
            }
        }
        $cacheFile = $container[Gdn_Filecache::CONTAINER_CACHEFILE];

        $cache = fopen($cacheFile, 'r');
        $timeoutMS = $container[Gdn_Cache::CONTAINER_TIMEOUT] * 1000;
        $endTimeMS = microtime(true) + $timeoutMS;
        $success = Gdn_Cache::CACHEOP_FAILURE;
        do {
            flock($cache, LOCK_EX | LOCK_NB, $block);
            if (!$block) {
                unlink($cacheFile);
                $success = Gdn_Cache::CACHEOP_SUCCESS;
                break;
            }
            usleep(50);
        } while (microtime(true) <= $endTimeMS);
        @fclose($cache);

        return $success;
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
        $container = $this->_exists($key);
        if ($container === Gdn_Cache::CACHEOP_FAILURE) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $options[Gdn_Filecache::OPT_PASSTHRU_CONTAINER] = $container;
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
        $container = $this->_exists($key);
        if ($container !== Gdn_Cache::CACHEOP_FAILURE) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $options[Gdn_Filecache::OPT_PASSTHRU_CONTAINER] = $container;
        $value = $this->get($key, $options);
        if ($value !== Gdn_Cache::CACHEOP_FAILURE) {
            if (($value + $amount) < 0) {
                return Gdn_Cache::CACHEOP_FAILURE;
            }
            $value += $amount;
            return $this->store($key, $value, $options);
        }

        return Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param string $key
     * @param int $amount
     * @param array $options
     * @return bool
     */
    public function decrement($key, $amount = 1, $options = []) {
        return $this->increment($key, 0 - $amount, $options);
    }

    /**
     *
     */
    public function flush() {
        foreach ($this->containers as &$container) {
            $cacheLocation = $container[Gdn_Filecache::CONTAINER_LOCATION];
            if (is_dir($cacheLocation)) {
                Gdn_FileSystem::removeFolder($cacheLocation);
                @mkdir($cacheLocation, 0755, true);
            }
        }
    }
}
