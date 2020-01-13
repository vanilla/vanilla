/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { viewHeight } from "csx";
import { colorOut } from "@library/styles/styleHelpersColors";
import { globalVariables } from "@library/styles/globalStyleVars";
import { unit } from "@library/styles/styleHelpers";

export const textEditorVariables = useThemeCache(() => {
    const makeTextEditorVars = variableFactory("textEditor");
    const themeToggleIcon = makeTextEditorVars("themeToggleIcon", {
        top: 12,
        right: 26,
    });
    const editorPadding = makeTextEditorVars("editorPadding", {
        padding: {
            top: 15,
            left: 25,
        },
    });
    return {
        themeToggleIcon,
        editorPadding,
    };
});

export const textEditorClasses = useThemeCache(() => {
    const vars = textEditorVariables();
    const style = styleFactory("textEditor");
    const globalVars = globalVariables();

    const root = theme => {
        return style({
            paddingTop: unit(vars.editorPadding.padding.top),
            paddingLeft: unit(vars.editorPadding.padding.left),
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
