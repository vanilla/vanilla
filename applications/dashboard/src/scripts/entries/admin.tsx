/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import "@dashboard/legacy";
import { initAllUserContent } from "@library/content";
import { onContent } from "@library/utility/appUtils";
import { Router } from "@library/Router";
import { AppContext } from "@library/AppContext";
import { addComponent, disableComponentTheming } from "@library/utility/componentRegistry";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";

addComponent("imageUploadGroup", DashboardImageUploadGroup, { overwrite: true });

disableComponentTheming();
onContent(() => initAllUserContent());

// Routing
addComponent("App", () => (
    <AppContext noTheme>
        <Router disableDynamicRouting />
    </AppContext>
));
