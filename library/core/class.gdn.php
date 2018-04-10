<?php
/**
 * The heart of the beast.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Framework superobject.
 *
 * Static object that provides an anchor and namespace for many framework
 * components, such as Controller, Dispatcher, Config, Database, etc.
 */
class Gdn {

    const AliasAuthenticator = 'Authenticator';

    const AliasAddonManager = 'AddonManager';

    const AliasCache = 'Cache';

    const AliasConfig = 'Config';

    const AliasDatabase = 'Database';

    const AliasDatabaseStructure = 'DatabaseStructure';

    const AliasDispatcher = 'Dispatcher';

    const AliasLocale = 'Locale';

    const AliasPermissionModel = 'PermissionModel';

    const AliasRequest = 'Request';

    const AliasRouter = 'Router';

    const AliasSession = 'Session';

    const AliasSqlDriver = 'SqlDriver';

    const AliasUserMetaModel = 'UserMetaModel';

    const AliasUserModel = 'UserModel';

    const AliasApplicationManager = 'ApplicationManager';

    const AliasPluginManager = 'PluginManager';

    const AliasThemeManager = 'ThemeManager';

    const FactoryInstance = 'Instance';

    const FactoryPrototype = 'Prototype';

    const FactorySingleton = 'Singleton';

    const FactoryRealSingleton = 'RealSingleton';

    /**
     * @var \Garden\Container\Container
     */
    private static $container;

    /** @var object  */
    protected static $_Config = null;

    /** @var Gdn_Factory The factory used to create core objects in the application. */
    protected static $_Factory = null;

    /** @var boolean Whether or not Gdn::FactoryInstall should overwrite existing objects. */
    protected static $_FactoryOverwrite = true;

    /** @var object  */
    protected static $_Locale = null;

    /** @var object  */
    protected static $_Request = null;

    /** @var object  */
    protected static $_PluginManager = null;

    /** @var object  */
    protected static $_Session = null;

    /**
     * Get the addon manager.
     *
     * @return \Vanilla\AddonManager
     */
    public static function addonManager() {
        return self::factory(self::AliasAddonManager);
    }

    /**
     * Get the application manager
     *
     * @return Gdn_ApplicationManager
     */
    public static function applicationManager() {
        return self::factory(self::AliasApplicationManager);
    }

    /**
     *
     *
     * @return Gdn_Auth
     */
    public static function authenticator() {
        return self::factory(self::AliasAuthenticator);
    }

    /**
     * Get the cache object
     *
     * @return Gdn_Cache
     */
    public static function cache() {
        return self::factory(self::AliasCache);
    }

    /**
     * Get a configuration setting for the application.
     *
     * @param string $name The name of the configuration setting. Settings in different sections are seperated by a dot ('.')
     * @param mixed $default The result to return if the configuration setting is not found.
     * @return Gdn_Configuration|mixed The configuration setting.
     */
    public static function config($name = false, $default = false) {
        if (self::$_Config === null) {
            self::$_Config = static::getContainer()->get(self::AliasConfig);
        }
        $config = self::$_Config;
        if ($name === false) {
            $result = $config;
        } else {
            $result = $config->get($name, $default);
        }

        return $result;
    }

    /**
     * The current controller being targetted.
     *
     * @param Gdn_Controller $value
     * @return Gdn_Controller
     */
    public static function controller($value = null) {
        static $controller = null;

        if ($value !== null) {
            $controller = $value;
        }

        return $controller;
    }

    /**
     * Gets the global dispatcher object.
     *
     * @return Gdn_Dispatcher
     */
    public static function dispatcher() {
        return self::factory(self::AliasDispatcher);
    }

    /**
     * Get a reference to the default database object.
     *
     * @return Gdn_Database
     */
    public static function database() {
        return self::factory(self::AliasDatabase);
    }

    /**
     * Get an object from the factory.
     *
     * @param string $alias The alias of the class.
     */
    public static function factory($alias = false) {
        if ($alias === false) {
            return static::getFactory();
        }

        // Get the arguments to pass to the factory.
        $args = func_get_args();
        array_shift($args);

        // Get the item from the container.
        // This code has been brought in from {@link Gdn_Factory::factory()}.
        $dic = static::getContainer();
        try {
            if (!$dic->has($alias) && $dic->has("Gdn_$alias")) {
                $alias = "Gdn_$alias";
            }

            $result = $dic->getArgs($alias, (array)$args);
            return $result;
        } catch (\Garden\Container\NotFoundException $ex) {
            return null;
        }
    }

    /**
     * Get or lazy-create the factory.
     *
     * @return Gdn_Factory
     */
    private static function getFactory() {
        deprecated('Gdn::getFactory()');
        if (is_null(self::$_Factory)) {
            static::setFactory(new Gdn_Factory(static::getContainer()));
            static::factoryOverwrite(false);
        }
        return self::$_Factory;
    }

    /**
     * Checks whether or not a factory alias exists.
     *
     * @param string $alias The alias of the factory to check for.
     * @return boolean Whether or not a factory definintion exists.
     * @see Gdn_Factory::exists()
     */
    public static function factoryExists($alias) {
        return static::getContainer()->hasRule($alias);
    }

