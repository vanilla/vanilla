<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;


class AddonManager {
    /// Properties ///

    private $cacheDir;
    private $scanDirs = [];
    private $classCache;
    private $addonCache;
    private $singleCache = [];

    private $enabled = [];
    private $theme;
    private $themeSubdirs;

    /// Methods ///

    /**
     * Initialize a new instance of the {@link AddonManager} class.
     *
     * @param array $scanDirs An array of root-relative directories to scan indexed by **Addon::TYPE_** constant.
     * Applications and plugins are treated as the same so pass their directories as an array with the
     * **Addon::TYPE_ADDON** key.
     *
     * @param string $cacheDir The path to the cache.
     */
    public function __construct(array $scanDirs, $cacheDir) {
        if (strpos($cacheDir, PATH_ROOT) !== 0) {
            $cacheDir = PATH_ROOT.$cacheDir;
        }

        // Make sure the cache directories exist.
        if (!file_exists($cacheDir)) {
            $r = mkdir($cacheDir, 0755, true);
        }
        foreach ([Addon::TYPE_LOCALE, Addon::TYPE_THEME] as $type) {
            $dir = "$cacheDir/$type";
            if (!file_exists($dir)) {
                $r = mkdir($dir, 0755);
            }
        }

        $scanDirs += array_fill_keys([Addon::TYPE_ADDON, Addon::TYPE_LOCALE, Addon::TYPE_THEME], []);
        foreach ($scanDirs as &$dir) {
            $dir = (array)$dir;
        }
        $this->scanDirs = $scanDirs;
        $this->cacheDir = $cacheDir;
    }

    /**
     *  Attempt to load undefined class based on the addons that are enabled.
     *
     * @param string $class The name of the class to load.
     */
    public function autoload($class) {

    }

    /**
     * Lookup an {@link Addon} by its type.
     *
     * @param string $key The addon's key.
     * @param string $type One of the **Addon::TYPE_** constants.
     * @return null|Addon Returns the addon or **null** if one isn't found.
     */
    public function lookupByType($key, $type) {
        switch ($type) {
            case Addon::TYPE_ADDON:
            case Addon::TYPE_APPLICATION:
            case Addon::TYPE_THEME:
                return $this->lookupAddon($key);
            default:
                return $this->lookupSingleCachedAddon($key, $type);
        }
    }

    /**
     * Lookup the addon with a given key.
     *
     * @param string $key The key of the addon.
     * @return Addon|null
     */
    public function lookupAddon($key) {
        $this->ensureAddonCache();

        $realKey = strtolower($key);
        if (array_key_exists($realKey, $this->addonCache)) {
            return $this->addonCache[$realKey];
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
    private function ensureAddonCache() {
        if (!isset($this->addonCache)) {
            if (!empty($this->cacheDir)) {
                $cachePath = $this->cacheDir.'/'.Addon::TYPE_ADDON.'.php';
                if (file_exists($cachePath)) {
                    $this->addonCache = require $cachePath;
                } else {
                    $this->addonCache = $this->scan(Addon::TYPE_ADDON, true);
                }
            } else {
                $this->addonCache = [];
            }
        }
    }

    /**
     * Scan the directories of all of the addons of a given type.
     *
     * @param string $type One of the **Addon::TYPE_** constants.
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
        foreach ($this->scanDirs[$type] as $subdir) {
            $paths = glob(PATH_ROOT."$subdir/*", GLOB_ONLYDIR | GLOB_NOSORT);
            foreach ($paths as $path) {
                $addon = new Addon("$subdir/".basename($path));
                $addons[$addon->getKey()] = $addon;
            }
        }
        $this->addonCache = $addons;

        if ($saveCache) {
            if ($type === Addon::TYPE_ADDON) {
                $varString = '<?php return '.var_export($addons, true).";\n";
                static::filePutContents("{$this->cacheDir}/$type.php", $varString);
            } else {
                // Each of these addons must be cached separately.
                foreach ($addons as $addon) {
                    $key = $addon->getKey();
                    $varString = '<?php return '.var_export($addon, true).";\n";
                    static::filePutContents("{$this->cacheDir}/$type/$key.php", $varString);
                }
            }
        }
        return $addons;
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
     * @param string $type One of the **Addon::TYPE_** constants.
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
        return $addon;
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
        $themes = $this->themeSubdirs();
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
            $subdirs[$theme->getKey] = $theme->getSubdir();

            // Look for this theme's base theme.
            if ($baseTheme = $theme->getInfoValue('baseTheme')) {
                $theme = $this->lookupTheme($baseTheme);
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
        $this->theme = $theme;
        $this->themeSubdirs = null;

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
     * Remove all of the cached files.
     *
     * @return bool Returns **true** if the files were removed or **false** otherwise.
     */
    public function clearCache() {
        $r = true;

        if (file_exists($this->cacheDir.'/addon.php')) {
            $r = unlink($this->cacheDir.'/addon.php');
        }

        $paths = glob("{$this->cacheDir}/*/*.php", GLOB_NOSORT);
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
     * @param string $cacheDir
     * @return AddonManager Returns `$this` for fluent calls.
     */
    public function setCacheDir($cacheDir) {
        $this->cacheDir = $cacheDir;
        return $this;
    }
}
