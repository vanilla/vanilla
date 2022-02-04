<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Garden\Web\Dispatcher;
use Garden\Web\PageControllerRoute;
use Vanilla\AddonContainerRules;
use Vanilla\Dashboard\Controllers\Api\SiteTotalsFilterOpenApi;
use Vanilla\Dashboard\Controllers\LayoutSettingsPageController;
use Vanilla\Dashboard\Layout\View\LegacyProfileLayoutView;
use Vanilla\Dashboard\Layout\View\LegacyRegistrationLayoutView;
use Vanilla\Dashboard\Layout\View\LegacySigninLayoutView;
use Vanilla\Dashboard\Controllers\Pages\AppearancePageController;
use Vanilla\Dashboard\Controllers\Pages\HomePageController;
use Vanilla\Dashboard\Models\ModerationMessageStructure;
use Vanilla\Dashboard\Models\UserSiteTotalProvider;
use Vanilla\Layout\LayoutService;
use Vanilla\Layout\Middleware\LayoutRoleFilterMiddleware;
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Models\SiteTotalService;
use Vanilla\OpenAPIBuilder;

/**
 * Container rules for the dashboard.
 */
class DashboardContainerRules extends AddonContainerRules {

    /**
     * @param ContainerConfigurationInterface $container
     */
    public function configureContainer(ContainerConfigurationInterface $container): void {
        PageControllerRoute::configurePageRoutes($container, [
            '/settings/layout' => LayoutSettingsPageController::class,
        ]);

        PageControllerRoute::configurePageRoutes($container, [
            '/' => HomePageController::class,
        ], 'CustomLayoutHomePage');

        $container->rule(SiteTotalService::class)
            ->addCall('registerProvider', [new Reference(UserSiteTotalProvider::class)])
        ;

        $container->rule(LayoutService::class)
            ->addCall('addLayoutView', [new Reference(LegacyProfileLayoutView::class)])
            ->addCall('addLayoutView', [new Reference(LegacyRegistrationLayoutView::class)])
            ->addCall('addLayoutView', [new Reference(LegacySigninLayoutView::class)]);

        $container->rule(OpenAPIBuilder::class)
            ->addCall("addFilter", ["filter" => new Reference(SiteTotalsFilterOpenApi::class)]);

        $container->rule(LayoutHydrator::class)
            ->addCall("addMiddleware", [new Reference(LayoutRoleFilterMiddleware::class)]);
    }
}
