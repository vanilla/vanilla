<?php
/**
 * Gdn_PluginManager
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */
use Garden\EventManager;
use Interop\Container\ContainerInterface;
use Vanilla\Addon;
use Vanilla\AddonManager;

/**
 * Plugin Manager.
 *
 * A singleton class used to identify extensions, register them in a central
 * location, and instantiate/call them when necessary.
 */
class Gdn_PluginManager extends Gdn_Pluggable implements ContainerInterface {

    const ACTION_ENABLE = 1;

    const ACTION_DISABLE = 2;

    //const ACTION_REMOVE  = 3;

    const ACCESS_CLASSNAME = 'classname';

    const ACCESS_PLUGINNAME = 'pluginname';

    /** @var array Available plugins. Never access this directly, instead use $this->availablePlugins(); */
    protected $pluginCache = null;

    /** @var array */
    protected $pluginsByClass = null;

    /** @var array */
    protected $pluginFoldersByPath = null;

    /** @var array A simple list of enabled plugins. */
    protected $enabledPlugins = null;

    /** @var array Search paths for plugins and their files. */
    protected $pluginSearchPaths = null;

    protected $alternatePluginSearchPaths = null;

    /** @var array A simple list of plugins that have already been registered. */
    private $registeredPlugins = [];

    /** @var array An array of the instances of plugins. */
    protected $instances = [];

    private $started = false;

    /** @var bool Whether or not to trace some event information. */
    public $trace = false;

    /**
     * @var AddonManager
     */
    private $addonManager;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * Whether we use the proper DI container or the Gdn_PluginManager as the container.
     * See https://github.com/vanilla/vanilla/pull/5859 for more details.
     *
     * @var bool.
     */
    private $hasProperContainer = false;

    /**
     * Initialize a new instance of the {@link Gdn_PluginManager} class.
     *
     * @param AddonManager $addonManager The addon manager that manages all of the addons.
     * @param EventManager $eventManager The event manager that handles all plugin events.
     */
    public function __construct(AddonManager $addonManager = null, EventManager $eventManager = null) {
        parent::__construct();
        $this->addonManager = $addonManager;
        $this->eventManager = $eventManager ?: new EventManager($this);
        $this->hasProperContainer = $eventManager !== null;
    }

    /**
     * Set up the plugin framework.
     */
    public function start() {
        // Register hooked methods.
        $this->registerPlugins();

        $this->started = true;
        $this->fireEvent('AfterStart');
    }

    /**
     * Is the plugin manager started?
     *
     * @return bool Returns **true** if the plugin manager is started or **false** otherwise.
     */
    public function started() {
        return $this->started;
    }

    /**
     * Get a list of available plugins.
     *
     * @deprecated Use {@link AddonManager::lookupAllByType()}.
     */
    public function availablePlugins() {
        $addons = $this->addonManager->lookupAllByType(Addon::TYPE_ADDON);

        $result = [];
        foreach ($addons as $addon) {
            /* @var Addon $addon */
            if ($addon->getInfoValue('oldType') !== 'plugin') {
                continue;
            }

            $infoArray  = static::calcOldInfoArray($addon);
            $result[$infoArray['Index']] = $infoArray;
        }
        return $result;
    }

    /**
     * The {@link AddonManager} does all this now.
     *
     * @deprecated
     */
    public function forceAutoloaderIndex() {
        deprecated('Gdn_PuginManager->forceAutoloaderIndex()');
    }

    /**
     * The {@link AddonManager} does all this now.
     *
     * @deprecated
     */
    public function clearPluginCache() {
        deprecated('Gdn_PluginManager->clearPluginCache()');
    }

    /**
     * Get a list of all of the enabled plugins.
     *
     * @return array
     */
    public function enabledPlugins() {
        $addons = $this->addonManager->getEnabled();
        $plugins = array_filter($addons, Addon::makeFilterCallback(['oldType' => 'plugin']));

        $result = [];
        /* @var Addon $plugin */
        foreach ($plugins as $key => $plugin) {
            $result[$plugin->getRawKey()] = $this->calcOldInfoArray($plugin);
        }
        return $result;
    }

    /**
     * Includes all of the plugin files for enabled plugins.
     *
     * Files are included in from the roots of each plugin directory if they have the following names.
     * - default.php
     * - *plugin.php
     *
     * @deprecated
     * @todo Remove this
     */
    public function includePlugins() {
        $enabled = $this->addonManager->getEnabled();
        foreach ($enabled as $addon) {
            /* @var \Vanilla\Addon $addon */
            if ($pluginClass = $addon->getPluginClass()) {
                include $addon->getClassPath($pluginClass);
            }
        }
    }

