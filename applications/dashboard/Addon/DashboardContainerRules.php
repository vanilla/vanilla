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
use Vanilla\Dashboard\Controllers\LayoutSettingsPageController;
use Vanilla\Dashboard\Models\UserSiteTotalProvider;
use Vanilla\Models\SiteTotalService;

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

        $container->rule(SiteTotalService::class)
            ->addCall('registerProvider', [new Reference(UserSiteTotalProvider::class)])
        ;
    }
}
