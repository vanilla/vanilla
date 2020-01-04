/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";

export const textEditorVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("themePreviewCard");
    const themeToggleIcon = makeThemeVars("themeToggleIcon", {
        top: 12,
        right: 26,
    });
    return {
        themeToggleIcon,
    };
});

export const textEditorClasses = useThemeCache(() => {
    const vars = textEditorVariables();
    const style = styleFactory("textEditor");
    const themeToggleIcon = style("themeToggleIcon", {
        position: "absolute",
        zIndex: 12,
        top: 12,
        right: 12,
    });

    return {
        themeToggleIcon,
    };
});

export default textEditorClasses;
