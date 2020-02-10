/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { initAllUserContent } from "@library/content";
import { onContent, onReady } from "@library/utility/appUtils";
import { Router } from "@library/Router";
import { AppContext } from "@library/AppContext";
import { addComponent, disableComponentTheming } from "@library/utility/componentRegistry";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";
import { mountReact, applySharedPortalContext } from "@vanilla/react-utils/src";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import "@library/theming/reset";

addComponent("imageUploadGroup", DashboardImageUploadGroup, { overwrite: true });

disableComponentTheming();
onContent(() => initAllUserContent());

applySharedPortalContext(props => {
    return (
        <AppContext noTheme errorComponent={ErrorPage}>
            {props.children}
        </AppContext>
    );
});

// Routing
addComponent("App", () => (
    <AppContext noTheme>
        <Router disableDynamicRouting />
    </AppContext>
));

const render = () => {
    const app = document.querySelector("#app") as HTMLElement;

    if (app) {
        mountReact(
            // Error component is set as null until we can refactor a non-kb specific Error page.
            <AppContext errorComponent={<ErrorPage /> || null}>
                <Router disableDynamicRouting />
            </AppContext>,
            app,
        );
    }
};
onReady(render);
