/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { viewHeight } from "csx";
import { colorOut } from "@library/styles/styleHelpersColors";
import { globalVariables } from "@library/styles/globalStyleVars";
import { unit, defaultTransition, absolutePosition } from "@library/styles/styleHelpers";

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
            transition: "backgroundColor 0.25s ease",
            display: "flex",
            flexDirection: "column",
            justifyContent: "stretch",
            height: viewHeight(90),
            backgroundColor: theme === "vs-dark" ? colorOut("#1E1E1E") : colorOut(globalVars.elementaryColors.white),
            position: "relative",
            paddingTop: unit(vars.editorPadding.padding.top),
            paddingLeft: unit(vars.editorPadding.padding.left),
        });
    };
    const themeToggleIcon = style("themeToggleIcon", {
        position: "absolute",
        zIndex: 12,
        top: vars.themeToggleIcon.top,
        right: vars.themeToggleIcon.right,
        border: "none",
        padding: 0,
        margin: 0,
        background: "none",
        ...defaultTransition("transform"),
    });

    const colorChangeOverlay = theme =>
        style("colorChangeOverlay", {
            ...absolutePosition.fullSizeOfParent(),
            backgroundColor: theme === "vs-dark" ? colorOut("#1E1E1E") : colorOut(globalVars.elementaryColors.white),
        });

    return {
        root,
        themeToggleIcon,
        colorChangeOverlay,
    };
});

export default textEditorClasses;
