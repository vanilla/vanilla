<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;


class AddonManager {
    /// Properties ///

    /**
     * @var string The full path to the addon cache.
     */
    private $cacheDir;
    /**
     * @var array An array of addon scan directories indexed by addon type. Each type can be an array of directories.
     */
    private $scanDirs = [];
    /**
     * @var array The cache of addons.
     */
    private $multiCache;
    /**
     * @var array The cache of themes and locales.
     */
    private $singleCache = [];
    /**
     * @var array The index of themes and locales.
     */
    private $singleIndex = [];
    /**
     * @var array An array of enabled addons, indexed by type/key.
     */
    private $enabled = [];
    /**
     * @var bool Whether or not the enabled addons needs to be sorted.
     */
    private $enabledSorted = true;

    /**
     * @var Addon The currently enabled theme.
     */
    private $theme;
    /**
     * @var array A cache of theme lookup directories starting at the current theme and working up parent themes.
     */
    private $themeSubdirs;

    /**
     * @var array A list of autoload classes based on enabled addons.
     */
    private $autoloadClasses = [];

    /**
     * @var array A backup of same-named classes from the autoloader.
     */
    private $autoloadClassesBak = [];

    /// Methods ///

    /**
     * Initialize a new instance of the {@link AddonManager} class.
     *
     * @param array $scanDirs An array of root-relative directories to scan indexed by **Addon::TYPE_*** constant.
     * Applications and plugins are treated as the same so pass their directories as an array with the
     * **Addon::TYPE_ADDON** key.
     *
     * @param string $cacheDir The path to the cache.
     */
    public function __construct(array $scanDirs, $cacheDir) {
        $this->setCacheDir($cacheDir);

        // Make sure the cache directories exist.
        $r = true;
        if (!file_exists($cacheDir)) {
            $r &= mkdir($cacheDir, 0755, true);
        }

        $types = [Addon::TYPE_ADDON, Addon::TYPE_LOCALE, Addon::TYPE_THEME];
        $scanDirs += array_fill_keys($types, []);

        foreach ($types as $type) {
            if (!$this->typeUsesMultiCaching($type)) {
                $dir = "$cacheDir/$type";
                if (!file_exists($dir)) {
                    $r &= mkdir($dir, 0755);
                }
            }

            $this->scanDirs[$type] = (array)$scanDirs[$type];
        }

        if (!$r) {
            trigger_error('Could not create necessary addon cache directories.', E_USER_WARNING);
        }
    }

    /**
     * Test whether an addon type uses multi-caching.
     *
     * @param string $type One of the **Addon::TYPE_*** constatns.
     * @return bool Returns **true** if the addon type uses multi caching or **false** if it uses single caching.
     */
    private function typeUsesMultiCaching($type) {
        return $type === Addon::TYPE_ADDON;
    }

    /**
     *  Attempt to load undefined class based on the addons that are enabled.
     *
     * @param string $class The name of the class to load.
     */
    public function autoload($class) {
        $classKey = strtolower($class);

        if (isset($this->autoloadClasses[$classKey])) {
            list($path, $_) = $this->autoloadClasses[$classKey];
            include_once $path;
        }
    }

    /**
     * Lookup an {@link Addon} by its type.
     *
     * @param string $key The addon's key.
     * @param string $type One of the **Addon::TYPE_*** constants.
     * @return null|Addon Returns the addon or **null** if one isn't found.
     */
    public function lookupByType($key, $type) {
        if ($this->typeUsesMultiCaching($type)) {
            return $this->lookupAddon($key);
        } else {
            return $this->lookupSingleCachedAddon($key, $type);
        }
    }

    /**
     * Lookup an addon by class name.
     *
     * This method should only be used with enabled addons as searching through all addons takes a performance hit.
     *
     * @param string $class The class name.
     * @param bool $searchAll Whether or not to search all addons or just the enabled ones.
     * @return Addon|null Returns an {@link Addon} object or **null** if one isn't found.
     */
    public function lookupByClassname($class, $searchAll = false) {
        $classKey = strtolower($class);

        if (isset($this->autoloadClasses[$classKey])) {
            list($_, $addon) = $this->autoloadClasses[$classKey];
            return $addon;
        } elseif ($searchAll) {
            foreach ($this->lookupAllByType(Addon::TYPE_ADDON) as $addon) {
                /* @var Addon $addon */
                if (array_key_exists($classKey, $addon->getClasses())) {
                    return $addon;
                }
            }
        }
        return null;
    }

