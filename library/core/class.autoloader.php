<?php
/**
 * Vanilla framework autoloader: Gdn_Autoloader & Gdn_Autoloader_Map
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0.16
 */

/**
 * Handles indexing of class files across the entire framework, as well as bringing those
 * classes into scope as needed.
 *
 * This is a static class that hooks into the SPL autoloader.
 */
class Gdn_Autoloader {
    const CONTEXT_APPLICATION = 'application';
    const CONTEXT_PLUGIN = 'plugin';
    const CONTEXT_LOCALE = 'locale';
    const CONTEXT_THEME = 'theme';

    const MAP_LIBRARY = 'library';
    const MAP_CONTROLLER = 'controller';
    const MAP_PLUGIN = 'plugin';
    const MAP_VENDORS = 'vendors';

    const PRIORITY_TYPE_PREFER = 'prefer';
    const PRIORITY_TYPE_RESTRICT = 'restrict';

    const PRIORITY_ONCE = 'once';
    const PRIORITY_PERSIST = 'persist';

    /** @var array Array of registered maps to search during load requests */
    protected static $maps;

    /** @var array Array of pathname prefixes used to namespace similar libraries */
    protected static $prefixes;

    /** @var array Array of contexts used to establish search order */
    protected static $contextOrder;

    /** @var array Array of maps that pertain to the same CONTEXT+Extension */
    protected static $mapGroups;

    /** @var array List of priority/preferred CONTEXT+Extension[+MapType] groups for the next lookup */
    protected static $priorities;

    /**
     * Attach mappings for vanilla extension folders.
     *
     * @param string $extensionType The type of extension to map.
     * This should be one of: CONTEXT_THEME, CONTEXT_PLUGIN, CONTEXT_APPLICATION.
     */
    public static function attach($extensionType) {

        switch ($extensionType) {
            case self::CONTEXT_APPLICATION:

                if (Gdn::applicationManager() instanceof Gdn_ApplicationManager) {
                    $enabledApplications = Gdn::applicationManager()->enabledApplicationFolders();

                    foreach ($enabledApplications as $enabledApplication) {
                        self::attachApplication($enabledApplication);
                    }
                }

                break;

            case self::CONTEXT_PLUGIN:

                if (Gdn::pluginManager() instanceof Gdn_PluginManager) {
                    foreach (Gdn::pluginManager()->searchPaths() as $searchPath => $searchPathName) {
                        if ($searchPathName === true || $searchPathName == 1) {
                            $searchPathName = md5($searchPath);
                        }

                        // If we have already loaded the plugin manager, use its internal folder list
                        if (Gdn::pluginManager()->started()) {
                            $folders = Gdn::pluginManager()->enabledPluginFolders($searchPath);
                            foreach ($folders as $pluginFolder) {
                                $fullPluginPath = combinePaths([$searchPath, $pluginFolder]);
                                self::registerMap(self::MAP_LIBRARY, self::CONTEXT_PLUGIN, $fullPluginPath, [
                                    'SearchSubfolders' => true,
                                    'Extension' => $searchPathName,
                                    'Structure' => Gdn_Autoloader_Map::STRUCTURE_SPLIT,
                                    'SplitTopic' => strtolower($pluginFolder),
                                    'PreWarm' => true
                                ]);
                            }

                            $pluginMap = self::getMap(self::MAP_LIBRARY, self::CONTEXT_PLUGIN);
                            if ($pluginMap && !$pluginMap->mapIsOnDisk()) {
                                Gdn::pluginManager()->forceAutoloaderIndex();
                            }
                        }
                    }
                }

                break;

            case self::CONTEXT_THEME:

                break;
        }

    }

