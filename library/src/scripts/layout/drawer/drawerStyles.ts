/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { componentThemeVariables } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
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
        size: globalVars.fonts.size.medium,
        weight: globalVars.fonts.weights.semiBold,
        ...themeVars.subComponentStyles("fonts"),
    };

    const sizing = {
        icon: globalVars.fonts.size.medium,
        ...themeVars.subComponentStyles("sizing"),
    };

    return { spacing, fonts, sizing };
});

export const drawerClasses = useThemeCache(() => {
    const vars = drawerVariables();
    const debug = debugHelper("drawer");

    const root = css({
        display: "block",
        position: "relative",
        ...debug.name(),
    });

    const contents = css({
        position: "relative",
        width: percent(100),
        ...debug.name("contents"),
    });

    const toggle = css({
        fontWeight: vars.fonts.weight,
        padding: `${styleUnit(vars.spacing.button.padding)} 0`,
        width: percent(100),
        textAlign: "left",
        ...debug.name("toggle"),
    });
    const icon = css({
        display: "inline-flex",
        minWidth: styleUnit(vars.sizing.icon),
        fontSize: styleUnit(vars.fonts.size),
        ...debug.name("icon"),
    });

    return { root, contents, toggle, icon };
});
