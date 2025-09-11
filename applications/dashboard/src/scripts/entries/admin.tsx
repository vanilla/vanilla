/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useLayoutEffect, useState } from "react";
import { initAllUserContent } from "@library/content";
import { onContent, onReady } from "@library/utility/appUtils";
import { Router } from "@library/Router";
import { AppContext } from "@library/AppContext";
import { addComponent } from "@library/utility/componentRegistry";
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
import { TextEditorContextProvider } from "@library/textEditor/MonacoEditor";
import { VanillaLabsPage } from "@dashboard/pages/VanillaLabsPage";
import { bindToggleChildrenEventListeners } from "@dashboard/settings";
import { LanguageSettingsPage } from "@dashboard/pages/LanguageSettingsPage";
import { escapeHTML } from "@vanilla/dom-utils";
import { getDashboardRoutes } from "@dashboard/dashboardRoutes";
import AdminHeader from "@dashboard/components/AdminHeader";
import ModernEmbedSettings from "@library/embed/ModernEmbedSettings";
import { userProfilesSlice } from "@dashboard/userProfiles/state/UserProfiles.slice";
import { UserProfileSettings } from "@dashboard/userProfiles/UserProfileSettings";
import { UserPreferences } from "@dashboard/userPreferences/UserPreferences";
import { EmailSettings } from "@dashboard/emailSettings/notificationSettings/EmailSettings";
import { DigestSettings } from "@dashboard/emailSettings/digestSettings/DigestSettings";
import DashboardAddEditUser from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser";
import UserManagementSpoof from "@dashboard/users/userManagement/UserManagementSpoof";
import ToggleInputInLegacyForm from "@library/forms/ToggleInputInLegacyForm";
import { ExternalSearchSettingsPage } from "@dashboard/pages/ExternalSearchSettingsPage";
import { MemoryRouter } from "react-router";
import { RouterRegistry } from "@library/Router.registry";
import { AISuggestions } from "@dashboard/aiSuggestions/AISuggestions";
import { InterestsSettings } from "@dashboard/interestsSettings/InterestsSettings";
import GenerateDataExportURL from "@dashboard/components/GenerateDataExportURL";
import Permission from "@library/features/users/Permission";
import { AdminAssistant } from "@library/features/adminAssistant/AdminAssistant";

// Expose some new module functions to our old javascript system.
declare global {
    interface Window {
        escapeHTML: (html: string) => string;
    }
}
window.escapeHTML = escapeHTML;

addComponent("imageUploadGroup", DashboardImageUploadGroup, { overwrite: true });
addComponent("VanillaLabsPage", VanillaLabsPage);

addComponent("LanguageSettingsPage", LanguageSettingsPage);
addComponent("ModernEmbedSettings", ModernEmbedSettings);

addComponent("UserManagementSpoof", UserManagementSpoof);

onContent(() => initAllUserContent());
registerReducer("roles", roleReducer);
registerReducer("themeSettings", themeSettingsReducer);
registerReducer("forum", forumReducer);
registerReducer("roleRequests", RoleRequestReducer);

registerReducer(userProfilesSlice.name, userProfilesSlice.reducer);
addComponent("UserProfileSettings", UserProfileSettings);
addComponent("UserPreferences", UserPreferences);
addComponent("EmailSettings", EmailSettings);
addComponent("DigestSettings", DigestSettings);
addComponent("toggleInputInLegacyForm", ToggleInputInLegacyForm);

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
        <AppContext variablesOnly errorComponent={<ErrorPage />}>
            <ScrollOffsetContext.Provider value={{ ...SCROLL_OFFSET_DEFAULTS, scrollOffset: navHeight }}>
                <TextEditorContextProvider>{props.children}</TextEditorContextProvider>
            </ScrollOffsetContext.Provider>
            <Permission permission={"site.manage"}>
                <AdminAssistant />
            </Permission>
        </AppContext>
    );
});

RouterRegistry.addRoutes(getDashboardRoutes());

// Routing
addComponent("App", () => {
    return <Router disableDynamicRouting />;
});

const WrappedAdminHeader = () => {
    return (
        <MemoryRouter>
            <AdminHeader />
        </MemoryRouter>
    );
};

addComponent("title-bar-hamburger", WrappedAdminHeader);

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
addComponent("DashboardAddEditUser", DashboardAddEditUser); //this should be gone when we entirely switch into new react user management page
addComponent("UserManagementPage", () => {
    return <Router sectionRoots={["/dashboard/user"]} />;
});
addComponent("AuditLogsPage", () => {
    return <Router sectionRoots={["/dashboard/settings/audit-logs"]} />;
});
addComponent("ExternalSearchSettingsPage", ExternalSearchSettingsPage);

addComponent("automationRules", () => {
    return <Router sectionRoots={["/settings/automation-rules"]} />;
});

addComponent("postTypes", () => {
    return <Router sectionRoots={["/settings/post-types"]} />;
});

addComponent("taggingSettings", () => {
    return <Router sectionRoots={["/settings/tagging"]} />;
});

addComponent("aiSuggestions", AISuggestions);
addComponent("interests", InterestsSettings);
addComponent("GenerateDataExportURL", GenerateDataExportURL);
