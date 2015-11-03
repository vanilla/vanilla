<?php
/**
 * Vanilla framework autoloader: Gdn_Autoloader & Gdn_Autoloader_Map
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
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

    const CONTEXT_CORE = 'core';
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
     * @param string $ExtensionType The type of extension to map.
     * This should be one of: CONTEXT_THEME, CONTEXT_PLUGIN, CONTEXT_APPLICATION.
     */
    public static function attach($ExtensionType) {

        switch ($ExtensionType) {
            case self::CONTEXT_APPLICATION:

                if (Gdn::applicationManager() instanceof Gdn_ApplicationManager) {
                    $EnabledApplications = Gdn::applicationManager()->enabledApplicationFolders();

                    foreach ($EnabledApplications as $EnabledApplication) {
                        self::attachApplication($EnabledApplication);
                    }
                }

                break;

            case self::CONTEXT_PLUGIN:

                if (Gdn::pluginManager() instanceof Gdn_PluginManager) {
                    foreach (Gdn::pluginManager()->searchPaths() as $SearchPath => $SearchPathName) {
                        if ($SearchPathName === true || $SearchPathName == 1) {
                            $SearchPathName = md5($SearchPath);
                        }

                        // If we have already loaded the plugin manager, use its internal folder list
                        if (Gdn::pluginManager()->started()) {
                            $Folders = Gdn::pluginManager()->enabledPluginFolders($SearchPath);
                            foreach ($Folders as $PluginFolder) {
                                $FullPluginPath = combinePaths(array($SearchPath, $PluginFolder));
                                self::registerMap(self::MAP_LIBRARY, self::CONTEXT_PLUGIN, $FullPluginPath, array(
                                    'SearchSubfolders' => true,
                                    'Extension' => $SearchPathName,
                                    'Structure' => Gdn_Autoloader_Map::STRUCTURE_SPLIT,
                                    'SplitTopic' => strtolower($PluginFolder),
                                    'PreWarm' => true
                                ));
                            }

                            $PluginMap = self::getMap(self::MAP_LIBRARY, self::CONTEXT_PLUGIN);
                            if ($PluginMap && !$PluginMap->mapIsOnDisk()) {
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
     * @param string $Application The name of the application.
     */
    public static function attachApplication($Application) {
        $ApplicationPath = combinePaths(array(PATH_APPLICATIONS."/{$Application}"));

        $AppControllers = combinePaths(array($ApplicationPath."/controllers"));
        self::registerMap(self::MAP_CONTROLLER, self::CONTEXT_APPLICATION, $AppControllers, array(
            'SearchSubfolders' => false,
            'Extension' => $Application
        ));

        $AppModels = combinePaths(array($ApplicationPath."/models"));
        self::registerMap(self::MAP_LIBRARY, self::CONTEXT_APPLICATION, $AppModels, array(
            'SearchSubfolders' => false,
            'Extension' => $Application,
            'ClassFilter' => '*model'
        ));

        $AppModules = combinePaths(array($ApplicationPath."/modules"));
        self::registerMap(self::MAP_LIBRARY, self::CONTEXT_APPLICATION, $AppModules, array(
            'SearchSubfolders' => false,
            'Extension' => $Application,
            'ClassFilter' => '*module'
        ));

        $AppLibrary = combinePaths(array($ApplicationPath."/library"));
        self::registerMap(self::MAP_LIBRARY, self::CONTEXT_APPLICATION, $AppLibrary, array(
            'SearchSubfolders' => false,
            'Extension' => $Application,
            'ClassFilter' => '*'
        ));
    }

    /**
     *
     *
     * @param $ClassName
     * @param $MapType
     * @return bool
     */
    protected static function doLookup($ClassName, $MapType) {
        // We loop over the caches twice. First, hit only their cached data.
        // If all cache hits miss, search filesystem.

        if (!is_array(self::$maps)) {
            return false;
        }

        $Priorities = array();

        // Binary flip - cacheonly or cache+fs
        foreach (array(true, false) as $MapOnly) {
            $SkipMaps = array();
            $ContextType = null;
            $SkipTillNextContext = false;

            // Drill to the caches associated with this map type
            foreach (self::$maps as $MapHash => &$Map) {
                if ($MapType !== null && $Map->mapType() != $MapType) {
                    continue;
                }

                $MapContext = self::getContextType($MapHash);
                if ($MapContext != $ContextType) {
                    // Hit new context
                    $SkipMaps = array();
                    $ContextType = $MapContext;

                    if (!array_key_exists($ContextType, $Priorities)) {
                        $Priorities[$ContextType] = self::priorities($ContextType, $MapType);
                    }

                    if (array_key_exists($ContextType, $Priorities) && is_array($Priorities[$ContextType])) {
                        foreach ($Priorities[$ContextType] as $PriorityMapHash => $PriorityInfo) {
                            // If we're in a RESTRICT priority and we come to the end, wait till we hit the next context before looking further
                            if ($PriorityMapHash == 'FAIL_CONTEXT_IF_NOT_FOUND') {
                                $SkipTillNextContext = true;
                                break;
                            }
                            $PriorityMap = self::map($PriorityMapHash);

                            $File = $PriorityMap->lookup($ClassName, $MapOnly);
                            if ($File !== false) {
                                return $File;
                            }

                            // Don't check this map again
                            array_push($SkipMaps, $PriorityMapHash);
                        }
                    }
                }

                // If this map was already checked by a priority, or if we've exhausted a RESTRICT priority, skip maps until the next
                // context level is reached.
                if (in_array($MapHash, $SkipMaps) || $SkipTillNextContext === true) {
                    continue;
                }

                // Finally, search this map.
                $File = $Map->lookup($ClassName, $MapOnly);
                if ($File !== false) {
                    return $File;
                }
            }
        }

        return false;
    }

    /**
     *
     *
     * @param $MapHash
     * @return bool
     */
    public static function getContextType($MapHash) {
        $Matched = preg_match('/^context:(\d+)_.*/', $MapHash, $Matches);
        if ($Matched && isset($Matches[1])) {
            $ContextIdentifier = $Matches[1];
        } else {
            return false;
        }

        if (isset($ContextIdentifier, self::$contextOrder)) {
            return self::$contextOrder[$ContextIdentifier];
        }
        return false;
    }

    /**
     * Figure out which map type contains a class.
     *
     * @param string $ClassName
     * @return string
     */
    public static function getMapType($ClassName) {
        // Strip leading 'Gdn_'
        if (substr($ClassName, 0, 4) == 'Gdn_') {
            $ClassName = substr($ClassName, 4);
        }

        $ClassName = strtolower($ClassName);
        $Length = strlen($ClassName);

        if (substr($ClassName, -10) == 'controller' && $Length > 10) {
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
    public static function lookup($ClassName, $Options = array()) {
        if (!preg_match("/^[a-zA-Z0-9_\x7f-\xff]*$/", $ClassName)) {
            return;
        }

        $MapType = val('MapType', $Options, self::getMapType($ClassName));

        $DefaultOptions = array(
            'Quiet' => false,
            'RespectPriorities' => true
        );
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
     * @param type $MapHash
     * @return Gdn_Autoloader_Map
     */
    public static function map($MapHash) {
        if (array_key_exists($MapHash, self::$maps)) {
            return self::$maps[$MapHash];
        }

        if (is_null($MapHash)) {
            return self::$maps;
        }
        return false;
    }

    /**
     * Lookup and return an autoloader map.
     *
     * @param type $MapType
     * @param type $ContextType
     * @param type $Extension
     * @param type $MapRootLocation
     * @return Gdn_Autoloader_Map
     */
    public static function getMap($MapType, $ContextType, $Extension = self::CONTEXT_CORE, $MapRootLocation = PATH_CACHE) {
        $MapHash = self::makeMapHash($MapType, $ContextType, $Extension, $MapRootLocation);
        return self::map($MapHash);
    }

    public static function priority($ContextType, $Extension, $MapType = null, $PriorityType = self::PRIORITY_TYPE_PREFER, $PriorityDuration = self::PRIORITY_ONCE) {
        $MapGroupIdentifier = implode('|', array(
            $ContextType,
            $Extension
        ));

        $MapGroupHashes = val($MapGroupIdentifier, self::$mapGroups, array());
        $PriorityHashes = array();
        $PriorityHashes = array();
        foreach ($MapGroupHashes as $MapHash => $Trash) {
            $ThisMapType = self::map($MapHash)->mapType();
            // We're restricting this priority to a certain map type, so exclude non-matches.
            if (!is_null($MapType) && $ThisMapType != $MapType) {
                continue;
            }

            $PriorityHashes[$MapHash] = array(
                'maptype' => $ThisMapType,
                'duration' => $PriorityDuration,
                'prioritytype' => $PriorityType
            );
        }

        if (!sizeof($PriorityHashes)) {
            return false;
        }

        if (!is_array(self::$priorities)) {
            self::$priorities = array();
        }

        if (!array_key_exists($ContextType, self::$priorities)) {
            self::$priorities[$ContextType] = array(
                self::PRIORITY_TYPE_RESTRICT => array(),
                self::PRIORITY_TYPE_PREFER => array()
            );
        }

        // Add new priorities to list
        self::$priorities[$ContextType][$PriorityType] = array_merge(
            self::$priorities[$ContextType][$PriorityType],
            $PriorityHashes
        );

        return true;
    }

    /**
     *
     *
     * @param $ContextType
     * @param null $MapType
     * @return array|bool
     */
    public static function priorities($ContextType, $MapType = null) {
        if (!is_array(self::$priorities) || !array_key_exists($ContextType, self::$priorities)) {
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
        foreach (array(self::PRIORITY_TYPE_RESTRICT, self::PRIORITY_TYPE_PREFER) as $PriorityType) {
            if (!sizeof(self::$priorities[$ContextType][$PriorityType])) {
                continue;
            }

            $ResultMapHashes = self::$priorities[$ContextType][$PriorityType];
            $ResponseHashes = array();
            foreach ($ResultMapHashes as $MapHash => $PriorityInfo) {
                if (val('duration', $PriorityInfo) == self::PRIORITY_ONCE) {
                    unset(self::$priorities[$ContextType][$PriorityType][$MapHash]);
                }

                // If this request is being specific about the required map type, reject anything that doesn't match
                if (!is_null($MapType) && val('maptype', $PriorityInfo) != $MapType) {
                    continue;
                }

                $ResponseHashes[$MapHash] = $PriorityInfo;
            }

            if ($PriorityType == self::PRIORITY_TYPE_RESTRICT) {
                $ResponseHashes['FAIL_CONTEXT_IF_NOT_FOUND'] = true;
            }

            return $ResponseHashes;
        }

        return false;
    }

    /**
     *
     *
     * @param $MapType
     * @param $ContextType
     * @param $SearchPath
     * @param array $Options
     * @return mixed
     */
    public static function registerMap($MapType, $ContextType, $SearchPath, $Options = array()) {
        $DefaultOptions = array(
            'SearchSubfolders' => true,
            'Extension' => null,
            'ContextPrefix' => null,
            'ClassFilter' => '*',
            'SaveToDisk' => true
        );
        if (array_key_exists($ContextType, self::$prefixes)) {
            $DefaultOptions['ContextPrefix'] = val($ContextType, self::$prefixes);
        }

        $Options = array_merge($DefaultOptions, $Options);

        $Extension = val('Extension', $Options, null);

        // Determine cache root on-disk location
        $Hits = 0;
        str_replace(PATH_ROOT, '', $SearchPath, $Hits);
        $MapRootLocation = PATH_CACHE;

        // Build a unique identifier that refers to this map (same map type, context, extension, and cachefile location)
        $MapIdentifier = implode('|', array(
            $MapType,
            $ContextType,
            $Extension,
            $MapRootLocation
        ));
        $Options['MapIdentifier'] = $MapIdentifier;
        $MapHash = md5($MapIdentifier);

        // Allow intrinsic ordering / layering of contexts by prefixing them with a context number
        $MapHash = 'context:'.val($ContextType, array_flip(self::$contextOrder)).'_'.$Extension.'_'.$MapHash;
        $MapHash = self::makeMapHash($MapType, $ContextType, $Extension, $MapRootLocation);

        if (!is_array(self::$maps)) {
            self::$maps = array();
        }

        if (!array_key_exists($MapHash, self::$maps)) {
            $Map = Gdn_Autoloader_Map::load($MapType, $ContextType, $MapRootLocation, $Options);
            self::$maps[$MapHash] = $Map;
        }

        ksort(self::$maps, SORT_REGULAR);

        $AddPathResult = self::$maps[$MapHash]->addPath($SearchPath, $Options);

        /*
         * Build a unique identifier that refers to this cached list (context and extension)
         *
         * For example, CONTEXT_APPLICATION and 'dashboard' would refer to all maps that store
         * information about the dashboard application: its controllers, models, modules, etc.
         */
        $MapGroupIdentifier = implode('|', array(
            $ContextType,
            $Extension
        ));

        if (!is_array(self::$mapGroups)) {
            self::$mapGroups = array();
        }

        if (!array_key_exists($MapGroupIdentifier, self::$mapGroups)) {
            self::$mapGroups[$MapGroupIdentifier] = array();
        }

        self::$mapGroups[$MapGroupIdentifier][$MapHash] = true;

        return $AddPathResult;
    }

    /**
     *
     *
     * @param $MapType
     * @param $ContextType
     * @param $Extension
     * @param $MapRootLocation
     * @return string
     */
    public static function makeMapHash($MapType, $ContextType, $Extension, $MapRootLocation) {
        $MapIdentifier = implode('|', array(
            $MapType,
            $ContextType,
            $Extension,
            $MapRootLocation
        ));

        $MapHash = md5($MapIdentifier);

        // Allow intrinsic ordering / layering of contexts by prefixing them with a context number
        $MapHash = 'context:'.val($ContextType, array_flip(self::$contextOrder)).'_'.$Extension.'_'.$MapHash;

        return $MapHash;
    }

    /**
     *
     *
     * @param $MapType
     * @param $ContextType
     * @param string $Extension
     */
    public static function forceIndex($MapType, $ContextType, $Extension = self::CONTEXT_CORE) {
        $Map = self::getMap($MapType, $ContextType, $Extension);
        $Map->index();
    }

    /**
     * This method frees the map storing information about the specified resource.
     *
     * Takes a map type, and an array of information about the resource in question:
     * a plugin info array, in the case of a plugin, or
     *
     * @param string $ContextType type of map to consider (one of the MAP_ constants).
     * @param array $MapResourceArray array of information about the mapped resource.
     */
    public static function smartFree($ContextType = null, $MapResourceArray = null) {

        $CacheFolder = @opendir(PATH_CACHE);
        if (!$CacheFolder) {
            return true;
        }
        while ($File = readdir($CacheFolder)) {
            $Extension = pathinfo($File, PATHINFO_EXTENSION);
            if ($Extension == 'ini' && $File != 'locale_map.ini') {
                @unlink(CombinePaths(array(PATH_CACHE, $File)));
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

        self::$prefixes = array(
            self::CONTEXT_CORE => 'c',
            self::CONTEXT_APPLICATION => 'a',
            self::CONTEXT_PLUGIN => 'p',
            self::CONTEXT_THEME => 't'
        );
        self::$contextOrder = array(
            self::CONTEXT_THEME,
            self::CONTEXT_LOCALE,
            self::CONTEXT_PLUGIN,
            self::CONTEXT_APPLICATION,
            self::CONTEXT_CORE
        );

        self::$maps = array();
        self::$mapGroups = array();

        // Register autoloader with the SPL
        spl_autoload_register(array('Gdn_Autoloader', 'lookup'));

        // Configure library/core and library/database
        self::registerMap(self::MAP_LIBRARY, self::CONTEXT_CORE, PATH_LIBRARY.'/core');
        self::registerMap(self::MAP_LIBRARY, self::CONTEXT_CORE, PATH_LIBRARY.'/database');
        self::registerMap(self::MAP_LIBRARY, self::CONTEXT_CORE, PATH_LIBRARY.'/vendors');

        // Register shutdown function to auto save changed cache files
        register_shutdown_function(array('Gdn_Autoloader', 'shutdown'));
    }

    /**
     * Save the current caches.
     *
     * This method executes once, just as the framework is shutting down. Its purpose
     * is to save the library maps to disk if they've changed.
     */
    public static function shutdown() {
        foreach (self::$maps as $MapHash => &$Map) {
            $Map->shutdown();
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

    private function __construct($MapType, $ContextType, $MapRootLocation, $Options) {
        $this->map = null;
        $this->ignore = array('.', '..');
        $this->paths = array();
        $this->buildOptions = $Options;

        $ExtensionName = val('Extension', $Options, null);
        $Recursive = val('SearchSubfolders', $Options, true);
        $ContextPrefix = val('ContextPrefix', $Options, null);
        $MapIdentifier = val('MapIdentifier', $Options, null);
        $SaveToDisk = val('SaveToDisk', $Options, true);
        $FileStructure = val('Structure', $Options, self::STRUCTURE_FLAT);

        $MapName = $MapType;
        if (!is_null($ExtensionName)) {
            $MapName = $ExtensionName.'_'.$MapName;
        }

        if (!is_null($ContextPrefix)) {
            $MapName = $ContextPrefix.'_'.$MapName;
        }

        $OnDiskMapFile = sprintf(self::DISK_MAP_NAME_FORMAT, $MapRootLocation, strtolower($MapName));

        $this->mapInfo = array(
            'ondisk' => $OnDiskMapFile,
            'identifier' => $MapIdentifier,
            'root' => $MapRootLocation,
            'name' => $MapName,
            'maptype' => $MapType,
            'contexttype' => $ContextType,
            'extension' => $ExtensionName,
            'dirty' => false,
            'save' => $SaveToDisk,
            'structure' => $FileStructure
        );
    }

    public function mapIsOnDisk() {
        return file_exists($this->mapInfo['ondisk']);
    }

    /**
     *
     *
     * @param $SearchPath
     * @param $Options
     * @throws Exception
     */
    public function addPath($SearchPath, $Options) {
        $PathOptions = array(
            'path' => $SearchPath,
            'recursive' => (bool)val('SearchSubfolders', $Options),
            'filter' => val('ClassFilter', $Options),
            'topic' => self::TOPIC_DEFAULT
        );

        if ($this->mapInfo['structure'] == self::STRUCTURE_SPLIT) {
            $SplitTopic = val('SplitTopic', $Options, null);
            if (is_null($SplitTopic)) {
                throw new Exception("Trying to use 'split' structure but no SplitTopic provided. Path: {$SearchPath}");
            }
            $PathOptions['topic'] = $SplitTopic;
        }

        $this->paths[$SearchPath] = $PathOptions;
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
     * @param string $Path
     * @param array|string $SearchFiles
     * @param bool $Recursive
     * @return bool|The
     */
    protected function findFile($Path, $SearchFiles, $Recursive) {
        if (!is_array($SearchFiles)) {
            $SearchFiles = array($SearchFiles);
        }

        $Folder = basename($Path);
        if (!is_dir($Path) || substr($Folder, 0, 1) === '.' || $Folder === 'node_modules') {
            return false;
        }

        $Files = scandir($Path);
        foreach ($Files as $FileName) {
            if (in_array($FileName, $this->ignore)) {
                continue;
            }
            $FullPath = CombinePaths(array($Path, $FileName));

            // If this is a folder...
            if (is_dir($FullPath)) {
                if ($Recursive) {
                    $File = $this->findFile($FullPath, $SearchFiles, $Recursive);
                    if ($File !== false) {
                        return $File;
                    }
                    continue;
                } else {
                    continue;
                }
            }

            if (in_array($FileName, $SearchFiles)) {
                return $FullPath;
            }
        }
        return false;
    }

    /**
     *
     *
     * @param $Path
     * @param $FileMasks
     * @param $Recursive
     * @return array|bool
     */
    protected function findFiles($Path, $FileMasks, $Recursive) {
        if (!is_array($FileMasks)) {
            $FileMasks = array($FileMasks);
        }

        if (!is_dir($Path)) {
            return false;
        }

        $FoundFiles = array();
        $Files = scandir($Path);
        foreach ($Files as $FileName) {
            if (in_array($FileName, $this->ignore)) {
                continue;
            }
            $FullPath = CombinePaths(array($Path, $FileName));

            // If this is a folder, maybe recurse it eh?
            if (is_dir($FullPath)) {
                if ($Recursive) {
                    $Recurse = $this->findFiles($FullPath, $FileMasks, $Recursive);
                    if ($Recurse !== false) {
                        $FoundFiles = array_merge($FoundFiles, $Recurse);
                    }
                    continue;
                } else {
                    continue;
                }
            } else {
                foreach ($FileMasks as $FileMask) {
                    // If this file matches one of the masks, add it to this loops's found files
                    if (fnmatch($FileMask, $FileName)) {
                        $FoundFiles[] = $FullPath;
                        break;
                    }
                }
            }
        }

        return $FoundFiles;
    }

    /**
     * Autoloader cache static constructor
     *
     * @return Gdn_Autoloader_Map
     */
    public static function load($MapType, $ContextType, $MapRootLocation, $Options) {
        return new Gdn_Autoloader_Map($MapType, $ContextType, $MapRootLocation, $Options);
    }

    /**
     *
     *
     * @param $ClassName
     * @param bool $MapOnly
     * @return bool|The
     */
    public function lookup($ClassName, $MapOnly = true) {
        $MapName = $this->mapInfo['name'];

        // Lazyload cache data
        if (is_null($this->map)) {
            $this->map = array();
            $OnDiskMapFile = $this->mapInfo['ondisk'];

            // Loading cache data from disk
            if (file_exists($OnDiskMapFile)) {
                $Structure = $this->mapInfo['structure'];
                $MapContents = parse_ini_file($OnDiskMapFile, true);

                try {
                    // Detect legacy flat files which are now stored split
                    if ($Structure == self::STRUCTURE_SPLIT && array_key_exists(self::TOPIC_DEFAULT, $MapContents)) {
                        throw new Exception();
                    }

                    // Bad file?
                    if ($MapContents == false || !is_array($MapContents)) {
                        throw new Exception();
                    }

                    // All's well that ends well. Load the cache.
                    $this->map = $MapContents;
                } catch (Exception $Ex) {
                    @unlink($OnDiskMapFile);
                }
            }
        }

        // Always look by lowercase classname
        $ClassName = strtolower($ClassName);

        switch ($this->mapInfo['structure']) {
            case 'split':
                // This file stored split, so look for this class in each virtual sub-path, by topic
                foreach ($this->paths as $Path => $PathOptions) {
                    $LookupSplitTopic = val('topic', $PathOptions);
                    if (array_key_exists($LookupSplitTopic, $this->map) && array_key_exists($ClassName, $this->map[$LookupSplitTopic])) {
                        return val($ClassName, $this->map[$LookupSplitTopic]);
                    }
                }
                break;

            default:
            case 'flat':
                // This file is stored flat, so just look in the DEFAULT_TOPIC
                $LookupSplitTopic = self::TOPIC_DEFAULT;
                if (array_key_exists($LookupSplitTopic, $this->map) && array_key_exists($ClassName, $this->map[$LookupSplitTopic])) {
                    return val($ClassName, $this->map[$LookupSplitTopic]);
                }
                break;
        }

        // Look at the filesystem, too
        if (!$MapOnly) {
            if (substr($ClassName, 0, 4) == 'gdn_') {
                $FSClassName = substr($ClassName, 4);
            } else {
                $FSClassName = $ClassName;
            }

            $Files = array(
                sprintf(self::LOOKUP_CLASS_MASK, $FSClassName),
                sprintf(self::LOOKUP_INTERFACE_MASK, $FSClassName)
            );

            foreach ($this->paths as $Path => $PathOptions) {
                $ClassFilter = val('filter', $PathOptions);
                if (!fnmatch($ClassFilter, $ClassName)) {
                    continue;
                }

                $Recursive = val('recursive', $PathOptions);
                $File = $this->findFile($Path, $Files, $Recursive);

                if ($File !== false) {
                    $SplitTopic = val('topic', $PathOptions, self::TOPIC_DEFAULT);
                    $this->map[$SplitTopic][$ClassName] = $File;
                    $this->mapInfo['dirty'] = true;

                    return $File;
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
    public function index($ExtraPaths = null) {

        $FileMasks = array(
            sprintf(self::LOOKUP_CLASS_MASK, '*'),
            sprintf(self::LOOKUP_INTERFACE_MASK, '*')
        );

        $ExtraPathsRemove = null;
        if (!is_null($ExtraPaths)) {
            $ExtraPathsRemove = array();
            foreach ($ExtraPaths as $PathOpts) {
                $ExtraPath = val('path', $PathOpts);
                if (array_key_exists($ExtraPath, $this->paths)) {
                    continue;
                }

                $ExtraPathsRemove[] = $ExtraPath;
                $ExtraOptions = $this->buildOptions;
                $ExtraOptions['SplitTopic'] = val('topic', $PathOpts);
                $this->addPath($ExtraPath, $ExtraOptions);
            }
        }

        foreach ($this->paths as $Path => $PathOptions) {
            $Recursive = val('recursive', $PathOptions);
            $Files = $this->findFiles($Path, $FileMasks, $Recursive);
            if ($Files === false) {
                continue;
            }

            foreach ($Files as $File) {
                $SplitTopic = val('topic', $PathOptions, self::TOPIC_DEFAULT);

                $ProvidedClass = $this->getClassNameFromFile($File);
                if ($ProvidedClass) {
                    $this->map[$SplitTopic][$ProvidedClass] = $File;
                    $this->mapInfo['dirty'] = true;
                }

//            $ProvidesClasses = $this->Investigate($File);
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

        if (!is_null($ExtraPathsRemove)) {
            foreach ($ExtraPathsRemove as $RemPath) {
                unset($this->paths[$RemPath]);
            }
        }
    }

    /**
     *
     *
     * @param $File
     * @return array|void
     */
    protected function investigate($File) {
        if (!file_exists($File)) {
            return;
        }
        $Lines = file($File);
        $ClassesFound = array();
        foreach ($Lines as $Line) {
            //strtolower(substr(trim($Line), 0, 6)) == 'class '
            if (preg_match('/^class[\s]+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\s]+(?:.*)?{/', $Line, $Matches)) {
                $ClassesFound[] = $Matches[1];
                continue;
            }

            if (preg_match('/^interface[\s]+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\s]+(?:.*)?{/', $Line, $Matches)) {
                $ClassesFound[] = $Matches[1];
                continue;
            }
        }
        return $ClassesFound;
    }

    /**
     *
     *
     * @param $File
     * @return bool|mixed
     */
    protected function getClassNameFromFile($File) {
        if (StringEndsWith($File, '.plugin.php')) {
            return false;
        }

        $FileName = basename($File);
        $Modes = array(
            'class' => self::LOOKUP_CLASS_MASK,
            'interface' => self::LOOKUP_INTERFACE_MASK
        );
        foreach ($Modes as $ModeType => $ModeMask) {
            $MatchModeMask = sprintf($ModeMask, '*');
            if (fnmatch($MatchModeMask, $FileName)) {
                $ModeRE = '/^'.str_replace('.', '\.', $ModeMask).'$/';
                $ModeRE = sprintf($ModeRE, '(.*)');
                $Matched = preg_match($ModeRE, $FileName, $Matches);
                if ($Matched) {
                    return str_replace('.', '', strtolower($Matches[1]));
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

        $MapName = val('name', $this->mapInfo);
        $FileName = val('ondisk', $this->mapInfo);

        $MapContents = '';
        foreach ($this->map as $SplitTopic => $TopicFiles) {
            $MapContents .= "[{$SplitTopic}]\n";
            foreach ($TopicFiles as $ClassName => $Location) {
                $Location = $this->fixBackSlash($Location);
                $MapContents .= "{$ClassName} = \"{$Location}\"\n";
            }
        }

        try {
            Gdn_FileSystem::saveFile($FileName, $MapContents, LOCK_EX);
            $this->mapInfo['dirty'] = false;
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
