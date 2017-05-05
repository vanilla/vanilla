<?php
/**
 * The heart of the beast.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
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
     * @param string $Name The name of the configuration setting. Settings in different sections are seperated by a dot ('.')
     * @param mixed $Default The result to return if the configuration setting is not found.
     * @return Gdn_Configuration|mixed The configuration setting.
     */
    public static function config($Name = false, $Default = false) {
        if (self::$_Config === null) {
            self::$_Config = static::getContainer()->get(self::AliasConfig);
        }
        $Config = self::$_Config;
        if ($Name === false) {
            $Result = $Config;
        } else {
            $Result = $Config->get($Name, $Default);
        }

        return $Result;
    }

    /**
     * The current controller being targetted.
     *
     * @param Gdn_Controller $Value
     * @return Gdn_Controller
     */
    public static function controller($Value = null) {
        static $Controller = null;

        if ($Value !== null) {
            $Controller = $Value;
        }

        return $Controller;
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
     * @param string $Alias The alias of the factory to check for.
     * @return boolean Whether or not a factory definintion exists.
     * @see Gdn_Factory::Exists()
     */
    public static function factoryExists($Alias) {
        return static::getContainer()->hasRule($Alias);
    }

    /**
     * Install a class to the factory.
     *
     * @param string $Alias An alias for the class that will be used to retreive instances of it.
     * @param string $ClassName The actual name of the class.
     * @param string $Path The path to the class' file. You can prefix the path with ~ to start at the application root.
     * @param string $FactoryType The way objects will be instantiated for the class. One of the Gdn::Factory* constants.
     * @param mixed $Data Additional data for the installation.
     * @see Gdn_Factory::Install()
     */
    public static function factoryInstall($Alias, $ClassName, $Path = '', $FactoryType = self::FactorySingleton, $Data = null) {
        // Don't overwrite an existing definition.
        if (self::$_FactoryOverwrite === false && self::factoryExists($Alias)) {
            return;
        }

        $dic = static::getContainer();

        $dic->rule($Alias);

        if ($Alias !== $ClassName) {
            $dic->setClass($ClassName);
        }

        // Set the other data of the object.
        switch (ucfirst($FactoryType)) {
            case Gdn::FactoryInstance:
                $dic->setShared(false);
                break;
            case Gdn::FactoryPrototype:
                if (is_null($Data)) {
                    throw new Exception('You must supply a prototype object when installing an object of type Prototype.');
                }
                $dic->setShared(false)
                    ->setFactory(function () use ($Data) {
                        $r = clone $Data;
                        return $r;
                    });
                break;
            case Gdn::FactorySingleton:
                $dic->setShared(true);
                if (is_array($Data)) {
                    $dic->setConstructorArgs($Data);
                } elseif ($Data !== null) {
                    $dic->setInstance($Alias, $Data);
                }
                break;
            case Gdn::FactoryRealSingleton:
                $dic->setShared(true)
                    ->setFactory([$ClassName, $Data]);
                break;
            default:
                throw new \Exception("Unknown factory type '$FactoryType'.", 500);
        }
    }

    /**
     * Installs a dependency to the factory.
     *
     * @param string $Alias The alias of the class that will have the dependency.
     * @param string $PropertyName The name of the property on the class that will have the dependency.
     * @param string $SourceAlias The alias of the class that will provide the value of the property when objects are instantiated.
     * @see Gdn_Factory::InstalDependency()
     */
    public static function factoryInstallDependency($Alias, $PropertyName, $SourceAlias) {
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
     * @param null $Value
     * @return int
     * @deprecated
     */
    public static function factoryOverwrite($Value = null) {
        $Result = (self::$_FactoryOverwrite & 1 > 0);

        if (!is_null($Value)) {
            self::$_FactoryOverwrite = $Value;
        }

        return $Result;
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
     * @staticvar string $InstallationID
     * @param string $SetInstallationID
     * @return string Installation ID or NULL
     */
    public static function installationID($SetInstallationID = null) {
        static $InstallationID = false;
        if (!is_null($SetInstallationID)) {
            if ($SetInstallationID !== false) {
                SaveToConfig('Garden.InstallationID', $SetInstallationID);
            } else {
                RemoveFromConfig('Garden.InstallationID');
            }
            $InstallationID = $SetInstallationID;
        }

        if ($InstallationID === false) {
            $InstallationID = c('Garden.InstallationID', null);
        }

        return $InstallationID;
    }

    /**
     * Gets/Sets the Garden Installation Secret
     *
     * @staticvar string $InstallationSecret
     * @param string $SetInstallationSecret
     * @return string Installation Secret or NULL
     */
    public static function installationSecret($SetInstallationSecret = null) {
        static $InstallationSecret = false;
        if (!is_null($SetInstallationSecret)) {
            if ($SetInstallationSecret !== false) {
                SaveToConfig('Garden.InstallationSecret', $SetInstallationSecret);
            } else {
                RemoveFromConfig('Garden.InstallationSecret');
            }
            $InstallationSecret = $SetInstallationSecret;
        }

        if ($InstallationSecret === false) {
            $InstallationSecret = c('Garden.InstallationSecret', null);
        }

        return $InstallationSecret;
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

        return self::$_PluginManager; //self::Factory(self::AliasPluginManager);
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
     * @param Gdn_Request $NewRequest The new request or null to just get the request.
     * @return Gdn_Request
     */
    public static function request($NewRequest = null) {
        if (self::$_Request === null) {
            self::$_Request = static::getContainer()->get(self::AliasRequest);
        }

        $Request = self::$_Request; //self::Factory(self::AliasRequest);
        if (!is_null($NewRequest)) {
            if (is_string($NewRequest)) {
                $Request->withURI($NewRequest);
            } elseif (is_object($NewRequest))
                $Request->fromImport($NewRequest);
        }

        return $Request;
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

    public static function set($Key, $Value = null) {
        return Gdn::userMetaModel()->setUserMeta(0, $Key, $Value);
    }

    public static function get($Key, $Default = null) {
        $Response = Gdn::userMetaModel()->getUserMeta(0, $Key, $Default);
        if (sizeof($Response) == 1) {
            return val($Key, $Response, $Default);
        }
        return $Default;
    }

    /**
     * Get a reference to the default SQL driver object.
     *
     * @return Gdn_SQLDriver
     * @see Gdn_Database::SQL()
     */
    public static function sql() {
        $Database = self::database();
        $Result = $Database->sql();
        return $Result;
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
        $Database = self::database();
        $Result = $Database->structure();
        return $Result;
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
     * @param string $Code The code related to the language-specific definition.
     * @param string $Default The default value to be displayed if the translation code is not found.
     * @return string The translated string or $Code if there is no value in $Default.
     */
    public static function translate($Code, $Default = false) {
        $Locale = Gdn::locale();
        if ($Locale) {
            return $Locale->translate($Code, $Default);
        } else {
            return $Default;
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
        static $UserMetaModel = null;
        if (is_null($UserMetaModel)) {
            $UserMetaModel = new UserMetaModel();
        }
        return $UserMetaModel;
    }

    /**
     * Set the object used as the factory for the api.
     *
     * @param Gdn_Factory $Factory The object used as the factory.
     * @param boolean $Override whether to override the property if it is already set.
     */
    public static function setFactory($Factory, $Override = true) {
        deprecated('Gdn::setFactory()');
        if ($Override || is_null(self::$_Factory)) {
            self::$_Factory = $Factory;
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
