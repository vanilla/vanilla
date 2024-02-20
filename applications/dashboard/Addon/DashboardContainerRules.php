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
use Garden\Web\Dispatcher;
use Garden\Web\PageControllerRoute;
use Gdn_Session;
use SpoofMiddleware;
use UserProfileFieldsExpander;
use SearchMembersEventProvider;
use Vanilla\AddonContainerRules;
use Vanilla\Analytics\EventProviderService;
use Vanilla\Analytics\PageViewEventProvider;
use Vanilla\Analytics\SearchAllEventProvider;
use Vanilla\Analytics\SearchPlacesEventProvider;
use Vanilla\Dashboard\Activity\ActivityCommentActivity;
use Vanilla\Dashboard\Activity\ApplicantActivity;
use Vanilla\Dashboard\Activity\BookmarkCommentActivity;
use Vanilla\Dashboard\Activity\CategoryCommentActivity;
use Vanilla\Dashboard\Activity\CategoryDiscussionActivity;
use Vanilla\Dashboard\Activity\CommentMentionActivity;
use Vanilla\Dashboard\Activity\DiscussionCommentActivity;
use Vanilla\Dashboard\Activity\DiscussionMentionActivity;
use Vanilla\Dashboard\Activity\EmailDigestActivity;
use Vanilla\Dashboard\Activity\ParticipateCommentActivity;
use Vanilla\Dashboard\Activity\WallCommentActivity;
use Vanilla\Dashboard\AuthenticatorTypeService;
use Vanilla\Dashboard\Controllers\Api\SiteTotalsFilterOpenApi;
use Vanilla\Dashboard\Controllers\LayoutSettingsPageController;
use Vanilla\Dashboard\Controllers\LeavingController;
use Vanilla\Dashboard\Controllers\Pages\AppearancePageController;
use Vanilla\Dashboard\Controllers\Pages\DeveloperPageController;
use Vanilla\Dashboard\Controllers\Pages\DeveloperProfilesPageController;
use Vanilla\Dashboard\Controllers\Pages\HomePageController;
use Vanilla\Dashboard\Layout\View\LegacyProfileLayoutView;
use Vanilla\Dashboard\Layout\View\LegacyRegistrationLayoutView;
use Vanilla\Dashboard\Layout\View\LegacySigninLayoutView;
use Vanilla\Dashboard\Models\ActivityService;
use Vanilla\Dashboard\Models\ModerationMessagesFilterOpenApi;
use Vanilla\Dashboard\Models\ProfileFieldsOpenApi;
use Vanilla\Dashboard\Models\RolesExpander;
use Vanilla\Dashboard\Models\SsoUsersExpander;
use Vanilla\Dashboard\Models\UsersExpander;
use Vanilla\Dashboard\Models\UserSiteTotalProvider;
use Vanilla\Dashboard\UserLeaderService;
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Layout\LayoutService;
use Vanilla\Layout\Middleware\LayoutPermissionFilterMiddleware;
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
                "/home/leaving" => LeavingController::class,
                "/settings/developer/profiles" => DeveloperProfilesPageController::class,
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
            ->rule(ActivityService::class)
            ->addCall("registerActivity", [DiscussionCommentActivity::class])
            ->addCall("registerActivity", [ActivityCommentActivity::class])
            ->addCall("registerActivity", [WallCommentActivity::class])
            ->addCall("registerActivity", [ApplicantActivity::class])
            ->addCall("registerActivity", [BookmarkCommentActivity::class])
            ->addCall("registerActivity", [DiscussionMentionActivity::class])
            ->addCall("registerActivity", [CommentMentionActivity::class])
            ->addCall("registerActivity", [CategoryDiscussionActivity::class])
            ->addCall("registerActivity", [CategoryCommentActivity::class])
            ->addCall("registerActivity", [ParticipateCommentActivity::class])
            ->addCall("registerActivity", [EmailDigestActivity::class]);

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
            ->addCall("addExpander", [new Reference(SsoUsersExpander::class)])
            ->addCall("addExpander", [new Reference(RolesExpander::class)]);
        $container
            ->addCall("addExpander", [new Reference(UserProfileFieldsExpander::class)])
            ->addCall("addExpander", [new Reference(ExtendedUserFieldsExpander::class)]);

        $container
            ->rule(OpenAPIBuilder::class)
            ->addCall("addFilter", [new Reference(ModerationMessagesFilterOpenApi::class)])
            ->addCall("addFilter", [new Reference(NotificationPreferencesFilterOpenApi::class)]);

        $container->rule(OpenAPIBuilder::class)->addCall("addFilter", [new Reference(AuthenticatorTypeService::class)]);

        $container->rule(UserLeaderService::class)->setShared(true);

        $container->rule(OpenAPIBuilder::class)->addCall("addFilter", [new Reference(ProfileFieldsOpenApi::class)]);

        $container
            ->rule(SpoofMiddleware::class)
            ->setConstructorArgs([new Reference(Gdn_Session::class), new Reference("@smart-id-middleware")]);

        $container->rule(Dispatcher::class)->addCall("addMiddleware", [new Reference(SpoofMiddleware::class)]);

        $container
            ->rule(\Vanilla\OpenAPIBuilder::class)
            ->addCall("addFilter", ["filter" => new Reference(\ReactionsFilterOpenApi::class)]);
    }
}
