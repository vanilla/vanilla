/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper, componentThemeVariables } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { IButtonType } from "@library/styles/buttonStyles";

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
        $nest: {
            ".searchBar-valueContainer": {
                cursor: "pointer",
            },
            ".searchBar__control": {
                cursor: "pointer",
            },
        },
    });

    return { root };
};
