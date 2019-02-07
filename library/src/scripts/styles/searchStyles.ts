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
    const borderRadius = globalVars.border.radius;

    const fullBackground = {
        bg: globalVars.mainColors.primary,
        image: "https://vanillaforums.com/images/backgrounds/header_blue.jpg",
    };

    const title = {
        fg: elementaryColor.white,
        fontSize: globalVars.fonts.title,
        textAlign: "center",
        fontWeight: globalVars.fonts.weights.semiBold,
        textShadow: `0 1px 25px rgba(27,31,35,0.01)`,
        marginBottom: 40,
    };

    const input = {
        border: {
            color: elementaryColor.white,
        },
        bg: "transparent",
        hover: {
            bg: elementaryColor.black.fade(0.1),
        },
    };

    const placeholder = {
        color: globalVars.mainColors.fg,
    };

    const border = {
        color: globalVars.mainColors.fg,
    };

    const spacing = {
        top: 76,
        bottom: 48,
    };

    return { fullBackground, title, input, placeholder, border, spacing };
};

export const searchClasses = () => {
    const vars = searchVariables();
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
