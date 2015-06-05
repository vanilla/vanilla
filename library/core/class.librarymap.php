<?php
/**
 * Gdn_LibraryMap.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Handle the creation, usage, and deletion of file cache entries which map paths
 * to locale files.
 */
class Gdn_LibraryMap {

    /** Sprintf format string that describes the on-disk name of the mapping caches. */
    const DISK_CACHE_NAME_FORMAT = '%s_map.ini';

    /** Sprintf format string that describes the on-disk name of the mapping caches. */
    const CACHE_CACHE_NAME_FORMAT = 'garden.librarymap.%s';

    /** @var array Holds the in-memory array of cache entries. */
    public static $Caches;

    /**
     * Prepare a cache library for use, either by loading it from file, filling it with
     * pre existing data in array form, or leaving it empty and waiting for new entries.
     *
     * @param string $CacheName The name of cache library.
     * @param array|null $ExistingCacheArray An optional array containing an initial seed cache.
     * @param string $CacheMode An optional mode of the cache. Defaults to flat.
     * @return void
     */
    public static function prepareCache($CacheName, $ExistingCacheArray = null, $CacheMode = 'flat') {
        // Onetime initialization of in-memory file cache
        if (!is_array(self::$Caches)) {
            self::$Caches = array();
        }

        if ($CacheName != 'locale') {
            return;
        }

        if (!array_key_exists($CacheName, self::$Caches)) {
            self::$Caches[$CacheName] = array(
                'cache' => array(),
                'mode' => $CacheMode
            );

            $UseCache = (Gdn::cache()->type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::cache()->activeEnabled());
            if ($UseCache) {
                $CacheKey = sprintf(Gdn_LibraryMap::CACHE_CACHE_NAME_FORMAT, $CacheName);
                $CacheContents = Gdn::cache()->get($CacheKey);
                $LoadedFromCache = ($CacheContents !== Gdn_Cache::CACHEOP_FAILURE);
                if ($LoadedFromCache && is_array($CacheContents)) {
                    self::import($CacheName, $CacheContents);
                }

            } else {
                $OnDiskCacheName = sprintf(self::DISK_CACHE_NAME_FORMAT, strtolower($CacheName));
                self::$Caches[$CacheName]['ondisk'] = $OnDiskCacheName;

                // Loading cache for the first time by name+path only... import data now.
                if (file_exists(PATH_CACHE.DS.$OnDiskCacheName)) {
                    $CacheContents = parse_ini_file(PATH_CACHE.DS.$OnDiskCacheName, true);
                    if ($CacheContents != false && is_array($CacheContents)) {
                        self::import($CacheName, $CacheContents);
                    } else {
                        @unlink(PATH_CACHE.DS.$OnDiskCacheName);
                    }
                }

            }
        }

        // If cache data array is passed in, merge it with our existing cache
        if (is_array($ExistingCacheArray)) {
            self::import($CacheName, $ExistingCacheArray, true);
        }
    }

    /**
     * Import an existing well formed cache chunk into the supplied library
     *
     * @param string $CacheName name of cache library
     * @param array $CacheContents well formed cache array
     * @param bool $AutoSave
     */
    protected static function import($CacheName, $CacheContents, $AutoSave = false) {
        if (!array_key_exists($CacheName, self::$Caches)) {
            return false;
        }

        self::$Caches[$CacheName]['cache'] = array_merge(self::$Caches[$CacheName]['cache'], $CacheContents);
        self::$Caches[$CacheName]['mode'] = (sizeof($CacheContents) == 1 && array_key_exists($CacheName, $CacheContents)) ? 'flat' : 'tree';
        if ($AutoSave) {
            self::saveCache($CacheName);
        }
    }

