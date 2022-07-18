/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { registerWidgetOverviews } from "@dashboard/layout/overview/LayoutOverview";
import { SectionFullWidth } from "@library/layout/SectionFullWidth";
import { SectionOneColumn } from "@library/layout/SectionOneColumn";
import SectionThreeColumns from "@library/layout/ThreeColumnSection";
import SectionTwoColumns from "@library/layout/TwoColumnSection";
import { QuickLinks } from "@library/navigation/QuickLinks";
import { UserSpotlightWidgetPreview } from "@library/userSpotlight/UserSpotlightWidget.preview";
import { CategoriesWidgetPreview } from "@library/categoriesWidget/CategoriesWidget.preview";
import { LeaderboardWidgetPreview } from "@library/leaderboardWidget/LeaderboardWidget.preview";
import { DiscussionsWidgetPreview } from "@library/discussions/DiscussionsWidget.preview";
import { HtmlWidgetPreview } from "@library/htmlWidget/HtmlWidget.preview";
import { RSSWidgetPreview } from "@library/rssWidget/RSSWidget.preview";
import { NewPostMenuPreview } from "@library/newPostMenu/NewPostMenu.preview";
import { BannerWidgetPreview } from "@library/banner/BannerWidget.preview";
import { BannerContentWidgetPreview } from "@library/banner/BannerContentWidget.preview";
import { TabWidgetPreview } from "@library/tabWidget/TabWidget.preview";
import "@library/theming/reset";

registerWidgetOverviews({
    // Sections
    SectionFullWidth,
    SectionOneColumn,
    SectionTwoColumns,
    SectionThreeColumns,
    // Widgets
    QuickLinks, // Quicklinks doesn't need server data so the same widget renders here as on the server.
    UserSpotlightWidget: UserSpotlightWidgetPreview,
    CategoriesWidget: CategoriesWidgetPreview,
    DiscussionsWidget: DiscussionsWidgetPreview,
    LeaderboardWidget: LeaderboardWidgetPreview,
    HtmlWidget: HtmlWidgetPreview,
    RSSWidget: RSSWidgetPreview,
    NewPostMenu: NewPostMenuPreview,
    BannerWidget: BannerWidgetPreview,
    BannerContentWidget: BannerContentWidgetPreview,
    TabWidget: TabWidgetPreview,
});
