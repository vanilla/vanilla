/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { registerWidgetOverviews } from "@dashboard/layout/overview/LayoutOverview";
import { SectionFullWidth } from "@library/layout/SectionFullWidth";
import { SectionOneColumn } from "@library/layout/SectionOneColumn";
import ThreeColumnSection from "@library/layout/ThreeColumnSection";
import TwoColumnSection from "@library/layout/TwoColumnSection";
import { QuickLinks } from "@library/navigation/QuickLinks";
import { UserSpotlightWidgetPreview } from "@library/userSpotlight/UserSpotlightWidget.preview";
import { CategoriesWidgetPreview } from "@library/categoriesWidget/CategoriesWidget.preview";
import { LeaderboardWidgetPreview } from "@library/leaderboardWidget/LeaderboardWidget.preview";
import { DiscussionListModulePreview } from "@library/discussions/DiscussionListModuleWidget.preview";
import { HtmlWidgetPreview } from "@library/htmlWidget/HtmlWidget.preview";

registerWidgetOverviews({
    SectionFullWidth,
    SectionOneColumn,
    // Todo fix these names.
    SectionTwoColumns: TwoColumnSection,
    SectionThreeColumns: ThreeColumnSection,
    QuickLinks, // Quicklinks doesn't need server data so the same widget renders here as on the server.
    UserSpotlightWidget: UserSpotlightWidgetPreview,
    CategoriesWidget: CategoriesWidgetPreview,
    DiscussionListModule: DiscussionListModulePreview,
    LeaderboardWidget: LeaderboardWidgetPreview,
    HtmlWidget: HtmlWidgetPreview,
});
