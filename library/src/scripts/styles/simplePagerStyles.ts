/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, debugHelper, unit } from "@library/styles/styleHelpers";
import { style } from "typestyle";

export function simplePagerVariables(theme?: object) {
    const themeVars = componentThemeVariables(theme, "simplePager");

    const sizing = {
        minWidth: 208,
    };

    const spacing = {
        outerMargin: 10,
        innerMargin: 8,
        ...themeVars.subComponentStyles("spacing"),
    };

    return { spacing, sizing };
}

export function simplePagerClasses(theme?: object) {
    const vars = simplePagerVariables(theme);
    const debug = debugHelper("simplePager");

    const root = style({
        alignItems: "center",
        display: "flex",
        justifyContent: "center",
        margin: `${unit(vars.spacing.outerMargin)} 0`,
        ...debug.name(),
    });

    const button = {
        margin: unit(vars.spacing.innerMargin),
        $nest: {
            "&.isSingle": {
                minWidth: unit(vars.sizing.minWidth),
            },
        },
        ...debug.name("button"),
    };

    return { root, button };
}
