/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { useContext } from "react";

interface IStackingContext {
    zIndex: number;
}

const StackingContext = React.createContext<IStackingContext>({
    // Modals default z-index
    zIndex: 1050,
});

/**
 * Get the current zIndex depth for things that stack on top of erach other.
 */
export function useStackingContext() {
    return useContext(StackingContext);
}

/**
 * Context that gradually increases zIndex as components nest inside of each other.
 */
export function StackingContextProvider(props: { children: React.ReactNode }) {
    const existingContext = useStackingContext();
    return (
        <StackingContext.Provider value={{ zIndex: existingContext.zIndex + 1 }}>
            {props.children}
        </StackingContext.Provider>
    );
}
