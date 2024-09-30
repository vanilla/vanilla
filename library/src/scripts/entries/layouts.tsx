/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

// Imports
import { AppContext } from "@library/AppContext";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { registerLayoutPage } from "@library/features/Layout/LayoutPage.registry";
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
import { getMeta, getSiteSection } from "@library/utility/appUtils";
import { logDebug } from "@vanilla/utils";
import { SearchContextProvider } from "@library/contexts/SearchContext";
import { CommunitySearchProvider } from "@vanilla/addon-vanilla/search/CommunitySearchProvider";
import { onPageViewWithContext } from "@library/analytics/AnalyticsData";
import { trackLink, trackPageView } from "@library/analytics/tracking";
import SectionFullWidth from "@library/layout/SectionFullWidth";
import SectionOneColumn from "@library/layout/SectionOneColumn";
import ThreeColumnSection from "@library/layout/ThreeColumnSection";
import TwoColumnSection from "@library/layout/TwoColumnSection";
import { ThreadItemHeader } from "@vanilla/addon-vanilla/thread/ThreadItemHeader";
import { MetaIcon } from "@library/metas/Metas";
import AiEscalationMetaIcon from "@library/features/discussions/integrations/components/AiEscalationMetaIcon";
import { DiscussionAttachment } from "@vanilla/addon-vanilla/thread/DiscussionAttachmentsAsset";

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

                <Router useLayoutRouting>
                    <TitleBar />
                </Router>
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
    SectionFullWidth,
    SectionOneColumn,
    SectionThreeColumns: ThreeColumnSection,
    SectionTwoColumns: TwoColumnSection,
});

registerLoadableWidgets({
    // Sections
    SectionTwoColumnsEven: () =>
        import(/* webpackChunkName: "sections/SectionEvenColumns" */ "@library/layout/SectionEvenColumns"),
    SectionThreeColumnsEven: () =>
        import(/* webpackChunkName: "sections/SectionEvenColumns" */ "@library/layout/SectionEvenColumns"),
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
        import(/* webpackChunkName: "widgets/SiteTotalsWidget" */ "@library/siteTotals/SiteTotalsWidget"),
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
    CategoryFollowWidget: () =>
        import(
            /* webpackChunkName: "widgets/CategoryFollowWidget" */ "@vanilla/addon-vanilla/categories/CategoryFollowDropdown"
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
        layoutViewType: getSiteSection().sectionID.toString() !== "0" ? "subcommunityHome" : "home",
        recordType: "siteSection",
        recordID: getSiteSection().sectionID,
        params: {
            siteSectionID: getSiteSection().sectionID.toString(),
            locale: getSiteSection().contentLocale,
        },
    };
});

SearchContextProvider.setOptionProvider(new CommunitySearchProvider());

// Track custom layout page view events
onPageViewWithContext((event: CustomEvent) => {
    trackPageView(window.location.href, event.detail);
});

trackLink();

function ThreadItemHeaderAiAssistantIcon() {
    return <MetaIcon icon="ai-sparkle-monocolor" size="compact" />;
}

if (getMeta("answerSuggestionsEnabled", false)) {
    const aiAssistant = getMeta("aiAssistant");
    const aiAssistantUserID = aiAssistant?.userID;
    ThreadItemHeader.registerMetaItem(
        ThreadItemHeaderAiAssistantIcon,
        (context) => {
            const { authorID } = context;
            const isAiAssistant = !!authorID && !!aiAssistantUserID && aiAssistantUserID === authorID;
            return isAiAssistant;
        },
        { placement: "author", order: 0 },
    );
}

if (getMeta("featureFlags.escalations.Enabled", false)) {
    DiscussionAttachment.registerMetaItem(AiEscalationMetaIcon, (attachment) => {
        return attachment?.escalatedByAi ?? false;
    });
}
