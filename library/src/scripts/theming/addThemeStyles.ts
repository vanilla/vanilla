/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit, paddings, margins, flexHelper } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { themeCardVariables } from "./themePreviewCardStyles";
import { percent, color } from "csx";

export const addthemeVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("currentThemeInfo");
    const globalVars = globalVariables();

    const colors = makeThemeVars("colors", {
        fg: globalVars.messageColors.warning.fg,
    });
    const addTheme = makeThemeVars("addTheme", {
        width: percent(100),
        height: percent(100),
        padding: globalVars.gutter.size,
    });

    return {
        addTheme,
    };
});

export const addThemeClasses = useThemeCache(() => {
    const vars = addthemeVariables();
    const globalVars = globalVariables();

    const style = styleFactory("addTheme");

    const addTheme = style("addTheme", {
        display: "block",
        ...flexHelper().middle(),
        ...paddings({
            all: vars.addTheme.padding,
        }),
        width: unit(vars.addTheme.width),
        height: unit(vars.addTheme.height),
        $nest: {
            "&:hover": {
                backgroundColor: color("#fff").toString(),
            },
        },
    });

    const button = style("button", {
        padding: 0,
        minHeight: 100,
        minWidth: themeCardVariables().container.minWidth,
        maxWidth: themeCardVariables().container.maxWidth,
        $nest: {
            "&&": {
                width: unit(vars.addTheme.width),
                height: unit(vars.addTheme.height),
                border: "1px dashed #979797",
            },
            "&&:hover": {
                backgroundColor: color("#fff").toString(),
                border: "1px dashed #979797",
            },
        },
    });

    const addThemeLink = style("addThemeLink", {});

    return {
        addTheme,
        button,
        addThemeLink,
    };
});

export default addThemeClasses;
