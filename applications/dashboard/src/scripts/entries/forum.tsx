/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { onContent, getMeta, onReady } from "@library/utility/appUtils";
import { Route } from "react-router-dom";
import { registerReducer } from "@library/redux/reducerRegistry";
// The forum section needs these legacy scripts that have been moved into the bundled JS so it could be refactored.
// Other sections should not need this yet.
import "@dashboard/legacy";
import { convertAllUserContent, initAllUserContent } from "@library/content";
import SignInPage from "@dashboard/pages/SignInPage";
import PasswordPage from "@dashboard/pages/PasswordPage";
import RecoverPasswordPage from "@dashboard/pages/RecoverPasswordPage";
import NotificationsModel from "@library/features/notifications/NotificationsModel";
import { Router } from "@library/Router";
import { AppContext } from "@library/AppContext";
import { addComponent, addPageComponent } from "@library/utility/componentRegistry";
import { TitleBarHamburger } from "@library/headers/TitleBarHamburger";
import { authReducer } from "@dashboard/auth/authReducer";
import { compatibilityStyles, cssOut } from "@dashboard/compatibilityStyles";
import { applyCompatibilityIcons } from "@dashboard/compatibilityStyles/compatibilityIcons";
import { createBrowserHistory, History } from "history";
import { applySharedPortalContext, mountPortal } from "@vanilla/react-utils";
import { ErrorPage } from "@vanilla/library/src/scripts/errorPages/ErrorComponent";
import { CommunityBanner, CommunityContentBanner } from "@vanilla/library/src/scripts/banner/CommunityBanner";
import { initPageViewTracking } from "@vanilla/library/src/scripts/pageViews/pageViewTracking";
import { enableLegacyAnalyticsTick } from "@vanilla/library/src/scripts/analytics/AnalyticsData";
import { NEW_SEARCH_PAGE_ENABLED } from "@vanilla/library/src/scripts/search/searchConstants";
import { SearchPageRoute } from "@vanilla/library/src/scripts/search/SearchPageRoute";
import { notEmpty, PromiseOrNormalCallback } from "@vanilla/utils";
import { applyCompatibilityUserCards } from "@dashboard/compatibilityStyles/compatibilityUserCards";

onReady(initAllUserContent);
onContent(convertAllUserContent);

// Redux
registerReducer("auth", authReducer);
registerReducer("notifications", new NotificationsModel().reducer);

Router.addRoutes(
    [
        <Route exact path="/authenticate/signin" component={SignInPage} key="signin" />,
        <Route exact path="/authenticate/password" component={PasswordPage} key="password" />,
        <Route exact path="/authenticate/recoverpassword" component={RecoverPasswordPage} key="recover" />,
        NEW_SEARCH_PAGE_ENABLED ? SearchPageRoute.route : null,
    ].filter(notEmpty),
);

applySharedPortalContext(props => {
    return (
        <AppContext variablesOnly errorComponent={ErrorPage}>
            {props.children}
        </AppContext>
    );
});

// Routing
addPageComponent(() => <Router disableDynamicRouting />);

// The community is still very tied into the global.js and legacyAnalyticsTick.json.
enableLegacyAnalyticsTick(true);

// Configure page view tracking
onReady(() => {
    initPageViewTracking(createBrowserHistory());
});

addComponent("title-bar-hamburger", TitleBarHamburger);
addComponent("community-banner", CommunityBanner);
addComponent("community-content-banner", CommunityContentBanner);

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
        onContent(e => {
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
    onNewHTML: e => {
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
    onNewHTML: e => {
        applyCompatibilityUserCards(
            e.target instanceof HTMLElement && e.target.parentElement ? e.target.parentElement : undefined,
        );
    },
});
