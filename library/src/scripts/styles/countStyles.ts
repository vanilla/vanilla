/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { absolutePosition, componentThemeVariables, debugHelper, unit } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { memoizeTheme } from "@library/styles/styleUtils";

export const countVariables = memoizeTheme(() => {
    const globalVars = globalVariables();
    const themeVars = componentThemeVariables("count");

    const font = {
        size: 10,
        ...themeVars.subComponentStyles("font"),
    };

    const sizing = {
        height: globalVars.fonts.size.large,
        ...themeVars.subComponentStyles("sizing"),
    };

    const color = {
        bg: globalVars.meta.colors.deleted,
        ...themeVars.subComponentStyles("sizing"),
    };

    return { font, sizing, color };
});

export const countClasses = memoizeTheme(() => {
    const globalVars = globalVariables();
    const vars = countVariables();
    const debug = debugHelper("count");

    const root = style({
        ...absolutePosition.topRight(4),
        display: "block",
        backgroundColor: vars.color.bg.toString(),
        height: unit(vars.sizing.height),
        lineHeight: unit(vars.sizing.height),
        minWidth: unit(vars.sizing.height),
        fontSize: unit(vars.font.size),
        fontWeight: globalVars.fonts.weights.semiBold,
        borderRadius: unit(vars.sizing.height / 2),
        whiteSpace: "nowrap",
        padding: `0 3px`,
        ...debug.name(),
    });

    const text = style({
        display: "block",
        textAlign: "center",
        ...debug.name("text"),
    });

    return { root, text };
});