    /**
     * Make an application's classes available to the autoloader.
     *
     * @param string $application The name of the application.
     */
    public static function attachApplication($application) {
        $applicationPath = combinePaths([PATH_APPLICATIONS."/{$application}"]);

        $appControllers = combinePaths([$applicationPath."/controllers"]);
        self::registerMap(self::MAP_CONTROLLER, self::CONTEXT_APPLICATION, $appControllers, [
            'SearchSubfolders' => false,
            'Extension' => $application
        ]);

        $appModels = combinePaths([$applicationPath."/models"]);
        self::registerMap(self::MAP_LIBRARY, self::CONTEXT_APPLICATION, $appModels, [
            'SearchSubfolders' => false,
            'Extension' => $application,
            'ClassFilter' => '*model'
        ]);

        $appModules = combinePaths([$applicationPath."/modules"]);
        self::registerMap(self::MAP_LIBRARY, self::CONTEXT_APPLICATION, $appModules, [
            'SearchSubfolders' => false,
            'Extension' => $application,
            'ClassFilter' => '*module'
        ]);

        $appLibrary = combinePaths([$applicationPath."/library"]);
        self::registerMap(self::MAP_LIBRARY, self::CONTEXT_APPLICATION, $appLibrary, [
            'SearchSubfolders' => false,
            'Extension' => $application,
            'ClassFilter' => '*'
        ]);
    }

    /**
     *
     *
     * @param $className
     * @param $mapType
     * @return bool
     */
    protected static function doLookup($className, $mapType) {
        // We loop over the caches twice. First, hit only their cached data.
        // If all cache hits miss, search filesystem.

        if (!is_array(self::$maps)) {
            return false;
        }

        $priorities = [];

        // Binary flip - cacheonly or cache+fs
        foreach ([true, false] as $mapOnly) {
            $skipMaps = [];
            $contextType = null;
            $skipTillNextContext = false;

            // Drill to the caches associated with this map type
            foreach (self::$maps as $mapHash => &$map) {
                if ($mapType !== null && $map->mapType() != $mapType) {
                    continue;
                }

                $mapContext = self::getContextType($mapHash);
                if ($mapContext != $contextType) {
                    // Hit new context
                    $skipMaps = [];
                    $contextType = $mapContext;

                    if (!array_key_exists($contextType, $priorities)) {
                        $priorities[$contextType] = self::priorities($contextType, $mapType);
                    }

                    if (array_key_exists($contextType, $priorities) && is_array($priorities[$contextType])) {
                        foreach ($priorities[$contextType] as $priorityMapHash => $priorityInfo) {
                            // If we're in a RESTRICT priority and we come to the end, wait till we hit the next context before looking further
                            if ($priorityMapHash == 'FAIL_CONTEXT_IF_NOT_FOUND') {
                                $skipTillNextContext = true;
                                break;
                            }
                            $priorityMap = self::map($priorityMapHash);

                            $file = $priorityMap->lookup($className, $mapOnly);
                            if ($file !== false) {
                                return $file;
                            }

                            // Don't check this map again
                            array_push($skipMaps, $priorityMapHash);
                        }
                    }
                }

                // If this map was already checked by a priority, or if we've exhausted a RESTRICT priority, skip maps until the next
                // context level is reached.
                if (in_array($mapHash, $skipMaps) || $skipTillNextContext === true) {
                    continue;
                }

                // Finally, search this map.
                $file = $map->lookup($className, $mapOnly);
                if ($file !== false) {
                    return $file;
                }
            }
        }

        return false;
    }

    /**
     *
     *
     * @param $mapHash
     * @return bool
     */
    public static function getContextType($mapHash) {
        $matched = preg_match('/^context:(\d+)_.*/', $mapHash, $matches);
        if ($matched && isset($matches[1])) {
            $contextIdentifier = $matches[1];
        } else {
            return false;
        }

        if (isset($contextIdentifier, self::$contextOrder)) {
            return self::$contextOrder[$contextIdentifier];
        }
        return false;
    }

    /**
     * Figure out which map type contains a class.
     *
     * @param string $className
     * @return string
     */
    public static function getMapType($className) {
        // Strip leading 'Gdn_'
        if (substr($className, 0, 4) == 'Gdn_') {
            $className = substr($className, 4);
        }

        $className = strtolower($className);
        $length = strlen($className);

        if (substr($className, -10) == 'controller' && $length > 10) {
            return self::MAP_CONTROLLER;
        }

        return self::MAP_LIBRARY;
    }

