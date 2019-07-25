/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, useThemeCache } from "@library/styles/styleUtils";
import { style } from "typestyle";
import { debugHelper } from "@library/styles/styleHelpers";

export const searchVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const elementaryColor = globalVars.elementaryColors;
    const themeVars = componentThemeVariables("search");

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
        ...themeVars.subComponentStyles("placeholder"),
    };

    return { input, placeholder };
});

export const searchClasses = useThemeCache(() => {
    const vars = searchVariables();
    const debug = debugHelper("search");

    const root = style({
        ...debug.name(),
        $nest: {
            ".inputText": {
                borderColor: vars.input.border.color.toString(),
            },
            ".searchBar__control": {
                ...debug.name("control"),
                cursor: "text",
            },
        },
    });

    return { root };
});
