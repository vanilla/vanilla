/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { componentThemeVariables, debugHelper, unit } from "../styles/styleHelpers";
import { useThemeCache } from "../styles/styleUtils";
import { style } from "typestyle";

export const simplePagerVariables = useThemeCache(() => {
    const themeVars = componentThemeVariables("simplePager");

    const sizing = {
        minWidth: 208,
    };

    const spacing = {
        outerMargin: 10,
        innerMargin: 8,
        ...themeVars.subComponentStyles("spacing"),
    };

    return { spacing, sizing };
});

export const simplePagerClasses = useThemeCache(() => {
    const vars = simplePagerVariables();
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
});
