/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    colorOut,
    ColorValues,
    offsetLightness,
    IBackground,
    IBorderRadiusOutput,
    modifyColorBasedOnLightness,
    radiusValue,
    EMPTY_BACKGROUND,
    getRatioBasedOnDarkness,
    fontFallbacks,
    monoFallbacks,
    IFont,
} from "@library/styles/styleHelpers";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { BorderStyleProperty, BorderWidthProperty } from "csstype";
import { color, ColorHelper, percent, rgba } from "csx";
import { TLength } from "typestyle/lib/types";
import { logDebug, logError, logWarning } from "@vanilla/utils";
import { ButtonPreset } from "@library/forms/buttonStyles";
import { IThemeVariables } from "@library/theming/themeReducer";
import { isLightColor } from "@library/styles/styleHelpersColors";

export enum GlobalPreset {
    DARK = "dark",
    LIGHT = "light",
}

export const FULL_GUTTER = 40;

export const defaultFontFamily = "Open Sans";

export const globalVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    // let colorPrimary = color("#0291db");
    let colorPrimary = color("#037DBC");
    const makeThemeVars = variableFactory("global", forcedVars);

    const constants = makeThemeVars("constants", {
        stateColorEmphasis: 0.15,
        fullGutter: FULL_GUTTER,
        states: {
            hover: {
                stateEmphasis: 0.08,
            },
            selected: {
                stateEmphasis: 0.5,
            },
            active: {
                stateEmphasis: 0.2,
            },
            focus: {
                stateEmphasis: 0.15,
            },
        },
    });

    const options = makeThemeVars("options", { preset: GlobalPreset.LIGHT });

    const elementaryColors = {
        black: color("#000"),
        almostBlack: color("#323639"),
        lowContrast: color("#555a62"),
        white: color("#fff"),
        transparent: rgba(0, 0, 0, 0),
    };

    const initialMainColors = makeThemeVars("mainColors", {
        fg: options.preset === GlobalPreset.LIGHT ? elementaryColors.lowContrast : elementaryColors.white,
        bg: options.preset === GlobalPreset.LIGHT ? elementaryColors.white : elementaryColors.almostBlack,
        primary: colorPrimary,
        primaryContrast: elementaryColors.white, // for good contrast with text.
        secondary: colorPrimary,
        secondaryContrast: elementaryColors.white, // for good contrast with text.
    });

    colorPrimary = initialMainColors.primary;
    const colorSecondary = initialMainColors.secondary;

    // Shorthand checking bg color for darkness
    const getRatioBasedOnBackgroundDarkness = (
        weight: number,
        color: ColorHelper = mainColors ? mainColors.bg : initialMainColors.bg,
    ) => {
        return getRatioBasedOnDarkness(weight, color);
    };

    const generatedMainColors = makeThemeVars("mainColors", {
        primaryContrast: isLightColor(colorPrimary) ? elementaryColors.almostBlack : elementaryColors.white, // High contrast color, for bg/fg or fg/bg contrast. Defaults to bg.
        statePrimary: offsetLightness(colorPrimary, 0.04), // Default state color change
        secondary: offsetLightness(colorPrimary, 0.05),
        stateSecondary: undefined, // Calculated below, but you can overwrite it here.
        secondaryContrast: isLightColor(colorSecondary) ? elementaryColors.almostBlack : elementaryColors.white,
    });

    const mainColors = {
        ...initialMainColors,
        ...generatedMainColors,
    };

    const mixBgAndFg = (weight: number) => {
        return mainColors.fg.mix(mainColors.bg, weight) as ColorHelper;
    };

    const mixPrimaryAndFg = (weight: number) => {
        return mainColors.primary.mix(mainColors.fg, getRatioBasedOnBackgroundDarkness(weight)) as ColorHelper;
    };

    const mixPrimaryAndBg = (weight: number) => {
        return mainColors.primary.mix(mainColors.bg, getRatioBasedOnBackgroundDarkness(weight)) as ColorHelper;
    };

    const messageColors = makeThemeVars("messageColors", {
        warning: {
            fg: color("#4b5057"),
            bg: color("#fff1ce"),
            state: color("#e55a1c"),
        },
        error: {
            fg: color("#d0021b"),
            bg: color("#FFF3D4"),
        },
        confirm: color("#60bd68"),
        deleted: {
            fg: color("#D0021B"),
            bg: color("#D0021B"),
        },
    });

    const links = makeThemeVars("links", {
        colors: {
            default: mainColors.secondary,
            hover: undefined,
            focus: undefined,
            keyboardFocus: undefined,
            active: undefined,
            visited: undefined,
        },
    });

    // Generated derived colors from mainColors.
    // You can set all of them by setting generatedMainColors.stateSecondary
    // You can set individual states with links.colors["your state"]
    // Will default to variation of links.colors.default (which is by default the secondary color)
    Object.keys(links.colors).forEach(state => {
        if (state !== "default" && state !== "visited") {
            if (!links[state]) {
                links.colors[state] =
                    generatedMainColors.stateSecondary ?? offsetLightness(links.colors.default, 0.008);
            }
        }
    });

    interface IBody {
        backgroundImage: IBackground;
    }

    const body: IBody = makeThemeVars("body", {
        backgroundImage: {
            ...EMPTY_BACKGROUND,
            color: mainColors.bg,
        },
    });

    const border = makeThemeVars("border", {
        color: mixBgAndFg(0.15),
        colorHover: mixBgAndFg(0.2),
        width: 1,
        style: "solid",
        radius: 6, // Global default
    });

    const borderType = makeThemeVars("borderType", {
        formElements: {
            default: {
                ...border,
                color: mixBgAndFg(0.35).saturate(0.1),
            },
            buttons: border,
        },
        modals: border,
        dropDowns: border,
    });

    const gutterSize = 16;
    // @Deprecated - It's confusing that this gutter is 16 and the layout is 48.
    // TODO: Refactor
    const gutter = makeThemeVars("gutter", {
        size: gutterSize,
        half: gutterSize / 2,
        quarter: gutterSize / 4,
    });

    /*
    // The gutter used to be like this:
    //     size: 16,
    //     half: 8,
    //     quarter: 4,
    //
    // This was very confusing because the layout uses "gutter" as well, but very different values.
    //
    // the fractions are not ideal, but it gives the same style as before without having 2 wildly different "gutter" sizes
    //
    // Mapping:
    // size -> third
    // half -> sixth
    // quarter -> twelfth

    const gutter = {
        size: constants.fullGutter,
        half: constants.fullGutter / 2, // 24
        third: constants.fullGutter / 3, // 16
        sixth: constants.fullGutter / 6, // 8
        twelfth: constants.fullGutter / 12, // 4
    };
    */

    const lineHeights = makeThemeVars("lineHeight", {
        base: 1.5,
        condensed: 1.25,
        code: 1.45,
        excerpt: 1.4,
        meta: 1.5,
    });

    // Three column
    // 216 + 40 + 672 + 40 + 216 = 1184 (correct full width of three column layout)
    // 1184 + 40 = 1224 - padded
    // 1184 - 343px =  (two column layout)
    //                                           52 (Extra space) + 244px (Foundation)

    // These globals are here because the layout system was created based on a 3 column layout
    // These variables are used as a starting off point and as a base, but each layout can define
    // its own variables. These are the globals from which the rest is calculated.
    const foundationalWidths = makeThemeVars("foundationalWidths", {
        panelWidth: 216,
        middleColumn: 672,
        minimalMiddleColumnWidth: 550, // Will break if middle column width is smaller than this value.
        narrowContentWidth: 900, // For home page widgets, narrower than full width
        breakPoints: {
            // Other break points are calculated
            twoColumns: 1200,
            xs: 500,
        },
    });

    const widgetInit = makeThemeVars("widget", {
        padding: 10,
    });

    const widget = makeThemeVars("widget", {
        ...widgetInit,
        paddingBothSides: widgetInit.padding * 2,
    });

    const panelInit = makeThemeVars("panel", {
        width: foundationalWidths.panelWidth,
    });

    const panel = makeThemeVars("panel", {
        ...panelInit,
        paddedWidth: panelInit.width + widget.paddingBothSides,
    });

    const middleColumnInit = makeThemeVars("middleColumn", {
        width: foundationalWidths.middleColumn,
    });

    const middleColumn = makeThemeVars("middleColumn", {
        width: middleColumnInit.width,
        paddedWidth: middleColumnInit.width + widget.paddingBothSides,
    });

    const contentWidth = middleColumn.paddedWidth + panel.paddedWidth * 2;

    const fontsInit0 = makeThemeVars("fonts", {
        size: {
            large: 16,
            medium: 14,
            small: 12,
            largeTitle: 32,
            title: 22,
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
        googleFontFamily: defaultFontFamily as undefined | string,
        forceGoogleFont: false,
        customFontUrl: undefined as undefined | string, // legacy
        customFont: {
            name: undefined as undefined | string,
            url: undefined as undefined | string,
            fallbacks: [],
        },
    });

    const fontsInit1 = makeThemeVars("fonts", {
        ...fontsInit0,
        families: {
            body: [
                fontsInit0.customFont.name && !fontsInit0.forceGoogleFont
                    ? fontsInit0.customFont.name
                    : fontsInit0.googleFontFamily ?? defaultFontFamily,
                ...fontFallbacks,
            ],
            monospace: monoFallbacks,
        },
    });

    const isOpenSans = fontsInit1.families.body[0] === defaultFontFamily;

    const fonts = makeThemeVars("fonts", {
        ...fontsInit1,
        alignment: {
            headings: {
                capitalLetterRatio: isOpenSans ? 0.73 : 0.75, // Calibrated for Open Sans
                verticalOffset: 1,
                horizontalOffset: isOpenSans ? -0.03 : 0, // Calibrated for Open Sans
                verticalOffsetForAdjacentElements: isOpenSans ? "-.13em" : "0em", // Calibrated for Open Sans
            },
        },
    });

    const icon = makeThemeVars("icon", {
        sizes: {
            large: 32,
            default: 24,
            small: 16,
            xSmall: 9.5,
        },
        color: mixBgAndFg(0.18),
    });

    const spacer = makeThemeVars("spacer", {
        size: fonts.size.medium * lineHeights.base,
    });

    const animation = makeThemeVars("animation", {
        defaultTiming: ".1s",
        defaultEasing: "ease-out",
    });

    const embed = makeThemeVars("embed", {
        error: {
            bg: messageColors.error,
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
    });

    const meta = makeThemeVars("meta", {
        text: {
            size: fonts.size.small,
            color: elementaryColors.lowContrast,
            lineHeight: lineHeights.base,
        } as IFont,
        spacing: {
            horizontalMargin: 4,
            verticalMargin: 12,
            default: 4,
        },
        colors: {
            fg: mixBgAndFg(0.85),
        },
        display: "block",
    });

    const states = makeThemeVars("states", {
        icon: {
            opacity: 0.6,
        },
        text: {
            opacity: 0.75,
        },
        hover: {
            highlight: mixPrimaryAndBg(constants.states.hover.stateEmphasis),
            contrast: undefined,
            opacity: 1,
        },
        selected: {
            highlight: mixPrimaryAndBg(constants.states.selected.stateEmphasis),
            contrast: undefined,
            opacity: 1,
        },
        active: {
            highlight: mixPrimaryAndBg(constants.states.active.stateEmphasis),
            contrast: undefined,
            opacity: 1,
        },
        focus: {
            highlight: mixPrimaryAndBg(constants.states.focus.stateEmphasis),
            contrast: undefined,
            opacity: 1,
        },
    });

    const overlayBg = modifyColorBasedOnLightness({ color: mainColors.fg, weight: 0.5 });
    const overlay = makeThemeVars("overlay", {
        dropShadow: `2px -2px 5px ${colorOut(overlayBg.fade(0.3))}`,
        bg: overlayBg,
        border: {
            color: border.color,
            radius: border.radius,
        },
        fullPageHeadingSpacer: 32,
        spacer: 32,
    });

    const userContent = makeThemeVars("userContent", {
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
    });

    const buttonIconSize = 36;
    const buttonIcon = makeThemeVars("buttonIcon", {
        size: buttonIconSize,
        offset: (buttonIconSize - icon.sizes.default) / 2,
    });

    // Sets global "style" for buttons. Use "ButtonPreset" enum to select. By default we use both "bordered" (default) and "solid" (primary) button styles
    // The other button styles are all "advanced" and need to be overwritten manually because they can't really be converted without completely changing
    // the style of them.
    const buttonPreset = makeThemeVars("buttonPreset", {
        style: undefined as ButtonPreset | undefined,
    });

    const separator = makeThemeVars("separator", {
        color: border.color,
        size: 1,
    });

    // https://medium.com/@clagnut/all-you-need-to-know-about-hyphenation-in-css-2baee2d89179
    // Requires language set on <html> tag
    const userContentHyphenation = makeThemeVars("userContentHyphenation", {
        minimumCharactersToHyphenate: 6,
        minimumCharactersBeforeBreak: 3,
        minimumCharactersAfterBreak: 3,
        maximumConsecutiveBrokenLines: 2,
        avoidLastWordToBeBroken: true,
        hyphenationZone: "6em",
    });

    // This function should not be used in production, but is helpful for development.
    // Helps to find the right "mix" of bg and fg for a target hex color

    const findColorMatch = (hexCode: string) => {
        if (process.env.NODE_ENV === "development") {
            logWarning("Don't use 'findColorMatch' in production");
            const globalVars = globalVariables();
            const colorToMatch = color(hexCode.replace("#", ""));
            const max = 100;
            const lightnessPrecision = 3;
            const targetLightness = colorToMatch.lightness().toFixed(lightnessPrecision);
            for (let i = 0; i <= max; i++) {
                const mix = i / max;
                const currentColor = globalVars.mixBgAndFg(mix);
                if (currentColor.toHexString() === colorToMatch.toHexString()) {
                    logDebug("---exact match");
                    logDebug("real grey: " + colorToMatch.toHexString());
                    logDebug("target grey: " + currentColor.toHexString());
                    logDebug("mix: " + mix);
                    logDebug("---");
                    i = max;
                    return;
                }
                if (currentColor.lightness().toFixed(lightnessPrecision) === targetLightness) {
                    logDebug("---lightness match: " + mix);
                    i = max;
                    return;
                }
            }
        } else if (process.env.NODE_ENV === "test") {
            throw new Error("Don't use 'findColorMatch' in production");
        }
        logError("The function 'findColorMatch' is not meant for production");
    };

    return {
        options,
        elementaryColors,
        mainColors,
        messageColors,
        body,
        borderType,
        border,
        meta,
        gutter,
        panel,
        middleColumn,
        contentWidth,
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
        separator,
        userContentHyphenation,
        findColorMatch,
        constants,
        getRatioBasedOnBackgroundDarkness,
        buttonPreset,
        foundationalWidths,
        widget,
    };
});

export interface IGlobalBorderStyles extends IBorderRadiusOutput {
    color?: ColorValues;
    width?: BorderWidthProperty<TLength> | number;
    style?: BorderStyleProperty;
    radius?: radiusValue;
}

export enum IIconSizes {
    SMALL = "small",
    DEFAULT = "default",
    LARGE = "large",
}
