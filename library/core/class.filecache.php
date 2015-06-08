<?php
/**
 * Gdn_Filecache.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
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

        $this->registerFeature(Gdn_Cache::FEATURE_COMPRESS, array('gzcompress', 'gzuncompress'));
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
        $this->addContainer(array(
            Gdn_Cache::CONTAINER_LOCATION => C('Cache.Filecache.Store')
        ));
    }

    /**
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

        $CacheLocation = $Options[Gdn_Cache::CONTAINER_LOCATION];
        $CacheLocationOK = Gdn_FileSystem::checkFolderR($CacheLocation, Gdn_FileSystem::O_CREATE | Gdn_FileSystem::O_WRITE);
        if (!$CacheLocationOK) {
            return $this->failure("Supplied cache folder '{$CacheLocation}' could not be found, or created.");
        }

        // Merge the options array with our local defaults
        $Defaults = array(
            Gdn_Cache::CONTAINER_ONLINE => true,
            Gdn_Cache::CONTAINER_TIMEOUT => 1
        );
        $FinalContainer = array_merge($Defaults, $Options);
        if ($FinalContainer[Gdn_Cache::CONTAINER_ONLINE]) {
            $this->containers[] = $FinalContainer;
        }

        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     *
     *
     * @param $KeyHash
     * @return bool
     */
    protected function _getContainer($KeyHash) {
        // Get a container based on the key. For now, loop through until we find one that is online.
        foreach ($this->containers as &$Container) {
            if ($Container[Gdn_Cache::CONTAINER_ONLINE]) {
                return $Container;
            }
        }

        return Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param $Key
     * @return string
     */
    protected function _hashKey($Key) {
        return sha1($Key);
    }

    /**
     *
     *
     * @param $Key
     * @param int $Flags
     * @return array|bool
     * @throws Exception
     */
    protected function _getKeyPath($Key, $Flags = 0) {
        $KeyHash = $this->_HashKey($Key);
        $SplitValue = intval('0x'.substr($KeyHash, 0, 8), 16);
        $TargetFolder = (string)($SplitValue % Gdn_Filecache::OPT_MOD_SPLIT);

        $Container = $this->_getContainer($KeyHash);
        if ($Container === Gdn_Cache::CACHEOP_FAILURE) {
            return $this->failure("Trying to fetch a container for hash '{$KeyHash}' but got back CACHEOP_FAILURE instead");
        }

        $CacheLocation = $Container[Gdn_Cache::CONTAINER_LOCATION];
        $SplitCacheLocation = CombinePaths(array($CacheLocation, $TargetFolder));

        $Flags = ($Flags & Gdn_Filecache::O_CREATE) ? Gdn_FileSystem::O_CREATE | Gdn_FileSystem::O_WRITE : 0;
        $CacheLocationOK = Gdn_FileSystem::checkFolderR($SplitCacheLocation, $Flags);
        if (!$CacheLocationOK) {
            return $this->failure("Computed cache folder '{$SplitCacheLocation}' could not be found, or created.");
        }

        $CacheFile = rtrim(CombinePaths(array($SplitCacheLocation, $KeyHash)), '/');

        return array_merge($Container, array(
            Gdn_Filecache::CONTAINER_CACHEFILE => $CacheFile
        ));
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
        if ($this->exists($Key) !== Gdn_Cache::CACHEOP_FAILURE) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        return $this->store($Key, $Value, $Options);
    }

    /**
     * This method is deprecated, but since cache files call it there will be low-level crashes without it.
     */
    public static function prepareCache($CacheName, $ExistingCacheArray = null) {
        Gdn_LibraryMap::prepareCache($CacheName, $ExistingCacheArray);
    }

    /**
     *
     *
     * @param string $Key
     * @param mixed $Value
     * @param array $Options
     * @return bool
     * @throws Exception
     */
    public function store($Key, $Value, $Options = array()) {
        $Defaults = array(
            Gdn_Cache::FEATURE_COMPRESS => false,
            Gdn_Cache::FEATURE_TIMEOUT => false,
            Gdn_Cache::FEATURE_EXPIRY => false
        );
        $FinalOptions = array_merge($Defaults, $Options);

        if (array_key_exists(Gdn_Filecache::OPT_PASSTHRU_CONTAINER, $FinalOptions)) {
            $Container = $FinalOptions[Gdn_Filecache::OPT_PASSTHRU_CONTAINER];
        } else {
            $Container = $this->_getKeyPath($Key, Gdn_Filecache::O_CREATE);
            if ($Container === Gdn_Cache::CACHEOP_FAILURE) {
                return Gdn_Cache::CACHEOP_FAILURE;
            }
        }
        $CacheFile = $Container[Gdn_Filecache::CONTAINER_CACHEFILE];

        if ($FinalOptions[Gdn_Cache::FEATURE_COMPRESS] && $CompressionMethod = $this->hasFeature(Gdn_Cache::FEATURE_COMPRESS)) {
            $Compressor = $CompressionMethod[0];
            if (!function_exists($Compressor)) {
                return $this->failure("Trying to compress a value, but method '{$Compressor}' is not available.");
            }
            $Value = call_user_func($Compressor, $Value);
        }

        $Context = implode('|', array(
            intval($FinalOptions[Gdn_Cache::FEATURE_COMPRESS]),
            intval($FinalOptions[Gdn_Cache::FEATURE_EXPIRY]),
            time()
        ));
        $Value = $Context."\n\n".$Value;
        try {
            $StoreOp = file_put_contents($CacheFile, $Value, LOCK_EX | LOCK_NB);
        } catch (Exception $e) {
            die("exp: ".$e->getMessage());
        }
        if ($StoreOp === false) {
            return $this->failure("Trying to save cache value to file '{$CacheFile}' but file_put_contents returned FALSE.");
        }

        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     *
     *
     * @param string $Key
     * @param array $Options
     * @return bool|mixed|null|string
     * @throws Exception
     */
    public function get($Key, $Options = array()) {
        if (array_key_exists(Gdn_Filecache::OPT_PASSTHRU_CONTAINER, $Options)) {
            $Container = $Options[Gdn_Filecache::OPT_PASSTHRU_CONTAINER];
        } else {
            $Container = $this->_getKeyPath($Key, Gdn_Filecache::O_CREATE);
            if ($Container === Gdn_Cache::CACHEOP_FAILURE) {
                return Gdn_Cache::CACHEOP_FAILURE;
            }
        }
        $CacheFile = $Container[Gdn_Filecache::CONTAINER_CACHEFILE];

        $Cache = @fopen($CacheFile, 'r');
        if (!$Cache) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }
        $TimeoutMS = $Container[Gdn_Cache::CONTAINER_TIMEOUT] * 1000;
        $EndTimeMS = microtime(true) + $TimeoutMS;
        $Data = null;
        do {
            flock($Cache, LOCK_SH | LOCK_NB, $Block);
            if (!$Block) {
                // Read in here, assign $Data, then break;

                // First get the meta data array
                $Context = fgets($Cache);
                list($Compressed, $Expires, $Set) = explode('|', $Context);

                // Check Expiry
                if ($Expires) {
                    // Expired
                    if ((intval($Set) + intval($Expires)) < time()) {
                        @fclose($Cache);
                        $this->remove($Key);
                        return Gdn_Cache::CACHEOP_FAILURE;
                    }
                }

                // Skip the newline
                $NL = fgetc($Cache);

                // Do a block-wise buffered read
                $Contents = '';
                while (!feof($Cache) && ($Buf = fread($Cache, 8192)) != '') {
                    $Contents .= $Buf;
                }
                @fclose($Cache);

                // Check Compression
                if ($Compressed) {
                    if ($CompressionMethod = $this->hasFeature(Gdn_Cache::FEATURE_COMPRESS)) {
                        $DeCompressor = $CompressionMethod[1];
                        if (!function_exists($DeCompressor)) {
                            return $this->failure("Trying to decompress a value, but method '{$DeCompressor}' is not available.");
                        }
                        $Data = call_user_func($DeCompressor, $Contents);
                    }
                } else {
                    $Data = $Contents;
                }
                break;
            }
            usleep(50);
        } while (microtime(true) <= $EndTimeMS);
        @fclose($Cache);

        if (!is_null($Data)) {
            return $Data;
        }

        return Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param string $Key
     * @return bool
     */
    public function exists($Key) {
        return ($this->_exists($Key) === Gdn_Cache::CACHEOP_FAILURE) ? Gdn_Cache::CACHEOP_FAILURE : Gdn_Cache::CACHEOP_SUCCESS;
        return Gdn_Cache::CACHEOP_FAILURE;

        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     *
     *
     * @param $Key
     * @return array|bool
     */
    protected function _exists($Key) {
        $Container = $this->_getKeyPath($Key);
        if ($Container === Gdn_Cache::CACHEOP_FAILURE) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $CacheFile = $Container[Gdn_Filecache::CONTAINER_CACHEFILE];
        if (!file_exists($CacheFile)) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        return $Container;
    }

    /**
     *
     *
     * @param string $Key
     * @param array $Options
     * @return bool
     */
    public function remove($Key, $Options = array()) {
        if (array_key_exists(Gdn_Filecache::OPT_PASSTHRU_CONTAINER, $Options)) {
            $Container = $Options[Gdn_Filecache::OPT_PASSTHRU_CONTAINER];
        } else {
            $Container = $this->_getKeyPath($Key, Gdn_Filecache::O_CREATE);
            if ($Container === Gdn_Cache::CACHEOP_FAILURE) {
                return Gdn_Cache::CACHEOP_FAILURE;
            }
        }
        $CacheFile = $Container[Gdn_Filecache::CONTAINER_CACHEFILE];

        $Cache = fopen($CacheFile, 'r');
        $TimeoutMS = $Container[Gdn_Cache::CONTAINER_TIMEOUT] * 1000;
        $EndTimeMS = microtime(true) + $TimeoutMS;
        $Success = Gdn_Cache::CACHEOP_FAILURE;
        do {
            flock($Cache, LOCK_EX | LOCK_NB, $Block);
            if (!$Block) {
                unlink($CacheFile);
                $Success = Gdn_Cache::CACHEOP_SUCCESS;
                break;
            }
            usleep(50);
        } while (microtime(true) <= $EndTimeMS);
        @fclose($Cache);

        return $Success;
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
        $Container = $this->_exists($Key);
        if ($Container === Gdn_Cache::CACHEOP_FAILURE) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $Options[Gdn_Filecache::OPT_PASSTHRU_CONTAINER] = $Container;
        return $this->store($Key, $Value, $Options);
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
        $Container = $this->_exists($Key);
        if ($Container !== Gdn_Cache::CACHEOP_FAILURE) {
            return Gdn_Cache::CACHEOP_FAILURE;
        }

        $Options[Gdn_Filecache::OPT_PASSTHRU_CONTAINER] = $Container;
        $Value = $this->get($Key, $Options);
        if ($Value !== Gdn_Cache::CACHEOP_FAILURE) {
            if (($Value + $Amount) < 0) {
                return Gdn_Cache::CACHEOP_FAILURE;
            }
            $Value += $Amount;
            return $this->store($Key, $Value, $Options);
        }

        return Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     *
     *
     * @param string $Key
     * @param int $Amount
     * @param array $Options
     * @return bool
     */
    public function decrement($Key, $Amount = 1, $Options = array()) {
        return $this->increment($Key, 0 - $Amount, $Options);
    }

    /**
     *
     */
    public function flush() {
        foreach ($this->containers as &$Container) {
            $CacheLocation = $Container[Gdn_Filecache::CONTAINER_LOCATION];
            if (is_dir($CacheLocation)) {
                Gdn_FileSystem::removeFolder($CacheLocation);
                @mkdir($CacheLocation, 0755, true);
            }
        }
    }
}