    /**
     * Lookup the addon with a given key.
     *
     * @param string $key The key of the addon.
     * @return Addon|null
     */
    public function lookupAddon($key) {
        $this->ensureMultiCache();

        $realKey = strtolower($key);
        if (array_key_exists($realKey, $this->multiCache)) {
            return $this->multiCache[$realKey];
        } else {
            return null;
        }
    }

    /**
     * Ensure that the addon cache has all of the addons.
     *
     * This method checks if the addon cache property is initialized. If it isn't it first looks for an addon cache and
     * then scans the addon directories.
     */
    private function ensureMultiCache() {
        if (!isset($this->multiCache)) {
            if (!empty($this->cacheDir)) {
                $cachePath = $this->cacheDir.'/'.Addon::TYPE_ADDON.'.php';
                if (file_exists($cachePath)) {
                    $this->multiCache = require $cachePath;
                } else {
                    $this->multiCache = $this->scan(Addon::TYPE_ADDON, true);
                }
            } else {
                $this->multiCache = [];
            }
        }
    }

    /**
     * Scan the directories of all of the addons of a given type.
     *
     * @param string $type One of the **Addon::TYPE_*** constants.
     * @param bool $saveCache Whether or not to save the found addons to the cache.
     * @return array Returns an array of {@link Addon} objects.
     */
    public function scan($type, $saveCache = false) {
        if ($saveCache && empty($this->cacheDir)) {
            throw new \InvalidArgumentException("Cannot save the addon cache when the cache directory is empty.", 500);
        }

        /* @var array[Addon] $addons */
        $addons = [];

        // Scan all of the addon directories.
        $addonDirs = $this->scanAddonDirs($type);
        foreach ($addonDirs as $subdir) {
            $addon = new Addon($subdir);
            $addons[$addon->getKey()] = $addon;
        }
        $this->multiCache = $addons;

        if ($saveCache) {
            if ($this->typeUsesMultiCaching($type)) {
                $varString = '<?php return '.var_export($addons, true).";\n";
                static::filePutContents("{$this->cacheDir}/$type.php", $varString);
            } else {
                // Each of these addons must be cached separately.
                foreach ($addons as $addon) {
                    $key = $addon->getKey();
                    $varString = '<?php return '.var_export($addon, true).";\n";
                    static::filePutContents("{$this->cacheDir}/$type/$key.php", $varString);
                }
                // Save a index of the addon names.
                $indexString = '<?php return '.var_export($addonDirs, true).";\n";
                static::filePutContents("{$this->cacheDir}/$type-index.php", $indexString);
            }
        }
        return $addons;
    }

    /**
     * Get a list of addon directories for a given type.
     *
     * @param string $type One of the **Addon::TYPE_*** constants.
     * @return array Returns an array of root-relative addon directories.
     */
    private function scanAddonDirs($type) {
        $strlen = strlen(PATH_ROOT);
        $result = [];

        foreach ($this->scanDirs[$type] as $subdir) {
            $paths = glob(PATH_ROOT."$subdir/*", GLOB_ONLYDIR | GLOB_NOSORT);
            foreach ($paths as $path) {
                $result[basename($path)] = substr($path, $strlen);
            }
        }

        return $result;
    }

    /**
     * A version of file_put_contents() that is multi-thread safe.
     *
     * @param string $filename Path to the file where to write the data.
     * @param mixed $data The data to write. Can be either a string, an array or a stream resource.
     * @param int $mode The permissions to set on a new file.
     * @return boolean
     * @category Filesystem Functions
     * @see http://php.net/file_put_contents
     */
    private function filePutContents($filename, $data, $mode = 0644) {
        $temp = tempnam(dirname($filename), 'atomic');

        if (!($fp = @fopen($temp, 'wb'))) {
            $temp = dirname($filename).DIRECTORY_SEPARATOR.uniqid('atomic');
            if (!($fp = @fopen($temp, 'wb'))) {
                trigger_error("file_put_contents_safe() : error writing temporary file '$temp'", E_USER_WARNING);
                return false;
            }
        }

        fwrite($fp, $data);
        fclose($fp);

        if (!@rename($temp, $filename)) {
            @unlink($filename);
            @rename($temp, $filename);
        }
        if (function_exists('apc_delete_file')) {
            // This fixes a bug with some configurations of apc.
            @apc_delete_file($filename);
        } elseif (function_exists('opcache_invalidate')) {
            @opcache_invalidate($filename);
        }

        @chmod($filename, $mode);
        return true;
    }

