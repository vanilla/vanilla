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

import { Router } from "@library/Router";
import { addPageComponent, registerLoadableWidgets, registerWidgets } from "@library/utility/componentRegistry";
import { applySharedPortalContext } from "@vanilla/react-utils";
import { registerReducer } from "@library/redux/reducerRegistry";
import { layoutSlice } from "@library/features/Layout/LayoutPage.slice";
import { Backgrounds } from "@library/layout/Backgrounds";

// Theming Reset
import "@library/theming/reset";
import { forumReducer } from "@vanilla/addon-vanilla/redux/reducer";
import NotificationsModel from "@library/features/notifications/NotificationsModel";
import { LayoutError } from "@library/features/Layout/LayoutErrorBoundary";
import { HamburgerMenuContextProvider } from "@library/contexts/HamburgerMenuContext";
import { getSiteSection } from "@library/utility/appUtils";
import { logDebug } from "@vanilla/utils";
import { SearchContextProvider } from "@library/contexts/SearchContext";
import { CommunitySearchProvider } from "@vanilla/addon-vanilla/search/CommunitySearchProvider";
import { AnalyticsData, onPageViewWithContext } from "@library/analytics/AnalyticsData";
import { trackPageView } from "@library/analytics/tracking";

// App Setup
logDebug("Boot layout app");
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
            <HamburgerMenuContextProvider>
                <Backgrounds />
                <TitleBar />
                <AnalyticsData uniqueKey={"customLayoutPage"} />
                <Router useLayoutRouting />
            </HamburgerMenuContextProvider>
        </>
    );
}

logDebug("Register layout app page");
addPageComponent(LayoutApp);

// Widgets
logDebug("Register core widgets");
registerWidgets({
    LayoutError,
});

registerLoadableWidgets({
    // Sections
    SectionFullWidth: () =>
        import(/* webpackChunkName: "sections/SectionFullWidth" */ "@library/layout/SectionFullWidth"),
    SectionOneColumn: () =>
        import(/* webpackChunkName: "sections/SectionOneColumn" */ "@library/layout/SectionOneColumn"),
    SectionTwoColumns: () =>
        import(/* webpackChunkName: "sections/SectionTwoColumns" */ "@library/layout/TwoColumnSection"),
    SectionThreeColumns: () =>
        import(/* webpackChunkName: "sections/SectionThreeColumns" */ "@library/layout/ThreeColumnSection"),
    // Widgets
    BannerWidget: () => import(/* webpackChunkName: "widgets/BannerWidget" */ "@library/banner/BannerWidget"),
    BannerContentWidget: () =>
        import(/* webpackChunkName: "widgets/BannerContentWidget" */ "@library/banner/BannerContentWidget"),

    Banner: () => import(/* webpackChunkName: "widgets/Banner" */ "@library/banner/Banner"),
    Breadcrumbs: () => import(/* webpackChunkName: "widgets/Breadcrumbs" */ "@library/navigation/Breadcrumbs"),
    DiscussionListModule: () =>
        import(
            /* webpackChunkName: "widgets/DiscussionListModule" */ "@library/features/discussions/DiscussionListModule"
        ),
    HtmlWidget: () => import(/* webpackChunkName: "widgets/HtmlWidget" */ "@library/htmlWidget/HtmlWidget"),
    QuickLinks: () => import(/* webpackChunkName: "widgets/QuickLinks" */ "@library/navigation/QuickLinks"),
    CategoriesWidget: () =>
        import(/* webpackChunkName: "widgets/CategoriesWidget" */ "@library/categoriesWidget/CategoriesWidget"),
    TagWidget: () => import(/* webpackChunkName: "widgets/TagWidget" */ "@vanilla/addon-vanilla/tag/TagWidget"),
    RSSWidget: () => import(/* webpackChunkName: "widgets/RSSWidget" */ "@library/rssWidget/RSSWidget"),
    UserSpotlightWidget: () =>
        import(/* webpackChunkName: "widgets/UserSpotlightWidget" */ "@library/userSpotlight/UserSpotlightWidget"),
    SiteTotalsWidget: () =>
        import(/* webpackChunkName: "widgets/SiteTotalsWidget" */ "@library/siteTotalsWidget/SiteTotalsWidget"),
    NewPostMenu: () => import(/* webpackChunkName: "widgets/NewPostMenu" */ "@library/newPostMenu/NewPostMenu"),
    LeaderboardWidget: () =>
        import(/* webpackChunkName: "widgets/LeaderboardWidget" */ "@library/leaderboardWidget/LeaderboardWidget"),
    DiscussionsWidget: () =>
        import(/* webpackChunkName: "widgets/DiscussionsWidget" */ "@library/features/discussions/DiscussionsWidget"),
    TabWidget: () => import(/* webpackChunkName: "widgets/TabWidget" */ "@library/tabWidget/TabWidget"),
    CallToActionWidget: () =>
        import(/* webpackChunkName: "widgets/CallToActionWidget" */ "@library/callToAction/CallToActionWidget"),
    GuestCallToActionWidget: () =>
        import(
            /* webpackChunkName: "widgets/GuestCallToActionWidget" */ "@library/callToAction/GuestCallToActionWidget"
        ),
    FeaturedCollectionsWidget: () =>
        import(
            /* webpackChunkName: "widgets/FeaturedCollectionsWidget" */ "@library/featuredCollections/FeaturedCollectionsWidget"
        ),
});

// Reducers
logDebug("Register core reducers");
registerReducer("notifications", new NotificationsModel().reducer);
registerReducer("forum", forumReducer);
registerReducer(layoutSlice.name, layoutSlice.reducer);

logDebug("Register homepage handler");
registerLayoutPage("/", () => {
    return {
        layoutViewType: "home",
        recordType: "siteSection",
        recordID: getSiteSection().sectionID,
        params: {
            siteSectionID: getSiteSection().sectionID,
            locale: getSiteSection().contentLocale,
        },
    };
});

SearchContextProvider.setOptionProvider(new CommunitySearchProvider());

// Track custom layout page view events
onPageViewWithContext((event: CustomEvent) => {
    trackPageView(window.location.href, event.detail);
});
