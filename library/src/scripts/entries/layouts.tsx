/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

// Imports
import React from "react";
import { AppContext } from "@library/AppContext";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { registerLayoutPage } from "@library/features/Layout/LayoutPage";
import TitleBar from "@library/headers/TitleBar";
import { HtmlWidget } from "@library/layout/HtmlWidget";
import ThreeColumnSection from "@library/layout/ThreeColumnSection";
import TwoColumnSection from "@library/layout/TwoColumnSection";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import { Router } from "@library/Router";
import { addPageComponent, registerWidgets } from "@library/utility/componentRegistry";
import { applySharedPortalContext } from "@vanilla/react-utils";
import { registerReducer } from "@library/redux/reducerRegistry";
import { layoutSlice } from "@library/features/Layout/LayoutPage.slice";
import { Backgrounds } from "@library/layout/Backgrounds";

// Theming Reset
import "@library/theming/reset";
import { QuickLinks } from "@library/navigation/QuickLinks";
import { LeaderboardWidget } from "@library/leaderboardWidget/LeaderboardWidget";
import { DiscussionListModule } from "@library/features/discussions/DiscussionListModule";
import { CategoriesWidget } from "@library/widgets/CategoriesWidget";
import { SectionOneColumn } from "@library/layout/SectionOneColumn";
import { themeSettingsReducer } from "@library/theming/themeSettingsReducer";
import { forumReducer } from "@vanilla/addon-vanilla/redux/reducer";
import { themePreviewToastReducer } from "@library/features/toaster/themePreview/ThemePreviewToastReducer";
import NotificationsModel from "@library/features/notifications/NotificationsModel";
import TagWidget from "@vanilla/addon-vanilla/tag/TagWidget";
import { RSSWidget } from "@library/rssWidget/RSSWidget";
import { UserSpotlightWidget } from "@library/userSpotlight/UserSpotlightWidget";
import ArticleArticlesWidget from "@knowledge/components/ArticleArticlesWidget";
import Banner from "@library/banner/Banner";
import { SectionFullWidth } from "@library/layout/SectionFullWidth";

// App Setup
applySharedPortalContext((props) => {
    return (
        <AppContext noWrap errorComponent={<ErrorPage />}>
            {props.children}
        </AppContext>
    );
});

// App setup.
function LayoutApp() {
    return (
        <>
            <Backgrounds />
            <TitleBar />
            <Router />
        </>
    );
}

addPageComponent(LayoutApp);

// Widgets
registerWidgets({
    SectionFullWidth,
    SectionOneColumn,
    // Todo fix these names.
    SectionTwoColumns: TwoColumnSection,
    SectionThreeColumns: ThreeColumnSection,
    Breadcrumbs,
    HtmlWidget,
    QuickLinks,
    CategoriesWidget,
    LeaderboardWidget,
    DiscussionListModule,
    TagWidget,
    RSSWidget,
    UserSpotlightWidget,
    ArticleArticlesWidget,
    Banner,
});

// Reducers
registerReducer("notifications", new NotificationsModel().reducer);
registerReducer("forum", forumReducer);
registerReducer(layoutSlice.name, layoutSlice.reducer);

// Route registration
registerLayoutPage("/", () => {
    return {
        layoutViewType: "home",
        params: {},
    };
});
