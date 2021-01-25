/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { viewHeight } from "csx";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { defaultTransition, absolutePosition } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";

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

    const root = (theme: string, minimal?: boolean, noPadding?: boolean) => {
        return style({
            transition: "backgroundColor 0.25s ease",
            display: "flex",
            flexDirection: "column",
            justifyContent: "stretch",
            height: minimal ? "300px" : viewHeight(100),
            backgroundColor:
                theme === "vs-dark"
                    ? ColorsUtils.colorOut("#1E1E1E")
                    : ColorsUtils.colorOut(globalVars.elementaryColors.white),
            position: "relative",
            paddingTop: styleUnit(vars.editorPadding.padding.top),
            paddingLeft: minimal || noPadding ? 0 : styleUnit(vars.editorPadding.padding.left),
            ...{
                ".decorationsOverviewRuler": {
                    display: "none",
                },
                ".monaco-editor .overflow-guard": {
                    borderRadius: 6,
                },
                ".monaco-editor": {
                    borderRadius: 6,
                },
            },
            ...(minimal
                ? {
                      ...Mixins.border(),
                  }
                : {}),
        });
    };
    const themeToggleIcon = style("themeToggleIcon", {
        position: "absolute",
        zIndex: 1,
        top: vars.themeToggleIcon.top,
        right: vars.themeToggleIcon.right,
        border: "none",
        padding: 0,
        margin: 0,
        background: "none",
        ...defaultTransition("transform"),
    });

    const colorChangeOverlay = (theme) =>
        style("colorChangeOverlay", {
            ...absolutePosition.fullSizeOfParent(),
            backgroundColor:
                theme === "vs-dark"
                    ? ColorsUtils.colorOut("#1E1E1E")
                    : ColorsUtils.colorOut(globalVars.elementaryColors.white),
        });

    return {
        root,
        themeToggleIcon,
        colorChangeOverlay,
    };
});

export default textEditorClasses;
