/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit, paddings, margins } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { themeCardVariables } from "./themeCardStyles";
import { percent, color } from "csx";

export const addthemeVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("currentThemeInfo");
    const globalVars = globalVariables();

    const colors = makeThemeVars("colors", {
        fg: globalVars.messageColors.warning.fg,
    });
    const addTheme = makeThemeVars("addTheme", {
        width: 310,
        height: 225,

        padding: {
            top: 70,
            bottom: 70,
            right: 117,
            left: 117,
        },
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
        width: unit(vars.addTheme.width),
        height: unit(vars.addTheme.height),

        ...paddings({
            top: unit(vars.addTheme.padding.top),
            bottom: unit(vars.addTheme.padding.bottom),
            left: unit(vars.addTheme.padding.left),
            right: unit(vars.addTheme.padding.right),
        }),
        $nest: {
            "&:hover": {
                backgroundColor: color("#fff").toString(),
            },
        },
    });

    const button = style("button", {
        padding: 0,
        border: "1px dashed #979797",
        $nest: {
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