    /**
     * Clear the contents of the supplied cache, and remove it from disk.
     *
     * @param string|bool $CacheName name of cache library
     * @return void
     */
    public static function clearCache($CacheName = false) {
        Gdn_Autoloader::smartFree();
        if ($CacheName != 'locale') {
            return;
        }

        if (!array_key_exists($CacheName, self::$Caches)) {
            return self::prepareCache($CacheName);
        }

        $UseCache = (Gdn::cache()->type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::cache()->activeEnabled());
        if ($UseCache) {
            $CacheKey = sprintf(Gdn_LibraryMap::CACHE_CACHE_NAME_FORMAT, $CacheName);
            $Deleted = Gdn::cache()->remove($CacheKey);
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
    public static function cacheReady($CacheName) {
        if (!array_key_exists($CacheName, self::$Caches)) {
            return false;
        }

        if (!sizeof(self::$Caches[$CacheName]['cache'])) {
            return false;
        }

        return true;
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
    public static function cache($CacheName, $CacheKey, $CacheContents, $CacheWrite = true) {
        if ($CacheName != 'locale') {
            return;
        }

        if (!array_key_exists($CacheName, self::$Caches)) {
            return false;
        }

        // Set and save cache data to memory and disk
        if (self::$Caches[$CacheName]['mode'] == 'flat') {
            $Target = &self::$Caches[$CacheName]['cache'][$CacheName];
        } else {
            $Target = &self::$Caches[$CacheName]['cache'];
        }

        $Target[$CacheKey] = $CacheContents;
        if ($CacheWrite === true) {
            self::saveCache($CacheName);
        }

        return $CacheContents;
    }

    public static function safeCache($CacheName, $CacheKey, $CacheContents, $CacheWrite = true) {
        if ($CacheName != 'locale') {
            return;
        }

        self::prepareCache($CacheName);
        return self::cache($CacheName, str_replace('.', '__', $CacheKey), $CacheContents, $CacheWrite);
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
    public static function cacheArray($CacheName, $CacheKey, $CacheContents, $CacheWrite = true) {
        if ($CacheName != 'locale') {
            return;
        }

        $ExistingCacheData = self::getCache($CacheName, $CacheKey);

        if ($ExistingCacheData === null) {
            $ExistingCacheData = array();
        }

        if (!is_array($ExistingCacheData)) {
            $ExistingCacheData = array($ExistingCacheData);
        }

        $ExistingCacheData[] = $CacheContents;

        // Save cache data to memory
        return self::cache($CacheName, $CacheKey, $ExistingCacheData, $CacheWrite);
    }

    /**
     * Retrieve an item from the cache
     *
     * @param string $CacheName name of cache library
     * @param string|null $CacheKey name of cache entry
     * @return mixed cache entry or null on failure
     */
    public static function getCache($CacheName, $CacheKey = null) {
        if ($CacheName != 'locale') {
            return;
        }

        if (!array_key_exists($CacheName, self::$Caches)) {
            self::prepareCache($CacheName);
        }

        if (self::$Caches[$CacheName]['mode'] == 'flat') {
            $Target = &self::$Caches[$CacheName]['cache'][$CacheName];
        } else {
            $Target = &self::$Caches[$CacheName]['cache'];
        }
        $Target = (array)$Target;

        if ($CacheKey === null) {
            return $Target;
        }

        if (array_key_exists($CacheKey, $Target)) {
            return $Target[$CacheKey];
        }

        return null;
    }

    /**
     * Save the provided library's data to the on disk location.
     *
     * @param string $CacheName name of cache library
     * @return void
     */
    public static function saveCache($CacheName) {
        if ($CacheName != 'locale') {
            return;
        }

        if (!array_key_exists($CacheName, self::$Caches)) {
            return false;
        }

        $UseCache = (Gdn::cache()->type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::cache()->activeEnabled());
        if ($UseCache) {
            $CacheKey = sprintf(Gdn_LibraryMap::CACHE_CACHE_NAME_FORMAT, $CacheName);
            $Stored = Gdn::cache()->store($CacheKey, self::$Caches[$CacheName]['cache']);
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
                // Fix slashes to get around parse_ini_file issue that drops off \ when loading network file.
                $CacheContents = str_replace("\\", "/", $CacheContents);

                Gdn_FileSystem::saveFile(PATH_CACHE.DS.$FileName, $CacheContents, LOCK_EX);
            } catch (Exception $e) {
            }
        }
    }
}