    /**
     * Install a class to the factory.
     *
     * @param string $alias An alias for the class that will be used to retreive instances of it.
     * @param string $className The actual name of the class.
     * @param string $path The path to the class' file. You can prefix the path with ~ to start at the application root.
     * @param string $factoryType The way objects will be instantiated for the class. One of the Gdn::Factory* constants.
     * @param mixed $data Additional data for the installation.
     * @see Gdn_Factory::install()
     */
    public static function factoryInstall($alias, $className, $path = '', $factoryType = self::FactorySingleton, $data = null) {
        // Don't overwrite an existing definition.
        if (self::$_FactoryOverwrite === false && self::factoryExists($alias)) {
            return;
        }

        $dic = static::getContainer();

        $dic->rule($alias);

        if ($alias !== $className) {
            $dic->setClass($className);
        }

        // Set the other data of the object.
        switch (ucfirst($factoryType)) {
            case Gdn::FactoryInstance:
                $dic->setShared(false);
                break;
            case Gdn::FactoryPrototype:
                if (is_null($data)) {
                    throw new Exception('You must supply a prototype object when installing an object of type Prototype.');
                }
                $dic->setShared(false)
                    ->setFactory(function () use ($data) {
                        $r = clone $data;
                        return $r;
                    });
                break;
            case Gdn::FactorySingleton:
                $dic->setShared(true);
                if (is_array($data)) {
                    $dic->setConstructorArgs($data);
                } elseif ($data !== null) {
                    $dic->setInstance($alias, $data);
                }
                break;
            case Gdn::FactoryRealSingleton:
                $dic->setShared(true)
                    ->setFactory([$className, $data]);
                break;
            default:
                throw new \Exception("Unknown factory type '$factoryType'.", 500);
        }
    }

    /**
     * Installs a dependency to the factory.
     *
     * @param string $alias The alias of the class that will have the dependency.
     * @param string $propertyName The name of the property on the class that will have the dependency.
     * @param string $sourceAlias The alias of the class that will provide the value of the property when objects are instantiated.
     * @see Gdn_Factory::instalDependency()
     */
    public static function factoryInstallDependency($alias, $propertyName, $sourceAlias) {
        deprecated('Gdn::factoryInstallDependency()');
    }

    /**
     * Installs a dependency to the factory with the settings from a configuration.
     *
     * @deprecated
     */
    public static function factoryInstallDependencyFromConfig() {
        deprecated('Gdn::factoryInstallDependencyFromConfig()');
    }

    /**
     * Installs a class to the factory with the settings from a configuration.
     *
     * @deprecated
     */
    public static function factoryInstallFromConfig() {
        deprecated('Gdn::factoryInstallFromConfig()');
    }

    /**
     *
     *
     * @param null $value
     * @return int
     * @deprecated
     */
    public static function factoryOverwrite($value = null) {
        $result = (self::$_FactoryOverwrite & 1 > 0);

        if (!is_null($value)) {
            self::$_FactoryOverwrite = $value;
        }

        return $result;
    }

    /**
     * Uninstall an class from the factory.
     *
     * @deprecated
     */
    public static function factoryUninstall() {
        deprecated('Gdn::factoryUninstall()');
    }

    /**
     * Uninstall a dependency from the factory.
     *
     * @deprecated
     */
    public static function factoryUninstallDependency() {
        deprecated('Gdn::factoryUninstallDependency()');
    }

    /**
     * Gets/Sets the Garden InstallationID
     *
     * @staticvar string $installationID
     * @param string $setInstallationID
     * @return string Installation ID or NULL
     */
    public static function installationID($setInstallationID = null) {
        static $installationID = false;
        if (!is_null($setInstallationID)) {
            if ($setInstallationID !== false) {
                saveToConfig('Garden.InstallationID', $setInstallationID);
            } else {
                removeFromConfig('Garden.InstallationID');
            }
            $installationID = $setInstallationID;
        }

        if ($installationID === false) {
            $installationID = c('Garden.InstallationID', null);
        }

        return $installationID;
    }

    /**
     * Gets/Sets the Garden Installation Secret
     *
     * @staticvar string $installationSecret
     * @param string $setInstallationSecret
     * @return string Installation Secret or NULL
     */
    public static function installationSecret($setInstallationSecret = null) {
        static $installationSecret = false;
        if (!is_null($setInstallationSecret)) {
            if ($setInstallationSecret !== false) {
                saveToConfig('Garden.InstallationSecret', $setInstallationSecret);
            } else {
                removeFromConfig('Garden.InstallationSecret');
            }
            $installationSecret = $setInstallationSecret;
        }

        if ($installationSecret === false) {
            $installationSecret = c('Garden.InstallationSecret', null);
        }

        return $installationSecret;
    }

    /**
     *
     *
     * @return Gdn_Locale
     */
    public static function locale() {
        if (is_null(self::$_Locale)) {
            self::$_Locale = self::factory(self::AliasLocale);
        }

        return self::$_Locale;
    }

