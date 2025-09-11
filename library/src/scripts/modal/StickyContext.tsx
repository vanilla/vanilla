/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import ReactDOM from "react-dom";

interface IStickyContext {
    mountStickyPortal(element: React.ReactNode): React.ReactPortal;
}

const StickyContext = React.createContext<IStickyContext>({
    mountStickyPortal: (element) => ReactDOM.createPortal(element, document.getElementById("modals") as HTMLElement),
});

export function useStickyContext() {
    return React.useContext(StickyContext);
}

export function StickyContextProvider(props: { portalLocation: HTMLElement | null; children: React.ReactNode }) {
    const mountStickyPortal = React.useCallback(
        (element: React.ReactNode) => {
            return ReactDOM.createPortal(
                element,
                props.portalLocation ?? (document.getElementById("modals") as HTMLElement),
            );
        },
        [props.portalLocation],
    );

    return <StickyContext.Provider value={{ mountStickyPortal }}>{props.children}</StickyContext.Provider>;
}
