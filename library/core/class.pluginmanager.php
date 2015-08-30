<?php
/**
 * Gdn_PluginManager
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Plugin Manager.
 *
 * A singleton class used to identify extensions, register them in a central
 * location, and instantiate/call them when necessary.
 */
class Gdn_PluginManager extends Gdn_Pluggable {

    const ACTION_ENABLE = 1;

    const ACTION_DISABLE = 2;

    //const ACTION_REMOVE  = 3;

    const ACCESS_CLASSNAME = 'classname';

    const ACCESS_PLUGINNAME = 'pluginname';

    /** @var array Available plugins. Never access this directly, instead use $this->AvailablePlugins(); */
    protected $PluginCache = null;

    /** @var array */
    protected $PluginsByClass = null;

    /** @var array */
    protected $PluginFoldersByPath = null;

    /** @var array A simple list of enabled plugins. */
    protected $EnabledPlugins = null;

    /** @var array Search paths for plugins and their files. */
    protected $PluginSearchPaths = null;

    protected $AlternatePluginSearchPaths = null;

    /** @var array A simple list of plugins that have already been registered. */
    protected $RegisteredPlugins = array();

    /** @var array  An associative array of EventHandlerName => PluginName pairs. */
    private $_EventHandlerCollection = array();

    /** @var array An associative array of MethodOverrideName => PluginName pairs. */
    private $_MethodOverrideCollection = array();

    /** @var array An associative array of NewMethodName => PluginName pairs. */
    private $_NewMethodCollection = array();

    /** @var array An array of the instances of plugins. */
    protected $Instances = array();

    protected $Started = false;

    /** @var bool Whether or not to trace some event information. */
    public $Trace = false;

    /** @var bool Whether to use APC for plugin cache storage. */
    protected $Apc = false;

    /**
     *
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Sets up the plugin framework
     *
     * This method indexes all available plugins and extracts their information.
     * It then determines which plugins have been enabled, and includes them.
     * Finally, it parses all plugin files and extracts their events and plugged
     * methods.
     */
    public function start($Force = false) {

        if (function_exists('apc_fetch') && C('Garden.Apc', false)) {
            $this->Apc = true;
        }

        // Build list of all available plugins
        $this->availablePlugins($Force);

        // Build list of all enabled plugins
        $this->enabledPlugins($Force);

        // Include enabled plugin source files
        $this->includePlugins();

        // Register hooked methods
        $this->registerPlugins();

        $this->Started = true;
        $this->fireEvent('AfterStart');
    }

    /**
     *
     *
     * @return bool
     */
    public function started() {
        return (bool)$this->Started;
    }

    /**
     *
     *
     * @param bool $Force
     * @return array|null
     */
    public function availablePlugins($Force = false) {

        if (is_null($this->PluginCache) || is_null($this->PluginsByClass) || $Force) {
            $this->PluginCache = array();
            $this->PluginsByClass = array();
            $this->PluginFoldersByPath = array();

            // Check cache freshness
            foreach ($this->searchPaths() as $SearchPath => $Trash) {
                unset($SearchPathCache);

                // Check Cache
                $SearchPathCacheKey = 'Garden.Plugins.PathCache.'.$SearchPath;
                if ($this->Apc) {
                    $SearchPathCache = apc_fetch($SearchPathCacheKey);
                } else {
                    $SearchPathCache = Gdn::cache()->get($SearchPathCacheKey, array(Gdn_Cache::FEATURE_NOPREFIX => true));
                }

                $CacheHit = ($SearchPathCache !== Gdn_Cache::CACHEOP_FAILURE);
                $CacheIntegrityCheck = false;
                if ($CacheHit && is_array($SearchPathCache)) {
                    $CacheIntegrityCheck = (sizeof(array_intersect(array_keys($SearchPathCache), array('CacheIntegrityHash', 'PluginInfo', 'ClassInfo'))) == 3);
                }

                if (!$CacheIntegrityCheck) {
                    $SearchPathCache = array(
                        'CacheIntegrityHash' => null,
                        'PluginInfo' => array(),
                        'ClassInfo' => array()
                    );
                }

                $CachePluginInfo = &$SearchPathCache['PluginInfo'];
                if (!is_array($CachePluginInfo)) {
                    $CachePluginInfo = array();
                }

                $CacheClassInfo = &$SearchPathCache['ClassInfo'];
                if (!is_array($CacheClassInfo)) {
                    $CacheClassInfo = array();
                }

                $PathListing = scandir($SearchPath, 0);
                sort($PathListing);

                $PathIntegrityHash = md5(serialize($PathListing));
                $CacheIntegrityHash = GetValue('CacheIntegrityHash', $SearchPathCache);
                if ($CacheIntegrityHash != $PathIntegrityHash) {
                    // Trace('Need to re-index plugin cache');
                    // Need to re-index this folder

                    // Since we're re-indexing this folder, need to unset all the plugins it was previously responsible for
                    // so that the merge below does what was intended
                    $this->PluginCache = array_diff_key($this->PluginCache, $CachePluginInfo);
                    $this->PluginsByClass = array_diff_key($this->PluginsByClass, $CacheClassInfo);

                    $CachePluginInfo = array();
                    $CacheClassInfo = array();
                    $PathIntegrityHash = $this->indexSearchPath($SearchPath, $CachePluginInfo, $CacheClassInfo, $PathListing);
                    if ($PathIntegrityHash === false) {
                        continue;
                    }

                    $SearchPathCache['CacheIntegrityHash'] = $PathIntegrityHash;
                    if ($this->Apc) {
                        apc_store($SearchPathCacheKey, $SearchPathCache);
                    } else {
                        Gdn::cache()->store($SearchPathCacheKey, $SearchPathCache, array(Gdn_Cache::FEATURE_NOPREFIX => true));
                    }
                }

                $this->PluginCache = array_merge($this->PluginCache, $CachePluginInfo);
                $this->PluginsByClass = array_merge($this->PluginsByClass, $CacheClassInfo);
                $this->PluginFoldersByPath[$SearchPath] = array_keys($CachePluginInfo);
            }
        }

        return $this->PluginCache;
    }