    /**
     * TODO: Remove this method.
     *
     * @param string $searchPath Deprecated.
     * @param array &$pluginInfo Deprecated.
     * @param array &$classInfo Deprecated.
     * @param array|null $pathListing Deprecated.
     * @return bool|string Deprecated.
     * @deprecated
     */
    public function indexSearchPath($searchPath, &$pluginInfo, &$classInfo, $pathListing = null) {
        if (is_null($pathListing) || !is_array($pathListing)) {
            $pathListing = scandir($searchPath, 0);
            sort($pathListing);
        }

        if ($pathListing === false) {
            return false;
        }

        foreach ($pathListing as $pluginFolderName) {
            if (substr($pluginFolderName, 0, 1) == '.') {
                continue;
            }

            $pluginPath = combinePaths([$searchPath, $pluginFolderName]);

            $pluginFile = $this->findPluginFileOld($pluginPath);

            if ($pluginFile === false) {
                continue;
            }

            $searchPluginInfo = $this->scanPluginFile($pluginFile);

            if (empty($searchPluginInfo)) {
                continue;
            }

            $realPluginFile = realpath($pluginFile);
            $searchPluginInfo['RealFile'] = $realPluginFile;
            $searchPluginInfo['RealRoot'] = dirname($realPluginFile);
            $searchPluginInfo['SearchPath'] = $searchPath;
            $searchPluginInfo['Dir'] = "/plugins/$pluginFolderName";

            $iconUrl = $searchPluginInfo['Dir'].'/'.val('Icon', $searchPluginInfo, 'icon.png');
            if (file_exists(PATH_ROOT.$iconUrl)) {
                $searchPluginInfo['IconUrl'] = $iconUrl;
            }

            $pluginInfo[$pluginFolderName] = $searchPluginInfo;

//            $addon = $this->addonManager->lookupAddon($SearchPluginInfo['Index']);
//            $info = static::calcOldInfoArray($addon);
//
//            $diff = array_diff($SearchPluginInfo, $info);

            $pluginClassName = val('ClassName', $searchPluginInfo);
            $classInfo[$pluginClassName] = $pluginFolderName;
        }

        return md5(serialize($pathListing));
    }

    /**
     * Calculate an old info array from a new {@link Addon}.
     *
     * This is a backwards compatibility function only and should not be used for new code.
     *
     * @param Addon $addon The addon to calculate.
     * @return array Returns an info array.
     * @deprecated
     */
    public static function calcOldInfoArray(Addon $addon) {
        $info = $addon->getInfo();

        $capitalCaseSheme = new \Vanilla\Utility\CapitalCaseScheme();
        $info = $capitalCaseSheme->convertArrayKeys($info, ['RegisterPermissions']);

        // This is the basic information from scanPluginFile().
        $name = $addon->getInfoValue('keyRaw', $addon->getKey());
        $info['Index'] = $name;
        $info['ClassName'] = $addon->getPluginClass();
        $info['PluginFilePath'] = $addon->getClassPath($addon->getPluginClass(), Addon::PATH_FULL);
        $info['PluginRoot'] = $addon->path();
        touchValue('Name', $info, $name);
        touchValue('Folder', $info, $name);
        $info['Dir'] = $addon->path('', Addon::PATH_ADDON);

        if ($icon = $addon->getIcon(Addon::PATH_ADDON)) {
            $info['IconUrl'] = $icon;
        }


        // This is some additional information from indexSearchPath().
        if ($info['PluginFilePath']) {
            $info['RealFile'] = realpath($info['PluginFilePath']);
        }
        $info['RealRoot'] = realpath($info['PluginRoot']);
        $info['SearchPath'] = dirname($addon->path());

        // Rejoin the authors.
        $names = [];
        $emails = [];
        $homepages = [];
        foreach ($addon->getInfoValue('authors', []) as $author) {
            if (isset($author['name'])) {
                $names[] = $author['name'];
            }
            if (isset($author['email'])) {
                $emails[] = $author['email'];
            }
            if (isset($author['homepage'])) {
                $homepages[] = $author['homepage'];
            }
        }
        if (!empty($names)) {
            $info['Author'] = implode(', ', $names);
        }
        if (!empty($emails)) {
            $info['AuthorEmail'] = implode(', ', $emails);
        }
        if (!empty($homepages)) {
            $info['AuthorUrl'] = implode(', ', $homepages);
        }
        unset($info['Authors']);

        // Themes have their own particular fields.
        if ($addon->getType() === Addon::TYPE_THEME) {
            $info['ThemeRoot'] = $addon->path('');
            if (file_exists($addon->path('about.php'))) {
                $info['AboutFile'] = $addon->path('about.php');
                $info['RealAboutFile'] = realpath($info['AboutFile']);
            }

            if ($hooksClass = $addon->getPluginClass()) {
                $info['HooksFile'] = $addon->getClassPath($hooksClass);
                $info['RealHooksFile'] = realpath($info['HooksFile']);
            }

            if ($screenshot = $addon->getIcon(Addon::PATH_ADDON)) {
                if (basename($screenshot) === 'mobile.png') {
                    $info['MobileScreenshotUrl'] = $screenshot;
                } else {
                    $info['ScreenshotUrl'] = $screenshot;
                }
            }
        }

        return $info;
    }

