/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { createContext, useState } from "react";

export const TitleBarTransparentContext = createContext({
    isLegacyPage: false,
    allowTransparency: true,
    setAllowTransparency: (allow: boolean) => {},
});

/**
 * For layout pages, we respect the title bar configuration of transparency, because it's set explicitly and the user can see it in the layout.
 *
 * For legacy pages, we don't respect the title bar configuration of transparency, because it's set implicitly and the user can't see it in the layout.
 */
export function LegacyTitleBarTransparentContextProvider(props: { children: React.ReactNode }) {
    const [allowTransparency, setAllowTransparency] = useState(false);

    return (
        <TitleBarTransparentContext.Provider value={{ isLegacyPage: true, allowTransparency, setAllowTransparency }}>
            {props.children}
        </TitleBarTransparentContext.Provider>
    );
}