    /**
     *
     */
    public function forceAutoloaderIndex() {
        $AutoloaderMap = Gdn_Autoloader::getMap(Gdn_Autoloader::MAP_LIBRARY, Gdn_Autoloader::CONTEXT_PLUGIN);
        if (!$AutoloaderMap) {
            return;
        }

        $ExtraPaths = array();
        foreach ($this->availablePlugins() as $AvailablePlugin) {
            $ExtraPaths[] = array(
                'path' => val('RealRoot', $AvailablePlugin),
                'topic' => strtolower(val('Folder', $AvailablePlugin))
            );
        }

        $AutoloaderMap->index($ExtraPaths);
    }

    /**
     *
     *
     * @param null $SearchPaths
     */
    public function clearPluginCache($SearchPaths = null) {
        if (!is_null($SearchPaths)) {
            if (!is_array($SearchPaths)) {
                $SearchPaths = array($SearchPaths);
            }
        } else {
            $SearchPaths = $this->searchPaths();
        }

        foreach ($SearchPaths as $SearchPath => $SearchPathName) {
            $SearchPathCacheKey = "Garden.Plugins.PathCache.{$SearchPath}";
            if ($this->Apc) {
                apc_delete($SearchPathCacheKey);
            } else {
                Gdn::cache()->remove($SearchPathCacheKey, array(Gdn_Cache::FEATURE_NOPREFIX => true));
            }
        }
    }

    public function enabledPlugins($Force = false) {

        if (!is_array($this->EnabledPlugins) || $Force) {
            // Make sure all known plugins are cached
            $this->availablePlugins($Force);

            $this->EnabledPlugins = array();
            $EnabledPlugins = C('EnabledPlugins', array());

            foreach ($EnabledPlugins as $PluginName => $PluginStatus) {
                // Plugins can be explicitly disabled
                if ($PluginStatus === false) {
                    continue;
                }

                // Check that the plugin is in AvailablePlugins...
                $Plugin = $this->getPluginInfo($PluginName);
                if ($Plugin === false) {
                    continue;
                }

                $this->EnabledPlugins[$PluginName] = true;
            }
        }

        return array_intersect_key($this->availablePlugins(), $this->EnabledPlugins);
    }

    /**
     * Includes all of the plugin files for enabled plugins.
     *
     * Files are included in from the roots of each plugin directory if they have the following names.
     * - default.php
     * - *plugin.php
     *
     * @param array $EnabledPlugins An array of plugins that should be included.
     * If this argument is null then all enabled plugins will be included.
     * @return array The plugin info array for all included plugins.
     */
    public function includePlugins($EnabledPlugins = null) {

        // Include all of the plugins.
        if (is_null($EnabledPlugins)) {
            $EnabledPlugins = $this->enabledPlugins();
        }

        $PluginManager = &$this;
        // Get a list of files to include.
        foreach ($EnabledPlugins as $PluginName => $Trash) {
            $PluginInfo = $this->getPluginInfo($PluginName);

            $ClassName = val('ClassName', $PluginInfo, false);
            $ClassFile = val('RealFile', $PluginInfo, false);

            if ($ClassName !== false && !class_exists($ClassName, false)) {
                if (file_exists($ClassFile)) {
                    include_once($ClassFile);
                }
            }

        }
    }

    /**
     *
     *
     * @param $SearchPath
     * @param $PluginInfo
     * @param $ClassInfo
     * @param null $PathListing
     * @return bool|string
     */
    public function indexSearchPath($SearchPath, &$PluginInfo, &$ClassInfo, $PathListing = null) {
        if (is_null($PathListing) || !is_array($PathListing)) {
            $PathListing = scandir($SearchPath, 0);
            sort($PathListing);
        }

        if ($PathListing === false) {
            return false;
        }

        foreach ($PathListing as $PluginFolderName) {
            if (substr($PluginFolderName, 0, 1) == '.') {
                continue;
            }

            $PluginPath = CombinePaths(array($SearchPath, $PluginFolderName));
            $PluginFile = $this->findPluginFile($PluginPath);

            if ($PluginFile === false) {
                continue;
            }

            $SearchPluginInfo = $this->scanPluginFile($PluginFile);

            if ($SearchPluginInfo === false) {
                continue;
            }

            $RealPluginFile = realpath($PluginFile);
            $SearchPluginInfo['RealFile'] = $RealPluginFile;
            $SearchPluginInfo['RealRoot'] = dirname($RealPluginFile);
            $SearchPluginInfo['SearchPath'] = $SearchPath;
            $PluginInfo[$PluginFolderName] = $SearchPluginInfo;

            $PluginClassName = val('ClassName', $SearchPluginInfo);
            $ClassInfo[$PluginClassName] = $PluginFolderName;
        }

        return md5(serialize($PathListing));
    }

    /**
     *
     *
     * @param $SearchPath
     * @param null $SearchPathName
     * @return bool
     */
    public function addSearchPath($SearchPath, $SearchPathName = null) {
        $AlternateSearchPaths = $this->searchPaths(true);
        $SearchPath = rtrim($SearchPath, '/');
        if (array_key_exists($SearchPath, $AlternateSearchPaths)) {
            return true;
        }

        $this->AlternateSearchPaths[$SearchPath] = $SearchPathName;
        SaveToConfig('Garden.PluginManager.Search', $this->AlternateSearchPaths);
        return true;
    }

