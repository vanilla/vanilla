<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Addon;

use ExtendedUserFieldsExpander;
use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Garden\Web\PageControllerRoute;
use UserProfileFieldsExpander;
use SearchMembersEventProvider;
use Vanilla\AddonContainerRules;
use Vanilla\Analytics\EventProviderService;
use Vanilla\Analytics\PageViewEventProvider;
use Vanilla\Analytics\SearchAllEventProvider;
use Vanilla\Analytics\SearchPlacesEventProvider;
use Vanilla\Dashboard\AuthenticatorTypeService;
use Vanilla\Dashboard\Controllers\Api\SiteTotalsFilterOpenApi;
use Vanilla\Dashboard\Controllers\LayoutSettingsPageController;
use Vanilla\Dashboard\Layout\View\LegacyProfileLayoutView;
use Vanilla\Dashboard\Layout\View\LegacyRegistrationLayoutView;
use Vanilla\Dashboard\Layout\View\LegacySigninLayoutView;
use Vanilla\Dashboard\Controllers\Pages\AppearancePageController;
use Vanilla\Dashboard\Controllers\Pages\HomePageController;
use Vanilla\Dashboard\Models\ModerationMessagesFilterOpenApi;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Dashboard\Models\ProfileFieldsOpenApi;
use Vanilla\Dashboard\Models\SsoUsersExpander;
use Vanilla\Dashboard\Models\UsersExpander;
use Vanilla\Dashboard\Models\UserSiteTotalProvider;
use Vanilla\Dashboard\UserLeaderService;
use Vanilla\FeatureFlagHelper;
use Vanilla\Layout\LayoutService;
use Vanilla\Layout\Middleware\LayoutPermissionFilterMiddleware;
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Models\SiteTotalService;
use Vanilla\OpenAPIBuilder;
use Vanilla\Web\APIExpandMiddleware;

/**
 * Container rules for the dashboard.
 */
class DashboardContainerRules extends AddonContainerRules
{
    /**
     * @param ContainerConfigurationInterface $container
     */
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        PageControllerRoute::configurePageRoutes(
            $container,
            [
                "/settings/layout" => LayoutSettingsPageController::class,
                "/appearance" => AppearancePageController::class,
            ],
            null,
            -1
        );

        PageControllerRoute::configurePageRoutes(
            $container,
            [
                "/" => HomePageController::class,
            ],
            "customLayout.home"
        );

        $container
            ->rule(SiteTotalService::class)
            ->addCall("registerProvider", [new Reference(UserSiteTotalProvider::class)]);

        $container
            ->rule(EventProviderService::class)
            ->addCall("registerEventProvider", [new Reference(PageViewEventProvider::class)])
            ->addCall("registerEventProvider", [new Reference(SearchAllEventProvider::class)])
            ->addCall("registerEventProvider", [new Reference(SearchPlacesEventProvider::class)])
            ->addCall("registerEventProvider", [new Reference(SearchMembersEventProvider::class)]);

        $container
            ->rule(LayoutService::class)
            ->addCall("addLayoutView", [new Reference(LegacyProfileLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(LegacyRegistrationLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(LegacySigninLayoutView::class)]);

        $container
            ->rule(OpenAPIBuilder::class)
            ->addCall("addFilter", ["filter" => new Reference(SiteTotalsFilterOpenApi::class)]);

        $container
            ->rule(LayoutHydrator::class)
            ->addCall("addMiddleware", [new Reference(LayoutPermissionFilterMiddleware::class)]);

        $container
            ->rule(APIExpandMiddleware::class)
            ->addCall("addExpander", [new Reference(UsersExpander::class)])
            ->addCall("addExpander", [new Reference(SsoUsersExpander::class)]);
        if (FeatureFlagHelper::featureEnabled(ProfileFieldModel::FEATURE_FLAG)) {
            $container
                ->addCall("addExpander", [new Reference(UserProfileFieldsExpander::class)])
                ->addCall("addExpander", [new Reference(ExtendedUserFieldsExpander::class)]);
        }

        $container
            ->rule(OpenAPIBuilder::class)
            ->addCall("addFilter", [new Reference(ModerationMessagesFilterOpenApi::class)]);

        $container->rule(OpenAPIBuilder::class)->addCall("addFilter", [new Reference(AuthenticatorTypeService::class)]);

        $container->rule(UserLeaderService::class)->setShared(true);

        $container->rule(OpenAPIBuilder::class)->addCall("addFilter", [new Reference(ProfileFieldsOpenApi::class)]);
    }
}
