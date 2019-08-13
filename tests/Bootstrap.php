<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\Container\Reference;
use Garden\Web\RequestInterface;
use Gdn;
use Interop\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Authenticator\PasswordAuthenticator;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Formatting\FormatService;
use Vanilla\InjectableInterface;
use Vanilla\Models\AuthenticatorModel;
use Vanilla\Models\SSOModel;
use VanillaTests\Fixtures\Authenticator\MockAuthenticator;
use VanillaTests\Fixtures\Authenticator\MockSSOAuthenticator;
use VanillaTests\Fixtures\NullCache;
use Vanilla\Utility\ContainerUtils;

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
    public function __construct($baseUrl) {
        $this->baseUrl = str_replace('\\', '/', $baseUrl);
    }


    /**
     * Run the bootstrap and set the global environment.
     *
     * @param Container $container The container to bootstrap.
     */
    public function run(Container $container, $addons = false) {
        $this->initialize($container);
        if ($addons) {
            $this->initializeAddons($container);
        }
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
            ->setInstance('@baseUrl', $this->getBaseUrl())
            ->setInstance(Container::class, $container)

            ->rule(ContainerInterface::class)
            ->setAliasOf(Container::class)

            // Base classes that want to support DI without polluting their constructor implement this.
            ->rule(InjectableInterface::class)
            ->addCall('setDependencies')

            ->rule(\DateTimeInterface::class)
            ->setAliasOf(\DateTimeImmutable::class)
            ->setConstructorArgs([null, null])

            ->rule(\Vanilla\Web\Asset\DeploymentCacheBuster::class)
            ->setShared(true)
            ->setConstructorArgs([
                'deploymentTime' => ContainerUtils::config('Garden.Deployed')
            ])

            // Cache
            ->setInstance(NullCache::class, new NullCache())

            ->rule(\Gdn_Cache::class)
            ->setAliasOf(NullCache::class)
            ->addAlias('Cache')

            // Configuration
            ->rule(ConfigurationInterface::class)
            ->setClass(\Gdn_Configuration::class)
            ->setShared(true)
            ->addCall('defaultPath', [$this->getConfigPath()])
            ->addCall('autoSave', [false])
            ->addCall('load', [PATH_ROOT.'/conf/config-defaults.php'])
            ->addAlias('Config')
            ->addAlias(\Gdn_Configuration::class)

            // AddonManager
            ->rule(AddonManager::class)
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
            ->addAlias(RequestInterface::class)

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

            // Locale
            ->rule(\Gdn_Locale::class)
            ->setShared(true)
            ->setConstructorArgs([new Reference(['Gdn_Configuration', 'Garden.Locale'])])
            ->addAlias(Gdn::AliasLocale)

            ->rule('Identity')
            ->setClass('Gdn_CookieIdentity')
            ->setShared(true)

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

            ->rule(\Gdn_Validation::class)
            ->addCall('addRule', ['BodyFormat', new Reference(\Vanilla\BodyFormatValidator::class)])

            ->rule(AuthenticatorModel::class)
            ->setShared(true)
            ->addCall('registerAuthenticatorClass', [PasswordAuthenticator::class])
            ->addCall('registerAuthenticatorClass', [MockAuthenticator::class])
            ->addCall('registerAuthenticatorClass', [MockSSOAuthenticator::class])

            ->rule(SearchModel::class)
            ->setShared(true)

            ->rule(SSOModel::class)
            ->setShared(true)

            ->rule(\Garden\Web\Dispatcher::class)
            ->setShared(true)
            ->addCall('addRoute', ['route' => new \Garden\Container\Reference('@api-v2-route'), 'api-v2'])

            ->rule(\Vanilla\Web\HttpStrictTransportSecurityModel::class)
            ->addAlias('HstsModel')

            ->rule('@api-v2-route')
            ->setClass(\Garden\Web\ResourceRoute::class)
            ->setConstructorArgs(['/api/v2/', '*\\%sApiController'])
            ->addCall('setConstraint', ['locale', ['position' => 0]])
            ->addCall('setMeta', ['CONTENT_TYPE', 'application/json; charset=utf-8'])

            ->rule('@view-application/json')
            ->setClass(\Vanilla\Web\JsonView::class)
            ->setShared(true)

            ->rule(\Garden\ClassLocator::class)
            ->setClass(\Vanilla\VanillaClassLocator::class)

            ->rule(\Gdn_Plugin::class)
            ->setShared(true)
            ->addCall('setAddonFromManager')

            ->rule(\Vanilla\FileUtils::class)
            ->setAliasOf(\VanillaTests\Fixtures\FileUtils::class)
            ->addAlias('FileUtils')

            ->rule('WebLinking')
            ->setClass(\Vanilla\Web\WebLinking::class)
            ->setShared(true)

            ->rule(\Vanilla\EmbeddedContent\EmbedService::class)
            ->setShared(true)

            ->rule(\Vanilla\PageScraper::class)
            ->addCall('registerMetadataParser', [new Reference(\Vanilla\Metadata\Parser\OpenGraphParser::class)])
            ->addCall('registerMetadataParser', [new Reference(\Vanilla\Metadata\Parser\JsonLDParser::class)])
            ->setShared(true)

            ->rule(\Vanilla\Formatting\Quill\Parser::class)
            ->addCall('addCoreBlotsAndFormats')
            ->setShared(true)

            ->rule(\Vanilla\Formatting\Quill\Renderer::class)
            ->setShared(true)

            ->rule('BBCodeFormatter')
            ->setClass(\BBCode::class)
            ->setShared(true)

            ->rule('HtmlFormatter')
            ->setClass(\VanillaHtmlFormatter::class)
            ->setShared(true)

            ->rule(FormatService::class)
            ->addCall('registerBuiltInFormats')
            ->setShared(true)

            ->rule('HtmlFormatter')
            ->setClass(\VanillaHtmlFormatter::class)
            ->setShared(true)
            ;
    }

    private function initializeAddons(Container $dic) {
        // Run through the bootstrap with dependencies.
        $dic->call(function (
            Container $dic,
            \Gdn_Configuration $config,
            AddonManager $addonManager,
            \Garden\EventManager $eventManager
        ) {

            // Load installation-specific configuration so that we know what apps are enabled.
            $config->load($config->defaultPath(), 'Configuration', true);


            /**
             * Extension Managers
             *
             * Now load the Addon, Application, Theme and Plugin managers into the Factory, and
             * process the application-specific configuration defaults.
             */

            // Start the addons, plugins, and applications.
            $addonManager->startAddonsByKey($config->get('EnabledPlugins'), Addon::TYPE_ADDON);
            $addonManager->startAddonsByKey($config->get('EnabledApplications'), Addon::TYPE_ADDON);
            $addonManager->startAddonsByKey(array_keys($config->get('EnabledLocales', [])), Addon::TYPE_LOCALE);

//            $currentTheme = c('Garden.Theme', Gdn_ThemeManager::DEFAULT_DESKTOP_THEME);
//            if (isMobile()) {
//                $currentTheme = c('Garden.MobileTheme', Gdn_ThemeManager::DEFAULT_MOBILE_THEME);
//            }
//            $addonManager->startAddonsByKey([$currentTheme], Addon::TYPE_THEME);

            // Load the configurations for enabled addons.
            foreach ($addonManager->getEnabled() as $addon) {
                /* @var Addon $addon */
                if ($configPath = $addon->getSpecial('config')) {
                    $config->load($addon->path($configPath));
                }
            }

            // Re-apply loaded user settings.
            $config->overlayDynamic();

            /**
             * Extension Startup
             *
             * Allow installed addons to execute startup and bootstrap procedures that they may have, here.
             */

            // Bootstrapping.
            foreach ($addonManager->getEnabled() as $addon) {
                /* @var Addon $addon */
                if ($bootstrapPath = $addon->getSpecial('bootstrap')) {
                    $bootstrapPath = $addon->path($bootstrapPath);
                    include_once $bootstrapPath;
                }
            }

            // Plugins startup
            $addonManager->bindAllEvents($eventManager);

            if ($eventManager->hasHandler('gdn_pluginManager_afterStart')) {
                $eventManager->fire('gdn_pluginManager_afterStart', $dic->get(\Gdn_PluginManager::class));
            }

            // Now that all of the events have been bound, fire an event that allows plugins to modify the container.
            $eventManager->fire('container_init', $dic);

            // Start Authenticators
            $dic->get('Authenticator')->startAuthenticator();
        });
    }

    /**
     * Set the global variables that have dependencies.
     *
     * @param Container $container The container with dependencies.
     */
    public function setGlobals(Container $container) {
        // Set some server globals.
        $baseUrl = $this->getBaseUrl();
        $_SERVER['REMOTE_ADDR'] = '::1'; // Simulate requests from local IPv6 address.
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
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public static function cleanup(Container $container) {
        self::cleanUpContainer($container);
        self::cleanUpGlobals();
    }

    /**
     * Clean up container.
     *
     * @param \Garden\Container\Container $container
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public static function cleanUpContainer(Container $container) {
       if ($container->hasInstance(AddonManager::class)) {
            /* @var AddonManager $addonManager */

            $addonManager = $container->get(AddonManager::class);
            $addonManager->unregisterAutoloader();
        }

        $container->clearInstances();
    }

    /**
     * Clean up global variables.
     */
    public static function cleanUpGlobals() {
        if (class_exists(\CategoryModel::class)) {
            \CategoryModel::$Categories = null;
        }

        unset($GLOBALS['dic']);
        Gdn::setContainer(new NullContainer());
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