    /**
     *
     *
     * @param $SearchPath
     * @return bool
     */
    public function removeSearchPath($SearchPath) {
        $AlternateSearchPaths = $this->searchPaths(true);
        $SearchPath = rtrim($SearchPath, '/');
        if (!array_key_exists($SearchPath, $AlternateSearchPaths)) {
            return true;
        }

        unset($this->AlternateSearchPaths[$SearchPath]);
        SaveToConfig('Garden.PluginManager.Search', $this->AlternateSearchPaths);
        return true;
    }

    /**
     *
     *
     * @param $PluginPath
     * @return bool|The
     */
    public function findPluginFile($PluginPath) {
        if (!is_dir($PluginPath)) {
            return false;
        }
        $PluginFiles = scandir($PluginPath);
        $TestPatterns = array(
            'default.php', '*plugin.php'
        );
        foreach ($PluginFiles as $PluginFile) {
            foreach ($TestPatterns as $Test) {
                if (fnmatch($Test, $PluginFile)) {
                    return CombinePaths(array($PluginPath, $PluginFile));
                }
            }
        }

        return false;
    }

    /**
     * Register all enabled plugins' event handlers and overrides
     *
     * Examines all declared classes, identifying which ones implement
     * Gdn_IPlugin and registers all of their event handlers and method
     * overrides. It recognizes them because Handlers end with _Handler,
     * _Before, and _After and overrides end with "_Override". They are prefixed
     * with the name of the class and method (or event) to be handled or
     * overridden.
     *
     * For example:
     *
     *  class MyPlugin implements Gdn_IPlugin {
     *   public function MyController_SignIn_After($Sender) {
     *      // Do something neato
     *   }
     *   public function Url_AppRoot_Override($WithDomain) {
     *      return "MyCustomAppRoot!";
     *   }
     *  }
     */
    public function registerPlugins() {

        // Loop through all declared classes looking for ones that implement Gdn_iPlugin.
        foreach (get_declared_classes() as $ClassName) {
            if ($ClassName == 'Gdn_Plugin') {
                continue;
            }

            // Only register the plugin if it implements the Gdn_IPlugin interface
            if (in_array('Gdn_IPlugin', class_implements($ClassName))) {
                // If this plugin was already indexed, skip it.
                if (array_key_exists($ClassName, $this->RegisteredPlugins)) {
                    continue;
                }

                // Register this plugin's methods
                $this->registerPlugin($ClassName);

            }
        }
    }

    /**
     *
     *
     * @return array
     */
    public function registeredPlugins() {
        return $this->RegisteredPlugins;
    }

    /**
     *
     *
     * @param $ClassName
     * @throws Exception
     */
    public function registerPlugin($ClassName) {
        $ClassMethods = get_class_methods($ClassName);
        if ($ClassMethods === null) {
            throw new Exception("There was an error getting the $ClassName class methods.", 401);
        }

        foreach ($ClassMethods as $Method) {
            $MethodName = strtolower($Method);
            // Loop through their individual methods looking for event handlers and method overrides.
            if (isset($MethodName[9])) {
                $Suffix = trim(strrchr($MethodName, '_'), '_');
                switch ($Suffix) {
                    case 'handler':
                    case 'before':
                    case 'after':
                    case 'render':
                        $this->registerHandler($ClassName, $MethodName);
                        break;
                    case 'override':
                        $this->registerOverride($ClassName, $MethodName);
                        break;
                    case 'create':
                        $this->registerNewMethod($ClassName, $MethodName);
                        break;
                }
            }
        }

        $this->RegisteredPlugins[$ClassName] = true;
    }

    /**
     *
     *
     * @param $PluginClassName
     * @return bool
     */
    public function unregisterPlugin($PluginClassName) {
        $this->removeFromCollectionByPrefix($PluginClassName, $this->_EventHandlerCollection);
        $this->removeFromCollectionByPrefix($PluginClassName, $this->_MethodOverrideCollection);
        $this->removeFromCollectionByPrefix($PluginClassName, $this->_NewMethodCollection);
        if (array_key_exists($PluginClassName, $this->RegisteredPlugins)) {
            unset($this->RegisteredPlugins[$PluginClassName]);
        }

        return true;
    }

    /**
     *
     *
     * @param $Prefix
     * @param $Collection
     */
    private function removeFromCollectionByPrefix($Prefix, &$Collection) {
        foreach ($Collection as $Event => $Hooks) {
            if (is_array($Hooks)) {
                foreach ($Hooks as $Index => $Hook) {
                    if (strpos($Hook, $Prefix.'.') === 0) {
                        unset($Collection[$Event][$Index]);
                    }
                }
            } elseif (is_string($Hooks)) {
                if (strpos($Hooks, $Prefix.'.') === 0) {
                    unset($Collection[$Event]);
                }
            }
        }
    }

    /**
     *
     */
    public function removeMobileUnfriendlyPlugins() {
        foreach ($this->enabledPlugins() as $PluginName => $Trash) {
            $PluginInfo = $this->getPluginInfo($PluginName);

            // Remove plugin hooks from plugins that dont explicitly claim to be friendly with mobile themes
            if (!val('MobileFriendly', $PluginInfo)) {
                $this->unregisterPlugin(val('ClassName', $PluginInfo));
            }
        }
    }

    /**
     * Check whether a plugin is enabled
     *
     * @param string $PluginName
     * @return bool
     */
    public function checkPlugin($PluginName) {
        if (array_key_exists($PluginName, $this->enabledPlugins())) {
            return true;
        }

        return false;
    }