    /**
     * Lookup a class.
     *
     * @param string $ClassName
     * @param array $Options
     * @return string? Returns the path to the file that contains the class or null if there is no file.
     */
    public static function lookup($ClassName, $Options = []) {
        if (!preg_match("/^[a-zA-Z0-9_\x7f-\xff]*$/", $ClassName)) {
            return;
        }

        $MapType = val('MapType', $Options, self::getMapType($ClassName));

        $DefaultOptions = [
            'Quiet' => false,
            'RespectPriorities' => true
        ];
        $Options = array_merge($DefaultOptions, $Options);

        $File = self::doLookup($ClassName, $MapType);

        if ($File !== false) {
            if (!isset($Options['Quiet']) || !$Options['Quiet']) {
                include_once $File;
            }
        }
        return $File;
    }

    /**
     * Get an Autoloader Map by hash.
     *
     * @param type $mapHash
     * @return Gdn_Autoloader_Map
     */
    public static function map($mapHash) {
        if (array_key_exists($mapHash, self::$maps)) {
            return self::$maps[$mapHash];
        }

        if (is_null($mapHash)) {
            return self::$maps;
        }
        return false;
    }

    /**
     * Lookup and return an autoloader map.
     *
     * @param type $mapType
     * @param type $contextType
     * @param type $extension
     * @param type $mapRootLocation
     * @return Gdn_Autoloader_Map
     */
    public static function getMap($mapType, $contextType, $extension = self::CONTEXT_PLUGIN, $mapRootLocation = PATH_CACHE) {
        $mapHash = self::makeMapHash($mapType, $contextType, $extension, $mapRootLocation);
        return self::map($mapHash);
    }

    public static function priority($contextType, $extension, $mapType = null, $priorityType = self::PRIORITY_TYPE_PREFER, $priorityDuration = self::PRIORITY_ONCE) {
        $mapGroupIdentifier = implode('|', [
            $contextType,
            $extension
        ]);

        $mapGroupHashes = val($mapGroupIdentifier, self::$mapGroups, []);
        $priorityHashes = [];
        $priorityHashes = [];
        foreach ($mapGroupHashes as $mapHash => $trash) {
            $thisMapType = self::map($mapHash)->mapType();
            // We're restricting this priority to a certain map type, so exclude non-matches.
            if (!is_null($mapType) && $thisMapType != $mapType) {
                continue;
            }

            $priorityHashes[$mapHash] = [
                'maptype' => $thisMapType,
                'duration' => $priorityDuration,
                'prioritytype' => $priorityType
            ];
        }

        if (!sizeof($priorityHashes)) {
            return false;
        }

        if (!is_array(self::$priorities)) {
            self::$priorities = [];
        }

        if (!array_key_exists($contextType, self::$priorities)) {
            self::$priorities[$contextType] = [
                self::PRIORITY_TYPE_RESTRICT => [],
                self::PRIORITY_TYPE_PREFER => []
            ];
        }

        // Add new priorities to list
        self::$priorities[$contextType][$priorityType] = array_merge(
            self::$priorities[$contextType][$priorityType],
            $priorityHashes
        );

        return true;
    }

    /**
     *
     *
     * @param $contextType
     * @param null $mapType
     * @return array|bool
     */
    public static function priorities($contextType, $mapType = null) {
        if (!is_array(self::$priorities) || !array_key_exists($contextType, self::$priorities)) {
            return false;
        }

        /**
         * First, gather the RESTRICT requirements. If these exist, they are the only hashes that will be sent, and a 'FAIL_IF_NOT_FOUND'
         * flag will be appended to the list to halt lookups.
         *
         * If there are no RESTRICT priorities, check for PREFER priorities and send those.
         *
         * Always optionally filter on $MapType if provided.
         */
        foreach ([self::PRIORITY_TYPE_RESTRICT, self::PRIORITY_TYPE_PREFER] as $priorityType) {
            if (!sizeof(self::$priorities[$contextType][$priorityType])) {
                continue;
            }

            $resultMapHashes = self::$priorities[$contextType][$priorityType];
            $responseHashes = [];
            foreach ($resultMapHashes as $mapHash => $priorityInfo) {
                if (val('duration', $priorityInfo) == self::PRIORITY_ONCE) {
                    unset(self::$priorities[$contextType][$priorityType][$mapHash]);
                }

                // If this request is being specific about the required map type, reject anything that doesn't match
                if (!is_null($mapType) && val('maptype', $priorityInfo) != $mapType) {
                    continue;
                }

                $responseHashes[$mapHash] = $priorityInfo;
            }

            if ($priorityType == self::PRIORITY_TYPE_RESTRICT) {
                $responseHashes['FAIL_CONTEXT_IF_NOT_FOUND'] = true;
            }

            return $responseHashes;
        }

        return false;
    }

