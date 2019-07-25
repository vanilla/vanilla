/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper, unit } from "@library/styles/styleHelpers";
import { componentThemeVariables, useThemeCache } from "@library/styles/styleUtils";
import { style } from "typestyle";
import { percent } from "csx";

export const drawerVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = componentThemeVariables("drawer");

    const spacing = {
        button: {
            padding: 9,
        },
        ...themeVars.subComponentStyles("spacing"),
    };

    const fonts = {
        size: globalVars.userContent.font.sizes.default,
        weight: globalVars.fonts.weights.semiBold,
        ...themeVars.subComponentStyles("fonts"),
    };

    const sizing = {
        icon: globalVars.userContent.font.sizes.default,
        ...themeVars.subComponentStyles("sizing"),
    };

    return { spacing, fonts, sizing };
});

export const drawerClasses = useThemeCache(() => {
    const vars = drawerVariables();
    const debug = debugHelper("drawer");

    const root = style({
        display: "block",
        position: "relative",
        ...debug.name(),
    });

    const contents = style({
        position: "relative",
        width: percent(100),
        ...debug.name("contents"),
    });

    const toggle = style({
        fontWeight: vars.fonts.weight,
        padding: `${unit(vars.spacing.button.padding)} 0`,
        width: percent(100),
        textAlign: "left",
        ...debug.name("toggle"),
    });
    const icon = style({
        display: "inline-flex",
        minWidth: unit(vars.sizing.icon),
        fontSize: unit(vars.fonts.size),
        ...debug.name("icon"),
    });

    return { root, contents, toggle, icon };
});