    /**
     *
     *
     * @param $Sender
     * @param $EventName
     * @param string $HandlerType
     * @param array $Options
     * @return array
     */
    public function getEventHandlers($Sender, $EventName, $HandlerType = 'Handler', $Options = array()) {
        // Figure out the classname.
        if (isset($Options['ClassName'])) {
            $ClassName = $Options['ClassName'];
        } elseif (property_exists($Sender, 'ClassName') && $Sender->ClassName)
            $ClassName = $Sender->ClassName;
        else {
            $ClassName = get_class($Sender);
        }

        // Build the list of event handler names.
        $Names = array(
            "{$ClassName}_{$EventName}_{$HandlerType}",
            "Base_{$EventName}_{$HandlerType}",
            "{$ClassName}_{$EventName}_All",
            "Base_{$EventName}_All");

        // Grab the event handlers.
        $Handlers = array();
        foreach ($Names as $Name) {
            $Name = strtolower($Name);
            if (isset($this->_EventHandlerCollection[$Name])) {
                $Handlers = array_merge($Handlers, $this->_EventHandlerCollection[$Name]);
            }
        }

        return $Handlers;
    }

    /**
     *
     *
     * @param $AccessName
     * @param string $AccessType
     * @return bool|mixed
     */
    public function getPluginInfo($AccessName, $AccessType = self::ACCESS_PLUGINNAME) {
        $PluginName = false;

        switch ($AccessType) {
            case self::ACCESS_PLUGINNAME:
                $PluginName = $AccessName;
                break;

            case self::ACCESS_CLASSNAME:
            default:
                $PluginName = GetValue($AccessName, $this->PluginsByClass, false);
                break;
        }
        $Available = $this->availablePlugins();
        return ($PluginName !== false) ? GetValue($PluginName, $Available, false) : false;
    }

    /**
     * Gets an instance of a given plugin.
     *
     * @param string $AccessName The key of the plugin.
     * @param string $AccessType The type of key for the plugin which must be one of the following:
     *  - Gdn_PluginManager::ACCESS_PLUGINNAME
     *  - Gdn_PluginManager::ACCESS_CLASSNAME
     * @param mixed $Sender An object to pass to a new plugin instantiation.
     * @return Gdn_IPlugin The plugin instance.
     */
    public function getPluginInstance($AccessName, $AccessType = self::ACCESS_CLASSNAME, $Sender = null) {
        $ClassName = null;
        switch ($AccessType) {
            case self::ACCESS_PLUGINNAME:
                $ClassName = val('ClassName', $this->getPluginInfo($AccessName), false);
                break;

            case self::ACCESS_CLASSNAME:
            default:
                $ClassName = $AccessName;
                break;
        }

        if (!class_exists($ClassName)) {
            throw new Exception("Tried to load plugin '{$ClassName}' from access name '{$AccessName}:{$AccessType}', but it doesn't exist.");
        }

        if (!array_key_exists($ClassName, $this->Instances)) {
            $this->Instances[$ClassName] = (is_null($Sender)) ? new $ClassName() : new $ClassName($Sender);
            $this->Instances[$ClassName]->PluginInfo = $this->getPluginInfo($AccessName, $AccessType);
        }

        return $this->Instances[$ClassName];
    }

    /**
     * Returns whether or not a plugin is enabled.
     *
     * @param string $Name The name of the plugin.
     * @return bool Whether or not the plugin is enabled.
     * @since 2.2
     */
    public function isEnabled($Name) {
        $Enabled = $this->EnabledPlugins;
        return isset($Enabled[$Name]) && $Enabled[$Name];
    }

    /**
     * Registers a plugin method name as a handler.
     *
     * @param string $HandlerClassName The name of the plugin class that will handle the event.
     * @param string $HandlerMethodName The name of the plugin method being registered to handle the event.
     * @param string $EventClassName The name of the class that will fire the event.
     * @param string $EventName The name of the event that will fire.
     * @param string $EventHandlerType The type of event handler.
     */
    public function registerHandler($HandlerClassName, $HandlerMethodName, $EventClassName = '', $EventName = '', $EventHandlerType = '') {
        $HandlerKey = $HandlerClassName.'.'.$HandlerMethodName;
        $EventKey = strtolower($EventClassName == '' ? $HandlerMethodName : $EventClassName.'_'.$EventName.'_'.$EventHandlerType);

        // Create a new array of handler class names if it doesn't exist yet.
        if (array_key_exists($EventKey, $this->_EventHandlerCollection) === false) {
            $this->_EventHandlerCollection[$EventKey] = array();
        }

        // Specify this class as a handler for this method if it hasn't been done yet.
        if (in_array($HandlerKey, $this->_EventHandlerCollection[$EventKey]) === false) {
            $this->_EventHandlerCollection[$EventKey][] = $HandlerKey;
        }
    }

    /**
     * Registers a plugin override method.
     *
     * @param string $OverrideClassName The name of the plugin class that will override the existing method.
     * @param string $OverrideMethodName The name of the plugin method being registered to override the existing method.
     * @param string $EventClassName The name of the class that will fire the event.
     * @param string $EventName The name of the event that will fire.
     */
    public function registerOverride($OverrideClassName, $OverrideMethodName, $EventClassName = '', $EventName = '') {
        $OverrideKey = $OverrideClassName.'.'.$OverrideMethodName;
        $EventKey = strtolower($EventClassName == '' ? $OverrideMethodName : $EventClassName.'_'.$EventName.'_Override');

        // Throw an error if this method has already been overridden.
        if (array_key_exists($EventKey, $this->_MethodOverrideCollection) === true) {
            trigger_error(ErrorMessage('Any object method can only be overridden by a single plugin. The "'.$EventKey.'" override has already been assigned by the "'.$this->_MethodOverrideCollection[$EventKey].'" plugin. It cannot also be overridden by the "'.$OverrideClassName.'" plugin.', 'PluginManager', 'RegisterOverride'), E_USER_ERROR);
        }

        // Otherwise, specify this class as the source for the override.
        $this->_MethodOverrideCollection[$EventKey] = $OverrideKey;
    }

