/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import { INITIAL_THEME_STATE, IThemeState } from "./themeReducer";
import { useSelector } from "react-redux";
import { getThemeVariables } from "@library/theming/getThemeVariables";

type IThemeContextValue = IThemeState;

const ThemeContext = React.createContext<IThemeContextValue>(INITIAL_THEME_STATE);

export function ThemeContextProvider(props: { theme: IThemeState; children: React.ReactNode }) {
    const { theme, children } = props;

    return <ThemeContext.Provider value={theme}>{children}</ThemeContext.Provider>;
}

export function ReduxThemeContextProvider(props: { children: React.ReactNode }) {
    const { children } = props;
    const theme = useSelector(({ theme }: { theme: IThemeState }) => theme);

    return <ThemeContextProvider theme={theme}>{children}</ThemeContextProvider>;
}

export function useThemeContext(): IThemeContextValue {
    return useContext(ThemeContext);
}

export function useThemeForcedVariables(): IThemeContextValue["forcedVariables"] {
    const context = useThemeContext();
    return context.forcedVariables;
}