    /**
     * TODO: Remove this.
     *
     * @return bool Deprecated.
     */
    public function addSearchPath() {
        deprecated('Gdn_PluginManager->addSearchPath()');
        return true;
    }

    /**
     * TODO: Remove this.
     *
     * @return bool Deprecated.
     */
    public function removeSearchPath() {
        deprecated('Gdn_PluginManager->removeSearchPath()');
        return true;
    }

    /**
     * Find a plugin file using the old plugin manager method.
     *
     * @param string $path The root path of the plugin.
     * @return string|false Returns the path to the plugin class or **false** if one isn't found.
     */
    private function findPluginFileOld($path) {
        if (!is_dir($path)) {
            return false;
        }
        $pluginFiles = scandir($path);
        $testPatterns = [
            'default.php', '*plugin.php'
        ];
        foreach ($pluginFiles as $pluginFile) {
            foreach ($testPatterns as $test) {
                if (fnmatch($test, $pluginFile)) {
                    return combinePaths([$path, $pluginFile]);
                }
            }
        }

        return false;
    }

    /**
     * Deprecated.
     *
     * @param string $path Deprecated.
     * @return string|false Deprecated.
     */
    public function findPluginFile($path) {
        deprecated('Gdn_PluginManager->findPluginFile()');
        try {
            $addon = new Addon($path);
            if ($pluginClass = $addon->getPluginClass()) {
                return $addon->getClassPath($pluginClass, Addon::PATH_FULL);
            }
            return false;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * Register all enabled plugins' event handlers and overrides.
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
     *   public function myController_SignIn_After($Sender) {
     *      // Do something neato
     *   }
     *   public function url_AppRoot_Override($WithDomain) {
     *      return "MyCustomAppRoot!";
     *   }
     *  }
     */
    public function registerPlugins() {
        $enabled = $this->addonManager->getEnabled();
        foreach ($enabled as $addon) {
            /* @var \Vanilla\Addon $addon */
            if ($pluginClass = $addon->getPluginClass()) {
                // Include the plugin here, rather than wait for it to hit the autoloader. This way is much faster.
                include_once $addon->getClassPath($pluginClass);

                if (!is_a($pluginClass, 'Gdn_IPlugin', true)) {
                    trigger_error("$pluginClass does not implement Gdn_IPlugin", E_USER_DEPRECATED);
                }

                // Only register the plugin if it implements the Gdn_IPlugin interface.
                if (is_a($pluginClass, 'Gdn_IPlugin', true) &&
                    !isset($this->registeredPlugins[$pluginClass])
                ) {

                    // Register this plugin's methods
                    $this->registerPlugin($pluginClass, $addon->getPriority());
                }
            }
        }
    }

    /**
     *
     *
     * @return array
     */
    public function registeredPlugins() {
        return $this->registeredPlugins;
    }

    /**
     * Register a plugin's events.
     *
     * @param string $className The name of the class.
     * @throws Exception
     */
    public function registerPlugin($className, $priority = EventManager::PRIORITY_NORMAL) {
        $className = strtolower($className);
        if (empty($this->registeredPlugins[$className])) {
            $this->eventManager->bindClass($className, $priority);
        }
        $this->registeredPlugins[$className] = true;
    }

    /**
     *
     *
     * @param $pluginClassName
     * @return bool
     */
    public function unregisterPlugin($pluginClassName) {
        $this->eventManager->unbindClass($pluginClassName);
        return true;
    }

    /**
     * Removes all plugins that are marked as mobile unfriendly.
     */
    public function removeMobileUnfriendlyPlugins() {
        $addons = $this->addonManager->getEnabled();
        $plugins = array_filter($addons, Addon::makeFilterCallback(['oldType' => 'plugin']));

        /* @var Addon $plugin */
        foreach ($plugins as $key => $plugin) {
            if (!$plugin->getInfoValue('mobileFriendly', true) && $plugin->getPluginClass()) {
                $this->unregisterPlugin($plugin->getPluginClass());
            }
        }
    }

    /**
     * Check whether a plugin is enabled.
     *
     * @param string $pluginName The name of the plugin.
     * @return bool Returns **true** if the plugin is enabled or **false** otherwise.
     * @deprecated Use {@link AddonManager::isEnabled()} instead.
     */
    public function checkPlugin($pluginName) {
        deprecated('Gdn_PluginManager->checkPlugin()', 'AddonManager->isEnabled()');
        $result = $this->isEnabled($pluginName);
        return $result;
    }

    /**
     *
     *
     * @param $sender
     * @param $eventName
     * @param string $handlerType
     * @param array $options
     * @return array
     */
    public function getEventHandlers($sender, $eventName, $handlerType = 'Handler', $options = []) {
        deprecated(__METHOD__);
        // Figure out the classname.
        if (isset($options['ClassName'])) {
            $className = $options['ClassName'];
        } elseif (property_exists($sender, 'ClassName') && $sender->ClassName) {
            $className = $sender->ClassName;
        } else {
            $className = get_class($sender);
        }

        $handlerType = strtolower($handlerType);
        switch ($handlerType) {
            case 'handler':
                $handlerType = '';
                break;
            case 'create':
            case 'override':
                $handlerType = '_method';
                break;
            default:
                $handlerType = '_'.$handlerType;
        }

        // Build the list of event handler names.
        $names = [
            "{$className}_{$eventName}{$handlerType}",
            "Base_{$eventName}{$handlerType}"
        ];

        // Grab the event handlers.
        $handlers = [];
        foreach ($names as $name) {
            $handlers[] = $this->eventManager->getHandlers($name);
        }

        return call_user_func('array_merge', $handlers);
    }

    /**
     * Get the information array for a plugin.
     *
     * @param string $name The name of the plugin to access.
     * @param string $accessType Either **Gdn_PluginManager::ACCESS_CLASSNAME** or
     * Gdn_PluginManager::ACCESS_PLUGINNAME** (default).
     * @return bool|array Returns an info array or **false** if the plugin isn't found.
     * @deprecated
     */
    public function getPluginInfo($name, $accessType = self::ACCESS_PLUGINNAME) {
        switch ($accessType) {
            case self::ACCESS_PLUGINNAME:
                $addon = $this->addonManager->lookupAddon($name);
                break;

            case self::ACCESS_CLASSNAME:
            default:
                trigger_error("Gdn_PluginManager->getPluginInfo() with ACCESS_CLASSNAME should not be called.");
                $addon = $this->addonManager->lookupByClassname($name, true);
                break;
        }

        if ($addon instanceof Addon) {
            $info = static::calcOldInfoArray($addon);
            return $info;
        } else {
            return false;
        }
    }

    /**
     * Gets an instance of a given plugin.
     *
     * @param string $name The key of the plugin.
     * @param string $accessType The type of key for the plugin which must be one of the following:
     *
     *  - Gdn_PluginManager::ACCESS_PLUGINNAME
     *  - Gdn_PluginManager::ACCESS_CLASSNAME
     * @param mixed $sender An object to pass to a new plugin instantiation.
     * @return Gdn_IPlugin The plugin instance.
     */
    public function getPluginInstance($name, $accessType = self::ACCESS_CLASSNAME, $sender = null) {
        $className = null;
        switch ($accessType) {
            case self::ACCESS_PLUGINNAME:
                $addon = $this->addonManager->lookupAddon($name);

                if ($addon === null || $addon->getPluginClass() == '') {
                    throw new InvalidArgumentException("The $name plugin doesn't have a plugin class.", 500);
                }
                $className = $addon->getPluginClass();
                break;
            case self::ACCESS_CLASSNAME:
            default:
                $className = $name;
                $addon = $this->addonManager->lookupByClassname($className);
                break;
        }

        if (!class_exists($className)) {
            throw new Exception("Tried to load plugin '{$className}' from access name '{$name}:{$accessType}', but it doesn't exist.");
        }

        if (!isset($this->instances[$className])) {
            if ($this->hasProperContainer) {
                $object = $this->eventManager->getContainer()->get($className);
            } else {
                if ($sender === null) {
                    $object = new $className();
                } else {
                    $object = new $className($sender);
                }
            }
            if (method_exists($object, 'setAddon')) {
                $object->setAddon($addon);
            }

            $this->instances[$className] = $object;
        }

        return $this->instances[$className];
    }

    /**
     * Returns whether or not a plugin is enabled.
     *
     * @param string $name The name of the plugin.
     * @return bool Whether or not the plugin is enabled.
     * @deprecated
     */
    public function isEnabled($name) {
        deprecated('Gdn_PluginManager->isEnabled()', 'AddonManager->isEnabled()');
        $enabled = $this->addonManager->isEnabled($name, Addon::TYPE_ADDON);
        return $enabled;
    }

    /**
     * Registers a plugin method name as a handler.
     *
     * @param string $handlerClassName The name of the plugin class that will handle the event.
     * @param string $handlerMethodName The name of the plugin method being registered to handle the event.
     * @param string $eventClassName The name of the class that will fire the event.
     * @param string $eventName The name of the event that will fire.
     * @param string $eventHandlerType The type of event handler.
     */
    public function registerHandler($handlerClassName, $handlerMethodName, $eventClassName = '', $eventName = '', $eventHandlerType = '') {
        deprecated(__METHOD__);

        $key = $this->normalizeEventKey($eventClassName ?: $handlerClassName, $eventName, $eventHandlerType);
        $this->eventManager->bindLazy($key, $handlerClassName, $handlerMethodName);
    }

    /**
     * Generate an event name from a class, event, and type.
     *
     * @param string $class The name of the class for the handler.
     * @param string $event The name of the event.
     * @param string $type The name of the type.
     * @return string Returns an event name as a string.
     */
    private function normalizeEventKey($class, $event, $type = 'handler') {
        $key = strtolower($class.'_'.$event);
        $type = strtolower($type);

        switch ($type) {
            case 'handler':
            case '':
                return $key;
            case 'create':
            case 'override':
                return $key.'_method';
            default:
                return $key.'_'.$type;
        }
    }

    /**
     * Register a a callback to handle an event.
     *
     * @param string $eventName The name of the event to register.
     * @param callable $callback The callback to call when the event is fired.
     */
    public function registerCallback($eventName, callable $callback) {
        $eventName = strtolower($eventName);
        $suffix = strrchr($eventName, '_');
        $basename = substr($eventName, 0, -strlen($suffix));

        switch ($suffix) {
            case '_handler':
                $eventName = $basename;
                break;
            case '_create':
            case '_override':
                $eventName = $basename.'_method';
                break;
        }

        $this->eventManager->bind($eventName, $callback);
    }

    /**
     * Registers a plugin override method.
     *
     * @param string $OverrideClassName The name of the plugin class that will override the existing method.
     * @param string $handlerMethodName The name of the plugin method being registered to override the existing method.
     * @param string $eventClassName The name of the class that will fire the event.
     * @param string $eventName The name of the event that will fire.
     */
    public function registerOverride($handlerClassName, $handlerMethodName, $eventClassName = '', $eventName = '') {
        $this->registerHandler($handlerClassName, $handlerMethodName, $eventClassName, $eventName, 'method');
    }

    /**
     * Registers a plugin new method.
     *
     * @param string $handlerClassName The name of the plugin class that will add a new method.
     * @param string $handlerMethodName The name of the plugin method being added.
     * @param string $eventClassName The name of the class that will fire the event.
     * @param string $eventName The name of the event that will fire.
     */
    public function registerNewMethod($handlerClassName, $handlerMethodName, $eventClassName = '', $eventName = '') {
        $this->registerHandler($handlerClassName, $handlerMethodName, $eventClassName, $eventName, 'method');
    }

    /**
     * Transfer control to the plugins.
     *
     * Looks through $this->_EventHandlerCollection for matching event
     * signatures to handle. If it finds any, it executes them in the order it
     * found them. It instantiates any plugins and adds them as properties to
     * this class (unless they were previously instantiated), and then calls
     * the handler in question.
     *
     * @param object $sender The object that fired the event being handled.
     * @param string $eventClassName The name of the class that fired the event being handled.
     * @param string $eventName The name of the event being fired.
     * @param string $eventHandlerType The type of handler being fired (Handler, Before, After).
     * @return bool Returns **true** if an event was executed.
     */
    public function callEventHandlers($sender, $eventClassName, $eventName, $eventHandlerType = 'Handler') {
        $return = false;

        // Look through $this->_EventHandlerCollection for relevant handlers
        if ($this->callEventHandler($sender, $eventClassName, $eventName, $eventHandlerType)) {
            $return = true;
        }

        // Look for "Base" (aka any class that has $EventName)
        if ($this->callEventHandler($sender, 'Base', $eventName, $eventHandlerType)) {
            $return = true;
        }

        return $return;
    }

    /**
     * Call a single event handler.
     *
     * @param object $sender The object firing the event.
     * @param string $className The name of the class firing the event.
     * @param string $eventName The name of the event being fired.
     * @param string $handlerType The type of event handler being looked for.
     * @return mixed Returns whatever the event handler returns or **false** of there is not event handler.
     */
    public function callEventHandler($sender, $className, $eventName, $handlerType = 'handler') {
        $eventKey = strtolower("{$className}_{$eventName}");
        $handlerType = strtolower($handlerType);
        $originalEventKey = $eventKey.'_'.$handlerType;

        switch ($handlerType) {
            case 'handler':
                // Do nothing.
                break;
            case 'create':
                $eventKey = $originalEventKey.'_method';
                break;
            default:
                $eventKey = $originalEventKey;
        }


        $results = $this->eventManager->fire(
            $eventKey,
            $sender,
            isset($sender->EventArguments) ? $sender->EventArguments : []
        );

        if (isset($sender->Returns)) {
            $sender->Returns[$originalEventKey] = $results;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Looks through $this->_MethodOverrideCollection for a matching method signature to override.
     *
     * It instantiates any plugins and adds them as properties to this class (unless they were previously instantiated),
     * then calls the method in question.
     *
     * @param object $sender The object being worked on.
     * @param string $className The name of the class that called the method being overridden.
     * @param string $methodName The name of the method that is being overridden.
     * @return mixed Returns the value of overridden method.
     */
    public function callMethodOverride($sender, $className, $methodName) {
        $callback = $this->getCallback($className, $methodName, 'Override');
        if (is_callable($callback)) {
            return call_user_func($callback, $sender, val('RequestArgs', $sender, []));
        }
    }

    /**
     * Checks to see if there are any plugins that override the method being executed.
     *
     * @param string $className The name of the class that called the method being overridden.
     * @param string $methodName The name of the method that is being overridden.
     * @return bool Returns **true** if an override exists or **false** otherwise.
     */
    public function hasMethodOverride($className, $methodName) {
        return $this->eventManager->hasHandler($this->normalizeEventKey($className, $methodName, 'method'));
    }

    /**
     * Looks through the registered new methods for a matching method signature to call.
     *
     * It instantiates any plugins and adds them as properties to this class (unless they were previously instantiated),
     * then calls the method in question.
     *
     * @param object $sender The object being worked on.
     * @param string $className The name of the class that called the method being created.
     * @param string $methodName The name of the method that is being created.
     * @return mixed Return value of new method.
     */
    public function callNewMethod($sender, $className, $methodName) {
        $callback = $this->getCallback($className, $methodName, 'Create');
        if (is_callable($callback)) {
            return call_user_func($callback, $sender, val('RequestArgs', $sender, []));
        }
    }

    /**
     * Get the callback for an event handler.
     *
     * @param string $className The name of the class throwing the event.
     * @param string $methodName The name of the event.
     * @return callback|null
     * @since 2.1
     */
    public function getCallback($className, $methodName) {
        $eventKey = "{$className}_{$methodName}_method";
        $handlers = $this->eventManager->getHandlers($eventKey);

        if (!empty($handlers)) {
            $callback = reset($handlers);
            return $callback;
        } else {
            return null;
        }
    }

    /**
     * Checks to see if there are any plugins that create the method being executed.
     *
     * @param string $className The name of the class that called the method being created.
     * @param string $methodName The name of the method that is being created.
     * @return bool Returns **true** if the method exists.
     */
    public function hasNewMethod($className, $methodName) {
        $event = "{$className}_{$methodName}_method";
        return $this->eventManager->hasHandler($event);
    }

    /**
     * Return the plugin's information.
     *
     * @param string $PluginFile
     * @param null $VariableName
     * @return array|null Return the plugin's information or null
     */
    public function scanPluginFile($PluginFile, $VariableName = null) {
        // Find the $PluginInfo array
        if (!file_exists($PluginFile)) {
            return null;
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

        foreach ($Lines as $Line) {
            $TrimmedLine = trim($Line);
            if ($InfoBuffer && substr($TrimmedLine, -1) == ';') {
                $PluginInfoString .= $Line;
                $ClassBuffer = true;
                $InfoBuffer = false;
            }

            if (stringBeginsWith($TrimmedLine, $ParseVariableName)) {
                $InfoBuffer = true;
            }

            if ($InfoBuffer) {
                $PluginInfoString .= $Line;
            }

            if ($ClassBuffer && strtolower(substr($TrimmedLine, 0, 6)) == 'class ') {
                $Parts = explode(' ', $TrimmedLine);
                if (count($Parts) > 2) {
                    $ClassName = $Parts[1];
                }

                break;
            }

        }
        unset($Lines);

        // Return early!
        if (empty($PluginInfoString)) {
            return null;
        }

        eval($PluginInfoString);

        // Define the folder name and assign the class name for the newly added item.
        $var = !empty(${$VariableName}) ? ${$VariableName} : null;
        if (is_array($var) && !array_key_exists('Name', $var)) {
            reset($var);
            $name = key($var);
            $var = (array)current($var);

            $var['Index'] = $name;
            $var['ClassName'] = $ClassName;
            $var['PluginFilePath'] = $PluginFile;
            $var['PluginRoot'] = dirname($PluginFile);
            touchValue('Name', $var, $name);
            touchValue('Folder', $var, $name);

            return $var;
        }

        return null;
    }

    /**
     * Get the current search paths
     *
     * By default, get all the paths as built by the constructor. Includes the two (or one) default plugin paths
     * of PATH_PLUGINS and PATH_LOCAL_PLUGINS, as well as any extra paths defined in the config variable.
     *
     * @param boolean $onlyCustom whether or not to exclude the two default paths and return only config paths
     * @return array Search paths
     */
    public function searchPaths($onlyCustom = false) {
        if (is_null($this->pluginSearchPaths) || is_null($this->alternatePluginSearchPaths)) {
            $this->pluginSearchPaths = [];
            $this->alternatePluginSearchPaths = [];

            // Add default search path(s) to list
            $this->pluginSearchPaths[rtrim(PATH_PLUGINS, '/')] = 'core';

            // Check for, and load, alternate search paths from config
            $rawAlternatePaths = c('Garden.PluginManager.Search', null);
            if (!is_null($rawAlternatePaths)) {
                $alternatePaths = $rawAlternatePaths;

                if (!is_array($alternatePaths)) {
                    $alternatePaths = [$alternatePaths => 'alternate'];
                }

                foreach ($alternatePaths as $altPath => $altName) {
                    $this->alternatePluginSearchPaths[rtrim($altPath, '/')] = $altName;
                    if (is_dir($altPath)) {
                        $this->pluginSearchPaths[rtrim($altPath, '/')] = $altName;
                    }
                }
            }
        }

        if (!$onlyCustom) {
            return $this->pluginSearchPaths;
        }

        return $this->alternatePluginSearchPaths;
    }

    /**
     *
     *
     * @param null $searchPath
     * @return array
     */
    public function enabledPluginFolders($searchPath = null) {
        if (is_null($searchPath)) {
            return array_keys($this->enabledPlugins());
        } else {
            $folders = array_flip(val($searchPath, $this->pluginFoldersByPath, []));
            return array_keys(array_intersect_key($folders, $this->enabledPlugins()));
        }
    }

    /**
     *
     *
     * @param null $searchPath
     * @return array|mixed
     */
    public function availablePluginFolders($searchPath = null) {
        if (is_null($searchPath)) {
            return array_keys($this->availablePlugins());
        } else {
            return val($searchPath, $this->pluginFoldersByPath, []);
        }
    }

    /**
     * Test to see if a plugin throws fatal errors.
     *
     * @param string $pluginName The name of the plugin to test.
     * @deprecated
     */
    public function testPlugin($pluginName) {
        $addon = $this->addonManager->lookupAddon($pluginName);
        if (!$addon) {
            throw notFoundException('Plugin');
        }

        try {
            $this->addonManager->checkRequirements($addon, true);
            $addon->test(true);
        } catch (\Exception $ex) {
            throw new Gdn_UserException($ex->getMessage(), $ex->getCode());
        }
        return true;
    }

    /**
     *
     *
     * @param $pluginName
     * @param Gdn_Validation $validation
     * @param array|bool $options
     * @return array|bool
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function enablePlugin($pluginName, $validation, $options = []) {

        $setup = val('Setup', $options, true);
        $force = val('Force', $options, false);

        // Check to see if the plugin is already enabled.
        if ($this->addonManager->isEnabled($pluginName, Addon::TYPE_ADDON) && !$force) {
            throw new Gdn_UserException(t('The plugin is already enabled.'));
        }

        $addon = $this->addonManager->lookupAddon($pluginName);
        if (!$addon) {
            throw notFoundException('Plugin');
        }

        if (!$validation instanceof Gdn_Validation) {
            $validation = new Gdn_Validation();
        }

        try {
            $this->addonManager->checkRequirements($addon, true);
            $addon->test(true);
        } catch (\Exception $ex) {
            $validation->addValidationResult('addon', '@'.$ex->getMessage());
            return false;
        }

        // Enable this addon's requirements.
        $requirements = $this->addonManager->lookupRequirements($addon, AddonManager::REQ_DISABLED);
        $enabledRequirements = [];
        foreach ($requirements as $addonKey => $row) {
            $requiredAddon = $this->addonManager->lookupAddon($addonKey);
            $this->enableAddon($requiredAddon, $setup);
            $enabledRequirements[] = $requiredAddon;
        }

        // Enable the addon.
        $this->enableAddon($addon, $setup);

        // Refresh the locale just in case there are some translations needed this request.
        Gdn::locale()->refresh();

        $this->EventArguments['AddonName'] = $addon->getRawKey();
        $this->fireEvent('AddonEnabled');

        $result = [
            'AddonEnabled' => true,
            'RequirementsEnabled' => $enabledRequirements
        ];
        return $result;
    }

    /**
     * Disable a plugin.
     *
     * @param string $pluginName The name of the plugin.
     * @return bool
     * @throws Exception
     */
    public function disablePlugin($pluginName) {
        $addon = $this->addonManager->lookupAddon($pluginName);

        if (!$addon) {
            return false;
        }

        $pluginClassName = $addon->getPluginClass();
        $pluginName = $addon->getRawKey();
        $enabled = $this->addonManager->isEnabled($pluginName, Addon::TYPE_ADDON);

        try {
            $this->addonManager->checkDependents($addon, true);
        } catch (\Exception $ex) {
            throw new Gdn_UserException($ex->getMessage(), 400);
        }

        // 2. Perform necessary hook action
        $this->pluginHook($pluginName, self::ACTION_DISABLE, true);

        // 3. Disable it.
        saveToConfig("EnabledPlugins.{$pluginName}", false);

        $this->addonManager->stopAddon($addon);

        // 4. Unregister the plugin properly.
        $this->unregisterPlugin($pluginClassName);

        if ($enabled) {
            Logger::event(
                'addon_disabled',
                Logger::INFO,
                'The {addonName} plugin was disabled.',
                ['addonName' => $pluginName]
            );
        }

        // Redefine the locale manager's settings $Locale->set($CurrentLocale, $EnabledApps, $EnabledPlugins, TRUE);
        Gdn::locale()->refresh();

        $this->EventArguments['AddonName'] = $pluginName;
        $this->fireEvent('AddonDisabled');

        return true;
    }

    /**
     * Split a string containing several authors.
     *
     * @param string $authorsString The author string.
     * @param string $format What format to return the result in.
     * @return array|string Returns the authors as an array or string if HTML is requested.
     * @deprecated The authors array is already properly split in the {@link Addon}.
     */
    public static function splitAuthors($authorsString, $format = 'html') {
        $authors = explode(';', $authorsString);
        $result = [];
        foreach ($authors as $authorString) {
            $parts = explode(',', $authorString, 3);
            $author = [];
            $author['Name'] = trim($author[0]);
            for ($i = 1; $i < count($parts); $i++) {
                if (strpos($parts[$i], '@') !== false) {
                    $author['Email'] = $parts[$i];
                } elseif (preg_match('`^https?://`', $parts[$i]))
                    $author['Url'] = $parts[$i];
            }
            $result[] = $author;
        }

        if (strtolower($format) == 'html') {
            // Build the html for the authors.
            $htmls = [];
            foreach ($result as $author) {
                $name = $author['Name'];
                if (isset($author['Url'])) {
                    $url = $author['Url'];
                } elseif (isset($author['Email']))
                    $url = "mailto:{$author['Email']}";

                if (isset($url)) {
                    $htmls[] = '<a href="'.htmlspecialchars($url).'">'.htmlspecialchars($name).'</a>';
                }
            }
            $result = implode(', ', $htmls);
        }
        return $result;

    }

    /**
     * Hooks to the various actions, i.e. enable, disable and load.
     *
     * @param string $pluginName The name of the plugin.
     * @param string $forAction Which action to hook it to, i.e. enable, disable or load.
     * @param boolean $callback whether to perform the hook method.
     * @return void
     */
    private function pluginHook($pluginName, $forAction, $callback = false) {
        switch ($forAction) {
            case self::ACTION_ENABLE:
                $methodName = 'setup';
                break;
            case self::ACTION_DISABLE:
                $methodName = 'onDisable';
                break;
            default:
                $methodName = '';
        }

        $addon = $this->addonManager->lookupAddon($pluginName);
        if (!$addon || !$addon->getPluginClass()) {
            return;
        }

        $pluginClass = $addon->getPluginClass();

        if ($callback && !empty($pluginClass) && class_exists($pluginClass)) {
            $plugin = $this->eventManager->getContainer()->get($pluginClass);
            if (method_exists($plugin, 'setAddon')) {
                $plugin->setAddon($addon);
            }
            if (method_exists($pluginClass, $methodName)) {
                $plugin->$methodName();
            }
        }
    }

    /**
     * Enable an addon and do all the stuff that's entailed there.
     *
     * @param Addon $addon The addon to enable.
     * @param bool $setup Whether or not to set the plugin up.
     * @throws Exception Throws an exception if something goes bonkers during the process.
     */
    private function enableAddon(Addon $addon, $setup) {
        if ($setup) {
            $this->addonManager->startAddon($addon);
            $this->pluginHook($addon->getRawKey(), self::ACTION_ENABLE, true);

            // If setup succeeded, register any specified permissions
            $permissions = $addon->getInfoValue('registerPermissions');
            if (!empty($permissions)) {
                $permissionModel = Gdn::permissionModel();
                $permissionModel->define($permissions);
            }

            // Write enabled state to config.
            saveToConfig("EnabledPlugins.".$addon->getRawKey(), true);
            Logger::event(
                'addon_enabled',
                Logger::INFO,
                'The {addonName} plugin was enabled.',
                ['addonName' => $addon->getRawKey()]
            );
        }

        $pluginClassName = $addon->getPluginClass();
        $this->registerPlugin($pluginClassName, $addon->getPriority());
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws \Interop\Container\Exception\NotFoundException  No entry was found for this identifier.
     * @throws \Interop\Container\Exception\ContainerException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id) {
        return $this->getPluginInstance($id, self::ACCESS_CLASSNAME);
    }

    /**
     * Returns true if the container can return an entry for the given identifier. Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($id) {
        return class_exists($id);
    }
}
