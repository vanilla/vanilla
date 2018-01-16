<?php
/**
 * Gdn_LibraryMap.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @param string $cacheName The name of cache library.
     * @param array|null $existingCacheArray An optional array containing an initial seed cache.
     * @param string $cacheMode An optional mode of the cache. Defaults to flat.
     * @return void
     */
    public static function prepareCache($cacheName, $existingCacheArray = null, $cacheMode = 'flat') {
        // Onetime initialization of in-memory file cache
        if (!is_array(self::$Caches)) {
            self::$Caches = [];
        }

        if ($cacheName != 'locale') {
            return;
        }

        if (!array_key_exists($cacheName, self::$Caches)) {
            self::$Caches[$cacheName] = [
                'cache' => [],
                'mode' => $cacheMode
            ];

            $useCache = (Gdn::cache()->type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::cache()->activeEnabled());
            if ($useCache) {
                $cacheKey = sprintf(Gdn_LibraryMap::CACHE_CACHE_NAME_FORMAT, $cacheName);
                $cacheContents = Gdn::cache()->get($cacheKey);
                $loadedFromCache = ($cacheContents !== Gdn_Cache::CACHEOP_FAILURE);
                if ($loadedFromCache && is_array($cacheContents)) {
                    self::import($cacheName, $cacheContents);
                }

            } else {
                $onDiskCacheName = sprintf(self::DISK_CACHE_NAME_FORMAT, strtolower($cacheName));
                self::$Caches[$cacheName]['ondisk'] = $onDiskCacheName;

                // Loading cache for the first time by name+path only... import data now.
                if (file_exists(PATH_CACHE.DS.$onDiskCacheName)) {
                    $cacheContents = parse_ini_file(PATH_CACHE.DS.$onDiskCacheName, true);
                    if ($cacheContents != false && is_array($cacheContents)) {
                        self::import($cacheName, $cacheContents);
                    } else {
                        @unlink(PATH_CACHE.DS.$onDiskCacheName);
                    }
                }

            }
        }

        // If cache data array is passed in, merge it with our existing cache
        if (is_array($existingCacheArray)) {
            self::import($cacheName, $existingCacheArray, true);
        }
    }

    /**
     * Import an existing well formed cache chunk into the supplied library
     *
     * @param string $cacheName name of cache library
     * @param array $cacheContents well formed cache array
     * @param bool $autoSave
     */
    protected static function import($cacheName, $cacheContents, $autoSave = false) {
        if (!array_key_exists($cacheName, self::$Caches)) {
            return false;
        }

        self::$Caches[$cacheName]['cache'] = array_merge(self::$Caches[$cacheName]['cache'], $cacheContents);
        self::$Caches[$cacheName]['mode'] = (sizeof($cacheContents) == 1 && array_key_exists($cacheName, $cacheContents)) ? 'flat' : 'tree';
        if ($autoSave) {
            self::saveCache($cacheName);
        }
    }

    /**
     * Clear the contents of the supplied cache, and remove it from disk.
     *
     * @param string|bool $cacheName name of cache library
     * @return void
     */
    public static function clearCache($cacheName = false) {
        Gdn_Autoloader::smartFree();
        if ($cacheName != 'locale') {
            return;
        }

        if (!array_key_exists($cacheName, self::$Caches)) {
            return self::prepareCache($cacheName);
        }

        $useCache = (Gdn::cache()->type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::cache()->activeEnabled());
        if ($useCache) {
            $cacheKey = sprintf(Gdn_LibraryMap::CACHE_CACHE_NAME_FORMAT, $cacheName);
            $deleted = Gdn::cache()->remove($cacheKey);
        } else {
            @unlink(PATH_CACHE.DS.self::$Caches[$cacheName]['ondisk']);
        }
        self::$Caches[$cacheName]['cache'] = [];
    }

    /**
     * Detect whether the cache has any items in it
     *
     * @param string $cacheName name of cache library
     * @return bool ready state of cache
     */
    public static function cacheReady($cacheName) {
        if (!array_key_exists($cacheName, self::$Caches)) {
            return false;
        }

        if (!sizeof(self::$Caches[$cacheName]['cache'])) {
            return false;
        }

        return true;
    }

    /**
     * Store the provided resource in the appropriate (named) cache
     *
     * @param string $cacheName name of cache library
     * @param string $cacheKey name of cache entry
     * @param mixed $cacheContents contents of cache entry
     * @param bool $cacheWrite optional, whether or not to perform a disk write after this set. default yes
     * @return mixed cache contents
     */
    public static function cache($cacheName, $cacheKey, $cacheContents, $cacheWrite = true) {
        if ($cacheName != 'locale') {
            return;
        }

        if (!array_key_exists($cacheName, self::$Caches)) {
            return false;
        }

        // Set and save cache data to memory and disk
        if (self::$Caches[$cacheName]['mode'] == 'flat') {
            $target = &self::$Caches[$cacheName]['cache'][$cacheName];
        } else {
            $target = &self::$Caches[$cacheName]['cache'];
        }

        $target[$cacheKey] = $cacheContents;
        if ($cacheWrite === true) {
            self::saveCache($cacheName);
        }

        return $cacheContents;
    }

    public static function safeCache($cacheName, $cacheKey, $cacheContents, $cacheWrite = true) {
        if ($cacheName != 'locale') {
            return;
        }

        self::prepareCache($cacheName);
        return self::cache($cacheName, str_replace('.', '__', $cacheKey), $cacheContents, $cacheWrite);
    }

    /**
     * Append the provided resource in the appropriate (named) cache under the named cache key.
     * If the entry is not already an array, convert it to one... then append the new data.
     *
     * @param string $cacheName name of cache library
     * @param string $cacheKey name of cache entry
     * @param mixed $cacheContents contents of cache entry
     * @param bool $cacheWrite optional, whether or not to perform a disk write after this set. default yes
     * @return array cache contents
     */
    public static function cacheArray($cacheName, $cacheKey, $cacheContents, $cacheWrite = true) {
        if ($cacheName != 'locale') {
            return;
        }

        $existingCacheData = self::getCache($cacheName, $cacheKey);

        if ($existingCacheData === null) {
            $existingCacheData = [];
        }

        if (!is_array($existingCacheData)) {
            $existingCacheData = [$existingCacheData];
        }

        $existingCacheData[] = $cacheContents;

        // Save cache data to memory
        return self::cache($cacheName, $cacheKey, $existingCacheData, $cacheWrite);
    }

    /**
     * Retrieve an item from the cache
     *
     * @param string $cacheName name of cache library
     * @param string|null $cacheKey name of cache entry
     * @return mixed cache entry or null on failure
     */
    public static function getCache($cacheName, $cacheKey = null) {
        if ($cacheName != 'locale') {
            return;
        }

        if (!array_key_exists($cacheName, self::$Caches)) {
            self::prepareCache($cacheName);
        }

        if (self::$Caches[$cacheName]['mode'] == 'flat') {
            $target = &self::$Caches[$cacheName]['cache'][$cacheName];
        } else {
            $target = &self::$Caches[$cacheName]['cache'];
        }
        $target = (array)$target;

        if ($cacheKey === null) {
            return $target;
        }

        if (array_key_exists($cacheKey, $target)) {
            return $target[$cacheKey];
        }

        return null;
    }

    /**
     * Save the provided library's data to the on disk location.
     *
     * @param string $cacheName name of cache library
     * @return void
     */
    public static function saveCache($cacheName) {
        if ($cacheName != 'locale') {
            return;
        }

        if (!array_key_exists($cacheName, self::$Caches)) {
            return false;
        }

        $useCache = (Gdn::cache()->type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::cache()->activeEnabled());
        if ($useCache) {
            $cacheKey = sprintf(Gdn_LibraryMap::CACHE_CACHE_NAME_FORMAT, $cacheName);
            $stored = Gdn::cache()->store($cacheKey, self::$Caches[$cacheName]['cache']);
        } else {
            $fileName = self::$Caches[$cacheName]['ondisk'];
            $cacheContents = "";
            foreach (self::$Caches[$cacheName]['cache'] as $sectionTitle => $sectionData) {
                $cacheContents .= "[{$sectionTitle}]\n";
                foreach ($sectionData as $storeKey => $storeValue) {
                    $cacheContents .= "{$storeKey} = \"{$storeValue}\"\n";
                }
            }
            try {
                // Fix slashes to get around parse_ini_file issue that drops off \ when loading network file.
                $cacheContents = str_replace("\\", "/", $cacheContents);

                Gdn_FileSystem::saveFile(PATH_CACHE.DS.$fileName, $cacheContents, LOCK_EX);
            } catch (Exception $e) {
            }
        }
    }
}