    /**
     *
     *
     * @param $mapType
     * @param $contextType
     * @param $searchPath
     * @param array $options
     * @return mixed
     */
    public static function registerMap($mapType, $contextType, $searchPath, $options = []) {
        $defaultOptions = [
            'SearchSubfolders' => true,
            'Extension' => null,
            'ContextPrefix' => null,
            'ClassFilter' => '*',
            'SaveToDisk' => true
        ];
        if (array_key_exists($contextType, self::$prefixes)) {
            $defaultOptions['ContextPrefix'] = val($contextType, self::$prefixes);
        }

        $options = array_merge($defaultOptions, $options);

        $extension = val('Extension', $options, null);

        // Determine cache root on-disk location
        $hits = 0;
        str_replace(PATH_ROOT, '', $searchPath, $hits);
        $mapRootLocation = PATH_CACHE;

        // Build a unique identifier that refers to this map (same map type, context, extension, and cachefile location)
        $mapIdentifier = implode('|', [
            $mapType,
            $contextType,
            $extension,
            $mapRootLocation
        ]);
        $options['MapIdentifier'] = $mapIdentifier;
        $mapHash = md5($mapIdentifier);

        // Allow intrinsic ordering / layering of contexts by prefixing them with a context number
        $mapHash = 'context:'.val($contextType, array_flip(self::$contextOrder)).'_'.$extension.'_'.$mapHash;
        $mapHash = self::makeMapHash($mapType, $contextType, $extension, $mapRootLocation);

        if (!is_array(self::$maps)) {
            self::$maps = [];
        }

        if (!array_key_exists($mapHash, self::$maps)) {
            $map = Gdn_Autoloader_Map::load($mapType, $contextType, $mapRootLocation, $options);
            self::$maps[$mapHash] = $map;
        }

        ksort(self::$maps, SORT_REGULAR);

        $addPathResult = self::$maps[$mapHash]->addPath($searchPath, $options);

        /*
         * Build a unique identifier that refers to this cached list (context and extension)
         *
         * For example, CONTEXT_APPLICATION and 'dashboard' would refer to all maps that store
         * information about the dashboard application: its controllers, models, modules, etc.
         */
        $mapGroupIdentifier = implode('|', [
            $contextType,
            $extension
        ]);

        if (!is_array(self::$mapGroups)) {
            self::$mapGroups = [];
        }

        if (!array_key_exists($mapGroupIdentifier, self::$mapGroups)) {
            self::$mapGroups[$mapGroupIdentifier] = [];
        }

        self::$mapGroups[$mapGroupIdentifier][$mapHash] = true;

        return $addPathResult;
    }

    /**
     *
     *
     * @param $mapType
     * @param $contextType
     * @param $extension
     * @param $mapRootLocation
     * @return string
     */
    public static function makeMapHash($mapType, $contextType, $extension, $mapRootLocation) {
        $mapIdentifier = implode('|', [
            $mapType,
            $contextType,
            $extension,
            $mapRootLocation
        ]);

        $mapHash = md5($mapIdentifier);

        // Allow intrinsic ordering / layering of contexts by prefixing them with a context number
        $mapHash = 'context:'.val($contextType, array_flip(self::$contextOrder)).'_'.$extension.'_'.$mapHash;

        return $mapHash;
    }

