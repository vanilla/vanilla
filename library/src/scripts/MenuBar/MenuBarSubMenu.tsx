/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useContext, useRef } from "react";
import { useMenuBarContext } from "@library/MenuBar/MenuBarContext";
import ReactDOM from "react-dom";

const RealMenuBarSubMenuContext = React.createContext({
    ref: { current: null } as React.RefObject<HTMLDivElement>,
    renderSubMenu: (subMenu: React.ReactNode) => subMenu,
});

export function useMenuBarSubMenuContext() {
    return useContext(RealMenuBarSubMenuContext);
}

export function MenuBarSubMenuContext(props: { children: React.ReactNode }) {
    const ref = useRef<HTMLDivElement>(null);
    return (
        <RealMenuBarSubMenuContext.Provider
            value={{
                ref,
                renderSubMenu: (subMenu) => {
                    if (ref.current) {
                        return ReactDOM.createPortal(subMenu, ref.current);
                    }
                },
            }}
        >
            {props.children}
        </RealMenuBarSubMenuContext.Provider>
    );
}

export function MenuBarSubMenuContainer() {
    const { ref } = useMenuBarSubMenuContext();
    return <div ref={ref}></div>;
}
