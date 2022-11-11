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
import { SiteTotalsWidgetPreview } from "@library/siteTotalsWidget/SiteTotalsWidget.preview";
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
import CallToActionWidget from "@library/callToAction/CallToActionWidget";
import GuestCallToActionWidget from "@library/callToAction/GuestCallToActionWidget";
import { FeaturedCollectionsWidgetPreview } from "@library/featuredCollections/FeaturedCollectionsWidget.preview";

registerWidgetOverviews({
    // Sections
    SectionFullWidth,
    SectionOneColumn,
    SectionTwoColumns,
    SectionThreeColumns,
    // Widgets
    QuickLinks, // Quicklinks doesn't need server data so the same widget renders here as on the server.
    UserSpotlightWidget: UserSpotlightWidgetPreview,
    SiteTotalsWidget: SiteTotalsWidgetPreview,
    CategoriesWidget: CategoriesWidgetPreview,
    DiscussionsWidget: DiscussionsWidgetPreview,
    LeaderboardWidget: LeaderboardWidgetPreview,
    HtmlWidget: HtmlWidgetPreview,
    RSSWidget: RSSWidgetPreview,
    NewPostMenu: NewPostMenuPreview,
    BannerWidget: BannerWidgetPreview,
    BannerContentWidget: BannerContentWidgetPreview,
    TabWidget: TabWidgetPreview,
    CallToActionWidget, // Just like Quicklinks, CTA data is also server free.
    GuestCallToActionWidget, // See comment above
    FeaturedCollectionsWidget: FeaturedCollectionsWidgetPreview,
});