    /**
     * Registers a plugin new method.
     *
     * @param string $NewMethodClassName The name of the plugin class that will add a new method.
     * @param string $NewMethodName The name of the plugin method being added.
     * @param string $EventClassName The name of the class that will fire the event.
     * @param string $EventName The name of the event that will fire.
     */
    public function registerNewMethod($NewMethodClassName, $NewMethodName, $EventClassName = '', $EventName = '') {
        $NewMethodKey = $NewMethodClassName.'.'.$NewMethodName;
        $EventKey = strtolower($EventClassName == '' ? $NewMethodName : $EventClassName.'_'.$EventName.'_Create');

        // Throw an error if this method has already been created.
        if (array_key_exists($EventKey, $this->_NewMethodCollection) === true) {
            trigger_error('New object methods must be unique. The new "'.$EventKey.'" method has already been assigned by the "'.$this->_NewMethodCollection[$EventKey].'" plugin. It cannot also be assigned by the "'.$NewMethodClassName.'" plugin.', E_USER_NOTICE);
            return;
        }

        // Otherwise, specify this class as the source for the new method.
        $this->_NewMethodCollection[$EventKey] = $NewMethodKey;
    }

    /**
     * Transfer control to the plugins
     *
     * Looks through $this->_EventHandlerCollection for matching event
     * signatures to handle. If it finds any, it executes them in the order it
     * found them. It instantiates any plugins and adds them as properties to
     * this class (unless they were previously instantiated), and then calls
     * the handler in question.
     *
     * @param object The object that fired the event being handled.
     * @param string The name of the class that fired the event being handled.
     * @param string The name of the event being fired.
     * @param string The type of handler being fired (Handler, Before, After).
     * @return bool True if an event was executed.
     */
    public function callEventHandlers($Sender, $EventClassName, $EventName, $EventHandlerType = 'Handler', $Options = array()) {
        $Return = false;

        // Look through $this->_EventHandlerCollection for relevant handlers
        if ($this->callEventHandler($Sender, $EventClassName, $EventName, $EventHandlerType)) {
            $Return = true;
        }

        // Look for "Base" (aka any class that has $EventName)
        if ($this->callEventHandler($Sender, 'Base', $EventName, $EventHandlerType)) {
            $Return = true;
        }

        // Look for Wildcard event handlers
        $WildEventKey = $EventClassName.'_'.$EventName.'_'.$EventHandlerType;
        if ($this->callEventHandler($Sender, 'Base', 'All', $EventHandlerType, $WildEventKey)) {
            $Return = true;
        }
        if ($this->callEventHandler($Sender, $EventClassName, 'All', $EventHandlerType, $WildEventKey)) {
            $Return = true;
        }

        return $Return;
    }

    /**
     *
     *
     * @param $Message
     * @param string $Type
     */
    public function trace($Message, $Type = TRACE_INFO) {
        if ($this->Trace) {
            trace($Message, $Type);
        }
    }

    /**
     *
     *
     * @param $Sender
     * @param $EventClassName
     * @param $EventName
     * @param $EventHandlerType
     * @param array $Options
     * @return bool
     * @throws Exception
     */
    public function callEventHandler($Sender, $EventClassName, $EventName, $EventHandlerType, $Options = array()) {
        $this->trace("CallEventHandler $EventClassName $EventName $EventHandlerType");
        $Return = false;

        // Backwards compatible for event key.
        if (is_string($Options)) {
            $PassedEventKey = $Options;
            $Options = array();
        } else {
            $PassedEventKey = val('EventKey', $Options, null);
        }

        $EventKey = strtolower($EventClassName.'_'.$EventName.'_'.$EventHandlerType);
        if (!array_key_exists($EventKey, $this->_EventHandlerCollection)) {
            return false;
        }

        if (is_null($PassedEventKey)) {
            $PassedEventKey = $EventKey;
        }

        // For "All" events, calculate the stack
        if ($EventName == 'All') {
            $Stack = debug_backtrace();
            // this call
            array_shift($Stack);

            // plural call
            array_shift($Stack);

            $EventCaller = array_shift($Stack);
            $Sender->EventArguments['WildEventStack'] = $EventCaller;
        }

        $this->trace($this->_EventHandlerCollection[$EventKey], 'Event Handlers');

        // Loop through the handlers and execute them
        foreach ($this->_EventHandlerCollection[$EventKey] as $PluginKey) {
            $PluginKeyParts = explode('.', $PluginKey);
            if (count($PluginKeyParts) == 2) {
                list($PluginClassName, $PluginEventHandlerName) = $PluginKeyParts;


                if (isset($Sender->Returns)) {
                    if (array_key_exists($EventKey, $Sender->Returns) === false || is_array($Sender->Returns[$EventKey]) === false) {
                        $Sender->Returns[$EventKey] = array();
                    }

                    $Return = $this->getPluginInstance($PluginClassName)->$PluginEventHandlerName($Sender, $Sender->EventArguments, $PassedEventKey);

                    $Sender->Returns[$EventKey][$PluginKey] = $Return;
                    $Return = true;
                } elseif (isset($Sender->EventArguments)) {
                    $this->getPluginInstance($PluginClassName)->$PluginEventHandlerName($Sender, $Sender->EventArguments, $PassedEventKey);
                } else {
                    $this->getPluginInstance($PluginClassName)->$PluginEventHandlerName($Sender, array(), $PassedEventKey);
                }
            }
        }

        return $Return;
    }

