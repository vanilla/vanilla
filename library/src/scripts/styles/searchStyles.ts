/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, percent, px } from "csx";
import { componentThemeVariables, debugHelper } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { style } from "typestyle";

export const searchVariables = (theme?: object) => {
    const globalVars = globalVariables(theme);
    const elementaryColor = globalVars.elementaryColors;
    const themeVars = componentThemeVariables(theme, "search");

    const input = {
        border: {
            color: elementaryColor.white,
        },
        bg: "transparent",
        hover: {
            bg: elementaryColor.black.fade(0.1),
        },
        ...themeVars.subComponentStyles("input"),
    };

    const placeholder = {
        color: globalVars.mainColors.fg,
        ...themeVars.subComponentStyles("input"),
    };

    return { input, placeholder };
};

export const searchClasses = (theme?: object) => {
    const vars = searchVariables(theme);
    const debug = debugHelper("search");

    const root = style({
        ...debug.name(),
    });

    const button = style({
        ...debug.name("button"),
    });

    const results = style({
        ...debug.name("results"),
    });

    return { root, button, results };
};