    /**
     * Lookup an addon that is cached on a per-addon basis.
     *
     * @param string $key The key of the addon.
     * @param string $type One of the **Addon::TYPE_*** constants.
     * @return Addon|null Returns an addon object or null if one isn't found.
     */
    private function lookupSingleCachedAddon($key, $type) {
        // Look at our in-request cache.
        if (isset($this->singleCache[$type][$key])) {
            $result = $this->singleCache[$type][$key];
            return $result === false ? null : $result;
        }
        // Look at the file cache.
        if (!empty($this->cacheDir)) {
            $cachePath = "{$this->cacheDir}/$type/$key.php";
            if (file_exists($cachePath)) {
                $addon = require $cachePath;
                $this->singleCache[$type][$key] = $addon;
                return $addon;
            }
        }
        // Look for the addon itself.
        $addon = false;
        foreach ($this->scanDirs[$type] as $scanDir) {
            $addonDir = PATH_ROOT."$scanDir/$key";
            if (file_exists($addonDir)) {
                $addon = new Addon("$scanDir/$key");
                break;
            }
        }
        // Cache the addon's information.
        if (!empty($this->cacheDir)) {
            $cachePath = "{$this->cacheDir}/$type/$key.php";
            $addonString = "<?php return ".var_export($addon, true).";\n";
            static::filePutContents($cachePath, $addonString);
        }
        $this->singleCache[$type][$key] = $addon;
        return $addon === false ? null : $addon;
    }

    /**
     * Lookup a locale pack based on its key.
     *
     * The local pack's key MUST be the same as the folder it's in.
     *
     * @param string $key The key of the locale pack.
     * @return null|Addon Returns an {@link Addon} object for the locale pack or **null** if it can't be found.
     */
    public function lookupLocale($key) {
        $result = $this->lookupSingleCachedAddon($key, Addon::TYPE_LOCALE);
        return $result;
    }

    /**
     * Lookup the path of an asset.
     *
     * @param string $subpath The subpath of the asset, relative an addon root.
     * @param Addon $addon The addon that should contain the asset.
     * @param bool $mustExist Whether or not the asset must exist in the addon.
     * @return string
     */
    public function lookupAsset($subpath, Addon $addon = null, $mustExist = true) {
        $subpath = '/'.ltrim($subpath, '\\/');

        // First lookup the asset on the theme.
        foreach ($this->themeSubdirs() as $subdir) {
            if (file_exists(PATH_ROOT.$subdir.$subpath)) {
                return $subdir.$subpath;
            }
        }

        if (isset($addon)) {
            $path = $addon->getSubdir().$subpath;
            if ($mustExist && !file_exists(PATH_ROOT.$path)) {
                return '';
            } else {
                return $path;
            }
        } else {
            return '';
        }
    }

    /**
     * Get the current theme and themes it's based on as an array.
     *
     * @return array Returns an array of string paths.
     */
    private function themeSubdirs() {
        $subdirs = []; // prevent infinite loop
        /* @var Addon $theme */
        $theme = $this->getTheme();
        while (isset($theme)) {
            if (array_key_exists($theme->getKey(), $subdirs)) {
                break;
            }
            $subdirs[$theme->getKey()] = $theme->getSubdir();

            // Look for this theme's base theme.
            if ($parentTheme = $theme->getInfoValue('parentTheme')) {
                $theme = $this->lookupTheme($parentTheme);
            } else {
                break;
            }
        }

        return $subdirs;
    }

    /**
     * Get the theme.
     *
     * @return Addon|null Returns the theme.
     */
    public function getTheme() {
        return $this->theme;
    }

    /**
     * Set the theme.
     *
     * @param Addon|null $theme The new theme to set.
     * @return AddonManager Returns `$this` for fluent calls.
     */
    public function setTheme(Addon $theme) {
        if ($theme !== null) {
            $this->startAddon($theme);
        } elseif ($this->theme !== null) {
            $this->stopAddon($this->theme);
            $this->theme = null;
        }

        return $this;
    }

