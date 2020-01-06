/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { viewHeight } from "csx";
import { colorOut } from "@library/styles/styleHelpersColors";
import { globalVariables } from "@library/styles/globalStyleVars";

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
    const globalVars = globalVariables();

    const root = theme => {
        return style({
            display: "flex",
            flexDirection: "column",
            justifyContent: "stretch",
            height: viewHeight(90),
            backgroundColor: theme === "dark" ? colorOut("#1f2024") : colorOut(globalVars.elementaryColors.white),
            position: "relative",
        });
    };
    const themeToggleIcon = style("themeToggleIcon", {
        position: "absolute",
        zIndex: 12,
        top: vars.themeToggleIcon.top,
        right: vars.themeToggleIcon.right,
    });

    return {
        root,
        themeToggleIcon,
    };
});

export default textEditorClasses;
