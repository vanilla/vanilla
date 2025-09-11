<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Addon;

use ExtendedUserFieldsExpander;
use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Garden\Events\ResourceEvent;
use Garden\Web\Dispatcher;
use Garden\Web\PageControllerRoute;
use Gdn_Session;
use SpoofMiddleware;
use UserProfileFieldsExpander;
use SearchMembersEventProvider;
use Vanilla\AddonContainerRules;
use Vanilla\Analytics\EventProviderService;
use Vanilla\Analytics\ExternalNavigationEventProvider;
use Vanilla\Analytics\PageViewEventProvider;
use Vanilla\Analytics\SearchAllEventProvider;
use Vanilla\Analytics\SearchPlacesEventProvider;
use Vanilla\Dashboard\Activity\ActivityCommentActivity;
use Vanilla\Dashboard\Activity\AiSuggestionsActivity;
use Vanilla\Dashboard\Activity\ApplicantActivity;
use Vanilla\Dashboard\Activity\BookmarkCommentActivity;
use Vanilla\Dashboard\Activity\CategoryCommentActivity;
use Vanilla\Dashboard\Activity\CategoryDiscussionActivity;
use Vanilla\Dashboard\Activity\CommentMentionActivity;
use Vanilla\Dashboard\Activity\DiscussionCommentActivity;
use Vanilla\Dashboard\Activity\DiscussionMentionActivity;
use Vanilla\Dashboard\Activity\EmailDigestActivity;
use Vanilla\Dashboard\Activity\EscalationActivity;
use Vanilla\Dashboard\Activity\MyEscalationActivity;
use Vanilla\Dashboard\Activity\ParticipateCommentActivity;
use Vanilla\Dashboard\Activity\ReportActivity;
use Vanilla\Dashboard\Activity\ScheduledPostFailedActivity;
use Vanilla\Dashboard\Activity\WallCommentActivity;
use Vanilla\Dashboard\AuthenticatorTypeService;
use Vanilla\Dashboard\Controllers\Api\SiteTotalsFilterOpenApi;
use Vanilla\Dashboard\Controllers\LayoutSettingsPageController;
use Vanilla\Dashboard\Controllers\LeavingController;
use Vanilla\Dashboard\Controllers\Pages\AppearancePageController;
use Vanilla\Dashboard\Controllers\Pages\DeveloperProfilesPageController;
use Vanilla\Dashboard\Controllers\Pages\HomePageController;
use Vanilla\Dashboard\Events\AccessDeniedEvent;
use Vanilla\Dashboard\Events\AddonToggledEvent;
use Vanilla\Dashboard\Events\AiSuggestionAccessEvent;
use Vanilla\Dashboard\Events\ConfigurationChangeEvent;
use Vanilla\Dashboard\Events\DashboardAccessEvent;
use Vanilla\Dashboard\Events\DashboardApiAccessEvent;
use Vanilla\Dashboard\Events\DiscussionPostTypeChangeEvent;
use Vanilla\Dashboard\Events\ExportAccessEvent;
use Vanilla\Dashboard\Events\LayoutApplyEvent;
use Vanilla\Dashboard\Events\PasswordResetCompletedEvent;
use Vanilla\Dashboard\Events\PasswordResetEmailSentEvent;
use Vanilla\Dashboard\Events\PasswordResetFailedEvent;
use Vanilla\Dashboard\Events\PasswordResetUserNotFoundEvent;
use Vanilla\Dashboard\Events\SsoSyncFailedEvent;
use Vanilla\Dashboard\Events\ThemeApplyEvent;
use Vanilla\Dashboard\Events\UserRoleModificationEvent;
use Vanilla\Dashboard\Events\UserSignInEvent;
use Vanilla\Dashboard\Events\UserSignInFailedEvent;
use Vanilla\Dashboard\Events\UserSpoofEvent;
use Vanilla\Dashboard\Layout\View\LegacyProfileLayoutView;
use Vanilla\Dashboard\Layout\View\LegacyRegistrationLayoutView;
use Vanilla\Dashboard\Layout\View\LegacySigninLayoutView;
use Vanilla\Dashboard\Models\ActivityService;
use Vanilla\Dashboard\Models\ExternalServiceTracker;
use Vanilla\Dashboard\Models\AiSuggestionSourceMeta;
use Vanilla\Dashboard\Models\AiSuggestionSourceService;
use Vanilla\Dashboard\Models\AttachmentMeta;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Dashboard\Models\CategoryAiSuggestionSource;
use Vanilla\Dashboard\Models\ModerationMeta;
use Vanilla\Dashboard\Models\ModerationMessagesFilterOpenApi;
use Vanilla\Dashboard\Models\NexusAiConversationClient;
use Vanilla\Dashboard\Models\ProfileFieldsOpenApi;
use Vanilla\Dashboard\Models\RolesExpander;
use Vanilla\Dashboard\Models\SsoUsersExpander;
use Vanilla\Dashboard\Models\SuggestedContentMeta;
use Vanilla\Dashboard\Models\UsersExpander;
use Vanilla\Dashboard\Models\UserSiteTotalProvider;
use Vanilla\Dashboard\UserLeaderService;
use Vanilla\Models\CustomPageSiteMetaExtra;
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Layout\LayoutService;
use Vanilla\Layout\Middleware\LayoutPermissionFilterMiddleware;
use Vanilla\Logging\AuditLogService;
use Vanilla\Logging\LogDecorator;
use Vanilla\Models\SiteMeta;
use Vanilla\Models\SiteTotalService;
use Vanilla\OpenAI\OpenAIClient;
use Vanilla\OpenAPIBuilder;
use Vanilla\Premoderation\SuperSpamAuditLog;
use Vanilla\SamlSSO\Events\JsConnectAuditEvent;
use Vanilla\SamlSSO\Events\OAuth2AuditEvent;
use Vanilla\Dashboard\Events\SsoStringAuditEvent;
use Vanilla\Web\APIExpandMiddleware;
use VanillaStaffPageController;
use UserModel;

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
                "/settings/vanilla-staff/profiles" => DeveloperProfilesPageController::class,
                "/settings/vanilla-staff" => VanillaStaffPageController::class,
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
            ->addCall("registerActivity", [ScheduledPostFailedActivity::class])
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
            ->addCall("registerActivity", [EmailDigestActivity::class])
            ->addCall("registerActivity", [AiSuggestionsActivity::class])
            ->addCall("registerActivity", [EscalationActivity::class])
            ->addCall("registerActivity", [MyEscalationActivity::class])
            ->addCall("registerActivity", [ReportActivity::class]);

        $container
            ->rule(EventProviderService::class)
            ->addCall("registerEventProvider", [new Reference(PageViewEventProvider::class)])
            ->addCall("registerEventProvider", [new Reference(SearchAllEventProvider::class)])
            ->addCall("registerEventProvider", [new Reference(SearchPlacesEventProvider::class)])
            ->addCall("registerEventProvider", [new Reference(SearchMembersEventProvider::class)])
            ->addCall("registerEventProvider", [new Reference(ExternalNavigationEventProvider::class)]);

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
            ->addCall("addFilter", [new Reference(NotificationPreferencesFilterOpenApi::class)])
            ->addCall("addFilter", [new Reference(DraftSchedulingOpenApi::class)]);

        $container->rule(OpenAPIBuilder::class)->addCall("addFilter", [new Reference(AuthenticatorTypeService::class)]);

        $container->rule(UserLeaderService::class)->setShared(true);

        $container->rule(OpenAPIBuilder::class)->addCall("addFilter", [new Reference(ProfileFieldsOpenApi::class)]);

        $container
            ->rule(SpoofMiddleware::class)
            ->setConstructorArgs([
                new Reference(Gdn_Session::class),
                new Reference("@smart-id-middleware"),
                new Reference(LogDecorator::class),
                new Reference(UserModel::class),
            ]);

        $container->rule(Dispatcher::class)->addCall("addMiddleware", [new Reference(SpoofMiddleware::class)]);

        $container
            ->rule(\Vanilla\OpenAPIBuilder::class)
            ->addCall("addFilter", ["filter" => new Reference(\ReactionsFilterOpenApi::class)]);

        $container->rule(SiteMeta::class)->addCall("addExtra", [new Reference(AttachmentMeta::class)]);

        $container
            ->rule(AuditLogService::class)
            ->addCall("registerEventClasses", [
                [
                    DashboardAccessEvent::class,
                    DashboardApiAccessEvent::class,
                    ExportAccessEvent::class,
                    ResourceEvent::class,
                    UserSpoofEvent::class,
                    UserSignInEvent::class,
                    AccessDeniedEvent::class,
                    UserSignInFailedEvent::class,
                    PasswordResetFailedEvent::class,
                    PasswordResetCompletedEvent::class,
                    PasswordResetUserNotFoundEvent::class,
                    PasswordResetEmailSentEvent::class,
                    SsoSyncFailedEvent::class,
                    UserRoleModificationEvent::class,
                    ConfigurationChangeEvent::class,
                    AddonToggledEvent::class,
                    ThemeApplyEvent::class,
                    LayoutApplyEvent::class,
                    OAuth2AuditEvent::class,
                    JsConnectAuditEvent::class,
                    AiSuggestionAccessEvent::class,
                    SuperSpamAuditLog::class,
                    SsoStringAuditEvent::class,
                    DiscussionPostTypeChangeEvent::class,
                ],
            ]);

        //Automation container rules
        $container->rule(AutomationRuleModel::class)->setShared(true);

        $container
            ->rule(AiSuggestionSourceService::class)
            ->addCall("registerSuggestionSource", [new Reference(CategoryAiSuggestionSource::class)]);
        $container->rule(SiteMeta::class)->addCall("addExtra", [new Reference(AiSuggestionSourceMeta::class)]);
        $container->rule(SiteMeta::class)->addCall("addExtra", [new Reference(ModerationMeta::class)]);
        $container->rule(SiteMeta::class)->addCall("addExtra", [new Reference(SuggestedContentMeta::class)]);
        $container->rule(SiteMeta::class)->addCall("addExtra", [new Reference(CustomPageSiteMetaExtra::class)]);

        $container
            ->rule(ExternalServiceTracker::class)
            ->setShared(true)
            ->addCall("registerService", [new Reference(OpenAIClient::class)])
            ->addCall("registerService", [new Reference(NexusAiConversationClient::class)]);
    }
}
