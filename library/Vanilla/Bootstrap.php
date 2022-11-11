<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Garden\Container\Container;
use Garden\Container\Reference;
use Garden\Web\Dispatcher;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\Psr16Adapter;
use Vanilla\Analytics\AnalyticsActionsProvider;
use Vanilla\Analytics\TrackableDecoratorInterface;
use Vanilla\Cache\CacheCacheAdapter;
use Vanilla\ImageSrcSet\ImageResizeProviderInterface;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\ImageSrcSet\Providers\DefaultImageResizeProvider;
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Controllers\SearchRootController;
use Vanilla\Logging\TraceCollector;
use Vanilla\Models\Model;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerMiddleware;
use Vanilla;
use Vanilla\Theme\FsThemeProvider;
use Vanilla\Theme\ThemeFeatures;
use Vanilla\Theme\VariableProviders\QuickLinksVariableProvider;
use Vanilla\Utility\ContainerUtils;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel;
use Vanilla\Web\Middleware\LogTransactionMiddleware;
use Vanilla\Web\Page;

/**
 * Contains static functions for bootstrapping Vanilla.
 *
 * This class is intended to be usable in the application and tests.
 */
class Bootstrap
{
    public const CACHE_FAST = "@fast-cache";

    /**
     * Configure the application's dependency injection container.
     *
     * Note to developers: This is a relatively new method that does not have the entire bootstrap in it. It is intended
     * to use to refactor so that the app and tests use a similar config where differences are more easily spotted and
     * configures.
     *
     * THIS METHOD SHOULD NOT HAVE SIDE EFFECTS BEYOND CONTAINER CONFIG. DO NOT CREATE INSTANCES IN THIS METHOD.
     *
     * @param Container $container
     */
    public static function configureContainer(Container $container): void
    {
        $container
            ->rule(\Psr\Container\ContainerInterface::class)
            ->setAliasOf(\Garden\Container\Container::class)

            ->rule(\Interop\Container\ContainerInterface::class)
            ->setClass(InteropContainer::class)
            ->setShared(true)

            ->rule(InjectableInterface::class)
            ->addCall("setDependencies")

            ->rule(\DateTimeInterface::class)
            ->setAliasOf(\DateTimeImmutable::class)
            ->setConstructorArgs([null, null])

            ->rule(LayoutHydrator::class)
            ->setShared(true);

        // Tracking
        $container->rule(TrackableDecoratorInterface::class)->setShared(true);

        // Logging
        $container
            ->rule(\Vanilla\Logger::class)
            ->setShared(true)
            ->rule(LoggerInterface::class)
            ->setShared(true)
            ->setAliasOf(\Vanilla\Logger::class)
            ->setClass(\Vanilla\Logger::class)

            ->rule(Vanilla\Logging\LogDecorator::class)
            ->setShared(true)

            ->rule(\Psr\Log\LoggerAwareInterface::class)
            ->addCall("setLogger");

        $container->rule(TraceCollector::class)->setShared(true);

        // Image Srcset
        $container
            ->rule(ImageSrcSetService::class)
            ->addCall("setImageResizeProvider", [new Reference(DefaultImageResizeProvider::class)]);

        // Addons
        // Addon Manager
        $container
            ->rule(Vanilla\AddonManager::class)
            ->setShared(true)
            ->setConstructorArgs([AddonManager::getDefaultScanDirectories(), PATH_CACHE])
            ->addAlias("AddonManager")
            ->addCall("registerAutoloader");

        // Analytics
        $container
            ->rule(\Vanilla\Analytics\Client::class)
            ->setShared(true)
            ->addAlias(\Vanilla\Contracts\Analytics\ClientInterface::class)

            ->rule(Page::class)
            ->addCall("registerReduxActionProvider", ["provider" => new Reference(AnalyticsActionsProvider::class)]);

        // Models
        $container
            ->rule(\Gdn_Model::class)
            ->setShared(true)

            ->rule(Model::class)
            ->setShared(true);

        // Caches
        $container
            ->rule(\Gdn_Cache::class)
            ->setShared(true)
            ->setFactory([\Gdn_Cache::class, "initialize"])
            ->addAlias("Cache")

            ->rule(Vanilla\Models\LockService::class)
            ->setShared(true)

            ->rule(CacheInterface::class)
            ->setShared(true)
            ->setClass(CacheCacheAdapter::class)

            ->rule(CacheItemPoolInterface::class)
            ->setShared(true)
            ->setClass(Psr16Adapter::class)

            ->rule(Dispatcher::class)
            ->addCall("addMiddleware", [new Reference(LongRunnerMiddleware::class)])

            ->rule(LongRunner::class)
            ->setShared(true)

            // Validation
            ->rule(\Gdn_Validation::class)
            ->addCall("addRule", ["BodyFormat", new Reference(BodyFormatValidator::class)])

            ->rule(\Gdn_Validation::class)
            ->addCall("addRule", ["plainTextLength", new Reference(PlainTextLengthValidator::class)])

            ->rule(self::CACHE_FAST)
            ->setShared(true)
            ->setFactory(function (ContainerInterface $container) {
                /** @var CacheCacheAdapter $mainCache */
                $mainCache = $container->get(CacheInterface::class);
                $mainCachePsr16 = new Psr16Adapter($mainCache);

                if (function_exists("apcu_fetch")) {
                    // @codeCoverageIgnoreStart
                    // This code doesn't usually get hit in unit tests, but was manually confirmed.
                    $cache = new ChainAdapter([
                        new ApcuAdapter((string) $mainCache->getCache()->getPrefix(), 5),
                        $mainCachePsr16,
                    ]);
                    return $cache;
                    // @codeCoverageIgnoreEnd
                }
                return $mainCachePsr16;
            })

            ->rule(HttpCacheMiddleware::class)
            ->setShared(true)

            ->rule(ContentSecurityPolicyModel::class)
            ->setShared(true)

            ->rule(ThemeFeatures::class)
            ->setShared(true)
            ->setConstructorArgs(["theme" => ContainerUtils::currentTheme()])

            // File base theme api provider
            ->rule(\Vanilla\Theme\ThemeService::class)
            ->setShared(true)
            ->addCall("addThemeProvider", [new Reference(FsThemeProvider::class)])

            ->rule(\Vanilla\Theme\ThemeSectionModel::class)
            ->setShared(true);

        // Core dispatcher and middlewares
        $container
            ->rule(\Gdn_Dispatcher::class)
            ->setShared(true)
            ->addAlias(\Gdn::AliasDispatcher)

            ->rule(Vanilla\Web\Controller::class)
            ->setShared(true)

            ->rule(\Garden\Web\Dispatcher::class)
            ->setShared(true)
            ->addCall("addRoute", ["route" => new Reference("@api-v2-route"), "api-v2"])
            ->addCall("addRoute", ["route" => new Reference("@new-search-route"), "new-search"])
            ->addCall("addRoute", [
                "route" => new \Garden\Container\Callback(function () {
                    return new \Garden\Web\PreflightRoute("/api/v2", true);
                }),
            ])
            ->addCall("setAllowedOrigins", ["isTrustedDomain"])
            ->addCall("addMiddleware", [new Reference(\Vanilla\Web\Middleware\SystemTokenMiddleware::class)])
            ->addCall("addMiddleware", [new Reference(\Vanilla\Web\CacheControlMiddleware::class)])
            ->addCall("addMiddleware", [new Reference(LogTransactionMiddleware::class)])
            ->addCall("addMiddleware", [new Reference("@smart-id-middleware")])
            ->addCall("addMiddleware", [new Reference(\Vanilla\Web\DeploymentHeaderMiddleware::class)])
            ->addCall("addMiddleware", [new Reference(\Vanilla\Web\ContentSecurityPolicyMiddleware::class)])
            ->addCall("addMiddleware", [new Reference(\Vanilla\Web\HttpStrictTransportSecurityMiddleware::class)])
            ->addCall("addMiddleware", [new Reference(\Vanilla\Web\APIExpandMiddleware::class)])
            ->addCall("addMiddleware", [new Reference(\Vanilla\Web\Middleware\ValidateUTF8Middleware::class)])
            ->addCall("addMiddleware", [new Reference(\Vanilla\Web\Middleware\ValidateJSONMiddleware::class)])

            // Specific route definitions and middlewares
            ->rule("@new-search-route")
            ->setClass(\Garden\Web\ResourceRoute::class)
            ->setConstructorArgs(["/search", "*\\%sSearchPageController"])
            ->addCall("setFeatureFlag", [SearchRootController::ENABLE_FLAG])
            ->addCall("setMeta", ["CONTENT_TYPE", "text/html; charset=utf-8"])
            ->addCall("setRootController", [SearchRootController::class])

            ->rule("@api-v2-route")
            ->setClass(\Garden\Web\ResourceRoute::class)
            ->setConstructorArgs(["/api/v2/", "*\\%sApiController"])
            ->addCall("setMeta", ["CONTENT_TYPE", "application/json; charset=utf-8"])
            ->addCall("addMiddleware", [new Reference(\Vanilla\Web\PrivateCommunityMiddleware::class)])
            ->addCall("addMiddleware", [new Reference(\Vanilla\Web\Middleware\RoleTokenAuthMiddleware::class)])
            ->addCall("addMiddleware", [new Reference(\Vanilla\Web\ApiFilterMiddleware::class)])

            // Middleware configuration
            ->rule(\Vanilla\Web\Middleware\SystemTokenMiddleware::class)
            ->setConstructorArgs(["/api/v2/"])
            ->setShared(true)

            ->rule(\Vanilla\Web\APIExpandMiddleware::class)
            ->setConstructorArgs(["/api/v2/"])
            ->setShared(true)

            ->rule(\Vanilla\Web\HttpStrictTransportSecurityModel::class)
            ->setShared(true)

            ->rule(\Vanilla\Web\Middleware\LogTransactionMiddleware::class)
            ->setShared(true)

            ->rule("@smart-id-middleware")
            ->setClass(\Vanilla\Web\SmartIDMiddleware::class)
            ->setShared(true)
            ->setConstructorArgs(["/api/v2/"])
            ->addCall("addSmartID", ["CategoryID", "categories", ["name", "urlcode"], "Category"])
            ->addCall("addSmartID", ["RoleID", "roles", ["name"], "Role"])
            ->addCall("addSmartID", ["statusID", "discussionStatus", ["name"], "RecordStatus"])
            ->addCall("addSmartID", ["RoleID", "roles", ["name"], "Role"])
            ->addCall("addSmartID", ["UserID", "users", "*", new Reference("@user-smart-id-resolver")])

            ->rule("@user-smart-id-resolver")
            ->setFactory(function (Container $dic) {
                /* @var \Vanilla\Web\UserSmartIDResolver $uid */
                $uid = $dic->get(\Vanilla\Web\UserSmartIDResolver::class);
                $uid->setEmailEnabled(
                    !$dic->get(\Gdn_Configuration::class)->get("Garden.Registration.NoEmail")
                )->setViewEmail($dic->get(\Gdn_Session::class)->checkPermission("Garden.PersonalInfo.View"));

                return $uid;
            })

            ->rule(\Vanilla\Web\PrivateCommunityMiddleware::class)
            ->setShared(true)
            ->setConstructorArgs([ContainerUtils::config("Garden.PrivateCommunity")])

            // Layouts
            ->rule(\Vanilla\Layout\Providers\FileBasedLayoutProvider::class)
            ->setShared(true)
            ->setConstructorArgs([PATH_CACHE . "/static-layouts"])
            ->addCall("registerStaticLayout", ["home", PATH_ROOT . "/library/Vanilla/Layout/Definitions/home.json"])

            ->rule(Layout\LayoutService::class)
            ->setShared(true)
            ->addCall("addProvider", [new Reference(\Vanilla\Layout\LayoutModel::class)])
            ->addCall("addProvider", [new Reference(\Vanilla\Layout\Providers\FileBasedLayoutProvider::class)]);
    }
}
