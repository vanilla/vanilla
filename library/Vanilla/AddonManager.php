<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

use Garden\EventManager;

/**
 * A class to manage all of the addons in the application.
 *
 * The {@link AddonManager} scans directories for addon folders and then maintains them in a catalogue. Addons can then
 * be started which makes them available to the application. When an addon is started it can do the following:
 *
 * - Any classes the addon has declared are available via the {@link AddonManager::autoload()} method.
 * - The addon can declare a class ending in "Plugin" and its events will be registered.
 * - Any translations the addon has declared will be loaded for the currently enabled locale.
 */
class AddonManager {
    /// Constants ///

    const REQ_ENABLED = 0x01; // addon enabled, yay!
    const REQ_DISABLED = 0x02; // addon disabled
    const REQ_MISSING = 0x04; // addon missing from the manager
    const REQ_VERSION = 0x08; // addon isn't the correct version

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
     *
     *  Structure:
     *  $autoloadClasses = [
     *      strtolower($className) => [ // classKey
     *          $namespace => [
     *              'className' => $className,
     *              'path' => $path, // class' relative path from its addon
     *              'addon' => $addon, // class' addon
     *          ]
     *      ],
     *  ];
     */
    private $autoloadClasses = [];

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
     * @param string $fqClassName Fully qualified class name to load.
     */
    public function autoload($fqClassName) {
        $suppliedClassInfo = Addon::parseFullyQualifiedClass($fqClassName);
        $classKey = strtolower($suppliedClassInfo['className']);

        if (isset($this->autoloadClasses[$classKey])) {
            foreach ($this->autoloadClasses[$classKey] as $namespace => $classData) {
                if ($suppliedClassInfo['namespace'] === $namespace) {
                    include_once $classData['addon']->path($classData['path']);
                }
            }
        }
    }

    /**
     * Register the autoloader for addons managed through this class.
     *
     * @param bool $throw This parameter specifies whether **spl_autoload_register()** should throw exceptions when the autoload_function cannot be registered.
     * @param bool $prepend If true, **spl_autoload_register()** will prepend the autoloader on the autoload queue instead of appending it.
     * @return bool Returns **true** on success or **false** on failure.
     */
    public function registerAutoloader($throw = true, $prepend = false) {
        return spl_autoload_register([$this, 'autoload'], $throw, $prepend);
    }