    /**
     *
     *
     * @param $mapType
     * @param $contextType
     * @param string $extension
     */
    public static function forceIndex($mapType, $contextType, $extension = self::CONTEXT_PLUGIN) {
        $map = self::getMap($mapType, $contextType, $extension);
        $map->index();
    }

    /**
     * This method frees the map storing information about the specified resource.
     *
     * Takes a map type, and an array of information about the resource in question:
     * a plugin info array, in the case of a plugin, or
     *
     * @param string $contextType type of map to consider (one of the MAP_ constants).
     * @param array $mapResourceArray array of information about the mapped resource.
     */
    public static function smartFree($contextType = null, $mapResourceArray = null) {

        $cacheFolder = @opendir(PATH_CACHE);
        if (!$cacheFolder) {
            return true;
        }
        while ($file = readdir($cacheFolder)) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($extension == 'ini' && $file != 'locale_map.ini') {
                @unlink(combinePaths([PATH_CACHE, $file]));
            }
        }

    }

    /**
     * Register core mappings.
     *
     * Set up the autoloader with known search directories, hook into the SPL autoloader
     * and load existing caches.
     */
    public static function start() {

        self::$prefixes = [
            self::CONTEXT_APPLICATION => 'a',
            self::CONTEXT_PLUGIN => 'p',
            self::CONTEXT_THEME => 't'
        ];
        self::$contextOrder = [
            self::CONTEXT_THEME,
            self::CONTEXT_LOCALE,
            self::CONTEXT_PLUGIN,
            self::CONTEXT_APPLICATION
        ];

        self::$maps = [];
        self::$mapGroups = [];

        // Register autoloader with the SPL
        spl_autoload_register(['Gdn_Autoloader', 'lookup']);

        // Register shutdown function to auto save changed cache files
        register_shutdown_function(['Gdn_Autoloader', 'shutdown']);
    }

    /**
     * Save the current caches.
     *
     * This method executes once, just as the framework is shutting down. Its purpose
     * is to save the library maps to disk if they've changed.
     */
    public static function shutdown() {
        foreach (self::$maps as $mapHash => &$map) {
            $map->shutdown();
        }
    }
}

class Gdn_Autoloader_Map {

    /** Sprintf format string that describes the on-disk name of the mapping caches. */
    const DISK_MAP_NAME_FORMAT = '%s/%s_map.ini';

    /** Name format for all class files. */
    const LOOKUP_CLASS_MASK = 'class.%s.php';

    /** Name format for all interface files. */
    const LOOKUP_INTERFACE_MASK = 'interface.%s.php';

    /** Map file structure type: File contains one topic, 'cache', with all classname -> file entries in a single list. */
    const STRUCTURE_FLAT = 'flat';

    /** Map file structure type: File contains a topic for each path, with only that path's files in the list. */
    const STRUCTURE_SPLIT = 'split';

    /** TOPIC_DEFAULT */
    const TOPIC_DEFAULT = 'cache';

    /** @var array  */
    protected $buildOptions;

    /** @var array  */
    protected $mapInfo;

    /** @var array  */
    protected $map;

    /** @var array  */
    protected $ignore;

    /** @var array  */
    protected $paths;

    private function __construct($mapType, $contextType, $mapRootLocation, $options) {
        $this->map = null;
        $this->ignore = ['.', '..'];
        $this->paths = [];
        $this->buildOptions = $options;

        $extensionName = val('Extension', $options, null);
        $recursive = val('SearchSubfolders', $options, true);
        $contextPrefix = val('ContextPrefix', $options, null);
        $mapIdentifier = val('MapIdentifier', $options, null);
        $saveToDisk = val('SaveToDisk', $options, true);
        $fileStructure = val('Structure', $options, self::STRUCTURE_FLAT);

        $mapName = $mapType;
        if (!is_null($extensionName)) {
            $mapName = $extensionName.'_'.$mapName;
        }

        if (!is_null($contextPrefix)) {
            $mapName = $contextPrefix.'_'.$mapName;
        }

        $onDiskMapFile = sprintf(self::DISK_MAP_NAME_FORMAT, $mapRootLocation, strtolower($mapName));

        $this->mapInfo = [
            'ondisk' => $onDiskMapFile,
            'identifier' => $mapIdentifier,
            'root' => $mapRootLocation,
            'name' => $mapName,
            'maptype' => $mapType,
            'contexttype' => $contextType,
            'extension' => $extensionName,
            'dirty' => false,
            'save' => $saveToDisk,
            'structure' => $fileStructure
        ];
    }

