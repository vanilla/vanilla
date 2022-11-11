<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Container\Reference;
use Vanilla\Community\RSSModule;
use Vanilla\Community\SearchWidgetModule;
use Vanilla\Dashboard\UserLeaderService;
use Vanilla\Dashboard\UserPointsModel;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\EmbeddedContent\Factories\CommentEmbedFactory;
use Vanilla\Forum\EmbeddedContent\Factories\DiscussionEmbedFactory;
use Garden\Container;
use Vanilla\Forum\Models\ForumQuickLinksProvider;
use Vanilla\Forum\Modules\AnnouncementWidgetModule;
use Vanilla\Forum\Modules\DiscussionListTabFactoryAbstract;
use Vanilla\Forum\Modules\DiscussionWidgetModule;
use Vanilla\Forum\Search\CommentSearchType;
use Vanilla\Forum\Search\DiscussionSearchType;
use Vanilla\Forums\Modules\DiscussionTabFactory;
use Vanilla\Search\SearchTypeCollectorInterface;
use Vanilla\Theme\VariableProviders\QuickLinksVariableProvider;
use Vanilla\Widgets\SimpleTabWidgetFactory;
use Vanilla\Widgets\TabWidgetTabService;
use Vanilla\Widgets\WidgetService;

Gdn::getContainer()
    ->rule(EmbedService::class)
    ->addCall("registerFactory", [
        "embedFactory" => new Container\Reference(DiscussionEmbedFactory::class),
        "priority" => EmbedService::PRIORITY_NORMAL,
    ])
    ->addCall("registerFactory", [
        "embedFactory" => new Container\Reference(CommentEmbedFactory::class),
        "priority" => EmbedService::PRIORITY_NORMAL,
    ])

    ->rule(\Vanilla\Navigation\BreadcrumbModel::class)
    ->addCall("addProvider", [new Reference(\Vanilla\Forum\Navigation\ForumBreadcrumbProvider::class)])

    // Search.
    ->rule(SearchTypeCollectorInterface::class)
    ->addCall("registerSearchType", [new Reference(DiscussionSearchType::class)])
    ->addCall("registerSearchType", [new Reference(CommentSearchType::class)])

    ->rule(WidgetService::class)
    ->addCall("registerWidget", [\Vanilla\Community\CategoriesModule::class])
    ->addCall("registerWidget", [\Vanilla\Community\UserSpotlightModule::class])
    ->addCall("registerWidget", [\Vanilla\Forum\Modules\DiscussionWidgetModule::class])
    ->addCall("registerWidget", [\Vanilla\Forum\Modules\AnnouncementWidgetModule::class])
    ->addCall("registerWidget", [RSSModule::class])
    ->rule(TabWidgetTabService::class)
    ->addCall("registerTabFactory", [DiscussionTabFactory::getRecentReference()])
    ->addCall("registerTabFactory", [DiscussionTabFactory::getTrendingReference()])
    ->addCall("registerTabFactory", [DiscussionTabFactory::getTopReference()])
    ->addCall("registerTabFactory", [DiscussionTabFactory::getAnnouncedReference()])
    ->rule(QuickLinksVariableProvider::class)
    ->addCall("addQuickLinkProvider", [new Reference(ForumQuickLinksProvider::class)])
    ->rule(PermissionModel::class)
    ->addCall("addJunctionModel", ["Category", new Reference(CategoryModel::class)]);

if (Gdn::config("Tagging.Discussions.Enabled", false)) {
    Gdn::getContainer()
        ->rule(WidgetService::class)
        ->addCall("registerWidget", [TagModule::class]);
}

if (FeatureFlagHelper::featureEnabled("SearchWidget")) {
    Gdn::getContainer()
        ->rule(WidgetService::class)
        ->addCall("registerWidget", [SearchWidgetModule::class]);
}
