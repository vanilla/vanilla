/**
 * @copyright 2009-2019 Vanilla Forums Inc.
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
import { SCROLL_OFFSET_DEFAULTS, ScrollOffsetContext } from "@vanilla/library/src/scripts/layout/ScrollOffsetContext";
import { registerReducer } from "@vanilla/library/src/scripts/redux/reducerRegistry";
import { roleReducer } from "@dashboard/roles/roleReducer";
import { themeSettingsReducer } from "@library/theming/themeSettingsReducer";
import { bodyCSS } from "@vanilla/library/src/scripts/layout/bodyStyles";
import { applyCompatibilityIcons } from "@dashboard/compatibilityStyles/compatibilityIcons";
import { forumReducer } from "@vanilla/addon-vanilla/redux/reducer";
import { Route } from "react-router-dom";
import RoleApplicationsPage from "@dashboard/roleRequests/pages/RoleApplicationsPage";
import { RoleRequestReducer } from "@dashboard/roleRequests/state/roleRequestReducer";
import { mountDashboardTabs } from "@dashboard/forms/mountDashboardTabs";
import { mountDashboardCodeEditors } from "@dashboard/forms/DashboardCodeEditor";
import { TextEditorContextProvider } from "@vanilla/library/src/scripts/textEditor/TextEditor";
import { WidgetFormGenerator } from "@dashboard/widgets/WidgetFormGenerator";

addComponent("imageUploadGroup", DashboardImageUploadGroup, { overwrite: true });

disableComponentTheming();
onContent(() => initAllUserContent());
registerReducer("roles", roleReducer);
registerReducer("themeSettings", themeSettingsReducer);
registerReducer("forum", forumReducer);
registerReducer("roleRequests", RoleRequestReducer);

applySharedPortalContext((props) => {
    const [navHeight, setNavHeight] = useState(0);

    useLayoutEffect(() => {
        bodyCSS();
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

Router.addRoutes([
    <Route exact path="/manage/requests/role-applications" component={RoleApplicationsPage} key="roleApplications" />,
]);

// Routing
addComponent("App", () => {
    return <Router disableDynamicRouting />;
});

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