    public function mapIsOnDisk() {
        return file_exists($this->mapInfo['ondisk']);
    }

    /**
     *
     *
     * @param $searchPath
     * @param $options
     * @throws Exception
     */
    public function addPath($searchPath, $options) {
        $pathOptions = [
            'path' => $searchPath,
            'recursive' => (bool)val('SearchSubfolders', $options),
            'filter' => val('ClassFilter', $options),
            'topic' => self::TOPIC_DEFAULT
        ];

        if ($this->mapInfo['structure'] == self::STRUCTURE_SPLIT) {
            $splitTopic = val('SplitTopic', $options, null);
            if (is_null($splitTopic)) {
                throw new Exception("Trying to use 'split' structure but no SplitTopic provided. Path: {$searchPath}");
            }
            $pathOptions['topic'] = $splitTopic;
        }

        $this->paths[$searchPath] = $pathOptions;
    }

    /**
     *
     *
     * @return mixed
     */
    public function contextType() {
        return $this->mapInfo['contexttype']; //val('contexttype', $this->MapInfo);
    }

    /**
     *
     *
     * @return mixed
     */
    public function extension() {
        return $this->mapInfo['extension']; //val('extension', $this->MapInfo);
    }

    /**
     *
     *
     * @param string $path
     * @param array|string $searchFiles
     * @param bool $recursive
     * @return bool|The
     */
    protected function findFile($path, $searchFiles, $recursive) {
        if (!is_array($searchFiles)) {
            $searchFiles = [$searchFiles];
        }

        $folder = basename($path);
        if (!is_dir($path) || substr($folder, 0, 1) === '.' || $folder === 'node_modules') {
            return false;
        }

        $files = scandir($path);
        foreach ($files as $fileName) {
            if (in_array($fileName, $this->ignore)) {
                continue;
            }
            $fullPath = combinePaths([$path, $fileName]);

            // If this is a folder...
            if (is_dir($fullPath)) {
                if ($recursive) {
                    $file = $this->findFile($fullPath, $searchFiles, $recursive);
                    if ($file !== false) {
                        return $file;
                    }
                    continue;
                } else {
                    continue;
                }
            }

            if (in_array($fileName, $searchFiles)) {
                return $fullPath;
            }
        }
        return false;
    }

    /**
     *
     *
     * @param $path
     * @param $fileMasks
     * @param $recursive
     * @return array|bool
     */
    protected function findFiles($path, $fileMasks, $recursive) {
        if (!is_array($fileMasks)) {
            $fileMasks = [$fileMasks];
        }

        if (!is_dir($path)) {
            return false;
        }

        $foundFiles = [];
        $files = scandir($path);
        foreach ($files as $fileName) {
            if (in_array($fileName, $this->ignore)) {
                continue;
            }
            $fullPath = combinePaths([$path, $fileName]);

            // If this is a folder, maybe recurse it eh?
            if (is_dir($fullPath)) {
                if ($recursive) {
                    $recurse = $this->findFiles($fullPath, $fileMasks, $recursive);
                    if ($recurse !== false) {
                        $foundFiles = array_merge($foundFiles, $recurse);
                    }
                    continue;
                } else {
                    continue;
                }
            } else {
                foreach ($fileMasks as $fileMask) {
                    // If this file matches one of the masks, add it to this loops's found files
                    if (fnmatch($fileMask, $fileName)) {
                        $foundFiles[] = $fullPath;
                        break;
                    }
                }
            }
        }

        return $foundFiles;
    }

    /**
     * Autoloader cache static constructor
     *
     * @return Gdn_Autoloader_Map
     */
    public static function load($mapType, $contextType, $mapRootLocation, $options) {
        return new Gdn_Autoloader_Map($mapType, $contextType, $mapRootLocation, $options);
    }

