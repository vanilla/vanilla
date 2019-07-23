<?php

use Garden\Container\Container;
use Garden\Container\Reference;
use Vanilla\Addon;
use Vanilla\Formatting\Embeds\EmbedManager;
use Vanilla\InjectableInterface;
use Vanilla\Contracts;
use Vanilla\Utility\ContainerUtils;
use \Vanilla\Formatting\Formats;
use Firebase\JWT\JWT;

if (!defined('APPLICATION')) exit();
/**
 * Bootstrap.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

// Guard against broken cache files.
if (!class_exists('Gdn')) {
    // Throwing an exception here would result in a white screen for the user.
    // This error usually indicates the .ini files in /cache are out of date and should be deleted.
    exit("Class Gdn not found.");
}

// Set up the dependency injection container.
$dic = new Container();
Gdn::setContainer($dic);

$dic->setInstance('Garden\Container\Container', $dic)
    ->rule('Interop\Container\ContainerInterface')
    ->setAliasOf('Garden\Container\Container')

    ->rule(InjectableInterface::class)
    ->addCall('setDependencies')

    ->rule(DateTimeInterface::class)
    ->setAliasOf(DateTimeImmutable::class)
    ->setConstructorArgs([null, null])

    // Cache
    ->rule('Gdn_Cache')
    ->setShared(true)
    ->setFactory(['Gdn_Cache', 'initialize'])
    ->addAlias('Cache')

    // Configuration
    ->rule('Gdn_Configuration')
    ->setShared(true)
    ->addAlias('Config')
    ->addAlias(Contracts\ConfigurationInterface::class)

    // AddonManager
    ->rule(Vanilla\AddonManager::class)
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
    ->addAlias(Contracts\AddonProviderInterface::class)
    ->addCall('registerAutoloader')

    // ApplicationManager
    ->rule('Gdn_ApplicationManager')
    ->setShared(true)
    ->addAlias('ApplicationManager')

    ->rule(Garden\Web\Cookie::class)
    ->setShared(true)
    ->addAlias('Cookie')

    // PluginManager
    ->rule('Gdn_PluginManager')
    ->setShared(true)
    ->addAlias('PluginManager')

    ->rule(SsoUtils::class)
    ->setShared(true)

    // ThemeManager
    ->rule('Gdn_ThemeManager')
    ->setShared(true)
    ->addAlias('ThemeManager')

    // File base theme api provider
    ->rule(\Vanilla\Models\ThemeModel::class)
        ->addCall("addThemeProvider", [new Reference(\Vanilla\Models\FsThemeProvider::class)])


    // Logger
    ->rule(\Vanilla\Logger::class)
    ->setShared(true)
    ->addAlias(\Psr\Log\LoggerInterface::class)

    ->rule(\Psr\Log\LoggerAwareInterface::class)
    ->addCall('setLogger')

    // EventManager
    ->rule(\Garden\EventManager::class)
    ->setShared(true)

    // Locale
    ->rule('Gdn_Locale')
    ->setShared(true)
    ->setConstructorArgs([new Reference(['Gdn_Configuration', 'Garden.Locale'])])
    ->addAlias('Locale')

    // Request
    ->rule('Gdn_Request')
    ->setShared(true)
    ->addCall('fromEnvironment')
    ->addAlias('Request')
    ->addAlias(\Garden\Web\RequestInterface::class)

    // Database.
    ->rule('Gdn_Database')
    ->setShared(true)
    ->setConstructorArgs([new Reference(['Gdn_Configuration', 'Database'])])
    ->addAlias('Database')

    ->rule('Gdn_DatabaseStructure')
    ->setClass('Gdn_MySQLStructure')
    ->setShared(true)
    ->addAlias(Gdn::AliasDatabaseStructure)
    ->addAlias('MySQLStructure')

    ->rule('Gdn_SQLDriver')
    ->setClass('Gdn_MySQLDriver')
    ->setShared(true)
    ->addAlias('Gdn_MySQLDriver')
    ->addAlias('MySQLDriver')
    ->addAlias(Gdn::AliasSqlDriver)

    ->rule('Identity')
    ->setClass('Gdn_CookieIdentity')
    ->setShared(true)

    ->rule('Gdn_Session')
    ->setShared(true)
    ->addAlias('Session')

    ->rule(Gdn::AliasAuthenticator)
    ->setClass('Gdn_Auth')
    ->setShared(true)

    ->rule('Gdn_Router')
    ->addAlias(Gdn::AliasRouter)
    ->setShared(true)

    ->rule('Gdn_Dispatcher')
    ->setShared(true)
    ->addAlias(Gdn::AliasDispatcher)

    ->rule(\Vanilla\Web\Asset\DeploymentCacheBuster::class)
    ->setShared(true)
    ->setConstructorArgs([
        'deploymentTime' => ContainerUtils::config('Garden.Deployed')
    ])

    ->rule(\Vanilla\Web\Asset\AssetPreloadModel::class)
    ->setShared(true)

    ->rule(\Vanilla\Web\Asset\WebpackAssetProvider::class)
    ->addCall('setHotReloadEnabled', [
        ContainerUtils::config('HotReload.Enabled'),
        ContainerUtils::config('HotReload.IP'),
    ])
    ->addCall('setLocaleKey', [ContainerUtils::currentLocale()])
    ->addCall('setCacheBusterKey', [ContainerUtils::cacheBuster()])

    ->rule(\Vanilla\Web\HttpStrictTransportSecurityModel::class)
    ->addAlias('HstsModel')

    ->rule(\Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel::class)
    ->setShared(true)
    ->addCall('addProvider', [new Reference(\Vanilla\Web\ContentSecurityPolicy\DefaultContentSecurityPolicyProvider::class)])
    ->addCall('addProvider', [new Reference(\Vanilla\Web\ContentSecurityPolicy\EmbedWhitelistContentSecurityPolicyProvider::class)])
    ->addCall('addProvider', [new Reference(\Vanilla\Web\Asset\WebpackContentSecurityPolicyProvider::class)])

    ->rule(\Vanilla\Web\Asset\LegacyAssetModel::class)
    ->setConstructorArgs([ContainerUtils::cacheBuster()])

    ->rule(\Garden\Web\Dispatcher::class)
    ->setShared(true)
    ->addCall('addRoute', ['route' => new Reference('@api-v2-route'), 'api-v2'])
    ->addCall('addRoute', ['route' => new \Garden\Container\Callback(function () {
        return new \Garden\Web\PreflightRoute('/api/v2', true);
    })])
    ->addCall('setAllowedOrigins', ['isTrustedDomain'])
    ->addCall('addMiddleware', [new Reference('@smart-id-middleware')])
    ->addCall('addMiddleware', [new Reference(\Vanilla\Web\CacheControlMiddleware::class)])
    ->addCall('addMiddleware', [new Reference(\Vanilla\Web\DeploymentHeaderMiddleware::class)])
    ->addCall('addMiddleware', [new Reference(\Vanilla\Web\ContentSecurityPolicyMiddleware::class)])
    ->addCall('addMiddleware', [new Reference(\Vanilla\Web\HttpStrictTransportSecurityMiddleware::class)])

    ->rule('@smart-id-middleware')
    ->setClass(\Vanilla\Web\SmartIDMiddleware::class)
    ->setConstructorArgs(['/api/v2/'])
    ->addCall('addSmartID', ['CategoryID', 'categories', ['name', 'urlcode'], 'Category'])
    ->addCall('addSmartID', ['RoleID', 'roles', ['name'], 'Role'])
    ->addCall('addSmartID', ['UserID', 'users', '*', new Reference('@user-smart-id-resolver')])

    ->rule('@user-smart-id-resolver')
    ->setFactory(function (Container $dic) {
        /* @var \Vanilla\Web\UserSmartIDResolver $uid */
        $uid = $dic->get(\Vanilla\Web\UserSmartIDResolver::class);
        $uid->setEmailEnabled(!$dic->get(Gdn_Configuration::class)->get('Garden.Registration.NoEmail'))
            ->setViewEmail($dic->get(\Gdn_Session::class)->checkPermission('Garden.PersonalInfo.View'));

        return $uid;
    })

    ->rule('@api-v2-route')
    ->setClass(\Garden\Web\ResourceRoute::class)
    ->setConstructorArgs(['/api/v2/', '*\\%sApiController'])
    ->addCall('setMeta', ['CONTENT_TYPE', 'application/json; charset=utf-8'])

    ->rule('@view-application/json')
    ->setClass(\Vanilla\Web\JsonView::class)
    ->setShared(true)

    ->rule(\Garden\ClassLocator::class)
    ->setClass(\Vanilla\VanillaClassLocator::class)

    ->rule('Gdn_Model')
    ->setShared(true)

    ->rule(Gdn_Validation::class)
    ->addCall('addRule', ['BodyFormat', new Reference(\Vanilla\BodyFormatValidator::class)])

    ->rule(\Vanilla\Models\AuthenticatorModel::class)
    ->setShared(true)
    ->addCall('registerAuthenticatorClass', [\Vanilla\Authenticator\PasswordAuthenticator::class])

    ->rule(SearchModel::class)
    ->setShared(true)

    ->rule('Gdn_IPlugin')
    ->setShared(true)

    ->rule(Gdn_Plugin::class)
    ->setShared(true)
    ->addCall('setAddonFromManager')

    ->rule('Gdn_Slice')
    ->setShared(true)
    ->addAlias('Slice')

    ->rule('Gdn_Statistics')
    ->addAlias('Statistics')
    ->setShared(true)

    ->rule('Gdn_Regarding')
    ->setShared(true)

    ->rule('BBCodeFormatter')
    ->setClass('BBCode')
    ->setShared(true)

    ->rule('HtmlFormatter')
    ->setClass(VanillaHtmlFormatter::class)
    ->setShared(true)

    ->rule(\Vanilla\Formatting\Quill\Renderer::class)
    ->setShared(true)

    ->rule(\Vanilla\Formatting\Quill\Parser::class)
    ->addCall('addCoreBlotsAndFormats')
    ->setShared(true)

    ->rule('Smarty')
    ->setShared(true)

    ->rule('WebLinking')
    ->setClass(\Vanilla\Web\WebLinking::class)
    ->setShared(true)

    ->rule('ViewHandler.tpl')
    ->setClass('Gdn_Smarty')
    ->setShared(true)

    ->rule('ViewHandler.twig')
    ->setClass(\Vanilla\Web\LegacyTwigViewHandler::class)
    ->setShared(true)

    ->rule('Gdn_Form')
    ->addAlias('Form')

    ->rule(Vanilla\Formatting\Embeds\EmbedManager::class)
    ->addCall('addCoreEmbeds')
    ->setShared(true)

    ->rule(\Vanilla\EmbeddedContent\EmbedService::class)
    ->addCall('addCoreEmbeds')
    ->setShared(true)

    ->rule(Vanilla\Models\SiteMeta::class)
    ->setConstructorArgs(['activeTheme' => ContainerUtils::currentTheme()])

    ->rule(Vanilla\PageScraper::class)
    ->addCall('registerMetadataParser', [new Reference(Vanilla\Metadata\Parser\OpenGraphParser::class)])
    ->addCall('registerMetadataParser', [new Reference(Vanilla\Metadata\Parser\JsonLDParser::class)])
    ->setShared(true)

    ->rule(Vanilla\Formatting\FormatService::class)
    ->addCall('registerFormat', [Formats\RichFormat::FORMAT_KEY, Formats\RichFormat::class])
    ->setShared(true)

    ->rule(\Vanilla\Analytics\Client::class)
    ->setShared(true)
    ->addAlias(\Vanilla\Contracts\Analytics\ClientInterface::class)
