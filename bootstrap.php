<?php

use Firebase\JWT\JWT;
use Garden\ClassLocator;
use Garden\Container\Container;
use Garden\Container\Reference;
use Garden\EventManager;
use Vanilla\Addon;
use Vanilla\BodyFormatValidator;
use Vanilla\Contracts;
use Vanilla\Contracts\Web\UASnifferInterface;
use Vanilla\Dashboard\UserLeaderService;
use Vanilla\Dashboard\UserPointsModel;
use Vanilla\EmbeddedContent\LegacyEmbedReplacer;
use Vanilla\Formatting\BaseFormat;
use Vanilla\Formatting\FormatConfig;
use Vanilla\Formatting\Html\HtmlEnhancer;
use Vanilla\Formatting\Html\HtmlPlainTextConverter;
use Vanilla\Formatting\Html\HtmlSanitizer;
use Vanilla\Formatting\Html\Processor\ExternalLinksProcessor;
use Vanilla\Formatting\Html\Processor\ImageHtmlProcessor;
use Vanilla\HttpCacheMiddleware;
use Vanilla\Layout\GlobalRecordProvider;
use Vanilla\Layout\CategoryRecordProvider;
use Vanilla\Layout\LayoutViewModel;
use Vanilla\Layout\RootRecordProvider;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\CurrentUserPreloadProvider;
use Vanilla\Models\LocalePreloadProvider;
use Vanilla\Models\Model;
use Vanilla\Models\ModelFactory;
use Vanilla\Models\SiteMeta;
use Vanilla\Models\TrustedDomainModel;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\PlainTextLengthValidator;
use Vanilla\Scheduler\DeferredScheduler;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Search\AbstractSearchDriver;
use Vanilla\Search\GlobalSearchType;
use Vanilla\Search\SearchService;
use Vanilla\Search\SearchTypeCollectorInterface;
use Vanilla\Site\OwnSiteProvider;
use Vanilla\Site\RootSiteSectionProvider;
use Vanilla\Site\SingleSiteSectionProvider;
use Vanilla\Theme\FsThemeProvider;
use Vanilla\Theme\ThemeAssetFactory;
use Vanilla\Theme\ThemeService;
use Vanilla\Theme\ThemeServiceHelper;
use Vanilla\Theme\VariableProviders\QuickLinksVariableProvider;
use Vanilla\Utility\ContainerUtils;
use Vanilla\Utility\Timers;
use Vanilla\Web\Page;
use Vanilla\Web\SafeCurlHttpHandler;
use Vanilla\Web\TwigEnhancer;
use Vanilla\Web\TwigRenderer;
use Vanilla\Web\UASniffer;
use Vanilla\Widgets\TabWidgetModule;
use Vanilla\Widgets\TabWidgetTabService;
use Vanilla\Widgets\WidgetService;

