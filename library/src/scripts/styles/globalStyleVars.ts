/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, ColorHelper, percent, px } from "csx";
import { componentThemeVariables, getColorDependantOnLightness } from "@library/styles/styleHelpers";

export const globalVariables = (theme?: object) => {
    const colorPrimary = color("#0291db");
    const themeVars = componentThemeVariables(theme, "globalVariables");

    const utility = {
        "percentage.third": percent(100 / 3),
        "percentage.nineSixteenths": percent((9 / 16) * 100),
        "svg.encoding": "data:image/svg+xml,",
    };

    const elementaryColors = {
        black: color("#000"),
        white: color("#fff"),
        transparent: `transparent`,
    };

    const mainColors = {
        fg: color("#555a62"),
        bg: color("#fff"),
        primary: colorPrimary,
        secondary: getColorDependantOnLightness(colorPrimary, colorPrimary, 0.1, true),
        ...themeVars.subComponentStyles("mainColors"),
    };

    const mixBgAndFg = weight => {
        return mainColors.fg.mix(mainColors.bg, weight);
    };

    const errorFg = color("#ff3933");
    const warning = color("#ffce00");
    const deleted = color("#D0021B");
    const feedbackColors = {
        warning,
        error: {
            fg: errorFg,
            bg: errorFg.mix(mainColors.bg, 10),
        },
        confirm: color("#60bd68"),
        unresolved: warning.mix(mainColors.fg, 10),
        deleted,
        ...themeVars.subComponentStyles("feedbackColors"),
    };

    const links = {
        color: mainColors.primary,
        visited: mainColors.primary,
    };

    const body = {
        bg: mainColors.bg,
        ...themeVars.subComponentStyles("body"),
    };

    const border = {
        color: mainColors.fg.mix(mainColors.bg, 24),
        width: px(1),
        style: "solid",
        radius: px(6),
        ...themeVars.subComponentStyles("border"),
    };

    const gutterSize = 24;
    const gutter = {
        size: gutterSize,
        half: gutterSize / 2,
        quarter: gutterSize / 4,
        ...themeVars.subComponentStyles("gutter"),
    };

    const lineHeights = {
        base: 1.5,
        condensed: 1.25,
        code: 1.45,
        excerpt: 1.45,
        ...themeVars.subComponentStyles("lineHeight"),
    };

    const panelWidth = 216;
    const panel = {
        width: panelWidth,
        paddedWidth: panelWidth + gutter.size,
        ...themeVars.subComponentStyles("panelWidth"),
    };

    const middleColumnWidth = 672;
    const middleColumn = {
        width: middleColumnWidth,
        paddedWidth: middleColumnWidth + gutter.size,
        ...themeVars.subComponentStyles("middleColumn"),
    };

    const content = {
        width:
            panel.paddedWidth * 2 +
            middleColumn.paddedWidth +
            gutter.size * 3 /* *3 from margin between columns and half margin on .container*/,
    };

    const fonts = {
        size: {
            large: 16,
            medium: 14,
            small: 12,
            title: 32,
            smallTitle: 22,
            subTitle: 18,
        },

        mobile: {
            size: {
                title: 26,
            },
        },
        weights: {
            normal: 400,
            semiBold: 600,
            bold: 700,
        },
        ...themeVars.subComponentStyles("fonts"),
    };

    const meta = {
        fontSize: fonts.size.small,
        color: mixBgAndFg(0.85),
        margin: 4,
    };

    const icon = {
        sizes: {
            large: 32,
            default: 24,
            small: 16,
        },
        color: mixBgAndFg(0.18),
        ...themeVars.subComponentStyles("icon"),
    };

    const spacer = fonts.size.medium * lineHeights.base;

    const animation = {
        defaultTiming: ".15s",
        defaultEasing: "ease-out",
        ...themeVars.subComponentStyles("animation"),
    };

    return {
        utility,
        elementaryColors,
        mainColors,
        feedbackColors,
        body,
        border,
        meta,
        gutter,
        panel,
        content,
        fonts,
        spacer,
        lineHeights,
        icon,
        mixBgAndFg,
        animation,
        links,
    };
};
