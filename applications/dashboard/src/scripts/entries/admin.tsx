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
import { mountReact } from "@vanilla/react-utils/src";
import { Backgrounds } from "@vanilla/library/src/scripts/layout/Backgrounds";

addComponent("imageUploadGroup", DashboardImageUploadGroup, { overwrite: true });

disableComponentTheming();
onContent(() => initAllUserContent());

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
            <AppContext errorComponent={null} noTheme>
                <Backgrounds />
                <Router sectionRoot="/theme" />
            </AppContext>,
            app,
        );
    }
};
onReady(render);