if (!defined("APPLICATION")) {
    exit();
}
/**
 * Bootstrap.
 *
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

// Guard against broken cache files.
if (!class_exists("Gdn")) {
    // Throwing an exception here would result in a white screen for the user.
    // This error usually indicates the .ini files in /cache are out of date and should be deleted.
    exit("Class Gdn not found.");
}

// Set up the dependency injection container.
$dic = new Container();
Gdn::setContainer($dic);

$timers = new Timers();

$timers->start("core-bootstrap");
\Vanilla\Bootstrap::configureContainer($dic);

$dic->setInstance(Container::class, $dic)

    // Configuration
    ->rule("Gdn_Configuration")
    ->setShared(true)
    ->addAlias("Config")
    ->addAlias(Contracts\ConfigurationInterface::class)

    ->rule(Contracts\Site\AbstractSiteProvider::class)
    ->setShared(true)
    ->setClass(OwnSiteProvider::class)

    ->setInstance(\Vanilla\Utility\Timers::class, $timers)

    // Root section
    ->rule(\Vanilla\Site\SiteSectionModel::class)
    ->addCall("addProvider", [new Reference(RootSiteSectionProvider::class)])
    ->setShared(true)

    // Site sections
    ->rule(\Vanilla\Site\SiteSectionModel::class)
    ->addCall("addProvider", [new Reference(SingleSiteSectionProvider::class)])
    ->setShared(true)

    // Translation model
    ->rule(\Vanilla\Site\TranslationModel::class)
    ->addCall("addProvider", [new Reference(\Vanilla\Site\TranslationProvider::class)])
    ->setShared(true)

    // Site applications
    ->rule(\Vanilla\Contracts\Site\ApplicationProviderInterface::class)
    ->setClass(\Vanilla\Site\ApplicationProvider::class)
    ->addCall("add", [
        new Reference(\Vanilla\Site\Application::class, [
            "garden",
            ["api", "entry", "sso", "utility", "robots.txt", "robots"],
        ]),
    ])
    ->setShared(true)

    // ApplicationManager
    ->rule("Gdn_ApplicationManager")
    ->setShared(true)
    ->addAlias("ApplicationManager")

    ->rule(Garden\Web\Cookie::class)
    ->setShared(true)
    ->addCall("setPrefix", [ContainerUtils::config("Garden.Cookie.Name", "Vanilla")])
    ->addCall("setSecure", [
        new \Garden\Container\Callback(function (\Psr\Container\ContainerInterface $container) {
            $config = $container->get(Gdn_Configuration::class);
            $request = $container->get(\Garden\Web\RequestInterface::class);
            $secure = $config->get("Garden.ForceSSL") && $request->getScheme() === "https";
            return $secure;
        }),
    ])
    ->addAlias("Cookie")

    // PluginManager
    ->rule("Gdn_PluginManager")
    ->setShared(true)
    ->addAlias("PluginManager")

    ->rule(SsoUtils::class)
    ->setShared(true)

    // ThemeManager
    ->rule("Gdn_ThemeManager")
    ->setShared(true)
    ->addAlias("ThemeManager")

    // File base theme api provider
    ->rule(\Vanilla\Theme\ThemeService::class)
    ->addCall("addVariableProvider", [new Reference(QuickLinksVariableProvider::class)])

    ->rule(HttpCacheMiddleware::class)
    ->setShared(true)

    // EventManager
    ->rule(\Garden\EventManager::class)
    ->addAlias(\Vanilla\Contracts\Addons\EventListenerConfigInterface::class)
    ->addAlias(\Psr\EventDispatcher\EventDispatcherInterface::class)
    ->addAlias(\Psr\EventDispatcher\ListenerProviderInterface::class)
    ->addCall("addListenerMethod", [\Vanilla\Logging\ResourceEventLogger::class, "logResourceEvent"])
    ->setShared(true)

    ->rule(\Vanilla\Logging\ResourceEventLogger::class)
    ->addCall("includeAction", [\Vanilla\Dashboard\Events\UserEvent::class, "*"])
    ->setShared(true)

    // Locale
    ->rule("Gdn_Locale")
    ->setShared(true)
    ->setConstructorArgs([new Reference(["Gdn_Configuration", "Garden.Locale"])])
    ->addAlias("Locale")

    ->rule(Contracts\LocaleInterface::class)
    ->setAliasOf(Gdn_Locale::class)
    ->setShared(true)

    // Request
    ->rule("Gdn_Request")
    ->setShared(true)
    ->addCall("fromEnvironment")
    ->addAlias("Request")
    ->addAlias(\Garden\Web\RequestInterface::class)

    ->rule(UASnifferInterface::class)
    ->setClass(UASniffer::class)

    // Database.
    ->rule("Gdn_Database")
    ->setShared(true)
    ->setConstructorArgs([new Reference(["Gdn_Configuration", "Database"])])
    ->addAlias("Database")

    ->rule("Gdn_DatabaseStructure")
    ->setClass("Gdn_MySQLStructure")
    ->addCall("setFullTextIndexingEnabled", [new Reference(["Gdn_Configuration", "Database.FullTextIndexing"])])
    ->setShared(true)
    ->addAlias(Gdn::AliasDatabaseStructure)
    ->addAlias("MySQLStructure")

    ->rule("Gdn_SQLDriver")
    ->setClass("Gdn_MySQLDriver")
    ->setShared(true)
    ->addAlias("Gdn_MySQLDriver")
    ->addAlias("MySQLDriver")
    ->addAlias(Gdn::AliasSqlDriver)

    ->rule("Identity")
    ->setClass("Gdn_CookieIdentity")
    ->setShared(true)

    ->rule("Gdn_Session")
    ->setShared(true)
    ->addAlias("Session")

    ->rule(Gdn::AliasAuthenticator)
    ->setClass("Gdn_Auth")
    ->setShared(true)

    ->rule("Gdn_Router")
    ->addAlias(Gdn::AliasRouter)
    ->setShared(true)

    ->rule(\Vanilla\Web\Asset\DeploymentCacheBuster::class)
    ->setShared(true)
    ->setConstructorArgs([
        "deploymentTime" => ContainerUtils::config("Garden.Deployed"),
    ])

    ->rule(\Vanilla\Web\Asset\AssetPreloadModel::class)
    ->setShared(true)

    ->rule(\Vanilla\Web\Asset\WebpackAssetProvider::class)
    ->addCall("setHotReloadEnabled", [ContainerUtils::config("HotReload.Enabled")])
    ->addCall("setLocaleKey", [ContainerUtils::currentLocale()])
    ->addCall("setCacheBusterKey", [ContainerUtils::cacheBuster()])
    // Explicitly cannot be set as shared.
    // If instantiated too early, then the request/site sections will not be processed yet.
    ->setShared(false)

    ->rule(\Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel::class)
    ->setShared(true)
    ->addCall("addProvider", [
        new Reference(\Vanilla\Web\ContentSecurityPolicy\DefaultContentSecurityPolicyProvider::class),
    ])
    ->addCall("addProvider", [
        new Reference(\Vanilla\Web\ContentSecurityPolicy\EmbedWhitelistContentSecurityPolicyProvider::class),
    ])
    ->addCall("addProvider", [
        new Reference(\Vanilla\Web\ContentSecurityPolicy\VanillaWhitelistContentSecurityPolicyProvider::class),
    ])
    ->addCall("addProvider", [new Reference(\Vanilla\Web\Asset\WebpackContentSecurityPolicyProvider::class)])

    ->rule(\Vanilla\Web\Asset\LegacyAssetModel::class)
    ->setConstructorArgs([ContainerUtils::cacheBuster()])

    ->rule("@view-application/json")
    ->setClass(\Vanilla\Web\JsonView::class)
    ->setShared(true)

    ->rule("@view-application/csv")
    ->setClass(\Vanilla\Web\CsvView::class)
    ->setShared(true)

    ->rule("@baseUrl")
    ->setFactory(function (Gdn_Request $request) {
        return $request->getSimpleUrl("");
    })

    ->rule(\Vanilla\Web\SystemTokenUtils::class)
    ->setConstructorArgs([ContainerUtils::config("Context.Secret", "")])
    ->setShared(true)

    ->rule(\Vanilla\Web\RoleTokenFactory::class)
    ->setShared(true)

    ->rule(\Vanilla\OpenAPIBuilder::class)
    ->addCall("addFilter", ["filter" => new Reference("@apiexpand-filter")])

    ->rule("@apiexpand-filter")
    ->setFactory([\Vanilla\Web\APIExpandMiddleware::class, "filterOpenAPIFactory"])

    ->rule(\Garden\ClassLocator::class)
    ->setClass(\Vanilla\VanillaClassLocator::class)

    ->rule("Gdn_Model")
    ->setShared(true)

    ->rule(Model::class)
    ->setShared(true)

    ->rule(TrustedDomainModel::class)
    ->setShared(true)

    ->rule(Contracts\Models\UserProviderInterface::class)
    ->setClass(UserModel::class)

    ->rule(BreadcrumbModel::class)
    ->setShared(true)

    ->rule(LayoutViewModel::class)
    ->addCall("addProvider", [new Reference(GlobalRecordProvider::class)])
    ->setShared(true)

    ->rule(LayoutViewModel::class)
    ->addCall("addProvider", [new Reference(RootRecordProvider::class)])
    ->setShared(true)

    ->rule(LayoutViewModel::class)
    ->addCall("addProvider", [new Reference(CategoryRecordProvider::class)])
    ->setShared(true)

    ->rule(\Vanilla\Models\AuthenticatorModel::class)
    ->setShared(true)
    ->addCall("registerAuthenticatorClass", [\Vanilla\Authenticator\PasswordAuthenticator::class])

    ->rule(SearchService::class)
    ->setShared(true)
    ->addCall("registerActiveDriver", [new Reference(\Vanilla\Search\MysqlSearchDriver::class)])
    ->rule(AbstractSearchDriver::class)
    ->setShared(true)
    ->rule(SearchTypeCollectorInterface::class)
    ->addCall("registerSearchType", [new Reference(GlobalSearchType::class)])
    ->setInherit(true)

    ->rule(WidgetService::class)
    ->addCall("registerWidget", [TabWidgetModule::class])
    ->setShared(true)

    ->rule(TabWidgetTabService::class)
    ->setShared(true)

    ->rule("Gdn_IPlugin")
    ->setShared(true)

    ->rule(Gdn_Plugin::class)
    ->setShared(true)
    ->addCall("setAddonFromManager")

    ->rule("Gdn_Slice")
    ->setShared(true)
    ->addAlias("Slice")

    ->rule("Gdn_Statistics")
    ->addAlias("Statistics")
    ->setShared(true)

    ->rule("Gdn_Regarding")
    ->setShared(true)

    ->rule("BBCodeFormatter")
    ->setClass("BBCode")
    ->setShared(true)

    ->rule("HtmlFormatter")
    ->setClass(VanillaHtmlFormatter::class)
    ->setShared(true)

    ->rule(\Vanilla\Formatting\Quill\Renderer::class)
    ->setShared(true)

    ->rule(\Vanilla\Formatting\Quill\Parser::class)
    ->addCall("addCoreBlotsAndFormats")
    ->setShared(true)

    ->rule("Smarty")
    ->setShared(true)

    ->rule(\Vanilla\Web\Pagination\WebLinking::class)
    ->setShared(true)

    ->rule("ViewHandler.tpl")
    ->setClass("Gdn_Smarty")
    ->setShared(true)

    ->rule("ViewHandler.php")
    ->setShared(true)

    ->rule("ViewHandler.twig")
    ->setClass(\Vanilla\Web\LegacyTwigViewHandler::class)
    ->setShared(true)

    ->rule(TwigRenderer::class)
    ->setShared(true)

    ->rule(TwigEnhancer::class)
    ->addCall("setCompileCacheDirectory", [PATH_CACHE . "/twig"])
    ->setShared(true)

    ->rule("Gdn_Form")
    ->addAlias("Form")

    ->rule(\Vanilla\EmbeddedContent\EmbedService::class)
    ->addCall("addCoreEmbeds")
    ->setShared(true)

    ->rule(\Vanilla\EmbeddedContent\Factories\ScrapeEmbedFactory::class)
    ->setConstructorArgs(["httpClient" => new Reference("@scrape-http-client")])
    ->rule("@scrape-http-client")
    ->setClass(\Garden\Http\HttpClient::class)
    ->setConstructorArgs(["handler" => new Reference(Vanilla\Web\SafeCurlHttpHandler::class)])
    ->addCall("addMiddleware", [new Reference(\Vanilla\Web\Middleware\CookiePassMiddleware::class)])

    ->rule(Vanilla\PageScraper::class)
    ->addCall("registerMetadataParser", [new Reference(Vanilla\Metadata\Parser\OpenGraphParser::class)])
    ->addCall("registerMetadataParser", [new Reference(Vanilla\Metadata\Parser\JsonLDParser::class)])
    ->setShared(true)

    ->rule(UserLeaderService::class)
    ->addCall("addProvider", [new Reference(UserPointsModel::class)])
    ->setShared(true)

    ->rule(\Vanilla\PageScraper::class)
    ->setConstructorArgs(["httpClient" => new Reference("@scrape-http-client")])
    ->addCall("registerMetadataParser", [new Reference(Vanilla\Metadata\Parser\OpenGraphParser::class)])
    ->addCall("registerMetadataParser", [new Reference(Vanilla\Metadata\Parser\JsonLDParser::class)])

    ->rule(Garden\Http\HttpClient::class)
    ->setConstructorArgs(["handler" => new Reference(Vanilla\Web\SafeCurlHttpHandler::class)])

    ->rule(Vanilla\Formatting\FormatService::class)
    ->addCall("registerBuiltInFormats")
    ->setInherit(true)
    ->setShared(true)

    ->rule(BaseFormat::class)
    ->addCall("addHtmlProcessor", [new Reference(ExternalLinksProcessor::class)])

    ->rule(BaseFormat::class)
    ->addCall("addHtmlProcessor", [new Reference(ImageHtmlProcessor::class)])

    ->rule(ExternalLinksProcessor::class)
    ->addCall("setWarnLeaving", [ContainerUtils::config("Garden.Format.WarnLeaving")])

    ->rule(LegacyEmbedReplacer::class)
    ->setShared(true)

    ->rule(HtmlEnhancer::class)
    ->setShared(true)

    ->rule(HtmlSanitizer::class)
    ->setShared(true)

    ->rule(Vanilla\Scheduler\SchedulerInterface::class)
    ->setClass(Vanilla\Scheduler\DeferredScheduler::class)
    ->addAlias(DeferredScheduler::class)
    ->addCall("addDriver", [Vanilla\Scheduler\Driver\LocalDriver::class])
    ->setShared(true)

    // Controller data preloading
    ->rule(Page::class)
    ->setInherit(true)
    ->addCall("registerReduxActionProvider", ["provider" => new Reference(LocalePreloadProvider::class)])
    ->addCall("registerReduxActionProvider", ["provider" => new Reference(CurrentUserPreloadProvider::class)])
    ->rule(Gdn_Controller::class)
    ->setInherit(true)
    ->addCall("registerReduxActionProvider", ["provider" => new Reference(LocalePreloadProvider::class)])
    ->addCall("registerReduxActionProvider", ["provider" => new Reference(CurrentUserPreloadProvider::class)])

    // Optimizations
    ->rule(ModelFactory::class)
    ->setShared(true)
    ->rule(BodyFormatValidator::class)
    ->setShared(true)
    ->rule(PlainTextLengthValidator::class)
    ->setShared(true)
    ->rule(SafeCurlHttpHandler::class)
    ->setShared(true)

    // These cannot be shared because they can be configured differently.
    ->rule(Contracts\Formatting\FormatInterface::class)
    ->setInherit(true)
    ->setShared(false)

    ->rule(SiteMeta::class)
    ->setShared(true)
    ->rule(ThemeServiceHelper::class)
    ->setShared(true)
    ->rule(ClassLocator::class)
    ->setShared(true)
    ->rule(HtmlPlainTextConverter::class)
    ->setShared(true)
    ->rule(FsThemeProvider::class)
    ->setShared(true)
    ->rule(ThemeAssetFactory::class)
    ->setShared(true)
    ->rule(FormatConfig::class)
    ->setShared(true)
    ->rule(DiscussionStatusModel::class)
    ->setShared(true);

$timers->stop("core-bootstrap");

// Run through the bootstrap with dependencies.
$dic->call(function (
    Container $dic,
    Gdn_Configuration $config,
    \Vanilla\AddonManager $addonManager,
    Gdn_Request $request // remove later
) use ($timers) {
    $timers->start("core-defaults");
    // Load default baseline Garden configurations.
    $config->load(PATH_CONF . "/config-defaults.php");

    // Load installation-specific configuration so that we know what apps are enabled.
    $config->load($config->defaultPath(), "Configuration", true);
    $timers->stop("core-defaults");

    /**
     * Bootstrap Early
     *
     * A lot of the framework is loaded now, most importantly the core autoloader,
     * default config and the general and error functions. More control is possible
     * here, but some things have already been loaded and are immutable.
     */
    $timers->start("bootstrap-early");
    if (file_exists(PATH_CONF . "/bootstrap.early.php")) {
        require_once PATH_CONF . "/bootstrap.early.php";
    }
    $timers->stop("bootstrap-early");

    $config->caching(true);
    debug($config->get("Debug", false));

    set_error_handler([ErrorLogger::class, "handleError"], E_ALL);
    set_exception_handler("gdnExceptionHandler");

    /**
     * Installer Redirect
     *
     * If Garden is not yet installed, force the request to /dashboard/setup and
     * begin installation.
     */
    if ($config->get("Garden.Installed", false) === false && strpos($request->path(), "setup") === false) {
        safeHeader("Location: " . $request->url("dashboard/setup", true));
        exit();
    }

    /**
     * Extension Managers
     *
     * Now load the Addon, Application, Theme and Plugin managers into the Factory, and
     * process the application-specific configuration defaults.
     */

    // Start the addons, plugins, and applications.
    $timers->start("start-addons");
    $addonManager->startAddonsByKey($config->get("EnabledPlugins"), Addon::TYPE_ADDON);
    $addonManager->startAddonsByKey($config->get("EnabledApplications"), Addon::TYPE_ADDON);
    $addonManager->startAddonsByKey(array_keys($config->get("EnabledLocales", [])), Addon::TYPE_LOCALE);

    $currentTheme = $config->get(
        // Despite our default theme being responsive, older sites could be configured with a different mobile/desktop theme.
        isMobile() ? "Garden.MobileTheme" : "Garden.Theme",
        ThemeService::FALLBACK_THEME_KEY
    );
    $addonManager->startAddonsByKey([$currentTheme], Addon::TYPE_THEME);

    $addonManager->applyConfigDefaults($config);
    $timers->stop("start-addons");

    /**
     * Bootstrap Late
     *
     * All configurations are loaded, as well as the Application, Plugin and Theme
     * managers.
     */
    $timers->start("bootstrap-late");
    if (file_exists(PATH_CONF . "/bootstrap.late.php")) {
        require_once PATH_CONF . "/bootstrap.late.php";
    }
    $timers->stop("bootstrap-late");

    /**
     * Extension Startup
     *
     * Allow installed addons to execute startup and bootstrap procedures that they may have, here.
     */

    $timers->start("addons-container-config");
    $addonManager->configureContainer($dic);
    $timers->stop("addons-container-config");
    // Delay instantiation in case an addon has configured it.
    $eventManager = $dic->get(EventManager::class);

    // Plugins startup
    $timers->start("addons-events-binding");
    $addonManager->bindAllEvents($eventManager);
    $timers->stop("addons-events-binding");

    // Prepare our locale.
    $locale = $dic->get(Gdn_Locale::class);
    $locale->loadExtraLocaleDefinitions();

    ///
    /// Both of these should be considered "soft" deprecated.
    /// New addons should use AddonContainerRules to configure the application.
    ///
    if ($eventManager->hasHandler("gdn_pluginManager_afterStart")) {
        $eventManager->fire("gdn_pluginManager_afterStart", $dic->get(Gdn_PluginManager::class));
    }

    // Now that all of the events have been bound, fire an event that allows plugins to modify the container.
    $eventManager->fire("container_init", $dic);
});

