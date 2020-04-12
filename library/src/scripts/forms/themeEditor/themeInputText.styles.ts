/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";

export const themeInputTextVariables = useThemeCache(() => {
    // Intentionally not overwritable with theming system.
    return {};
});

export const themeInputTextClasses = useThemeCache(() => {
    const vars = themeInputTextVariables();
    const style = styleFactory("themeInputText");
    const root = style({});

    return {
        root,
    };
});
