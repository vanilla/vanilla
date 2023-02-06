/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ButtonPreset } from "@library/forms/ButtonPreset";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { IBackground, IFont, LinkDecorationType } from "@library/styles/cssUtilsTypes";
import { fontFallbacks, monoFallbacks } from "@library/styles/fontFallbacks";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { ensureColorHelper } from "@library/styles/styleHelpersColors";
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

    let colorPrimary = color("#037DBC");
    if (options.preset === GlobalPreset.DARK) {
        // given better contrast in the dark preset.
        colorPrimary = colorPrimary.lighten(0.25);
    }

    const elementaryColors = {
        black: color("#000"),
        almostBlack: color("#272A2D"),
        darkText: color("#555a62"),
        white: color("#fff"),
        almostWhite: color("#f5f6f7"),
        transparent: rgba(0, 0, 0, 0),
        primary: colorPrimary,
    };

    /**
     * @varGroup global.mainColors
     * @commonDescription Main colors
     */
    const mainColorsInit = makeThemeVars("mainColors", {
        /**
         * @var global.mainColors.fg
         * @title Foreground
         * @description Sets the foreground color
         * @type string
         * @format hex-color
         */
        fg: options.preset === GlobalPreset.LIGHT ? elementaryColors.darkText : elementaryColors.almostWhite,

        /**
         * @var global.mainColors.bg
         * @title Background
         * @description Sets the background color
         * @type string
         * @format hex-color
         */
        bg: options.preset === GlobalPreset.LIGHT ? elementaryColors.white : elementaryColors.almostBlack,
        /**
         * @var global.mainColors.primary
         * @title Primary
         * @description Sets the primary color
         * @type string
         * @format hex-color
         */
        primary: colorPrimary,
        /**
         * @var global.mainColors.primaryContrast
         * @title Primary Contrast
         * @description Primary color for contrast
         * @type string
         * @format hex-color
         */
        primaryContrast: elementaryColors.white, // for good contrast with text.
        /**
         * @var global.mainColors.secondary
         * @title Secondary
         * @description Sets the secondary color
         * @type string
         * @format hex-color
         */
        secondary: colorPrimary,
        /**
         * @var global.mainColors.secondaryContrast
         * @title Secondary Contrast
         * @description Secondary color for contrast
         * @type string
         * @format hex-color
         */
        secondaryContrast: elementaryColors.white, // for good contrast with text.
    });

    const initialMainColors = makeThemeVars("mainColors", {
        ...mainColorsInit,
        /**
         * @var global.mainColors.fgHeading
         * @title Foreground Heading
         * @description Sets the foreground color of headings
         * @type string
         * @format hex-color
         */
        fgHeading: mainColorsInit.fg,
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
         * @title State Primary
         * @type string
         * @format hex-color
         */
        statePrimary: ColorsUtils.offsetLightness(colorPrimary, 0.04), // Default state color change
        secondary: ColorsUtils.offsetLightness(colorPrimary, 0.05),
        /**
         * @var global.mainColors.stateSecondary
         * @title State Secondary
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

    const getFgForBg = (bgColor: ColorHelper | string | undefined) => {
        bgColor = bgColor ?? mainColors.bg;
        bgColor = ensureColorHelper(bgColor);
        const darkFg = options.preset === GlobalPreset.LIGHT ? mainColors.fg : mainColors.bg;

        return ColorsUtils.isLightColor(bgColor) ? darkFg : elementaryColors.almostWhite;
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
     * @commonDescription Message colors
     */
    const messageColors = makeThemeVars("messageColors", {
        /**
         * @varGroup global.messageColors.warning
         * @commonDescription Warning
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
         * @commonDescription Error
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
         * @commonDescription Deleted
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
         * @commonTitle Link Colors
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

        /**
         * @var global.links.linkDecorationType
         * @commonTitle Link Decoration Type
         * @type string
         * @enum auto | always
         */
        linkDecorationType: LinkDecorationType.AUTO,
    });

    // Generated derived colors from mainColors.
    // You can set all of them by setting generatedMainColors.stateSecondary
    // You can set individual states with links.colors["your state"]
    // Will default to variation of links.colors.default (which is by default the secondary color)
    Object.keys(links.colors).forEach((state) => {
        if (state !== "default" && state !== "visited") {
            if (!links.colors[state]) {
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
         * @var global.border.colorHover
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
     * @commonTitle Gutter
     */
    let gutterVars = makeThemeVars("gutter", {
        /**
         * @var global.gutter.size
         * @type number
         */
        size: gutterSize,
    });

    const gutter = makeThemeVars("gutter", {
        ...gutterVars,
        half: gutterVars.size / 2,
        quarter: gutterVars.size / 4,
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
     * @commonTitle Line Height
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
         * @type number
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
         * @type number
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
             * @var global.fonts.size.extraSmall
             */
            extraSmall: 10,
            /**
             * @var global.fonts.size.largeTitle
             */
            largeTitle: 32,
            /**
             * @var global.fonts.size.title
             */
            title: 24,
            /**
             * @var global.fonts.size.subTitle
             */
            subTitle: 18,
        },
        sizeWeight: {
            // Intentinlaly undocumented until stabilized.
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
             * @commonTitle Size
             */
            size: {
                /**
                 * @var global.fonts.mobile.size.title
                 * @type number
                 */
                title: 20,
                /**
                 * @var global.fonts.mobile.size.largeTitle
                 * @type number
                 */
                largeTitle: 26 as undefined | number,
            },
        },
        /**
         * @varGroup global.fonts.weights
         * @commonDescription Predefined global font weight
         */
        weights: {
            /**
             * @var global.fonts.weights.normal
             * @type number
             */
            normal: 400,
            /**
             * @var global.fonts.weights.semiBold
             * @type number
             */
            semiBold: 600,
            /**
             * @var global.fonts.weights.bold
             * @type number
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

    if (fonts.customFont.name && fonts.customFont.url) {
        fonts.forceGoogleFont = false;
        fonts.googleFontFamily = "custom";
    }

    const fontSizeAndWeightVars = (
        size: keyof typeof fonts.size,
        weight?: keyof typeof fonts.weights,
    ): Pick<IFont, "size" | "weight"> => {
        return {
            size: fonts.size[size],
            weight: weight ? fonts.weights[weight] : fonts.sizeWeight[size],
        };
    };

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
             * @var global.icon.sizes.large
             * @type number
             */
            large: 32,
            /**
             * @var global.icon.sizes.default
             * @type number
             */
            default: 24,
            /**
             * @var global.icon.sizes.small
             * @type number
             */
            small: 16,
            /**
             * @var global.icon.sizes.xSmall
             * @type number
             */
            xSmall: 9.5,
        },
        /**
         * @var global.icon.color
         * @title Color
         * @description Sets the size of the icon
         * @type string
         * @format hex-color
         */
        color: mixBgAndFg(0.18),
    });

    /**
     * @varGroup global.spacer
     * @title Spacers
     * @description Commonly used spacing in components and around them. This is the primary place to adjust spacing of the site overall.
     *
     * Component spacings collapse when next to each other where possible.
     */
    const spacer = makeThemeVars("spacer", {
        // @deprecated
        size: fonts.size.medium * lineHeights.base,

        /**
         * @var global.spacer.mainLayout
         * @description Controls spacing around main layouts like panel layouts.
         * When there are breadcrumbs this controls above the breadcrumbs.
         * @type number
         */
        mainLayout: 40,

        /**
         * @var global.spacer.pageComponent
         * @description Controls spacing around and inside of most top level site widgets placed in the top level of a page.
         */
        pageComponent: 48,

        /**
         * @var global.spacer.pageComponentCompact
         * @description Controls spacing around and inside of most widgets placed in the top level of a page **on mobile device sizes**.
         *
         * Additionally this value is used for widgets placed inside of the main panel in panel layouts on all device sizes.
         */
        pageComponentCompact: 32,

        /**
         * @var global.spacer.panelComponent
         * @description Controls spacing around and inside of most widgets placed inside of a secondary/side panel.
         */
        panelComponent: 16,

        /**
         * @var global.spacer.headingBox
         * @description Controls spacing below heading boxes (a heading box includes a title, optional description, and optional subtitle). This will be used in addition to the `headingItem` spacing.
         */
        headingBox: 16,

        /**
         * @var global.spacer.headingBoxCompact
         * @description Controls spacing below heading boxes (a heading box includes a title, optional description, and optional subtitle). This will be used in addition to the `headingItem` spacing.
         *
         * **This compact version is used on viewport sizes.**
         */
        headingBoxCompact: 8,

        /**
         * @var global.spacer.headingBoxCompact
         * @description Controls spacing titles, descriptions and subtitles inside of a heading box.
         *
         * **This compact version is used on viewport sizes.**
         */
        headingItem: 8,

        /**
         * @var global.spacer.headingBoxCompact
         * @description Controls inside small components with borders or shadows around them.
         *
         * **This compact version is used on viewport sizes.**
         */
        componentInner: 16,
    });

    const animation = makeThemeVars("animation", {
        defaultTiming: ".10s",
        defaultEasing: "ease-out",
    });

    /**
     * @varGroup global.embed
     * @commonDescription Embed
     */
    const embed = makeThemeVars("embed", {
        /**
         * @varGroup global.embed.error
         * @commonDescription Error
         */
        error: {
            /**
             * @var global.embed.error.bg
             */
            bg: messageColors.error,
        },
        /**
         * @varGroup global.embed.focus
         * @commonDescription Focus
         */
        focus: {
            /**
             * @var global.embed.focus.color
             */
            color: mainColors.primary,
        },
        /**
         * @varGroup global.embed.text
         * @commonDescription Text
         */
        text: {
            /**
             * @var global.embed.text.padding
             */
            padding: fonts.size.medium,
        },
        /**
         * @varGroup global.embed.sizing
         * @commonDescription Sizing
         */
        sizing: {
            /**
             * @var global.embed.text.smallPadding
             */
            smallPadding: 4,
            /**
             * @var global.embed.text.width
             */
            width: 720,
        },
        /**
         * @varGroup global.embed.select
         * @commonDescription Select
         */
        select: {
            /**
             * @var global.embed.select.borderWidth
             */
            borderWidth: 2,
        },
        /**
         * @varGroup global.embed.overlay
         * @commonDescription Overlay
         */
        overlay: {
            /**
             * @varGroup global.embed.overlay.hover
             * @commonDescription Hover
             */
            hover: {
                /**
                 * @var global.embed.overlay.hover.color
                 * @type string
                 * @format hex-color
                 */
                color: mainColors.bg.fade(0.5),
            },
        },
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
     * @title Overlay
     */
    const overlay = makeThemeVars("overlay", {
        /**
         * @var global.overlay.dropShadow
         * @type string
         */
        dropShadow: `2px -2px 5px ${ColorsUtils.colorOut(overlayBg.fade(0.3))}`,
        /**
         * @var global.overlay.bg
         * @type string
         * @format hex-color
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
         * @type number
         */
        fullPageHeadingSpacer: 32,
        /**
         * @var global.overlay.spacer
         * @type number
         */
        spacer: 32,
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
     * @description Content box preset that will apply to every page.
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
     * @varGroup global.panelBoxes
     * @description Panel box preset that will apply to panel items on every page.
     * Some panel items may have their own that will need to modified separately.
     * @expand contentBoxes
     */
    const panelBoxes = makeThemeVars(
        "panelBoxes",
        Variables.contentBoxes({
            depth1: {
                borderType: BorderType.NONE,
            },
            depth2: {
                borderType: BorderType.NONE,
            },
            depth3: {
                borderType: BorderType.NONE,
            },
        }),
    );

    const itemList = makeThemeVars("itemList", {
        /**
         * @varGroup global.itemList
         * @commonTitle Item List
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
        getFgForBg,
        mixBgAndFg,
        mixPrimaryAndFg,
        mixPrimaryAndBg,
        separator,
        findColorMatch,
        constants,
        getRatioBasedOnBackgroundDarkness,
        foundationalWidths,
        widget,
        itemList,
        contentBoxes,
        panelBoxes,
        fontSizeAndWeightVars,
    };
});