    /**
     * Looks through $this->_MethodOverrideCollection for a matching method
     * signature to override. It instantiates any plugins and adds them as
     * properties to this class (unless they were previously instantiated), then
     * calls the method in question.
     *
     * @param object The object being worked on.
     * @param string The name of the class that called the method being overridden.
     * @param string The name of the method that is being overridden.
     * @return mixed Return value of overridden method.
     */
    public function callMethodOverride($Sender, $ClassName, $MethodName) {
        $EventKey = strtolower($ClassName.'_'.$MethodName.'_Override');
        $OverrideKey = ArrayValue($EventKey, $this->_MethodOverrideCollection, '');
        $OverrideKeyParts = explode('.', $OverrideKey);
        if (count($OverrideKeyParts) != 2) {
            return false;
        }

        list($OverrideClassName, $OverrideMethodName) = $OverrideKeyParts;

        return $this->getPluginInstance($OverrideClassName, self::ACCESS_CLASSNAME, $Sender)->$OverrideMethodName($Sender, $Sender->EventArguments);
    }

    /**
     * Checks to see if there are any plugins that override the method being
     * executed.
     *
     * @param string The name of the class that called the method being overridden.
     * @param string The name of the method that is being overridden.
     * @return bool True if an override exists.
     */
    public function hasMethodOverride($ClassName, $MethodName) {
        return array_key_exists(strtolower($ClassName.'_'.$MethodName.'_Override'), $this->_MethodOverrideCollection) ? true : false;
    }

    /**
     * Looks through $this->_NewMethodCollection for a matching method signature
     * to call. It instantiates any plugins and adds them as properties to this
     * class (unless they were previously instantiated), then calls the method
     * in question.
     *
     * @param object The object being worked on.
     * @param string The name of the class that called the method being created.
     * @param string The name of the method that is being created.
     * @return mixed Return value of new method.
     */
    public function callNewMethod($Sender, $ClassName, $MethodName) {
        $Return = false;
        $EventKey = strtolower($ClassName.'_'.$MethodName.'_Create');
        $NewMethodKey = ArrayValue($EventKey, $this->_NewMethodCollection, '');
        $NewMethodKeyParts = explode('.', $NewMethodKey);
        if (count($NewMethodKeyParts) != 2) {
            return false;
        }

        list($NewMethodClassName, $NewMethodName) = $NewMethodKeyParts;

        return $this->getPluginInstance($NewMethodClassName, self::ACCESS_CLASSNAME, $Sender)->$NewMethodName($Sender, GetValue('RequestArgs', $Sender, array()));
    }

    /**
     * Get the callback for an event handler.
     *
     * @param string $ClassName The name of the class throwing the event.
     * @param string $MethodName The name of the event.
     * @param string $Type The type of event handler.
     *  - Create: A new method creation.
     *  - Override: A method override.
     * @return callback
     * @since 2.1
     */
    public function getCallback($ClassName, $MethodName, $Type = 'Create') {
        $EventKey = strtolower("{$ClassName}_{$MethodName}_{$Type}");

        switch ($Type) {
            case 'Create':
                $MethodKey = GetValue($EventKey, $this->_NewMethodCollection);
                break;
            case 'Override':
                $MethodKey = GetValue($EventKey, $this->_MethodOverrideCollection);
                break;
        }
        $Parts = explode('.', $MethodKey, 2);
        if (count($Parts) != 2) {
            return false;
        }

        list($ClassName, $MethodName) = $Parts;
        $Instance = $this->getPluginInstance($ClassName, self::ACCESS_CLASSNAME);
        return array($Instance, $MethodName);
    }

    /**
     * Checks to see if there are any plugins that create the method being executed.
     *
     * @param string The name of the class that called the method being created.
     * @param string The name of the method that is being created.
     * @return True if method exists.
     */
    public function hasNewMethod($ClassName, $MethodName) {
        $Key = strtolower($ClassName.'_'.$MethodName.'_Create');
        if (array_key_exists($Key, $this->_NewMethodCollection)) {
            $Result = explode('.', $this->_NewMethodCollection[$Key]);
            return $Result[0];
        } else {
            return false;
        }
    }

    /**
     *
     *
     * @param $PluginFile
     * @param null $VariableName
     * @return null|void
     */
    public function scanPluginFile($PluginFile, $VariableName = null) {
        // Find the $PluginInfo array
        if (!file_exists($PluginFile)) {
            return;
        }
        $Lines = file($PluginFile);
        $InfoBuffer = false;
        $ClassBuffer = false;
        $ClassName = '';
        $PluginInfoString = '';
        if (!$VariableName) {
            $VariableName = 'PluginInfo';
        }

        $ParseVariableName = '$'.$VariableName;
        ${$VariableName} = array();

        foreach ($Lines as $Line) {
            if ($InfoBuffer && substr(trim($Line), -1) == ';') {
                $PluginInfoString .= $Line;
                $ClassBuffer = true;
                $InfoBuffer = false;
            }

            if (StringBeginsWith(trim($Line), $ParseVariableName)) {
                $InfoBuffer = true;
            }

            if ($InfoBuffer) {
                $PluginInfoString .= $Line;
            }

            if ($ClassBuffer && strtolower(substr(trim($Line), 0, 6)) == 'class ') {
                $Parts = explode(' ', $Line);
                if (count($Parts) > 2) {
                    $ClassName = $Parts[1];
                }

                break;
            }

        }
        unset($Lines);
        if ($PluginInfoString != '') {
            eval($PluginInfoString);
        }

        // Define the folder name and assign the class name for the newly added item
        if (isset(${$VariableName}) && is_array(${$VariableName})) {
            $Item = array_pop($Trash = array_keys(${$VariableName}));

            ${$VariableName}[$Item]['Index'] = $Item;
            ${$VariableName}[$Item]['ClassName'] = $ClassName;
            ${$VariableName}[$Item]['PluginFilePath'] = $PluginFile;
            ${$VariableName}[$Item]['PluginRoot'] = dirname($PluginFile);

            if (!array_key_exists('Name', ${$VariableName}[$Item])) {
                ${$VariableName}[$Item]['Name'] = $Item;
            }

            if (!array_key_exists('Folder', ${$VariableName}[$Item])) {
                ${$VariableName}[$Item]['Folder'] = $Item;
            }

            return ${$VariableName}[$Item];
        } elseif ($VariableName !== null) {
            if (isset(${$VariableName})) {
                return $$VariableName;
            }
        }

        return null;
    }

