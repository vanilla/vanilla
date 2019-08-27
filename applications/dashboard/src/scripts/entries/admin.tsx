/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import "@dashboard/legacy";
import { initAllUserContent } from "@library/content";
import { onContent, addComponent } from "@library/utility/appUtils";
import { Router } from "@library/Router";
import { AppContext } from "@library/AppContext";

onContent(() => initAllUserContent());

// Routing
addComponent("App", () => (
    <AppContext>
        <Router disableDynamicRouting />
    </AppContext>
));
