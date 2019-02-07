/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { style } from "typestyle";
import { px, quote, viewWidth, viewHeight } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, debugHelper } from "@library/styles/styleHelpers";
import { searchVariables } from "@library/styles/searchStyles";

export function splashVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const elementaryColor = globalVars.elementaryColors;
    const themeVars = componentThemeVariables(theme, "splash");

    const fullBackground = {
        bg: globalVars.mainColors.primary,
        image: "https://vanillaforums.com/images/backgrounds/header_blue.jpg",
        ...themeVars.subComponentStyles("fullBackground"),
    };

    const title = {
        fg: elementaryColor.white,
        fontSize: globalVars.fonts.title,
        textAlign: "center",
        fontWeight: globalVars.fonts.weights.semiBold,
        textShadow: `0 1px 25px rgba(27,31,35,0.01)`,
        marginBottom: 40,
        ...themeVars.subComponentStyles("title"),
    };

    const spacing = {
        top: 76,
        bottom: 48,
        ...themeVars.subComponentStyles("spacing"),
    };

    const border = {
        color: globalVars.mainColors.fg,
        ...themeVars.subComponentStyles("border"),
    };

    const search = searchVariables({
        ...themeVars.subComponentStyles("search"),
    });

    return { fullBackground, title, spacing, border, search };
}

export function splashStyles() {
    const debug = debugHelper("search");
    const root = style({
        ...debug.name(),
    });
    const container = style({
        ...debug.name("container"),
    });
    const innerContainer = style({
        ...debug.name("innerContainer"),
    });
    const title = style({
        ...debug.name("title"),
    });
    const search = style({
        ...debug.name("search"),
    });
    return { root, container, innerContainer, title, search };
}
