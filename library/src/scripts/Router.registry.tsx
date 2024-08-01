/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalValueRef } from "@vanilla/utils";
import React from "react";

class RouteRegistry {
    /**
     * The currently registered routes.
     * @private
     */
    public routes: React.ReactNode[] = [];

    /**
     * Register one or more routes to the app component.
     *
     * @param routes An array of routes to add.
     */
    public addRoutes = (routes: React.ReactNode[]) => {
        if (!Array.isArray(routes)) {
            this.routes.push(routes);
        } else {
            this.routes.push(...routes);
        }
    };
}

const RouterRegistryRef = globalValueRef("RouterRegistry", new RouteRegistry());
export const RouterRegistry = RouterRegistryRef.current();