    /**
     * Lookup a theme based on its key.
     *
     * The theme's key MUST be the same as the folder it's in.
     *
     * @param string $key The key of the theme.
     * @return null|Addon Returns an {@link Addon} object for the theme or **null** if it can't be found.
     */
    public function lookupTheme($key) {
        $result = $this->lookupSingleCachedAddon($key, Addon::TYPE_THEME);
        return $result;
    }

    /**
     * Start an addon and make it available.
     *
     * @param Addon $addon The addon to start.
     */
    public function startAddon(Addon $addon) {
        $this->enabled[$addon->getType().'/'.$addon->getKey()] = $addon;
        $this->enabledSorted = count($this->enabled) <= 1;

        if ($addon->getType() === Addon::TYPE_THEME) {
            if (isset($this->theme)) {
                $this->stopAddon($this->theme);
            }

            $this->theme = $addon;
            $this->themeSubdirs = null;
        }

        // Add the addon's classes to the autoload list.
        foreach ($addon->getClasses() as $classKey => $row) {
            list($_, $subpath) = $row;

            if (isset($this->autoloadClasses[$classKey])) {
                // There is already a class registered here. Only override if higher priority.
                if ($this->autoloadClasses[$classKey][1]->getPriority() < $addon->getPriority()) {
                    $bak = $this->autoloadClasses[$classKey];
                    $this->autoloadClasses[$classKey] = [$addon->path($subpath), $addon];
                } else {
                    $bak = [$addon->path($subpath), $addon];
                }
                $this->autoloadClassesBak[$classKey][] = $bak;
            } else {
                $this->autoloadClasses[$classKey] = [$addon->path($subpath), $addon];
            }
        }
    }

