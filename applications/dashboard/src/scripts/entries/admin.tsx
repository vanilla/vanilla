/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useLayoutEffect, useState } from "react";
import { initAllUserContent } from "@library/content";
import { onContent, onReady } from "@library/utility/appUtils";
import { Router } from "@library/Router";
import { AppContext } from "@library/AppContext";
import { addComponent, disableComponentTheming } from "@library/utility/componentRegistry";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";
import { applySharedPortalContext, mountReact } from "@vanilla/react-utils/src";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import "@library/theming/reset";
import { SCROLL_OFFSET_DEFAULTS, ScrollOffsetContext } from "@library/layout/ScrollOffsetContext";
import { registerReducer } from "@library/redux/reducerRegistry";
import { roleReducer } from "@dashboard/roles/roleReducer";
import { themeSettingsReducer } from "@library/theming/themeSettingsReducer";
import { globalCSS, useBodyCSS } from "@library/layout/bodyStyles";
import { applyCompatibilityIcons } from "@dashboard/compatibilityStyles/compatibilityIcons";
import { forumReducer } from "@vanilla/addon-vanilla/redux/reducer";
import { RoleRequestReducer } from "@dashboard/roleRequests/state/roleRequestReducer";
import { mountDashboardTabs } from "@dashboard/forms/mountDashboardTabs";
import { mountDashboardCodeEditors } from "@dashboard/forms/DashboardCodeEditor";
import { TextEditorContextProvider } from "@library/textEditor/TextEditor";
import { VanillaLabsPage } from "@dashboard/pages/VanillaLabsPage";
import { bindToggleChildrenEventListeners } from "@dashboard/settings";
import { LanguageSettingsPage } from "@dashboard/pages/LanguageSettingsPage";
import { escapeHTML } from "@vanilla/dom-utils";
import { getDashboardRoutes } from "@dashboard/dashboardRoutes";
import { dashboardSectionSlice } from "@dashboard/DashboardSectionSlice";
import AdminHeader from "@dashboard/components/AdminHeader";
import ModernEmbedSettings from "@library/embed/ModernEmbedSettings";
import { userProfilesSlice } from "@dashboard/userProfiles/state/UserProfiles.slice";
import { UserProfileSettings } from "@dashboard/userProfiles/UserProfileSettings";
import DashboardAddEditUser from "@dashboard/users/DashboardAddEditUser";

// Expose some new module functions to our old javascript system.
window.escapeHTML = escapeHTML;

addComponent("imageUploadGroup", DashboardImageUploadGroup, { overwrite: true });
addComponent("VanillaLabsPage", VanillaLabsPage);

addComponent("LanguageSettingsPage", LanguageSettingsPage);
addComponent("ModernEmbedSettings", ModernEmbedSettings);

disableComponentTheming();
onContent(() => initAllUserContent());
registerReducer("roles", roleReducer);
registerReducer("themeSettings", themeSettingsReducer);
registerReducer("forum", forumReducer);
registerReducer("roleRequests", RoleRequestReducer);

registerReducer(userProfilesSlice.name, userProfilesSlice.reducer);
addComponent("UserProfileSettings", UserProfileSettings);

applySharedPortalContext((props) => {
    const [navHeight, setNavHeight] = useState(0);

    useBodyCSS();
    useLayoutEffect(() => {
        globalCSS();
        const navbar = document.querySelector(".js-navbar");
        if (navbar) {
            setNavHeight(navbar.getBoundingClientRect().height);
        }
    }, [setNavHeight]);
    return (
        <AppContext variablesOnly errorComponent={ErrorPage}>
            <ScrollOffsetContext.Provider value={{ ...SCROLL_OFFSET_DEFAULTS, scrollOffset: navHeight }}>
                <TextEditorContextProvider>{props.children}</TextEditorContextProvider>
            </ScrollOffsetContext.Provider>
        </AppContext>
    );
});

registerReducer(dashboardSectionSlice.name, dashboardSectionSlice.reducer);

Router.addRoutes(getDashboardRoutes());

// Routing
addComponent("App", () => {
    return <Router disableDynamicRouting />;
});

addComponent("title-bar-hamburger", AdminHeader);

const render = () => {
    const app = document.querySelector("#app") as HTMLElement;

    if (app) {
        mountReact(<Router disableDynamicRouting />, app);
    } else {
        applyCompatibilityIcons();
    }
};
onReady(render);

onContent(mountDashboardTabs);
onContent(mountDashboardCodeEditors);

bindToggleChildrenEventListeners();
addComponent("DashboardAddEditUser", DashboardAddEditUser);
