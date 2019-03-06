/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, ColorHelper, percent, px, rgba } from "csx";
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

    const mixBgAndFg = (weight: number) => {
        return mainColors.fg.mix(mainColors.bg, weight) as ColorHelper;
    };

    const mixPrimaryAndFg = (weight: number) => {
        return mainColors.primary.mix(mainColors.fg, weight) as ColorHelper;
    };

    const mixPrimaryAndBg = (weight: number) => {
        return mainColors.primary.mix(mainColors.bg, weight) as ColorHelper;
    };

    const errorFg = color("#555A62");
    const warning = color("#ffce00");
    const deleted = color("#D0021B");
    const feedbackColors = {
        warning,
        error: {
            fg: errorFg,
            bg: color("#FFF3D4"),
        },
        confirm: color("#60bd68"),
        unresolved: warning.mix(mainColors.fg, 10),
        deleted,
        ...themeVars.subComponentStyles("feedbackColors"),
    };

    const links = {
        colors: {
            default: mainColors.fg,
            hover: mainColors.secondary,
            focus: mainColors.secondary,
            accessibleFocus: mainColors.secondary,
            active: mainColors.secondary,
        },
        ...themeVars.subComponentStyles("links"),
    };

    const body = {
        bg: mainColors.bg,
        ...themeVars.subComponentStyles("body"),
    };

    const border = {
        color: mixBgAndFg(0.24),
        width: 1,
        style: "solid",
        radius: 6,
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

    const embed = {
        error: {
            bg: feedbackColors.error,
        },
        focus: {
            color: mainColors.primary,
        },
        text: {
            padding: fonts.size.medium,
        },
        sizing: {
            smallPadding: 4,
            width: 640,
        },
        select: {
            borderWidth: 2,
        },
        overlay: {
            hover: {
                color: mainColors.bg.fade(0.5),
            },
        },
        ...themeVars.subComponentStyles("embed"),
    };

    const meta = {
        text: {
            fontSize: fonts.size.small,
            color: mixBgAndFg(0.85),
            margin: 4,
        },
        spacing: {
            verticalMargin: 12,
            default: gutter.quarter,
        },
        lineHeights: {
            default: lineHeights.base,
        },
        colors: {
            fg: mixBgAndFg(0.85),
            deleted: feedbackColors.deleted,
        },
    };

    const states = {
        icon: {
            opacity: 0.6,
        },
        text: {
            opacity: 0.75,
        },
        hover: {
            color: mixPrimaryAndBg(0.1),
            opacity: 1,
        },
        focus: {
            color: mixPrimaryAndBg(0.12),
            opacity: 1,
        },
        active: {
            color: mixPrimaryAndBg(0.95),
            opacity: 1,
        },
    };

    const overlayBg = getColorDependantOnLightness(mainColors.bg, mainColors.fg, 0.2);
    const overlay = {
        dropShadow: `0 5px 10px ${overlayBg}`,
        border: {
            color: mixBgAndFg(0.15),
            radius: border.radius,
        },
        spacer: 32,
    };

    const userContent = {
        font: {
            sizes: {
                default: fonts.size.medium,
                h1: "2em",
                h2: "1.5em",
                h3: "1.25em",
                h4: "1em",
                h5: ".875em",
                h6: ".85em",
            },
        },
        list: {
            margin: "2em",
            listDecoration: {
                minWidth: "2em",
            },
        },
    };

    const buttonIconSize = 36;
    const buttonIcon = {
        size: buttonIconSize,
        offset: (buttonIconSize - icon.sizes.default) / 2,
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
        buttonIcon,
        animation,
        links,
        embed,
        states,
        overlay,
        userContent,
        mixBgAndFg,
        mixPrimaryAndFg,
        mixPrimaryAndBg,
    };
};