    /**
     * Start one or more addons by specifying their keys.
     *
     * This method is useful for starting the addons that are stored in a configuration file.
     *
     * @param array $keys The keys of the addons. The addon keys can be the keys of the array or the values.
     * @param string $type One of the **Addon::TYPE_*** constants.
     * @return int Returns the number of addons that were started.
     */
    public function startAddonsByKey($keys, $type) {
        // Filter out false keys.
        $keys = array_filter((array)$keys);

        $count = 0;
        foreach ($keys as $key => $value) {
            if (in_array($value, [true, 1, '1'], true)) {
                // This addon key is represented as addon => true.
                $addon = $this->lookupByType($key, $type);
            } else {
                // This addon is represented as addon => folder.
                $addon = $this->lookupByType($value, $type);
            }
            if (empty($addon)) {
                trigger_error("The $type with key $key could not be found and will not be started.");
            } else {
                $this->startAddon($addon);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Stop an addon and make it unavailable.
     *
     * @param Addon $addon The addon to stop.
     */
    public function stopAddon(Addon $addon) {
        if (empty($addon)) {
            trigger_error("Null addon supplied to AddonManager->stopAddon().", E_USER_NOTICE);
            return;
        }

        unset($this->enabled[$addon->getType().'/'.$addon->getKey()]);

        // Remove all of the addon's classes from the autoloader.
        foreach ($addon->getClasses() as $classKey => $row) {
//            list($class, $subpath) = $row;
            unset($this->autoloadClasses[$classKey]);

            // See if there is another class that can be registered in place.
            if (!empty($this->autoloadClassesBak[$classKey])) {
                foreach ($this->autoloadClassesBak[$classKey] as $i => $row) {
                    list($path, $addon) = $row;
                    if (!isset($maxAddon) || $maxAddon->getPriority() < $addon->getPriority()) {
                        $maxAddon = $addon;
                        $maxIndex = $i;
                    }
                }
                if (isset($maxIndex)) {
                    $this->autoloadClasses[$classKey] = $this->autoloadClassesBak[$classKey][$maxIndex];
                    unset($this->autoloadClassesBak[$classKey][$maxIndex]);
                }
            }
        }
    }

    /**
     * Stop one or more addons by specifying their keys.
     *
     * @param array $keys The keys of the addons. The addon keys can be the keys of the array or the values.
     * @param string $type One of the **Addon::TYPE_*** constants.
     * @return int Returns the number of addons that were stopped.
     */
    public function stopAddonsByKey($keys, $type) {
        // Filter out false keys.
        $keys = array_filter((array)$keys);

        $count = 0;
        foreach ($keys as $key => $value) {
            if (in_array($value, [true, 1, '1'], true)) {
                // This addon key is represented as addon => true.
                $addon = $this->lookupByType($key, $type);
            } else {
                // This addon is represented as addon => folder.
                $addon = $this->lookupByType($value, $type);
            }
            if (empty($addon)) {
                trigger_error("The $type with key $key could not be found and will not be stopped.");
            } else {
                $this->stopAddon($addon);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get all of the addons of a certain type.
     *
     * @param string $type One of the **Addon::TYPE_*** constants.
     */
    public function lookupAllByType($type) {
        if ($this->typeUsesMultiCaching($type)) {
            $this->ensureMultiCache();
            return $this->multiCache;
        } else {
            $index = $this->getSingleIndex($type);
            $addons = [];
            foreach ($index as $key => $subdir) {
                $caseKey = basename($subdir);
                $addons[$caseKey] = $this->lookupSingleCachedAddon($caseKey, $type);
            }
            return $addons;
        }
    }

    /**
     * Get the index for an addon type that is cached by single addon.
     *
     * @param string $type One of the **Addon::TYPE_*** constants.
     * @return array Returns the index mapping lowercase addon name to directory.
     */
    private function getSingleIndex($type) {
        if (!isset($this->singleIndex[$type])) {
            $cachePath = $this->cacheDir."/$type-index.php";

            if (file_exists($cachePath)) {
                $this->singleIndex[$type] = require $cachePath;
            } else {
                $addonDirs = $this->scanAddonDirs($type);
                $indexString = '<?php return '.var_export($addonDirs, true).";\n";
                static::filePutContents($cachePath, $indexString);

                $this->singleIndex[$type] = $addonDirs;
            }
        }
        return $this->singleIndex[$type];
    }

    /**
     * Remove all of the cached files.
     *
     * @return bool Returns **true** if the files were removed or **false** otherwise.
     */
    public function clearCache() {
        $r = true;

        $paths = array_merge(
            glob("{$this->cacheDir}/*.php", GLOB_NOSORT),
            glob("{$this->cacheDir}/*/*.php", GLOB_NOSORT)
        );
        foreach ($paths as $path) {
            $r &= unlink($path);
        }

        return $r;
    }

    /**
     * Get the cacheDir.
     *
     * @return string Returns the cacheDir.
     */
    public function getCacheDir() {
        return $this->cacheDir;
    }

    /**
     * Set the cacheDir.
     *
     * @param string $cacheDir The cache directory to set. If this doesn't include **PATH_ROOT** then it will be
     * prepended.
     * @return AddonManager Returns `$this` for fluent calls.
     */
    public function setCacheDir($cacheDir) {
        if (strpos($cacheDir, PATH_ROOT) !== 0) {
            $cacheDir = PATH_ROOT.$cacheDir;
        }

        $this->cacheDir = $cacheDir;
        return $this;
    }

    /**
     * Get the enabled addons, sorted by priority with the highest priority first.
     *
     * @return array[Addon] Returns an array of {@link Addon} objects.
     */
    public function getEnabled() {
        if (!$this->enabledSorted) {
            uasort($this->enabled, ['\Vanilla\Addon', 'comparePriority']);
            $this->enabledSorted = true;
        }
        return $this->enabled;
    }

    /**
     * Check whether or not an addon is enabled.
     *
     * @param string $key The addon key.
     * @param string $type One of the **Addon::TYPE_*** constants.
     * @return bool Returns
     */
    public function isEnabled($key, $type) {
        if ($type === Addon::TYPE_ADDON) {
            $key = strtolower($key);
        }
        $enabled = array_key_exists($key, $this->enabled);
        return $enabled;
    }

    /**
     * Get the paths to the current translation files.
     *
     * @param string $locale The locale to get the translation paths for.
     * @return array Returns an array of paths.
     */
    public function getEnabledTranslationSources($locale) {
        $addons = array_reverse($this->getEnabled(), true);

        $result = [];
        foreach ($addons as $addon) {
            /* @var Addon $addon */
            foreach ($addon->getTranslations($locale) as $path) {
                $result[] = $addon->path($path);
            }
        }
        return $result;
    }
}