// Send out cookie headers.
register_shutdown_function(function () use ($dic) {
    $dic->call(function (Garden\Web\Cookie $cookie) {
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
$dic->get("Gdn_Locale");

require_once PATH_LIBRARY_CORE . "/functions.validation.php";

// Configure JWT library to allow for five seconds of leeway.
JWT::$leeway = 5;

// Start Authenticators
$dic->get("Authenticator")->startAuthenticator();

/**
 * Bootstrap After
 *
 * After the bootstrap has finished loading, this hook allows developers a last
 * chance to customize Garden's runtime environment before the actual request
 * is handled.
 */
if (file_exists(PATH_ROOT . "/conf/bootstrap.after.php")) {
    require_once PATH_ROOT . "/conf/bootstrap.after.php";
}

// Include "Render" functions now - this way pluggables and custom confs can override them.
require_once PATH_LIBRARY_CORE . "/functions.render.php";

if (!defined("CLIENT_NAME")) {
    define("CLIENT_NAME", "vanilla");
}

register_shutdown_function(function () use ($dic) {
    $dic->call(function (
        Gdn_Configuration $config,
        \Garden\EventManager $eventManager,
        \Psr\Log\LoggerInterface $log,
        \Vanilla\Utility\Timers $timers,
        SchedulerInterface $scheduler
    ) {
        // Logs timers
        if ($config->get("trace.timers")) {
            $timers->stopAll();
            $timers->logAll($log, "app_timers");
            $timers->reset();
        }

        // Flush our buffers and close the request.
        $scheduler->finalizeRequest();

        // Now extend our time limit a bit for the local jobs.
        set_time_limit(60);

        // Now this will continue running.
        $timers->start("schedulerDispatch");
        try {
            $scheduler->dispatchJobs();
        } finally {
            $timers->stop("schedulerDispatch");
            if ($config->get("trace.timers")) {
                $timers->stopAll();
                $timers->logAll($log, "scheduler_timers");
                $timers->reset();
            }
        }
    });
});
