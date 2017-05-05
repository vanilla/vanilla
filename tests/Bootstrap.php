<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\Container\Reference;
use Gdn;
use Interop\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Vanilla\Addon;
use Vanilla\InjectableInterface;

/**
 * Run bootstrap code for Vanilla tests.
 *
 * This class is meant to be re-used. Calling {@link Bootstrap::run()} on a polluted environment should reset it.
 */
class Bootstrap {
    private $baseUrl;

    /**
     * Bootstrap constructor.
     *
     * A different base URL affects
     *
     * @param string $baseUrl The base URL of the installation.
     */
    public function __construct($baseUrl = 'http://vanilla.test') {
        $this->baseUrl = str_replace('\\', '/', $baseUrl);
    }


    /**
     * Run the bootstrap and set the global environment.
     *
     * @param Container $container The container to bootstrap.
     */
    public function run(Container $container) {
        $this->initialize($container);
        $this->setGlobals($container);
    }

    /**
     * Initialize the container with Vanilla's environment.
     *
     * @param Container $container The container to initialize.
     */
    public function initialize(Container $container) {
        // Set up the dependency injection container.
        Gdn::setContainer($container);

        $container
            ->setInstance(Container::class, $container)

            ->rule(ContainerInterface::class)
            ->setAliasOf(Container::class)

            // Base classes that want to support DI without polluting their constructor implement this.
            ->rule(InjectableInterface::class)
            ->addCall('setDependencies')

            // Cache
            ->rule(\Gdn_Cache::class)
            ->setShared(true)
            ->setFactory(['Gdn_Cache', 'initialize'])
            ->addAlias('Cache')

            // Configuration
            ->rule(\Gdn_Configuration::class)
            ->setShared(true)
            ->addCall('defaultPath', [$this->getConfigPath()])
            ->addCall('autoSave', [false])
            ->addCall('load', [PATH_ROOT.'/conf/config-defaults.php'])
            ->addAlias('Config')

            // AddonManager
            ->rule(\Vanilla\AddonManager::class)
            ->setShared(true)
            ->setConstructorArgs([
                [
                    Addon::TYPE_ADDON => ['/applications', '/plugins'],
                    Addon::TYPE_THEME => '/themes',
                    Addon::TYPE_LOCALE => '/locales'
                ],
                PATH_CACHE
            ])
            ->addAlias('AddonManager')
            ->addCall('registerAutoloader')

            // ApplicationManager
            ->rule(\Gdn_ApplicationManager::class)
            ->setShared(true)
            ->addAlias('ApplicationManager')

            // PluginManager
            ->rule(\Gdn_PluginManager::class)
            ->setShared(true)
            ->addAlias('PluginManager')

            // ThemeManager
            ->rule(\Gdn_ThemeManager::class)
            ->setShared(true)
            ->addAlias('ThemeManager')

            // Logger
            ->rule(\Vanilla\Logger::class)
            ->setShared(true)
            ->addAlias(LoggerInterface::class)

            ->rule(LoggerAwareInterface::class)
            ->addCall('setLogger')

            // EventManager
            ->rule(\Garden\EventManager::class)
            ->setShared(true)

            ->rule(InjectableInterface::class)
            ->addCall('setDependencies')

            ->rule(\Gdn_Request::class)
            ->setShared(true)
            ->addAlias('Request')

            // Database.
            ->rule('Gdn_Database')
            ->setShared(true)
            ->setConstructorArgs([new Reference([\Gdn_Configuration::class, 'Database'])])
            ->addAlias('Database')

            ->rule(\Gdn_DatabaseStructure::class)
            ->setClass(\Gdn_MySQLStructure::class)
            ->setShared(true)
            ->addAlias(Gdn::AliasDatabaseStructure)
            ->addAlias('MySQLStructure')

            ->rule(\Gdn_SQLDriver::class)
            ->setClass(\Gdn_MySQLDriver::class)
            ->setShared(true)
            ->addAlias('Gdn_MySQLDriver')
            ->addAlias('MySQLDriver')
            ->addAlias(Gdn::AliasSqlDriver)

            ->rule(Gdn::AliasLocale)
            ->setClass(\VanillaTests\Fixtures\Locale::class)

            ->rule(\Gdn_Session::class)
            ->setShared(true)
            ->addAlias('Session')

            ->rule(Gdn::AliasAuthenticator)
            ->setClass(\Gdn_Auth::class)
            ->setShared(true)

            ->rule(\Gdn_Router::class)
            ->addAlias(Gdn::AliasRouter)
            ->setShared(true)

            ->rule(\Gdn_Dispatcher::class)
            ->setShared(true)
            ->addAlias(Gdn::AliasDispatcher)

            ->rule(\Garden\Web\Dispatcher::class)
            ->setShared(true)
            ->addCall('addRoute', ['route' => new \Garden\Container\Reference('@api-v2-route'), 'api-v2'])

            ->rule('@api-v2-route')
            ->setClass(\Garden\Web\ResourceRoute::class)
            ->setConstructorArgs(['/api/v2/', '%sApiController'])

            ->rule(\Garden\ClassLocator::class)
            ->setClass(\Vanilla\VanillaClassLocator::class)

            ->rule(\Gdn_Plugin::class)
            ->setShared(true)
            ->addCall('setAddonFromManager')
        ;
    }

    /**
     * Set the global variables that have dependencies.
     *
     * @param Container $container The container with dependencies.
     */
    public function setGlobals(Container $container) {
        // Set some server globals.
        $baseUrl = $this->getBaseUrl();
        $_SERVER['HTTP_HOST'] = parse_url($baseUrl, PHP_URL_HOST);
        $_SERVER['SERVER_PORT'] = parse_url($baseUrl, PHP_URL_PORT) ?: null;
        $_SERVER['SCRIPT_NAME'] = parse_url($baseUrl, PHP_URL_PATH);
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['HTTPS'] = parse_url($baseUrl, PHP_URL_SCHEME) === 'https';


        $GLOBALS['dic'] = $container;
        Gdn::setContainer($container);
    }

    /**
     * Clean up a container and remove its global references.
     *
     * @param Container $container The container to clean up.
     */
    public static function cleanup(Container $container) {
        $container->clearInstances();

        if ($GLOBALS['dic'] === $container) {
            unset($GLOBALS['dic']);
        }
        if (Gdn::getContainer() === $container) {
            Gdn::setContainer(null);
        }
    }

    /**
     * Get the baseUrl.
     *
     * @return mixed Returns the baseUrl.
     */
    public function getBaseUrl() {
        return $this->baseUrl;
    }

    /**
     * Get the bath of the site's configuration file.
     *
     * @return string Returns a path.
     */
    public function getConfigPath() {
        $host = parse_url($this->getBaseUrl(), PHP_URL_HOST);
        $path = parse_url($this->getBaseUrl(), PHP_URL_PATH);
        if ($path) {
            $path = '-'.ltrim(str_replace('/', '-', $path), '-');
        }

        return PATH_ROOT."/conf/{$host}{$path}.php";
    }
}