    /**
     * Get the current search paths
     *
     * By default, get all the paths as built by the constructor. Includes the two (or one) default plugin paths
     * of PATH_PLUGINS and PATH_LOCAL_PLUGINS, as well as any extra paths defined in the config variable.
     *
     * @param boolean $OnlyCustom whether or not to exclude the two default paths and return only config paths
     * @return array Search paths
     */
    public function searchPaths($OnlyCustom = false) {
        if (is_null($this->PluginSearchPaths) || is_null($this->AlternatePluginSearchPaths)) {
            $this->PluginSearchPaths = array();
            $this->AlternatePluginSearchPaths = array();

            // Add default search path(s) to list
            $this->PluginSearchPaths[rtrim(PATH_PLUGINS, '/')] = 'core';

            // Check for, and load, alternate search paths from config
            $RawAlternatePaths = C('Garden.PluginManager.Search', null);
            if (!is_null($RawAlternatePaths)) {
                $AlternatePaths = $RawAlternatePaths;

                if (!is_array($AlternatePaths)) {
                    $AlternatePaths = array($AlternatePaths => 'alternate');
                }

                foreach ($AlternatePaths as $AltPath => $AltName) {
                    $this->AlternatePluginSearchPaths[rtrim($AltPath, '/')] = $AltName;
                    if (is_dir($AltPath)) {
                        $this->PluginSearchPaths[rtrim($AltPath, '/')] = $AltName;
                    }
                }
            }
        }

        if (!$OnlyCustom) {
            return $this->PluginSearchPaths;
        }

        return $this->AlternatePluginSearchPaths;
    }

    /**
     *
     *
     * @param null $SearchPath
     * @return array
     */
    public function enabledPluginFolders($SearchPath = null) {
        if (is_null($SearchPath)) {
            return array_keys($this->enabledPlugins());
        } else {
            $Folders = array_flip(val($SearchPath, $this->PluginFoldersByPath, array()));
            return array_keys(array_intersect_key($Folders, $this->enabledPlugins()));
        }
    }

    /**
     *
     *
     * @param null $SearchPath
     * @return array|mixed
     */
    public function availablePluginFolders($SearchPath = null) {
        if (is_null($SearchPath)) {
            return array_keys($this->availablePlugins());
        } else {
            return val($SearchPath, $this->PluginFoldersByPath, array());
        }
    }

    /**
     * Test to see if a plugin throws fatal errors.
     */
    public function testPlugin($PluginName, &$Validation, $Setup = false) {
        // Make sure that the plugin's requirements are met
        // Required Plugins
        $PluginInfo = $this->getPluginInfo($PluginName);
        $RequiredPlugins = val('RequiredPlugins', $PluginInfo, false);
        CheckRequirements($PluginName, $RequiredPlugins, $this->enabledPlugins(), 'plugin');

        // Required Themes
        $EnabledThemes = Gdn::themeManager()->enabledThemeInfo();
        $RequiredThemes = ArrayValue('RequiredTheme', $PluginInfo, false);
        CheckRequirements($PluginName, $RequiredThemes, $EnabledThemes, 'theme');

        // Required Applications
        $EnabledApplications = Gdn::applicationManager()->enabledApplications();
        $RequiredApplications = ArrayValue('RequiredApplications', $PluginInfo, false);
        CheckRequirements($PluginName, $RequiredApplications, $EnabledApplications, 'application');

        // Include the plugin, instantiate it, and call its setup method
        $PluginClassName = ArrayValue('ClassName', $PluginInfo, false);
        $PluginFolder = ArrayValue('Folder', $PluginInfo, false);
        if ($PluginFolder == '') {
            throw new Exception(T('The plugin folder was not properly defined.'));
        }

        $this->pluginHook($PluginName, self::ACTION_ENABLE, $Setup);

        // If setup succeeded, register any specified permissions
        $PermissionName = val('RegisterPermissions', $PluginInfo, false);
        if ($PermissionName != false) {
            $PermissionModel = Gdn::permissionModel();
            $PermissionModel->define($PermissionName);
        }

        return true;
    }