    /**
     * Unregister the autoloader registered with {@link AddonManager::registerAutoloader()}.
     *
     * @return bool Returns **true** on success or **false** on failure.
     */
    public function unregisterAutoloader() {
        return spl_autoload_unregister([$this, 'autoload']);
    }
    /**
     * Lookup an addon by fully qualified class name.
     *
     * This method should only be used with enabled addons as searching through all addons takes a performance hit.
     *
     * @param string $fqClassName Fully qualified class name.
     * @param bool $searchAll Whether or not to search all addons or just the enabled ones.
     * @return Addon|null Returns an {@link Addon} object or **null** if one isn't found.
     */
    public function lookupByClassname($fqClassName, $searchAll = false) {
        $lookupClassInfo = Addon::parseFullyQualifiedClass($fqClassName);
        $classKey = strtolower($lookupClassInfo['className']);

        if (isset($this->autoloadClasses[$classKey])) {
            return reset($this->autoloadClasses[$classKey])['addon'];
        } elseif ($searchAll) {
            foreach ($this->lookupAllByType(Addon::TYPE_ADDON) as $addon) {
                /* @var Addon $addon */
                $classes = $addon->getClasses();
                if (isset($classes[$classKey])) {
                    foreach ($classes[$classKey] as $namespace => $classInfo) {
                        if ($namespace === $lookupClassInfo['namespace']) {
                            return $addon;
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * Find classes that match a glob style pattern.
     *
     * Pattern guideline:
     *  findClasses('*'), // Returns everything
     *  findClasses('\*'), // Returns anything that is not namespaced
     *  findClasses('*\*'), // Returns anything that is namespaced
     *  findClasses('*Plugin'), // Returns everything that ends with "Plugin"
     *  findClasses('\*Plugin'), // Returns anything that is not namespaced and that ends with "Plugin"
     *  findClasses('*\*Plugin'), // Returns anything that is namespaced and that ends with "Plugin"
     *  ...
     *  You can see "\" as "not namespaced" or "in the global namespace". Same thing.
     *
     * @param string $classPattern The class pattern to match.
     * @param bool $searchAll Whether to search all classes or just the started ones. Using this will be way slower!
     * @return array Returns an array of fully qualified class names.
     */
    public function findClasses($classPattern, $searchAll = false) {
        $foundClasses = [];
        if ($searchAll === false) {
            $foundClasses = $this->findPatternInClassCollection($classPattern, $this->autoloadClasses);
        } else {
            foreach($this->lookupAllByType(Addon::TYPE_ADDON) as $addon) {
                $addonClasses = $addon->getClasses();
                $foundClasses = array_merge($foundClasses, $this->findPatternInClassCollection($classPattern, $addonClasses));
            }
        }

        return $foundClasses;
    }

    /**
     *
     *
     * @param $pattern
     * @param $collection Must match the structure of $autoloadClasses (Only 'className' is required)
     * @return array
     */
    protected function findPatternInClassCollection($pattern, $collection) {
        $foundClasses = [];

        $patterns = Addon::parseFullyQualifiedClass($pattern);
        $namespacePattern = $patterns['namespace'];
        $classNamePattern = $patterns['className'];

        $classNameWildcardPos = strpos($classNamePattern, '*');
        $classNameHasWildcard = $classNameWildcardPos !== false;
        $classNameStartWithWilcard = $classNameWildcardPos === 0;

        if ($namespacePattern === '' && $classNameStartWithWilcard) {
            $namespacePattern = '*';
            $namespaceHasWildcard = true;
        } else if ($namespacePattern === '\\') {
            $namespacePattern = '';
            $namespaceHasWildcard = false;
        } else if ($namespacePattern === '*\\') {
            $namespacePattern = '.+';
            $namespaceHasWildcard = true;
        } else {
            $namespaceHasWildcard = strpos($namespacePattern, '*') !== false;
        }

        $fnPatternToRegex = function($pattern) {
            return str_replace(['\\*', '\.\+'], ['.*?', '.+?'], preg_quote($pattern));
        };
        $namespaceRegex = '/^'.$fnPatternToRegex($namespacePattern).'$/i';
        $classNameRegex = '/^'.$fnPatternToRegex($classNamePattern).'$/i';

        // Add classes that matches the namespacePattern to $foundClasses
        // Assume that the data is coming from $fnIterateOnClasses
        $fnMatchNamespace = function($classesData) use
            (&$foundClasses, $namespacePattern, $namespaceHasWildcard, $namespaceRegex) {
            foreach ($classesData as $namespace => $classData) {
                if ($namespaceHasWildcard && preg_match($namespaceRegex, $namespace) === 1) {
                    $foundClasses[] = $namespace.$classData['className'];
                } else if ($namespacePattern === $namespace) {
                    $foundClasses[] = $namespace.$classData['className'];
                }
            }
        };

        // Iterate on a collection of classesInfo and check if the classKey matches!
        if ($classNameHasWildcard) {
            foreach ($collection as $classKey => $classesData) {
                if (preg_match($classNameRegex, $classKey) === 1) {
                    $fnMatchNamespace($classesData);
                }
            }
        } else {
            $classKey = strtolower($classNamePattern);
            if (isset($collection[$classKey])) {
                $fnMatchNamespace($collection[$classKey]);
            }

        }

        return $foundClasses;
    }

    /**
     * Get all of the addons of a certain type.
     *
     * @param string $type One of the **Addon::TYPE_*** constants.
     * @return array
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
                try {
                    $addons[$caseKey] = $this->lookupSingleCachedAddon($caseKey, $type);
                } catch (\Exception $ex) {
                    trigger_error("The $type in $subdir is invalid and will be skipped.", E_USER_WARNING);
                    // Clear the addon out of the index.
                    $this->deleteSingleIndexKey($type, $key);
                }
            }
            return $addons;
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
                if (is_readable($cachePath)) {
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
            try {
                $addon = new Addon($subdir);
                $addons[$addon->getKey()] = $addon;
            } catch (\Exception $ex) {
                trigger_error("The $type in $subdir is invalid.", E_USER_WARNING);
            }
        }
        $this->multiCache = $addons;

        if ($saveCache) {
            if ($this->typeUsesMultiCaching($type)) {
                static::saveArrayCache($path = "{$this->cacheDir}/$type.php", $addons);
            } else {
                // Each of these addons must be cached separately.
                foreach ($addons as $addon) {
                    $key = $addon->getKey();
                    static::saveArrayCache("{$this->cacheDir}/$type/$key.php", $addon);
                }
                // Save a index of the addon names.
                static::saveArrayCache("{$this->cacheDir}/$type-index.php", $addonDirs);
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
     * Cache an array.
     *
     * @param string $path The path to save the array to.
     * @param string $array The array to save.
     */
    private static function saveArrayCache($path, $array) {
        $varString = '<?php return '.var_export($array, true).";\n";
        static::filePutContents($path, $varString);
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
    private static function filePutContents($filename, $data, $mode = 0644) {
        $temp = tempnam(dirname($filename), 'atomic');

        if (!($fp = @fopen($temp, 'wb'))) {
            $temp = dirname($filename).DIRECTORY_SEPARATOR.uniqid('atomic');
            if (!($fp = @fopen($temp, 'wb'))) {
                trigger_error("AddonManager::filePutContents(): error writing temporary file '$temp'", E_USER_WARNING);
                return false;
            }
        }

        fwrite($fp, $data);
        fclose($fp);

        if (!@rename($temp, $filename)) {
            $r = @unlink($filename);
            $r &= @rename($temp, $filename);
            if (!$r) {
                trigger_error("AddonManager::filePutContents(): error writing file '$filename'", E_USER_WARNING);
                return false;
            }
        }
        if (function_exists('apc_delete_file')) {
            // This fixes a bug with some configurations of apc.
            apc_delete_file($filename);
        } elseif (function_exists('opcache_invalidate')) {
            opcache_invalidate($filename);
        }

        @chmod($filename, $mode);
        return true;
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

            if (is_readable($cachePath)) {
                $this->singleIndex[$type] = require $cachePath;
            } else {
                $addonDirs = $this->scanAddonDirs($type);
                static::saveArrayCache($cachePath, $addonDirs);

                $this->singleIndex[$type] = $addonDirs;
            }
        }
        return $this->singleIndex[$type];
    }

    /**
     * Delete an item from a single index and re-cache it.
     *
     * @param string $type One of the **Addon::TYPE_*** constants.
     * @param string $key The index key.
     * @return bool Returns **true** if the item was in the index or **false** otherwise.
     */
    private function deleteSingleIndexKey($type, $key) {
        $index = $this->getSingleIndex($type);
        if (isset($index[$key])) {
            unset($index[$key]);

            $cachePath = $this->cacheDir."/$type-index.php";
            static::saveArrayCache($cachePath, $index);
            $this->singleIndex[$type] = $index;
            return true;
        }
        return false;
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
            if (is_readable($cachePath)) {
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
            static::saveArrayCache("{$this->cacheDir}/$type/$key.php", $addon);
        }
        $this->singleCache[$type][$key] = $addon;
        return $addon === false ? null : $addon;
    }

    /**
     * Check an addon's requirements.
     *
     * An addon cannot be enabled if it has missing or invalid requirements. If an addon has requirements that are
     * simply disabled it will pass this test as long as it's requirements also meet *their* requirements.
     *
     * @param Addon $addon The addon to check.
     * @param bool $throw Whether or not to throw an exception if the requirements are not met.
     * @return bool Returns **true** if the requirements are met or **false** otherwise.
     */
    public function checkRequirements(Addon $addon, $throw = false) {
        // Get all of the addon requirements.
        $requirements = $this->lookupRequirements($addon, self::REQ_MISSING | self::REQ_VERSION);
        $missing = [];
        foreach ($requirements as $addonKey => $requirement) {
            switch ($requirement['status']) {
                case self::REQ_MISSING:
                    $missing[] = $addonKey;
                    break;
                case self::REQ_VERSION:
                    $checkAddon = $this->lookupAddon($addonKey);
                    $missing[] = $checkAddon->getName()." {$requirement['req']}";
                    break;
            }
        }

        if (!empty($missing)) {
            if ($throw) {
                // TODO: Localize after dependency injection can be done.
                $msg = sprintf(
                    '%1$s requires: %2$s.',
                    $addon->getName(),
                    implode(', ', $missing)
                );
                throw new \Exception($msg, 400);
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all of the requirements for an addon.
     *
     * This method returns an array of all of the addon requirements for a given addon. The return is an array of
     * requirements in the following form:
     *
     * ```
     * 'addonKey' => ['req' => 'versionRequirement', 'status' => AddonManager::REQ_*]
     * ```
     *
     * @param Addon $addon The addon to check.
     * @param int $filter One or more of the **AddonManager::REQ_*** constants concatenated by `|`.
     *
     * @return Returns the requirements array. An empty array represents an addon with no requirements.
     */
    public function lookupRequirements(Addon $addon, $filter = null) {
        $array = [];
        $this->lookupRequirementsRecursive($addon, $array);

        // Filter the list.
        if ($filter) {
            $array = array_filter($array, function ($row) use ($filter) {
                return ($row['status'] & $filter) === $filter;
            });
        }

        return $array;
    }

    /**
     * The implementation of {@link lookupRequirements()}.
     *
     * @param Addon $addon The addon to lookup.
     * @param array &$array The current requirements list.
     * @see AddonManager::lookupRequirements()
     */
    private function lookupRequirementsRecursive(Addon $addon, array &$array) {
        $addonReqs = $addon->getRequirements();
        foreach ($addonReqs as $addonKey => $versionReq) {
            $addonKey = strtolower($addonKey);
            if (isset($array[$addonKey])) {
                continue;
            }
            $addonReq = $this->lookupAddon($addonKey);
            if (!$addonReq) {
                $status = self::REQ_MISSING;
            } elseif ($this->isEnabled($addonReq->getKey(), $addonReq->getType())) {
                $status = self::REQ_ENABLED;
            } elseif (Addon::checkVersion($addonReq->getVersion(), $versionReq)) {
                $status = self::REQ_DISABLED;
            } else {
                $status = self::REQ_VERSION;
            }
            $array[$addonKey] = ['req' => $versionReq, 'status' => $status];

            // Check the required addon's requirements.
            if ($addonReq && $status !== self::REQ_ENABLED) {
                $this->lookupRequirementsRecursive($addonReq, $array);
            }
        }
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
        if (isset($this->multiCache[$realKey])) {
            return $this->multiCache[$realKey];
        } else {
            return null;
        }
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
        $enabled = isset($this->enabled["$type/$key"]);
        return $enabled;
    }

    /**
     * Check the enabled dependents of an addon.
     *
     * Addons should always check their dependents before being disabled. This check does not consider dependents that
     * are not enabled.
     *
     * @deprecated Use checkDependents instead.
     * @param Addon $addon The addon to check.
     * @param bool $throw Whether or not to throw an exception or just return **false** if the check fails.
     * @return bool Returns **true** if the addon a
     * @throws \Exception Throws an exception if {@link $throw} is **true** and there are enabled dependents.
     */
    public function checkDependants(Addon $addon, $throw = false) {
        trigger_error('checkDependants is deprecated. Use checkDependents instead.', E_USER_DEPRECATED);
        return $this->checkDependents($addon, $throw);
    }

    /**
     * Check the enabled dependents of an addon.
     *
     * Addons should always check their dependents before being disabled. This check does not consider dependents that
     * are not enabled.
     *
     * @param Addon $addon The addon to check.
     * @param bool $throw Whether or not to throw an exception or just return **false** if the check fails.
     * @return bool Returns **true** if the addon a
     * @throws \Exception Throws an exception if {@link $throw} is **true** and there are enabled dependents.
     */
    public function checkDependents(Addon $addon, $throw = false) {
        $dependents = $this->lookupDependents($addon);

        if (empty($dependents)) {
            return true;
        } elseif (!$throw) {
            return false;
        } else {
            $names = [];
            /* @var Addon $dependent */
            foreach ($dependents as $dependent) {
                $names[] = $dependent->getName();
            }
            $msg = sprintf(
                'The following addons depend on %1$s: %2$s.',
                $addon->getName(),
                implode(', ', $names)
            );
            throw new \Exception($msg, 400);
        }
    }

    /**
     * Get all of the enabled addons that depend on a given addon.
     *
     * @deprecated Use lookupDependents instead.
     * @param Addon $addon The addon to check the requirements.
     * @return array Returns an array of {@link Addon} objects.
     */
    public function lookupDependants(Addon $addon) {
        trigger_error('lookupDependants is deprecated. Use lookupDependents instead.', E_USER_DEPRECATED);
        return $this->lookupDependents($addon);
    }

    /**
     * Get all of the enabled addons that depend on a given addon.
     *
     * @param Addon $addon The addon to check the requirements.
     * @return array Returns an array of {@link Addon} objects.
     */
    public function lookupDependents(Addon $addon) {
        $result = [];
        foreach ($this->getEnabled() as $enabledKey => $enabledAddon) {
            /* @var Addon $enabledAddon */
            $requirements = array_change_key_case($enabledAddon->getRequirements());
            if (isset($requirements[$addon->getKey()])) {
                $result[$enabledKey] = $enabledAddon;
            }
        }
        return $result;
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
            if (isset($subdirs[$theme->getKey()])) {
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
        $classes = $addon->getClasses();
        foreach ($classes as $classKey => $classesInfo) {
            foreach ($classesInfo as $namespace => $classInfo) {
                $classInfo['addon'] = $addon;

                if (isset($this->autoloadClasses[$classKey])) {
                    $orderedByPriority = [];
                    $addonAdded = false;
                    foreach($this->autoloadClasses[$classKey] as $loadedClassNamespace => $loadedClassData) {
                        if (!$addonAdded && $addon->getPriority() < $loadedClassData['addon']->getPriority()) {
                            $orderedByPriority[$namespace] = $classInfo;
                            $addonAdded = true;
                        }
                        $orderedByPriority[$loadedClassNamespace] = $loadedClassData;
                    }
                    // Make sure we add the addon hes last after the priority check!
                    if (!$addonAdded) {
                        $orderedByPriority[$namespace] = $classInfo;
                    }
                    $this->autoloadClasses[$classKey] = $orderedByPriority;
                } else {
                    $this->autoloadClasses[$classKey][$namespace] = $classInfo;
                }
            }
        }
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
        foreach ($addon->getClasses() as $classKey => $classInfo) {
            if (isset($this->autoloadClasses[$classKey])) {
                foreach($this->autoloadClasses[$classKey] as $namespace => $classData) {
                    if ($classData['namespace'] === $namespace) {
                        unset($this->autoloadClasses[$classKey][$namespace]);
                    }
                }
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
     * @return int Returns the number of addons that were enabled.
     */
    public function startAddonsByKey($keys, $type) {
        // Filter out false keys.
        $keys = array_filter((array)$keys);

        $count = 0;
        foreach ($keys as $key => $value) {
            if (in_array($value, [true, 1, '1'], true)) {
                // This addon key is represented as addon => true.
                $lookup = $key;
            } else {
                // This addon is represented as addon => folder.
                $lookup = $value;
            }
            $addon = $this->lookupByType($lookup, $type);
            if (empty($addon)) {
                trigger_error("The $type with key $lookup could not be found and will not be started.");
            } else {
                $this->startAddon($addon);
                $count++;
            }
        }
        return $count;
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
     * Get the paths to the current translation files.
     *
     * @param string $locale The locale to get the translation paths for.
     * @return array Returns an array of paths.
     */
    public function getEnabledTranslationPaths($locale) {
        $addons = array_reverse($this->getEnabled(), true);

        $result = [];
        foreach ($addons as $addon) {
            /* @var Addon $addon */
            foreach ($addon->getTranslationPaths($locale) as $path) {
                $result[] = $addon->path($path);
            }
        }
        return $result;
    }

    /**
     * Bind the events of all of the addon plugin classes managed by this class.
     *
     * This method also includes the plugin classes that haven't been included yet.
     *
     * @param EventManager $eventManager The event manager to bind the plugin classes to.
     */
    public function bindAllEvents(EventManager $eventManager) {
        $enabled = $this->getEnabled();

        foreach ($enabled as $addon) {
            /* @var \Vanilla\Addon $addon */
            if ($pluginClass = $addon->getPluginClass()) {
                // Include the plugin here, rather than wait for it to hit the autoloader. This way is much faster.
                include_once $addon->getClassPath($pluginClass);

                $this->bindAddonEvents($addon, $eventManager);
            }
        }
    }

    /**
     * Bind the events for an addon's plugin class (if any).
     *
     * If the addon doesn't have a plugin then nothing will happen.
     *
     * @param Addon $addon The addon to bind.
     * @param EventManager $eventManager The event manager to bind the plugin classes to.
     */
    public function bindAddonEvents(Addon $addon, EventManager $eventManager) {
        // Check that the addon has a plugin.
        if (!($pluginClass = $addon->getPluginClass())) {
            return;
        }

        // Only register the plugin if it implements the Gdn_IPlugin interface.
        if (is_a($pluginClass, 'Gdn_IPlugin', true)) {
            $eventManager->bindClass($pluginClass, $addon->getPriority());
        } else {
            trigger_error("$pluginClass does not implement Gdn_IPlugin", E_USER_DEPRECATED);
        }
    }
}
