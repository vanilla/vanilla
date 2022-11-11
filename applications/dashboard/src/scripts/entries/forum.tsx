/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { onContent, getMeta, onReady } from "@library/utility/appUtils";
import { registerReducer } from "@library/redux/reducerRegistry";
// The forum section needs these legacy scripts that have been moved into the bundled JS so it could be refactored.
// Other sections should not need this yet.
import "@dashboard/legacy";
import { convertAllUserContent, initAllUserContent } from "@library/content";
import NotificationsModel from "@library/features/notifications/NotificationsModel";
import { Router } from "@library/Router";
import { AppContext } from "@library/AppContext";
import { addComponent, addPageComponent } from "@library/utility/componentRegistry";
import { TitleBarHamburger } from "@library/headers/TitleBarHamburger";
import { authReducer } from "@dashboard/auth/authReducer";
import { compatibilityStyles } from "@dashboard/compatibilityStyles";
import { applyCompatibilityIcons } from "@dashboard/compatibilityStyles/compatibilityIcons";
import { createBrowserHistory } from "history";
import { applySharedPortalContext } from "@vanilla/react-utils";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { CommunityBanner, CommunityContentBanner } from "@library/banner/CommunityBanner";
import { initPageViewTracking } from "@library/pageViews/pageViewTracking";
import { NEW_SEARCH_PAGE_ENABLED } from "@library/search/searchConstants";
import { SearchPageRoute } from "@library/search/SearchPageRoute";
import { notEmpty } from "@vanilla/utils";
import { applyCompatibilityUserCards } from "@library/features/userCard/UserCard.compat";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";
import { SectionProvider } from "@library/layout/LayoutContext";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";
// This is a misleading place for the component to live. Its used in the /profile/preferences/[USER_ID]
import { CategoryNotificationPreferences } from "@dashboard/components/CategoryNotificationPreferences";
import { userProfilesSlice } from "@dashboard/userProfiles/state/UserProfiles.slice";
import { TokensInputInLegacyForm } from "@library/forms/select/TokensInputInLegacyForm";

onReady(initAllUserContent);
onContent(convertAllUserContent);

addComponent("imageUploadGroup", DashboardImageUploadGroup, { overwrite: true });
addComponent("tokensInputInLegacyForm", TokensInputInLegacyForm, { overwrite: true });

// Redux
registerReducer("auth", authReducer);
registerReducer("notifications", new NotificationsModel().reducer);

Router.addRoutes([NEW_SEARCH_PAGE_ENABLED ? SearchPageRoute.route : null].filter(notEmpty));

applySharedPortalContext((props) => {
    return (
        <AppContext variablesOnly noWrap errorComponent={ErrorPage}>
            <SectionProvider type={SectionTypes.THREE_COLUMNS}>{props.children}</SectionProvider>
        </AppContext>
    );
});

// Routing
addPageComponent(() => <Router disableDynamicRouting />);

// Configure page view tracking
onReady(() => {
    initPageViewTracking(createBrowserHistory());
});

addComponent("title-bar-hamburger", TitleBarHamburger);
addComponent("community-banner", CommunityBanner, { overwrite: true });
addComponent("community-content-banner", CommunityContentBanner, { overwrite: true });

const applyReactElementsInForum = (props: {
    metaPermissionKey: string;
    onInitialLoad: () => void;
    onNewHTML: (e) => void;
}) => {
    const { metaPermissionKey, onInitialLoad, onNewHTML } = props;

    if (getMeta(metaPermissionKey, false)) {
        onReady(() => {
            onInitialLoad();
        });
        onContent((e) => {
            onNewHTML(e);
        });
    }
};

applyReactElementsInForum({
    metaPermissionKey: "themeFeatures.DataDrivenTheme",
    onInitialLoad: () => {
        compatibilityStyles();
        applyCompatibilityIcons();
    },
    onNewHTML: (e) => {
        applyCompatibilityIcons(
            e.target instanceof HTMLElement && e.target.parentElement ? e.target.parentElement : undefined,
        );
    },
});

applyReactElementsInForum({
    metaPermissionKey: "themeFeatures.UserCards",
    onInitialLoad: () => {
        applyCompatibilityUserCards();
    },
    onNewHTML: (e) => {
        applyCompatibilityUserCards(
            e.target instanceof HTMLElement && e.target.parentElement ? e.target.parentElement : undefined,
        );
    },
});

addComponent("CategoryNotificationPreferences", CategoryNotificationPreferences, { overwrite: true });

// For aboot page
registerReducer(userProfilesSlice.name, userProfilesSlice.reducer);
