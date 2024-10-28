/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { createContext, useContext } from "react";

export interface IOpenApiRoute {
    path: string;
    method: string;
}

export interface ITryItContext {
    enabled: boolean;
    route: IOpenApiRoute | null;
    setRoute(route: IOpenApiRoute | null): void;
}

const context = createContext<ITryItContext>({
    enabled: false,
    route: null,
    setRoute() {},
});

export const TryItContextProvider = context.Provider;
export const useTryItContext = () => useContext(context);
