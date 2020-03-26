/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useLayoutEffect } from "react";
import { initAllUserContent } from "@library/content";
import { onContent, onReady } from "@library/utility/appUtils";
import { Router } from "@library/Router";
import { AppContext } from "@library/AppContext";
import { addComponent, disableComponentTheming } from "@library/utility/componentRegistry";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";
import { mountReact, applySharedPortalContext } from "@vanilla/react-utils/src";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import "@library/theming/reset";
import { ScrollOffsetContext, SCROLL_OFFSET_DEFAULTS } from "@vanilla/library/src/scripts/layout/ScrollOffsetContext";
import { registerReducer } from "@vanilla/library/src/scripts/redux/reducerRegistry";
import { roleReducer } from "@dashboard/roles/roleReducer";

addComponent("imageUploadGroup", DashboardImageUploadGroup, { overwrite: true });

disableComponentTheming();
onContent(() => initAllUserContent());
registerReducer("roles", roleReducer);

applySharedPortalContext(props => {
    const [navHeight, setNavHeight] = useState(0);

    useLayoutEffect(() => {
        const navbar = document.querySelector(".js-navbar");
        if (navbar) {
            setNavHeight(navbar.getBoundingClientRect().height);
        }
    }, [setNavHeight]);
    return (
        <AppContext variablesOnly errorComponent={ErrorPage}>
            <ScrollOffsetContext.Provider value={{ ...SCROLL_OFFSET_DEFAULTS, scrollOffset: navHeight }}>
                {props.children}
            </ScrollOffsetContext.Provider>
        </AppContext>
    );
});

// Routing
addComponent("App", () => {
    return <Router disableDynamicRouting />;
});

const render = () => {
    const app = document.querySelector("#app") as HTMLElement;

    if (app) {
        mountReact(<Router disableDynamicRouting />, app);
    }
};
onReady(render);
