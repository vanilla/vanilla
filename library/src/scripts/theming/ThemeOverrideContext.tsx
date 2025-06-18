/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { IThemeVariables } from "@library/theming/themeReducer";
import { createContext, useContext } from "react";

interface IThemeOverrideContext {
    overridesVariables: IThemeVariables | null;
    themeID: string | number | null;
}

const EMPTY_CONTEXT: IThemeOverrideContext = {
    overridesVariables: null,
    themeID: null,
};

Object.freeze(EMPTY_CONTEXT);

declare global {
    interface Window {
        __THEME_OVERRIDE_CONTEXT__: IThemeOverrideContext | null;
    }
}

export const ThemeOverrideContext = createContext<IThemeOverrideContext>(EMPTY_CONTEXT);

export function ClearThemeOverrideContext(props: { children?: React.ReactNode }) {
    return (
        <ThemeOverrideContext.Provider value={{ overridesVariables: null, themeID: null }}>
            {props.children}
        </ThemeOverrideContext.Provider>
    );
}

export function useWithThemeContext<T extends () => any>(callback: T): ReturnType<T> {
    const themeOverride = useThemeOverrideContext();

    let original = window.__THEME_OVERRIDE_CONTEXT__;
    window.__THEME_OVERRIDE_CONTEXT__ = themeOverride;
    const result = callback();
    window.__THEME_OVERRIDE_CONTEXT__ = original;

    return result;
}

/**
 * Notably has a try catch so it doens't blow up if used outside of react.
 */
export function useThemeOverrideContext() {
    try {
        return useContext(ThemeOverrideContext);
    } catch (e) {
        // Don't care about the error
        return EMPTY_CONTEXT;
    }
}
