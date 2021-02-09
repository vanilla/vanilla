/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { BorderType } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Variables } from "@library/styles/Variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { getPixelNumber, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { color, px, rgba, viewHeight } from "csx";
import { media, TLength } from "@library/styles/styleShim";
import { CSSObject } from "@emotion/css";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { LogoAlignment } from "./LogoAlignment";
import { BackgroundProperty } from "csstype";
import { IThemeVariables } from "@library/theming/themeReducer";

/**
 * @varGroup titleBar
 * @title Title Bar
 * @description The TitleBar is the sticky navigation bar, normally containing a logo,
 * navigation links, a searchbar, and the mebox.
 */

export const titleBarVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const formElementVars = formElementsVariables(forcedVars);
    const makeThemeVars = variableFactory("titleBar", forcedVars);

    const sizing = makeThemeVars("sizing", {
        /**
         * @var titleBar.sizing.height
         * @description Pixel height of the Title Bar. Recommended between 48 and 90
         * @type number|string
         */
        height: 48,
        spacer: 12,
        mobile: {
            /**
             * @var titleBar.sizing.mobile.height
             * @title Height (Mobile)
             * @description Pixel height of the Title Bar for mobile screen sizes.
             * As mobile devices have smaller screens, it's recommended to use a smaller height here than on desktop.
             * @type number|string
             */
            height: 44,
            width: formElementVars.sizing.height,
        },
    });

    const spacing = makeThemeVars("spacing", {
        /**
         * @varGroup titleBar.spacing.padding
         * @title Title Bar
         * @expand spacing
         */
        padding: Variables.spacing({
            top: 0,
            bottom: 0,
        }),
    });

    // Note that this overlay will go on top of the bg image, if you have one.
    const overlay = makeThemeVars("overlay", {
        /**
         * @var titleBar.overlay.background
         * @title Background Overlay
         * @description An background to overlay on top of a background image if one is specified.
         * Accepts any value that works for a CSS "background" property.
         * This can be useful to add contrast to a titlebar background that otherwise might not have enough.
         * @default linear-gradient(to bottom, rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.3))
         */
        background: undefined as BackgroundProperty<TLength> | Array<BackgroundProperty<TLength>> | undefined,
    });

    const colorsInit = makeThemeVars("colors", {
        /**
         * @var titleBar.colors.fg
         * @title Foreground/Text Color
         * @description Foreground color for the titlebar.
         * It's recommended that this color have sufficient color with the background color.
         * @type string
         * @format hex-color
         */
        fg: globalVars.mainColors.primaryContrast,
        /**
         * @var titleBar.colors.bg
         * @title Background Color
         * @description Background color for the titlebar.
         * It's recommended that this color have sufficient color with the foreground color.
         * @type string
         * @format hex-color
         */
        bg: globalVars.mainColors.primary,
        /**
         * @var titleBar.colors.bgImage
         * @title Background Image
         * @type string
         * @description Apply a background image over the Title Bar.
         * This image will will fill the full background of the Title Bar and will not be stretched.
         *
         * It's recommended to use a very wide patterned image,
         * of which the middle portion make sense on it's own (for mobile devices.)
         *
         * This should be a full URL to an image.
         */
        bgImage: null as string | null,
    });

    const colors = makeThemeVars("colors", {
        ...colorsInit,
        /**
         * @varGroup titleBar.colors.state
         * @title TitleBar - State Colors
         * @commonTitle TitleBar
         * @commonDescription By default this color is generated based on the regular color.
         */
        state: {
            /**
             * @var titleBar.colors.state.bg
             * @title Background Color (State)
             * @description The background hover/state color for various buttons in the Title Bar.
             * It is recommended that this color have strong contrast with the foreground color.
             * @type string
             * @format hex-color
             */
            bg: ColorsUtils.isLightColor(colorsInit.bg) ? rgba(0, 0, 0, 0.1) : rgba(255, 255, 255, 0.1),

            /**
             * @var titleBar.colors.state.bg
             * @title Foreground Color (State)
             * @description The foreground hover/state color for various buttons in the Title Bar.
             * It is recommended that this color have strong contrast with the background color.
             * @type string
             * @format hex-color
             */
            fg: colorsInit.fg,
        },
    });

    /**
     * @varGroup titleBar.border
     * @title TitleBar - Border
     * @expand border
     */
    const border = makeThemeVars("border", {
        /**
         * @var titleBar.border.type
         * @type string
         * @enum none|border|shadow
         */
        type: BorderType.NONE,
        color: globalVars.border.color,
        width: globalVars.border.width,
    });

    // sizing.height gives you the height of the contents of the titleBar.
    // If spacing.paddingBottom is set, this value will be different than the height. To be used if you need to get the full height, not just the contents height.
    const fullHeight =
        getPixelNumber(sizing.height) +
            getPixelNumber(spacing.padding.bottom) +
            getPixelNumber(spacing.padding.top) +
            border.type ==
        BorderType.SHADOW_AS_BORDER
            ? getPixelNumber(border.width)
            : 0;

    /**
     * @varGroup titleBar.swoop
     * @title Banner - Swoop
     * @commonDescription The follow variables should be used together to fine your desired swoop.
     * - titleBar.spacings.paddings.top
     * - titleBar.spacings.paddings.bottom
     * - titleBar.swoop.amount
     * - titleBar.swoop.swoopOffset
     * - titleBar.sizing.height
     */
    const swoopInit = makeThemeVars("swoop", {
        /**
         * @var titleBar.swoop.amount
         * @description Create a curved bottom border on the Title Bar.
         * Larger numbers give a bigger "swoop". Please note that this adds some additional space into the bottom of the titlebar.
         * A value of 10 gives a very subtle swoop that should not require much adjustment elsewhere.
         * A value of 50 gives a very pronounced swoop and will require adjustments in padding to compensate.
         * @default 20
         * @type number
         */
        amount: 0,
    });

    const swoop = makeThemeVars("swoop", {
        ...swoopInit,
        /**
         * @var titleBar.swoop.swoopOffset
         * @title Offset
         * @description This parameter determines how much the swoop extends "outside" of the TitleBar
         * Large offsets may require adjustments in padding.
         * @type number
         */
        swoopOffset: (16 * swoopInit.amount) / 50,
    });

    /**
     * @varGroup titleBar.fullBleed
     * @title TitleBar - Full Bleed
     */
    const fullBleed = makeThemeVars("fullBleed", {
        /**
         * @var titleBar.fullBleed.enabled
         * @description Enable a "full bleed" title bar.
         * This caused to titlebar to be transparent or semi-transparent and site *on top* of the banner.
         * @type boolean
         */
        enabled: false,

        /**
         * @var titleBar.fullBleed.startingOpacity
         * @type number
         * @description An opacity value between 0 and 1 that determins how transparent the full bleed titlebar is
         * when sitting on top of the banner.
         *
         * 0 - Fully transparent.
         * 0.3 - Semi transparent.
         * 1 - Completely solid
         * @default 0
         */
        startingOpacity: 0,

        /**
         * @var titleBar.fullBleed.endingOpacity
         * @type number
         * @description An opacity value between 0 and 1 that determins how transparent the full bleed titlebar is
         * as the banner is scrolled out of view.
         *
         * The banner *always* becomes completely solid after the transition,
         * so this value only affects the transition between states.
         *
         * This value cannot be less than the `titleBar.fullBleed.startingOpacity` value.
         *
         * 0 - Fully transparent.
         * 0.3 - Semi transparent.
         * 1 - Completely solid
         * @default 0.15
         */
        endingOpacity: 0.15,

        /**
         * @var titleBar.fullBleed.bgColor
         * @title Background Color
         * @description The background color of the full bleed titlebar when it is transparent/semi-transparent.
         * This defaults to the normal titleBar.colors.bg color, but can be a different color.
         *
         * For example, you may want to use a semi-transparent white/black while overlaying the banner,
         * but want the titlebar to transition a brand color when on it's own or scrolled down the page.
         * @type string
         * @format hex-color
         */
        bgColor: colors.bg,
    });

    const clearBorder = {
        type: BorderType.NONE,
        color: "transparent",
        width: styleUnit(0),
        radius: styleUnit(0),
    };

    const titleBarContainer = makeThemeVars("titleBarContainer", {
        /**
         * @var titleBar.titleBarContainer.maxWidth
         * @description Set the maximum width of the titlebar.
         * @type number
         */
        maxWidth: undefined as number | undefined,

        /**
         * @varGroup titleBar.titleBarContainer.gutterSpacing
         * @description Gutters for the container of the titlebar.
         * @expand spacing
         */
        gutterSpacing: Variables.spacing({}),
        border: Variables.border({
            left: {
                ...clearBorder,
            },
            right: {
                ...clearBorder,
            },
            top: {
                ...clearBorder,
            },
            bottom: {
                ...clearBorder,
            },
        }),
    });

    // Fix up the ending opacity so it is always darker than the starting one.
    fullBleed.endingOpacity = Math.max(fullBleed.startingOpacity, fullBleed.endingOpacity);

    const guest = makeThemeVars("guest", {
        spacer: 8,
    });

    const buttonSize = globalVars.buttonIcon.size;
    const button = makeThemeVars("button", {
        borderRadius: globalVars.border.radius,
        size: buttonSize,
        guest: {
            minWidth: 86,
        },
        mobile: {
            fontSize: 16,
            width: buttonSize,
        },
        state: {
            bg: colors.state.bg,
        },
    });

    const generatedColors = makeThemeVars("generatedColors", {
        state: ColorsUtils.offsetLightness(colors.bg, 0.04),
    });

    const linkButtonDefaults: IButtonType = {
        name: ButtonTypes.TITLEBAR_LINK,
        colors: {
            bg: rgba(0, 0, 0, 0),
            fg: colors.fg,
        },
        fonts: Variables.font({
            color: colors.fg,
        }),
        sizing: {
            minWidth: styleUnit(globalVars.icon.sizes.large),
            minHeight: styleUnit(globalVars.icon.sizes.large),
        },
        padding: {
            horizontal: 6,
        },
        borders: {
            style: "none",
            color: rgba(0, 0, 0, 0),
        },
        hover: {
            colors: {
                bg: generatedColors.state,
            },
        },
        focus: {
            colors: {
                bg: generatedColors.state,
            },
        },
        focusAccessible: {
            colors: {
                bg: generatedColors.state,
            },
        },
        active: {
            colors: {
                bg: generatedColors.state,
            },
        },
    };
    const linkButton: IButtonType = makeThemeVars("linkButton", linkButtonDefaults);

    const count = makeThemeVars("count", {
        size: 18,
        fontSize: 10,
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.primary,
    });

    const dropDownContents = makeThemeVars("dropDownContents", {
        minWidth: 350,
        maxHeight: viewHeight(90),
    });

    const endElements = makeThemeVars("endElements", {
        flexBasis: buttonSize * 4,
        mobile: {
            flexBasis: button.mobile.width - 20,
        },
    });

    const buttonContents = makeThemeVars("buttonContents", {
        state: {
            bg: button.state.bg,
        },
    });

    const signIn = makeThemeVars("signIn", {
        fg: colors.fg,
        bg: ColorsUtils.modifyColorBasedOnLightness({
            color: globalVars.mainColors.primary,
            weight: 0.1,
            inverse: true,
        }),
        hover: {
            bg: ColorsUtils.modifyColorBasedOnLightness({
                color: globalVars.mainColors.primary,
                weight: 0.2,
                inverse: true,
            }),
        },
    });

    const resister = makeThemeVars("register", {
        fg: colors.bg,
        bg: colors.fg,
        borderColor: colors.bg,
        states: {
            bg: colors.fg.fade(0.9),
        },
    });

    const mobileDropDown = makeThemeVars("mobileDropdown", {
        height: px(sizing.mobile.height),
    });

    const meBox = makeThemeVars("meBox", {
        sizing: {
            buttonContents: formElementVars.sizing.height,
        },
    });

    // Note that the logo defined here is the last fallback. If set through the dashboard, it will overwrite these values.
    /**
     * @varGroup titleBar.logo
     * @title TitleBar - Logo
     */
    const logo = makeThemeVars("logo", {
        /**
         * @var titleBar.logo.doubleLogoStrategy
         * @description A strategy for dealing with multiple logos when using a custom theme header with the titlebar.
         * - visible (default) - Always show the logo in the titlebar.
         * - hidden - Hide the logo in the titlebar.
         * - fade-in - Fade the logo in after the custom theme header scrolls of the page.
         * @type string
         * @enum visible | hidden | fade-in
         * @default visible
         */
        doubleLogoStrategy: "visible" as "hidden" | "visible" | "fade-in",
        /**
         * @var titleBar.logo.offsetRight
         * @title Right Margin
         * @description Apply some spacing between the logo and the navigation items.
         * @type number
         */
        offsetRight: globalVars.gutter.size,

        /**
         * @var titleBar.logo.justifyContent
         * @title Logo Placement
         * @description Where to place the logo in the TitleBar.
         * @type string
         * @enum left | center
         */
        justifyContent: LogoAlignment.LEFT,
        maxHeight: undefined,
        /**
         * @var titleBar.logo.maxWidth
         * @type string|number
         * @description Maximum pixel width to the display the logo.
         */
        maxWidth: 200,
        heightOffset: sizing.height / 3,
        tablet: {},
        desktop: {
            url: undefined,
        },
        mobile: {
            url: undefined,
            /**
             * @var titleBar.logo.mobile.maxWidth
             * @type string | number
             * @description Maximum pixel width to the display the logo on mobile device sizes.
             */
            maxWidth: undefined,
            maxHeight: undefined,
            heightOffset: sizing.mobile.height / 4,
        },
        offsetVertical: {
            amount: 0,
            mobile: {
                amount: 0,
            },
        },
    });

    /**
     * @var titleBar.navAlignment.alignment
     * @title Navigation Alignment
     * @description How to align the navigation in the titlebar.
     * _Note: The navigation cannot use center alignment while the logo placement is set to center.
     * @type string
     * @enum left | center
     */
    const navAlignment = makeThemeVars("navAlignment", {
        alignment: "left" as "left" | "center",
    });

    if (logo.justifyContent === LogoAlignment.CENTER) {
        // Forced to the left because they can't both be in the center.
        navAlignment.alignment = "left";
    }

    const mobileLogo = makeThemeVars("mobileLogo", {
        justifyContent: LogoAlignment.CENTER,
    });

    const breakpoints = makeThemeVars("breakpoints", {
        compact: 800,
    });

    const mediaQueries = () => {
        const full = (styles: CSSObject, useMinWidth: boolean = true) => {
            return media(
                {
                    minWidth: breakpoints.compact + 1,
                },
                styles,
            );
        };

        const compact = (styles: CSSObject) => {
            return media(
                {
                    maxWidth: breakpoints.compact,
                },
                styles,
            );
        };

        return {
            full,
            compact,
        };
    };

    const cancelButtonInit = makeThemeVars("closeButtonInit", {
        allStates: colors.fg,
        hoverOpacity: globalVars.constants.states.hover.borderEmphasis,
    });

    const stateColors = makeThemeVars("stateColors", {
        hover: cancelButtonInit.allStates.mix(colorsInit.bg, cancelButtonInit.hoverOpacity),
        focus: cancelButtonInit.allStates,
        active: cancelButtonInit.allStates,
    });

    return {
        fullBleed,
        titleBarContainer,
        border,
        sizing,
        colors,
        overlay,
        signIn,
        resister,
        guest,
        button,
        linkButton,
        count,
        dropDownContents,
        endElements,
        buttonContents,
        mobileDropDown,
        meBox,
        logo,
        mediaQueries,
        breakpoints,
        navAlignment,
        mobileLogo,
        spacing,
        swoop,
        fullHeight,
        stateColors,
    };
});