    /**
     *
     *
     * @param $PluginName
     * @param $Validation
     * @param bool $Setup
     * @param string $EnabledPluginValueIndex
     * @return bool
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function enablePlugin($PluginName, $Validation, $Setup = false, $EnabledPluginValueIndex = 'Folder') {
        // Check that the plugin is in AvailablePlugins...
        $PluginInfo = $this->getPluginInfo($PluginName);

        // Couldn't load the plugin info.
        if (!$PluginInfo) {
            return false;
        }

        // Check to see if the plugin is already enabled.
        if (array_key_exists($PluginName, $this->enabledPlugins())) {
            throw new Gdn_UserException(T('The plugin is already enabled.'));
        }

        $this->testPlugin($PluginName, $Validation, $Setup);

        if (is_object($Validation) && count($Validation->results()) > 0) {
            return false;
        }

        // Write enabled state to config
        SaveToConfig("EnabledPlugins.{$PluginName}", true);
        Logger::event(
            'addon_enabled',
            LogLevel::NOTICE,
            'The {addonName} plugin was enabled.',
            array('addonName' => $PluginName)
        );

        $this->EnabledPlugins[$PluginName] = true;

        $PluginClassName = GetValue('ClassName', $PluginInfo);
        $this->registerPlugin($PluginClassName);

        Gdn::locale()->set(Gdn::locale()->current(), Gdn::applicationManager()->enabledApplicationFolders(), $this->enabledPluginFolders(), true);

        $this->EventArguments['AddonName'] = $PluginName;
        $this->fireEvent('AddonEnabled');

        return true;
    }

    /**
     *
     *
     * @param $PluginName
     * @return bool
     * @throws Exception
     */
    public function disablePlugin($PluginName) {
        // Get the plugin and make sure its name is the correct case.
        $Plugin = $this->getPluginInfo($PluginName);
        if ($Plugin) {
            $PluginName = $Plugin['Index'];
        }

        Gdn_Autoloader::smartFree(Gdn_Autoloader::CONTEXT_PLUGIN, $Plugin);

        $enabled = $this->isEnabled($PluginName);

        // 1. Check to make sure that no other enabled plugins rely on this one
        // Get all available plugins and compile their requirements
        foreach ($this->enabledPlugins() as $CheckingName => $Trash) {
            $CheckingInfo = $this->getPluginInfo($CheckingName);
            $RequiredPlugins = ArrayValue('RequiredPlugins', $CheckingInfo, false);
            if (is_array($RequiredPlugins) && array_key_exists($PluginName, $RequiredPlugins) === true) {
                throw new Exception(sprintf(T('You cannot disable the %1$s plugin because the %2$s plugin requires it in order to function.'), $PluginName, $CheckingName));
            }
        }

        // 2. Perform necessary hook action
        $this->pluginHook($PluginName, self::ACTION_DISABLE, true);

        // 3. Disable it.
        SaveToConfig("EnabledPlugins.{$PluginName}", false);
        unset($this->EnabledPlugins[$PluginName]);
        if ($enabled) {
            Logger::event(
                'addon_disabled',
                LogLevel::NOTICE,
                'The {addonName} plugin was disabled.',
                array('addonName' => $PluginName)
            );
        }

        // Redefine the locale manager's settings $Locale->Set($CurrentLocale, $EnabledApps, $EnabledPlugins, TRUE);
        Gdn::locale()->refresh();

        $this->EventArguments['AddonName'] = $PluginName;
        $this->fireEvent('AddonDisabled');

        return true;
    }

    /**
     *
     *
     * @param $AuthorsString
     * @param string $Format
     * @return array|string
     */
    public static function splitAuthors($AuthorsString, $Format = 'html') {
        $Authors = explode(';', $AuthorsString);
        $Result = array();
        foreach ($Authors as $AuthorString) {
            $Parts = explode(',', $AuthorString, 3);
            $Author = array();
            $Author['Name'] = trim($Author[0]);
            for ($i = 1; $i < count($Parts); $i++) {
                if (strpos($Parts[$i], '@') !== false) {
                    $Author['Email'] = $Parts[$i];
                } elseif (preg_match('`^https?://`', $Parts[$i]))
                    $Author['Url'] = $Parts[$i];
            }
            $Result[] = $Author;
        }

        if (strtolower($Format) == 'html') {
            // Build the html for the authors.
            $Htmls = array();
            foreach ($Result as $Author) {
                $Name = $Author['Name'];
                if (isset($Author['Url'])) {
                    $Url = $Author['Url'];
                } elseif (isset($Author['Email']))
                    $Url = "mailto:{$Author['Email']}";

                if (isset($Url)) {
                    $Htmls[] = '<a href="'.htmlspecialchars($Url).'">'.htmlspecialchars($Name).'</a>';
                }
            }
            $Result = implode(', ', $Htmls);
        }
        return $Result;

    }

    /**
     * Hooks to the various actions, i.e. enable, disable and remove.
     *
     * @param string $PluginName
     * @param string $ForAction which action to hook it to, i.e. enable, disable or remove
     * @param boolean $Callback whether to perform the hook method
     * @return void
     */
    private function pluginHook($PluginName, $ForAction, $Callback = false) {

        switch ($ForAction) {
            case self::ACTION_ENABLE:
                $HookMethod = 'Setup';
                break;
            case self::ACTION_DISABLE:
                $HookMethod = 'OnDisable';
                break;
            //case self::ACTION_REMOVE:  $HookMethod = 'CleanUp'; break;
            case self::ACTION_ONLOAD:
                $HookMethod = 'OnLoad';
                break;
        }

        $PluginInfo = ArrayValue($PluginName, $this->availablePlugins(), false);
        $PluginFolder = ArrayValue('Folder', $PluginInfo, false);
        $PluginClassName = ArrayValue('ClassName', $PluginInfo, false);

        if ($PluginFolder !== false && $PluginClassName !== false && class_exists($PluginClassName) === false) {
            if ($ForAction !== self::ACTION_DISABLE) {
                $this->includePlugins(array($PluginName => true));
            }

            $this->pluginCallbackExecution($PluginClassName, $HookMethod);
        } elseif ($Callback === true) {
            $this->pluginCallbackExecution($PluginClassName, $HookMethod);
        }
    }

    /**
     * Executes the plugin hook action if it exists.
     *
     * @param string $PluginClassName
     * @param string $HookMethod
     * @return void
     */
    private function pluginCallbackExecution($PluginClassName, $HookMethod) {
        if (class_exists($PluginClassName)) {
            $Plugin = new $PluginClassName();
            if (method_exists($PluginClassName, $HookMethod)) {
                $Plugin->$HookMethod();
            }
        }
    }
}
