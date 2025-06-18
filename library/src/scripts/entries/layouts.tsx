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
import { ContentItemHeader } from "@vanilla/addon-vanilla/contentItem/ContentItemHeader";
import { MetaIcon } from "@library/metas/Metas";
import AiEscalationMetaIcon from "@library/features/discussions/integrations/components/AiEscalationMetaIcon";
import { supportsFrames } from "@library/embeddedContent/IFrameEmbed";
import { ContentItemAttachmentService } from "@vanilla/addon-vanilla/contentItem/ContentItemAttachments.service";
import SearchWidget from "@library/search/SearchWidget";
import { CustomFragmentWidget } from "@library/widgets/CustomFragmentWidget";

if (getMeta("inputFormat.desktop")?.match(/rich2/i)) {
    supportsFrames(true);
}

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
    TitleBar,
    LayoutError,
    SectionFullWidth,
    SectionOneColumn,
    SectionThreeColumns: ThreeColumnSection,
    SectionTwoColumns: TwoColumnSection,
    CustomFragmentWidget,
});

registerLoadableWidgets({
    // Sections
    SectionTwoColumnsEven: () => import("@library/layout/SectionEvenColumns"),
    SectionThreeColumnsEven: () => import("@library/layout/SectionEvenColumns"),
    // Widgets
    BannerWidget: () => import("@library/banner/BannerWidget"),
    BannerContentWidget: () => import("@library/banner/BannerContentWidget"),

    Banner: () => import("@library/banner/Banner"),
    Breadcrumbs: () => import("@library/navigation/Breadcrumbs"),
    DiscussionListModule: () => import("@library/features/discussions/DiscussionListModule"),
    HtmlWidget: () => import("@library/htmlWidget/HtmlWidget"),
    QuickLinks: () => import("@library/navigation/QuickLinks"),
    CategoriesWidget: () => import("@library/categoriesWidget/CategoriesWidget"),
    TagWidget: () => import("@vanilla/addon-vanilla/tag/TagWidget"),
    RSSWidget: () => import("@library/rssWidget/RSSWidget"),
    UserSpotlightWidget: () => import("@library/userSpotlight/UserSpotlightWidget"),
    SiteTotalsWidget: () => import("@library/widgets/SiteTotalsWidget"),
    NewPostMenu: () => import("@library/newPostMenu/NewPostMenu"),
    LeaderboardWidget: () => import("@library/leaderboardWidget/LeaderboardWidget"),
    DiscussionsWidget: () => import("@library/features/discussions/DiscussionsWidget"),
    TabWidget: () => import("@library/tabWidget/TabWidget"),
    CallToActionWidget: () => import("@library/widgets/CallToActionWidget"),
    GuestCallToActionWidget: () => import("@library/widgets/GuestCallToActionWidget"),
    FeaturedCollectionsWidget: () => import("@library/featuredCollections/FeaturedCollectionsWidget"),
    CategoryFollowWidget: () => import("@vanilla/addon-vanilla/categories/CategoryFollowDropdown"),
    SuggestedContentWidget: () => import("@library/suggestedContent/SuggestedContentWidget"),
    SearchWidget: () => import("@library/search/SearchWidget"),
});

// Reducers
logDebug("Register core reducers");
registerReducer("notifications", new NotificationsModel().reducer);
registerReducer("forum", forumReducer);
logDebug("Register homepage handler");
registerLayoutPage("/", () => {
    return {
        layoutViewType: getSiteSection().sectionID.toString() !== "0" ? "subcommunityHome" : "home",
        recordType: "siteSection",
        recordID: getSiteSection().sectionID,
        params: {},
    };
});

SearchContextProvider.setOptionProvider(new CommunitySearchProvider());

// Track custom layout page view events
onPageViewWithContext((event: CustomEvent) => {
    trackPageView(window.location.href, event.detail);
});

trackLink();

function AiAssistantIcon() {
    return <MetaIcon icon="ai-indicator" size="compact" />;
}

if (getMeta("answerSuggestionsEnabled", false)) {
    const aiAssistant = getMeta("aiAssistant");
    const aiAssistantUserID = aiAssistant?.userID;
    ContentItemHeader.registerMetaItem(
        AiAssistantIcon,
        (context) => {
            const { authorID } = context;
            const isAiAssistant = !!authorID && !!aiAssistantUserID && aiAssistantUserID === authorID;
            return isAiAssistant;
        },
        { placement: "author", order: 0 },
    );
}

if (getMeta("featureFlags.escalations.Enabled", false)) {
    ContentItemAttachmentService.registerMetaItem(AiEscalationMetaIcon, (attachment) => {
        return attachment?.escalatedByAi ?? false;
    });
}