    /**
     * Get the permission model for the application.
     *
     * @return PermissionModel
     */
    public static function permissionModel() {
        return self::factory(self::AliasPermissionModel);
    }

    /**
     * Get the plugin manager for the application.
     *
     * @return Gdn_PluginManager
     */
    public static function pluginManager() {
        if (self::$_PluginManager === null) {
            self::$_PluginManager = static::getContainer()->get(self::AliasPluginManager);
        }

        return self::$_PluginManager; //self::factory(self::AliasPluginManager);
    }

    /**
     * @return Gdn_Regarding
     */
    public static function regarding() {
        return self::factory('Regarding');
    }

    /**
     * Get or set the current request object.
     *
     * @param Gdn_Request $newRequest The new request or null to just get the request.
     * @return Gdn_Request
     */
    public static function request($newRequest = null) {
        if (self::$_Request === null) {
            self::$_Request = static::getContainer()->get(self::AliasRequest);
        }

        $request = self::$_Request; //self::factory(self::AliasRequest);
        if (!is_null($newRequest)) {
            if (is_string($newRequest)) {
                $request->withURI($newRequest);
            } elseif (is_object($newRequest))
                $request->fromImport($newRequest);
        }

        return $request;
    }

    /**
     * Get the router object
     *
     * @return Gdn_Router
     */
    public static function router() {
        return self::factory(self::AliasRouter);
    }

    /**
     * Get the session object.
     *
     * @return Gdn_Session
     */
    public static function session() {
        if (is_null(self::$_Session)) {
            self::$_Session = self::factory(self::AliasSession);
        }
        return self::$_Session;
    }

    public static function set($key, $value = null) {
        return Gdn::userMetaModel()->setUserMeta(0, $key, $value);
    }

    public static function get($key, $default = null) {
        $response = Gdn::userMetaModel()->getUserMeta(0, $key, $default);
        if (sizeof($response) == 1) {
            return val($key, $response, $default);
        }
        return $default;
    }

    /**
     * Get a reference to the default SQL driver object.
     *
     * @return Gdn_SQLDriver
     * @see Gdn_Database::sql()
     */
    public static function sql() {
        $database = self::database();
        $result = $database->sql();
        return $result;
    }

    /**
     * Get a reference to the Statistics object.
     *
     * @return Gdn_Statistics
     */
    public static function statistics() {
        return self::factory('Statistics');
    }

    /**
     * Get a reference to the default database structure object.
     *
     * @return Gdn_DatabaseStructure
     */
    public static function structure() {
        $database = self::database();
        $result = $database->structure();
        return $result;
    }

    /**
     * Get the plugin manager for the application.
     *
     * @return Gdn_ThemeManager
     */
    public static function themeManager() {
        return self::factory(self::AliasThemeManager);
    }

    /**
     * Translates a code into the selected locale's definition.
     *
     * @param string $code The code related to the language-specific definition.
     * @param string $default The default value to be displayed if the translation code is not found.
     * @return string The translated string or $code if there is no value in $default.
     */
    public static function translate($code, $default = false) {
        $locale = Gdn::locale();
        if ($locale) {
            return $locale->translate($code, $default);
        } else {
            return $default;
        }
    }

    /**
     * Get a reference to the user model.
     *
     * @return UserModel
     */
    public static function userModel() {
        return self::factory(self::AliasUserModel);
    }

    /**
     * Get a reference to the user meta model.
     *
     * @return UserMetaModel
     */
    public static function userMetaModel() {
        static $userMetaModel = null;
        if (is_null($userMetaModel)) {
            $userMetaModel = new UserMetaModel();
        }
        return $userMetaModel;
    }

    /**
     * Set the object used as the factory for the api.
     *
     * @param Gdn_Factory $factory The object used as the factory.
     * @param boolean $override whether to override the property if it is already set.
     */
    public static function setFactory($factory, $override = true) {
        deprecated('Gdn::setFactory()');
        if ($override || is_null(self::$_Factory)) {
            self::$_Factory = $factory;
        }
    }

    /**
     * Get the global container.
     *
     * @return \Garden\Container\Container Returns the container.
     */
    public static function getContainer() {
        if (self::$container === null) {
            $dic = new Garden\Container\Container();

            $dic->setInstance('Garden\Container\Container', $dic)
                ->setInstance('Interop\Container\ContainerInterface', $dic);

            self::$container = $dic;
        }
        return self::$container;
    }

    /**
     * Set the container used in this object.
     *
     * There is intentionally only a setter for the container because use of the Gdn object should begin to be limited in
     * favor of the container.
     *
     * @param \Garden\Container\Container $container
     */
    public static function setContainer(\Garden\Container\Container $container = null) {
        self::$container = $container;

        /**
         * Reset all of the cached objects that are fetched from the container.
         */
        self::$_Config = null;
        self::$_Factory = null;
        self::$_FactoryOverwrite = true;
        self::$_Locale = null;
        self::$_Request = null;
        self::$_PluginManager = null;
        self::$_Session = null;
    }
}
