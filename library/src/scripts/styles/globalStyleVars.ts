/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ButtonPreset } from "@library/forms/ButtonPreset";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { IBackground } from "@library/styles/cssUtilsTypes";
import { fontFallbacks, monoFallbacks } from "@library/styles/fontFallbacks";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";
import { IThemeVariables } from "@library/theming/themeReducer";
import { logDebug, logError, logWarning } from "@vanilla/utils";
import { color, ColorHelper, rgba } from "csx";

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
                bgEmphasis: 0.08,
                borderEmphasis: 0.7,
            },
            selected: {
                bgEmphasis: 0.5,
                borderEmphasis: 1,
            },
            active: {
                bgEmphasis: 0.2,
                borderEmphasis: 1,
            },
            focus: {
                bgEmphasis: 0.15,
                borderEmphasis: 1,
            },
        },
    });

    const options = makeThemeVars(
        "options",
        /**
         * @var global.options.preset
         * @title  Options Preset
         * @description Choose global preset
         * @type string
         * @enum light | dark
         */
        { preset: GlobalPreset.LIGHT },
    );

    const elementaryColors = {
        black: color("#000"),
        almostBlack: color("#323639"),
        white: color("#fff"),
        transparent: rgba(0, 0, 0, 0),
    };

    /**
     * @varGroup global.mainColors
     * @commonDescription Global main colors
     */
    const initialMainColors = makeThemeVars("mainColors", {
        /**
         * @var global.mainColors.fg
         * @title Main Colors - Foreground
         * @description Sets the foreground color
         * @type string
         * @format hex-color
         */
        fg: options.preset === GlobalPreset.LIGHT ? color("#555a62") : elementaryColors.white,
        /**
         * @var global.mainColors.bg
         * @title Main Colors - Background
         * @description Sets the background color
         * @type string
         * @format hex-color
         */
        bg: options.preset === GlobalPreset.LIGHT ? elementaryColors.white : elementaryColors.almostBlack,
        /**
         * @var global.mainColors.primary
         * @title Main Colors - Primary
         * @description Sets the primary color
         * @type string
         * @format hex-color
         */
        primary: colorPrimary,
        /**
         * @var global.mainColors.primaryContrast
         * @title Main Colors - Primary Contrast
         * @description Primary color for contrast
         * @type string
         * @format hex-color
         */
        primaryContrast: elementaryColors.white, // for good contrast with text.
        /**
         * @var global.mainColors.secondary
         * @title Main Colors - Secondary
         * @description Sets the secondary color
         * @type string
         * @format hex-color
         */
        secondary: colorPrimary,
        /**
         * @var global.mainColors.secondaryContrast
         * @title Main Colors - Secondary Contrast
         * @description Secondary color for contrast
         * @type string
         * @format hex-color
         */
        secondaryContrast: elementaryColors.white, // for good contrast with text.
    });

    colorPrimary = initialMainColors.primary;
    const colorSecondary = initialMainColors.secondary;

    // Shorthand checking bg color for darkness
    const getRatioBasedOnBackgroundDarkness = (
        weight: number,
        color: ColorHelper = mainColors ? mainColors.bg : initialMainColors.bg,
    ) => {
        return ColorsUtils.getRatioBasedOnDarkness(weight, color);
    };

    const generatedMainColors = makeThemeVars("mainColors", {
        primaryContrast: ColorsUtils.isLightColor(colorPrimary) ? elementaryColors.almostBlack : elementaryColors.white, // High contrast color, for bg/fg or fg/bg contrast. Defaults to bg.
        /**
         * @var global.mainColors.statePrimary
         * @title Main Colors - State Primary
         * @type string
         * @format hex-color
         */
        statePrimary: ColorsUtils.offsetLightness(colorPrimary, 0.04), // Default state color change
        secondary: ColorsUtils.offsetLightness(colorPrimary, 0.05),
        /**
         * @var global.mainColors.stateSecondary
         * @title Main Colors - State Secondary
         * @type string
         * @format hex-color
         */
        stateSecondary: undefined, // Calculated below, but you can overwrite it here.
        secondaryContrast: ColorsUtils.isLightColor(colorSecondary)
            ? elementaryColors.almostBlack
            : elementaryColors.white,
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

    /**
     * @varGroup global.messageColors
     * @commonDescription Global message colors
     */
    const messageColors = makeThemeVars("messageColors", {
        /**
         * @varGroup global.messageColors.warning
         * @commonDescription Message - Warning
         */
        warning: {
            /**
             * @var global.messageColors.warning.fg
             * @type string
             * @format hex-color
             */
            fg: color("#4b5057"),
            /**
             * @var global.messageColors.warning.bg
             * @type string
             * @format hex-color
             */
            bg: color("#fff1ce"),
            /**
             * @var global.messageColors.warning.state
             * @type string
             * @format hex-color
             */
            state: color("#e55a1c"),
        },
        /**
         * @varGroup global.messageColors.error
         * @commonDescription Message - Error
         */
        error: {
            /**
             * @var global.messageColors.error.fg
             * @type string
             * @format hex-color
             */
            fg: color("#d0021b"),
            /**
             * @var global.messageColors.error.bg
             * @type string
             * @format hex-color
             */
            bg: color("#FFF3D4"),
        },
        /**
         * @var global.messageColors.confirm
         * @type string
         * @format hex-color
         */
        confirm: color("#60bd68"),
        /**
         * @varGroup global.messageColors.deleted
         * @commonDescription Message - Deleted
         */
        deleted: {
            /**
             * @var global.messageColors.deleted.fg
             * @type string
             * @format hex-color
             */
            fg: color("#D0021B"),
            /**
             * @var global.messageColors.deleted.bg
             * @type string
             * @format hex-color
             */
            bg: color("#D0021B"),
        },
    });

    const links = makeThemeVars("links", {
        /**
         * @varGroup global.links.colors
         * @commonTitle Global Link Colors
         * @expand clickable
         */
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
    Object.keys(links.colors).forEach((state) => {
        if (state !== "default" && state !== "visited") {
            if (!links[state]) {
                links.colors[state] =
                    generatedMainColors.stateSecondary ?? ColorsUtils.offsetLightness(links.colors.default, 0.008);
            }
        }
    });

    interface IBody {
        backgroundImage: IBackground;
    }

    const body: IBody = makeThemeVars("body", {
        /**
         * @varGroup global.body.backgroundImage
         * @description Background variables for the page.
         * @expand background
         */
        backgroundImage: Variables.background({
            color: mainColors.bg,
        }),
    });

    const border = makeThemeVars("border", {
        /**
         * @var global.border.color
         * @title Border Color
         * @description Choose the color of the border.
         * @type string
         * @format hex-color
         */
        color: mixBgAndFg(0.2),
        /**
         * @var global.border.width
         * @title Border Hover Color
         * @description Choose the hover color of the border.
         * @type string
         * @format hex-color
         */
        colorHover: mixBgAndFg(0.4),
        /**
         * @var global.border.width
         * @title Border Width
         * @description Choose the width of the border.
         * @type number|string
         */
        width: 1,
        /**
         * @var global.border.style
         * @title Border Style
         * @description Choose the style of the border.
         * @type number|string
         */
        style: "solid",
        /**
         * @var global.border.radius
         * @title  Border Radius
         * @description Choose the radius of the border.
         * @type number|string
         */
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
        contentBox: border,
    });

    const gutterSize = 16;
    // @Deprecated - It's confusing that this gutter is 16 and the layout is 48.
    // TODO: Refactor
    /**
     * @varGroup global.gutter
     * @commonTitle Global - Gutter
     */
    const gutter = makeThemeVars("gutter", {
        /**
         * @var global.gutter.size
         */
        size: gutterSize,
        /**
         * @var global.gutter.half
         */
        half: gutterSize / 2,
        /**
         * @var global.gutter.quater
         */
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

    /**
     * @varGroup global.lineHeight
     * @commonTitle Global - Line Height
     */
    const lineHeights = makeThemeVars("lineHeight", {
        /**
         * @var global.lineHeight.base
         */
        base: 1.5,
        /**
         * @var global.lineHeight.condensed
         */
        condensed: 1.25,
        /**
         * @var global.lineHeight.code
         */
        code: 1.45,
        /**
         * @var global.lineHeight.excerpt
         */
        excerpt: 1.4,
        /**
         * @var global.lineHeight.meta
         */
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
        /**
         * @var global.widget.padding
         */
        padding: 10,
    });

    const widget = makeThemeVars("widget", {
        ...widgetInit,
        paddingBothSides: widgetInit.padding * 2,
    });

    const panelInit = makeThemeVars("panel", {
        /**
         * @var global.panel.width
         */
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
        /**
         * @varGroup global.fonts.size
         * @commonDescription Preset global font size
         */
        size: {
            /**
             * @var global.fonts.size.large
             */
            large: 16,
            /**
             * @var global.fonts.size.medium
             */
            medium: 14,
            /**
             * @var global.fonts.size.small
             */
            small: 12,
            /**
             * @var global.fonts.size.largeTitle
             */
            largeTitle: 32,
            /**
             * @var global.fonts.size.title
             */
            title: 22,
            /**
             * @var global.fonts.size.subTitle
             */
            subTitle: 18,
        },
        sizeWeight: {
            large: undefined as undefined | number,
            medium: undefined as undefined | number,
            small: undefined as undefined | number,
            largeTitle: undefined as undefined | number,
            title: undefined as undefined | number,
            subTitle: undefined as undefined | number,
        },

        mobile: {
            /**
             * @varGroup global.fonts.mobile.size
             * @commonTitle Global - Mobile
             */
            size: {
                /**
                 * @var global.mobile.size.title
                 */
                title: 26,
            },
        },
        /**
         * @varGroup global.fonts.weights
         * @commonDescription Predefined global font weight
         */
        weights: {
            /**
             * @var global.fonts.weights.normal
             */
            normal: 400,
            /**
             * @var global.fonts.weights.semiBold
             */
            semiBold: 600,
            /**
             * @var global.fonts.weights.bold
             */
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

    /**
     * @varGroup global.icon
     * @commonDescription Sets size and color of icon
     */
    const icon = makeThemeVars("icon", {
        /**
         * @varGroup global.icon.sizes
         * @commonDescription Sets icon size
         */
        sizes: {
            /**
             * @var global.sizes.large
             */
            large: 32,
            /**
             * @var global.sizes.default
             */
            default: 24,
            /**
             * @var global.sizes.small
             */
            small: 16,
            /**
             * @var global.sizes.xSmall
             */
            xSmall: 9.5,
        },
        /**
         * @var global.icon.color
         * @title Icon - Color
         * @description Sets the size of the icon
         * @type string
         * @format hex-color
         */
        color: mixBgAndFg(0.18),
    });

    const spacer = makeThemeVars("spacer", {
        /**
         * @var global.spacer.size
         * @description Sets the size of the spacing element
         * @type number
         */
        size: fonts.size.medium * lineHeights.base,
    });

    const animation = makeThemeVars("animation", {
        defaultTiming: ".10s",
        defaultEasing: "ease-out",
    });

    /**
     * @varGroup global.embed
     * @commonDescription Global - Embed
     */
    const embed = makeThemeVars("embed", {
        /**
         * @varGroup global.embed.error
         * @commonDescription  Embed - Error
         */
        error: {
            /**
             * @var global.embed.error.bg
             */
            bg: messageColors.error,
        },
        /**
         * @varGroup global.embed.focus
         * @commonDescription  Embed - Focus
         */
        focus: {
            /**
             * @var global.embed.focus.color
             */
            color: mainColors.primary,
        },
        /**
         * @varGroup global.embed.text
         * @commonDescription  Embed - Text
         */
        text: {
            /**
             * @var global.embed.text.padding
             */
            padding: fonts.size.medium,
        },
        /**
         * @varGroup global.embed.sizing
         * @commonDescription  Embed - Sizing
         */
        sizing: {
            /**
             * @var global.embed.text.smallPadding
             */
            smallPadding: 4,
            /**
             * @var global.embed.text.width
             */
            width: 640,
        },
        /**
         * @varGroup global.embed.select
         * @commonDescription  Embed - Select
         */
        select: {
            /**
             * @var global.embed.select.borderWidth
             */
            borderWidth: 2,
        },
        /**
         * @varGroup global.embed.overlay
         * @commonDescription  Embed - Overlay
         */
        overlay: {
            /**
             * @varGroup global.embed.overlay.hover
             * @commonDescription  Embed - Overlay - Hover
             */
            hover: {
                /**
                 * @var global.embed.overlay.hover.color
                 */
                color: mainColors.bg.fade(0.5),
            },
        },
    });

    /**
     * @varGroup global.meta
     * @commonDescription Global meta
     */
    const meta = makeThemeVars("meta", {
        /**
         * @varGroup global.meta.font
         * @commonDescription Global meta font
         * @expand font
         */
        text: Variables.font({
            size: fonts.size.small,
            color: options.preset === GlobalPreset.LIGHT ? color("#767676") : elementaryColors.white,
            lineHeight: lineHeights.base,
        }),
        /**
         * @varGroup global.meta.spacing
         * @commonDescription Global meta spacing
         */
        spacing: {
            /**
             * @var global.meta.spacing.horizontalMargin
             */
            horizontalMargin: 4,
            /**
             * @var global.meta.spacing.verticalMargin
             */
            verticalMargin: 12,
            /**
             * @var global.meta.spacing.default
             */
            default: 4,
        },
        /**
         * @varGroup global.meta.colors
         * @commonDescription Global meta colors
         */
        colors: {
            /**
             * @var global.meta.colors.fg
             */
            fg: mixBgAndFg(0.85),
        },
        /**
         * @var global.meta.display
         */
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
            highlight: links.colors.default.fade(constants.states.hover.bgEmphasis),
            contrast: undefined,
            opacity: 1.0,
        },
        selected: {
            highlight: links.colors.default.fade(constants.states.selected.bgEmphasis),
            contrast: undefined,
            opacity: 1,
        },
        active: {
            highlight: links.colors.default.fade(constants.states.active.bgEmphasis),
            contrast: undefined,
            opacity: 1,
        },
        focus: {
            highlight: links.colors.default.fade(constants.states.focus.bgEmphasis),
            contrast: undefined,
            opacity: 1,
        },
    });

    const overlayBg = ColorsUtils.modifyColorBasedOnLightness({ color: mainColors.fg, weight: 0.5 });

    /**
     * @varGroup global.overlay
     * @commonDescription Global - OverLay
     */
    const overlay = makeThemeVars("overlay", {
        /**
         * @var global.overlay.dropShadow
         */
        dropShadow: `2px -2px 5px ${ColorsUtils.colorOut(overlayBg.fade(0.3))}`,
        /**
         * @var global.overlay.bg
         */
        bg: overlayBg,
        /**
         * @varGroup global.overlay.border
         * @expand border
         */
        border: {
            color: border.color,
            radius: border.radius,
        },
        /**
         * @var global.overlay.fullPageHeadingSpacer
         */
        fullPageHeadingSpacer: 32,
        /**
         * @var global.overlay.spacer
         */
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

    /**
     * @varGroup global.buttonIcon
     * @commonDescription Controls icon in button
     */
    const buttonIcon = makeThemeVars("buttonIcon", {
        /**
         * @var  global.buttonIcon.size
         * @type number
         */
        size: buttonIconSize,
        /**
         * @var  global.buttonIcon.offset
         * @type number
         */
        offset: (buttonIconSize - icon.sizes.default) / 2,
    });

    // Sets global "style" for buttons. Use "ButtonPreset" enum to select. By default we use both "bordered" (default) and "solid" (primary) button styles
    // The other button styles are all "advanced" and need to be overwritten manually because they can't really be converted without completely changing
    // the style of them.
    const buttonPreset = makeThemeVars("buttonPreset", {
        style: undefined as ButtonPreset | undefined,
    });

    /**
     * @varGroup global.separator
     * @commonDescription Sets color and size of separator
     */
    const separator = makeThemeVars("separator", {
        /**
         * @var global.separator.color
         * @type string
         * @format hex-color
         */
        color: border.color,
        /**
         * @var global.separator.size
         * @type number
         */
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

    /**
     * @varGroup global.contentBoxes
     * @description Global content box preset that will apply to every page.
     * Some pages may have their own that will need to modified separately.
     * @expand contentBoxes
     */
    const contentBoxes = makeThemeVars(
        "contentBoxes",
        Variables.contentBoxes({
            depth1: {
                borderType: BorderType.NONE,
            },
            depth2: {
                borderType: BorderType.SEPARATOR,
            },
            depth3: {
                borderType: BorderType.SEPARATOR,
            },
        }),
    );

    /**
     * @varGroup global.headingBox
     * @description Heading boxes sit above every box.
     * They can have titles, subtitles, descriptions, and sometimes action items in them.
     */
    const headingBox = makeThemeVars("headingBox", {
        /**
         * @varGroup global.headingBox.spacing
         * @expand spacing
         */
        spacing: Variables.spacing({
            top: 24,
            bottom: 8,
            horizontal: 0,
        }),
        /**
         * @varGroup global.headingBox.mobileSpacing
         * @expand spacing
         */
        mobileSpacing: Variables.spacing({
            top: 28,
            bottom: 16,
            horizontal: 0,
        }),
        /**
         * @varGroup global.headingBox.descriptionSpacing
         * @expand spacing
         */
        descriptionSpacing: Variables.spacing({
            top: 8,
            bottom: 0,
            horizontal: 0,
        }),
    });

    const itemList = makeThemeVars("itemList", {
        /**
         * @varGroup global.itemList
         * @commonTitle Global - Item List
         * @expand spacing
         */
        padding: Variables.spacing({
            top: 15,
            right: widget.padding,
            bottom: 16,
            left: widget.padding,
        }),
    });

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
        headingBox,
        itemList,
        contentBoxes,
    };
});
