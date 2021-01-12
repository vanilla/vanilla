/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { flexHelper } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { themeCardVariables } from "./themePreviewCardStyles";
import { percent, color } from "csx";
import { Mixins } from "@library/styles/Mixins";

export const addthemeVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("currentThemeInfo");
    const globalVars = globalVariables();

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

    const style = styleFactory("addTheme");

    const addTheme = style("addTheme", {
        ...flexHelper().middle(),
        ...Mixins.padding({
            all: vars.addTheme.padding,
        }),
        width: styleUnit(vars.addTheme.width),
        height: styleUnit(vars.addTheme.height),
        ...{
            "&:hover": {
                backgroundColor: color("#fff").toString(),
            },
        },
    });

    const button = style("button", {
        padding: 0,
        minHeight: 200,
        minWidth: themeCardVariables().container.minWidth,
        maxWidth: themeCardVariables().container.maxWidth,
        ...{
            "&&": {
                width: styleUnit(vars.addTheme.width),
                height: styleUnit(vars.addTheme.height),
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