    /**
     *
     *
     * @param $className
     * @param bool $mapOnly
     * @return bool|The
     */
    public function lookup($className, $mapOnly = true) {
        $mapName = $this->mapInfo['name'];

        // Lazyload cache data
        if (is_null($this->map)) {
            $this->map = [];
            $onDiskMapFile = $this->mapInfo['ondisk'];

            // Loading cache data from disk
            if (file_exists($onDiskMapFile)) {
                $structure = $this->mapInfo['structure'];
                $mapContents = parse_ini_file($onDiskMapFile, true);

                try {
                    // Detect legacy flat files which are now stored split
                    if ($structure == self::STRUCTURE_SPLIT && array_key_exists(self::TOPIC_DEFAULT, $mapContents)) {
                        throw new Exception();
                    }

                    // Bad file?
                    if ($mapContents == false || !is_array($mapContents)) {
                        throw new Exception();
                    }

                    // All's well that ends well. Load the cache.
                    $this->map = $mapContents;
                } catch (Exception $ex) {
                    @unlink($onDiskMapFile);
                }
            }
        }

        // Always look by lowercase classname
        $className = strtolower($className);

        switch ($this->mapInfo['structure']) {
            case 'split':
                // This file stored split, so look for this class in each virtual sub-path, by topic
                foreach ($this->paths as $path => $pathOptions) {
                    $lookupSplitTopic = val('topic', $pathOptions);
                    if (array_key_exists($lookupSplitTopic, $this->map) && array_key_exists($className, $this->map[$lookupSplitTopic])) {
                        return val($className, $this->map[$lookupSplitTopic]);
                    }
                }
                break;

            default:
            case 'flat':
                // This file is stored flat, so just look in the DEFAULT_TOPIC
                $lookupSplitTopic = self::TOPIC_DEFAULT;
                if (array_key_exists($lookupSplitTopic, $this->map) && array_key_exists($className, $this->map[$lookupSplitTopic])) {
                    return val($className, $this->map[$lookupSplitTopic]);
                }
                break;
        }

        // Look at the filesystem, too
        if (!$mapOnly) {
            if (substr($className, 0, 4) == 'gdn_') {
                $fSClassName = substr($className, 4);
            } else {
                $fSClassName = $className;
            }

            $files = [
                sprintf(self::LOOKUP_CLASS_MASK, $fSClassName),
                sprintf(self::LOOKUP_INTERFACE_MASK, $fSClassName)
            ];

            foreach ($this->paths as $path => $pathOptions) {
                $classFilter = val('filter', $pathOptions);
                if (!fnmatch($classFilter, $className)) {
                    continue;
                }

                $recursive = val('recursive', $pathOptions);
                $file = $this->findFile($path, $files, $recursive);

                if ($file !== false) {
                    $splitTopic = val('topic', $pathOptions, self::TOPIC_DEFAULT);
                    $this->map[$splitTopic][$className] = $file;
                    $this->mapInfo['dirty'] = true;

                    return $file;
                }
            }
        }

        return false;
    }

    /**
     * Try to index the entire map
     *
     * @return void
     */
    public function index($extraPaths = null) {

        $fileMasks = [
            sprintf(self::LOOKUP_CLASS_MASK, '*'),
            sprintf(self::LOOKUP_INTERFACE_MASK, '*')
        ];

        $extraPathsRemove = null;
        if (!is_null($extraPaths)) {
            $extraPathsRemove = [];
            foreach ($extraPaths as $pathOpts) {
                $extraPath = val('path', $pathOpts);
                if (array_key_exists($extraPath, $this->paths)) {
                    continue;
                }

                $extraPathsRemove[] = $extraPath;
                $extraOptions = $this->buildOptions;
                $extraOptions['SplitTopic'] = val('topic', $pathOpts);
                $this->addPath($extraPath, $extraOptions);
            }
        }

        foreach ($this->paths as $path => $pathOptions) {
            $recursive = val('recursive', $pathOptions);
            $files = $this->findFiles($path, $fileMasks, $recursive);
            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                $splitTopic = val('topic', $pathOptions, self::TOPIC_DEFAULT);

                $providedClass = $this->getClassNameFromFile($file);
                if ($providedClass) {
                    $this->map[$splitTopic][$providedClass] = $file;
                    $this->mapInfo['dirty'] = true;
                }

//            $ProvidesClasses = $this->investigate($File);
//            if ($ProvidesClasses === false) continue;
//
//            foreach ($ProvidesClasses as $ProvidedClass) {
//               $ProvidedClass = strtolower($ProvidedClass);
//               $this->Map[$SplitTopic][$ProvidedClass] = $File;
//               $this->MapInfo['dirty'] = true;
//            }
            }
        }