;

// Run through the bootstrap with dependencies.
$dic->call(function (
    Container $dic,
    Gdn_Configuration $config,
    \Vanilla\AddonManager $addonManager,
    \Garden\EventManager $eventManager,
    Gdn_Request $request // remove later
) {

    // Load default baseline Garden configurations.
    $config->load(PATH_CONF.'/config-defaults.php');

    // Load installation-specific configuration so that we know what apps are enabled.
    $config->load($config->defaultPath(), 'Configuration', true);

    /**
     * Bootstrap Early
     *
     * A lot of the framework is loaded now, most importantly the core autoloader,
     * default config and the general and error functions. More control is possible
     * here, but some things have already been loaded and are immutable.
     */
    if (file_exists(PATH_CONF.'/bootstrap.early.php')) {
        require_once PATH_CONF.'/bootstrap.early.php';
    }

    $config->caching(true);
    debug($config->get('Debug', false));

    setHandlers();

    /**
     * Installer Redirect
     *
     * If Garden is not yet installed, force the request to /dashboard/setup and
     * begin installation.
     */
    if ($config->get('Garden.Installed', false) === false && strpos($request->path(), 'setup') === false) {
        safeHeader('Location: '.$request->url('dashboard/setup', true));
        exit();
    }

    /**
     * Extension Managers
     *
     * Now load the Addon, Application, Theme and Plugin managers into the Factory, and
     * process the application-specific configuration defaults.
     */

    // Start the addons, plugins, and applications.
    $addonManager->startAddonsByKey(c('EnabledPlugins'), Addon::TYPE_ADDON);
    $addonManager->startAddonsByKey(c('EnabledApplications'), Addon::TYPE_ADDON);
    $addonManager->startAddonsByKey(array_keys(c('EnabledLocales', [])), Addon::TYPE_LOCALE);

    $currentTheme = c('Garden.Theme', Gdn_ThemeManager::DEFAULT_DESKTOP_THEME);
    if (isMobile()) {
        $currentTheme = c('Garden.MobileTheme', Gdn_ThemeManager::DEFAULT_MOBILE_THEME);
    }
    $addonManager->startAddonsByKey([$currentTheme], Addon::TYPE_THEME);

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
     * Bootstrap Late
     *
     * All configurations are loaded, as well as the Application, Plugin and Theme
     * managers.
     */
    if (file_exists(PATH_CONF.'/bootstrap.late.php')) {
        require_once PATH_CONF.'/bootstrap.late.php';
    }

    if ($config->get('Debug')) {
        debug(true);
    }

    Gdn_Cache::trace(debug()); // remove later

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
        $eventManager->fire('gdn_pluginManager_afterStart', $dic->get(Gdn_PluginManager::class));
    }

    // Now that all of the events have been bound, fire an event that allows plugins to modify the container.
    $eventManager->fire('container_init', $dic);
});

// Send out cookie headers.
register_shutdown_function(function () use ($dic) {
    $dic->call(function(Garden\Web\Cookie $cookie) {
        $cookie->flush();
    });
});

/**
 * Locales
 *
 * Install any custom locales provided by applications and plugins, and set up
 * the locale management system.
 */

// Load the Garden locale system.
$dic->get('Gdn_Locale');

require_once PATH_LIBRARY_CORE.'/functions.validation.php';

// Configure JWT library to allow for five seconds of leeway.
JWT::$leeway = 5;

// Start Authenticators
$dic->get('Authenticator')->startAuthenticator();

/**
 * Bootstrap After
 *
 * After the bootstrap has finished loading, this hook allows developers a last
 * chance to customize Garden's runtime environment before the actual request
 * is handled.
 */
if (file_exists(PATH_ROOT.'/conf/bootstrap.after.php')) {
    require_once PATH_ROOT.'/conf/bootstrap.after.php';
}

// Include "Render" functions now - this way pluggables and custom confs can override them.
require_once PATH_LIBRARY_CORE.'/functions.render.php';

if (!defined('CLIENT_NAME')) {
    define('CLIENT_NAME', 'vanilla');
}