        // Save
        $this->shutdown();

        if (!is_null($extraPathsRemove)) {
            foreach ($extraPathsRemove as $remPath) {
                unset($this->paths[$remPath]);
            }
        }
    }

    /**
     *
     *
     * @param $file
     * @return array|void
     */
    protected function investigate($file) {
        if (!file_exists($file)) {
            return;
        }
        $lines = file($file);
        $classesFound = [];
        foreach ($lines as $line) {
            //strtolower(substr(trim($Line), 0, 6)) == 'class '
            if (preg_match('/^class[\s]+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\s]+(?:.*)?{/', $line, $matches)) {
                $classesFound[] = $matches[1];
                continue;
            }

            if (preg_match('/^interface[\s]+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\s]+(?:.*)?{/', $line, $matches)) {
                $classesFound[] = $matches[1];
                continue;
            }
        }
        return $classesFound;
    }

    /**
     *
     *
     * @param $file
     * @return bool|mixed
     */
    protected function getClassNameFromFile($file) {
        if (stringEndsWith($file, '.plugin.php')) {
            return false;
        }

        $fileName = basename($file);
        $modes = [
            'class' => self::LOOKUP_CLASS_MASK,
            'interface' => self::LOOKUP_INTERFACE_MASK
        ];
        foreach ($modes as $modeType => $modeMask) {
            $matchModeMask = sprintf($modeMask, '*');
            if (fnmatch($matchModeMask, $fileName)) {
                $modeRE = '/^'.str_replace('.', '\.', $modeMask).'$/';
                $modeRE = sprintf($modeRE, '(.*)');
                $matched = preg_match($modeRE, $fileName, $matches);
                if ($matched) {
                    return str_replace('.', '', strtolower($matches[1]));
                }
            }
        }
        return false;
    }

    /**
     *
     *
     * @return mixed
     */
    public function mapType() {
        return $this->mapInfo['maptype'];
    }

    /**
     * Normalize paths
     *
     * Replaces any "\" with "/" and prepends another slash to a single leading slash. 
     *
     * @param string $path
     * @return string
     */
    function fixBackSlash($path) {
        // Convert to slash to avoid parse_in_file create array with missing backslash.
        $path = str_replace('\\', '/', $path);

        // If there is only 1 slash, add another to have a valid network path.
        if (preg_match('`^/[^/]`', $path) && stripos(PHP_OS, 'win') === 0) {
            $path = '/'.$path;
        }

        return $path;
    }

    /**
     *
     *
     * @return bool
     */
    public function shutdown() {

        if (!val('dirty', $this->mapInfo)) {
            return false;
        }
        if (!val('save', $this->mapInfo)) {
            return false;
        }

        if (!sizeof($this->map)) {
            return false;
        }

        $mapName = val('name', $this->mapInfo);
        $fileName = val('ondisk', $this->mapInfo);

        $mapContents = '';
        foreach ($this->map as $splitTopic => $topicFiles) {
            $mapContents .= "[{$splitTopic}]\n";
            foreach ($topicFiles as $className => $location) {
                $location = $this->fixBackSlash($location);
                $mapContents .= "{$className} = \"{$location}\"\n";
            }
        }

        try {
            Gdn_FileSystem::saveFile($fileName, $mapContents, LOCK_EX);
            $this->mapInfo['dirty'] = false;
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
