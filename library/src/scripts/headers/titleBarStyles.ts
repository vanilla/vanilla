/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
    absolutePosition,
    allButtonStates,
    borders,
    BorderType,
    colorOut,
    flexHelper,
    modifyColorBasedOnLightness,
    offsetLightness,
    pointerEvents,
    singleBorder,
    sticky,
    unit,
    userSelect,
    EMPTY_FONTS,
    isLightColor,
    negativeUnit,
    paddings,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import {
    calc,
    ColorHelper,
    important,
    linearGradient,
    percent,
    px,
    quote,
    rgba,
    translate,
    translateX,
    translateY,
    viewHeight,
} from "csx";
import backLinkClasses from "@library/routing/links/backLinkStyles";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { buttonResetMixin } from "@library/forms/buttonStyles";
import { ButtonTypes } from "@library/forms/buttonTypes";
import generateButtonClass from "@library/forms/styleHelperButtonGenerator";
import { media } from "typestyle";
import { LogoAlignment } from "./TitleBar";
import { searchBarClasses } from "@library/features/search/searchBarStyles";
import { BackgroundProperty } from "csstype";
import { IThemeVariables } from "@library/theming/themeReducer";

export const titleBarVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const formElementVars = formElementsVariables(forcedVars);
    const makeThemeVars = variableFactory("titleBar", forcedVars);

    const sizing = makeThemeVars("sizing", {
        height: 48,
        spacer: 12,
        mobile: {
            height: 44,
            width: formElementVars.sizing.height,
        },
    });

    const spacing = makeThemeVars("spacing", {
        padding: {
            top: 0, // This is to add extra padding under the content of the title bar.
            bottom: 0, // This is to add extra padding under the content of the title bar.
        },
    });

    // Note that this overlay will go on top of the bg image, if you have one.
    const overlay = makeThemeVars("overlay", {
        background: undefined as BackgroundProperty<TLength> | Array<BackgroundProperty<TLength>> | undefined,
    });

    const colorsInit = makeThemeVars("colors", {
        fg: globalVars.mainColors.primaryContrast,
        bg: globalVars.mainColors.primary,
        bgImage: null as string | null,
    });

    const colors = makeThemeVars("colors", {
        ...colorsInit,
        state: {
            bg: isLightColor(colorsInit.bg) ? rgba(0, 0, 0, 0.1) : rgba(255, 255, 255, 0.1),
            fg: colorsInit.fg,
        },
    });

    const border = makeThemeVars("border", {
        type: BorderType.NONE,
        color: globalVars.border.color,
        width: globalVars.border.width,
    });

    // sizing.height gives you the height of the contents of the titleBar.
    // If spacing.paddingBottom is set, this value will be different than the height. To be used if you need to get the full height, not just the contents height.
    const fullHeight =
        sizing.height + spacing.padding.bottom + spacing.padding.top + border.type == BorderType.SHADOW_AS_BORDER
            ? border.width
            : 0;

    const swoopInit = makeThemeVars("swoop", {
        amount: 0,
    });

    const swoop = makeThemeVars("swoop", {
        ...swoopInit,
        swoopOffset: (16 * swoopInit.amount) / 50,
    });

    const fullBleed = makeThemeVars("fullBleed", {
        enabled: false,
        startingOpacity: 0,
        endingOpacity: 0.15, // Scale of 0 -> 1 where 1 is opaque.
        bgColor: colors.bg,
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
        state: offsetLightness(colors.bg, 0.04), // Default state color change
    });

    const linkButtonDefaults: IButtonType = {
        name: ButtonTypes.TITLEBAR_LINK,
        colors: {
            bg: rgba(0, 0, 0, 0),
            fg: colors.fg,
        },
        fonts: {
            ...EMPTY_FONTS,
            color: colors.fg,
        },
        sizing: {
            minWidth: unit(globalVars.icon.sizes.large),
            minHeight: unit(globalVars.icon.sizes.large),
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

    const compactSearch = makeThemeVars("compactSearch", {
        bg: fullBleed.enabled ? colors.bg.fade(0.2) : globalVars.mainColors.secondary,
        fg: colors.fg,
        mobile: {
            width: button.mobile.width,
        },
    });

    const buttonContents = makeThemeVars("buttonContents", {
        state: {
            bg: button.state.bg,
        },
    });

    const signIn = makeThemeVars("signIn", {
        fg: colors.fg,
        bg: modifyColorBasedOnLightness({ color: globalVars.mainColors.primary, weight: 0.1, inverse: true }),
        hover: {
            bg: modifyColorBasedOnLightness({ color: globalVars.mainColors.primary, weight: 0.2, inverse: true }),
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

    const bottomRow = makeThemeVars("bottomRow", {
        bg: modifyColorBasedOnLightness({ color: colors.bg, weight: 0.1 }).desaturate(0.2, true),
    });

    // Note that the logo defined here is the last fallback. If set through the dashboard, it will overwrite these values.
    const logo = makeThemeVars("logo", {
        doubleLogoStrategy: "visible" as "hidden" | "visible" | "fade-in",
        offsetRight: globalVars.gutter.size,
        justifyContent: LogoAlignment.LEFT,
        maxWidth: 200,
        heightOffset: sizing.height / 3,
        tablet: {},
        desktop: {
            url: undefined,
        }, // add "url" if you want to set in theme. Use full path eg. "/addons/themes/myTheme/design/myLogo.png"
        mobile: {
            url: undefined,
            maxWidth: undefined,
            heightOffset: sizing.height / 3,
        }, // add "url" if you want to set in theme. Use full path eg. "/addons/themes/myTheme/design/myLogo.png"
        offsetVertical: {
            amount: 0,
            mobile: {
                amount: 0,
            },
        },
    });

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
        const full = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return media(
                {
                    minWidth: px(breakpoints.compact + 1),
                },
                styles,
            );
        };

        const compact = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(breakpoints.compact),
                },
                styles,
            );
        };

        return {
            full,
            compact,
        };
    };

    return {
        fullBleed,
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
        compactSearch,
        buttonContents,
        mobileDropDown,
        meBox,
        bottomRow,
        logo,
        mediaQueries,
        breakpoints,
        navAlignment,
        mobileLogo,
        spacing,
        swoop,
        fullHeight,
    };
});

export const titleBarClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = titleBarVariables();
    const formElementVars = formElementsVariables();
    const mediaQueries = vars.mediaQueries();
    const flex = flexHelper();
    const style = styleFactory("titleBar");

    const getBorderVars = (): NestedCSSProperties => {
        switch (vars.border.type) {
            case BorderType.BORDER:
                return {
                    borderBottom: singleBorder({
                        color: vars.border.color,
                        width: vars.border.width,
                    }),
                };
            case BorderType.SHADOW:
                return {
                    boxShadow: shadowHelper().embed(globalVars.elementaryColors.black).boxShadow,
                };
            case BorderType.SHADOW_AS_BORDER:
                // Note that this is empty because this option is set on the background elsewhere.
                return {};
            case BorderType.NONE:
                return {};
            default:
                return {};
        }
    };

    const root = style({
        maxWidth: percent(100),
        color: colorOut(vars.colors.fg),
        position: "relative",
        ...getBorderVars(),
        $nest: {
            "& .searchBar__control": {
                color: vars.colors.fg.toString(),
                cursor: "pointer",
            },
            "&& .suggestedTextInput-clear.searchBar-clear": {
                $nest: {
                    "&:hover": {
                        color: vars.colors.fg.toString(),
                    },
                    "&:active": {
                        color: vars.colors.fg.toString(),
                    },
                    "&:focus": {
                        color: vars.colors.fg.toString(),
                    },
                },
            },
            "& .searchBar__placeholder": {
                color: vars.colors.fg.fade(0.8).toString(),
                cursor: "pointer",
            },
            [`& .${backLinkClasses().link}`]: {
                $nest: {
                    "&, &:hover, &:focus, &:active": {
                        color: colorOut(vars.colors.fg),
                    },
                },
            },
            [`& .${searchBarClasses().valueContainer}`]: {
                backgroundColor: colorOut(vars.compactSearch.bg),
            },
        },
        ...(vars.swoop.amount
            ? {
                  $nest: {
                      "& + *": {
                          // Offset the next element to account for the swoop. (next element should go under the swoop slightly).
                          marginTop: -vars.swoop.swoopOffset,
                      },
                  },
              }
            : {}),
    });

    const swoopStyles = {
        top: 0,
        left: 0,
        margin: `0 auto`,
        position: `absolute`,
        height: calc(`80% - ${unit(vars.border.width + 1)}`),
        transform: translateX(`-10vw`),
        width: `120vw`,
        borderRadius: `0 0 100% 100%/0 0 ${percent(vars.swoop.amount)} ${percent(vars.swoop.amount)}`,
    };

    const swoop = style("swoop", {});

    const shadowAsBorder =
        vars.border.type === BorderType.SHADOW_AS_BORDER
            ? { boxShadow: `0 ${unit(vars.border.width)} 0 ${colorOut(vars.border.color)}` }
            : {};

    const bg1 = style("bg1", {
        willChange: "opacity",
        ...absolutePosition.fullSizeOfParent(),
        // backgroundColor: colorOut(vars.colors.bg),
        ...shadowAsBorder,
        overflow: "hidden",
        $nest: {
            [`&.${swoop}`]: swoopStyles as NestedCSSProperties,
        },
    });

    const bg2 = style("bg2", {
        willChange: "opacity",
        ...absolutePosition.fullSizeOfParent(),
        backgroundColor: colorOut(vars.colors.bg),
        ...shadowAsBorder,
        overflow: "hidden",
        $nest: {
            [`&.${swoop}`]: swoopStyles as NestedCSSProperties,
        },
    });

    const container = style("container", {
        position: "relative",
        height: percent(100),
        width: percent(100),
        paddingTop: unit(vars.spacing.padding.top),
        paddingBottom: unit(vars.spacing.padding.bottom),
    });

    const bgContainer = style("bgContainer", {
        ...absolutePosition.fullSizeOfParent(),
        height: percent(100),
        width: percent(100),
        paddingTop: unit(vars.spacing.padding.top),
        paddingBottom: unit(vars.spacing.padding.bottom),
        boxSizing: "content-box",
        overflow: "hidden",
    });

    const bgImage = style("bgImage", {
        ...absolutePosition.fullSizeOfParent(),
        objectFit: "cover",
    });

    const bannerPadding = style(
        "bannerPadding",
        {
            paddingTop: px(vars.sizing.height / 2),
        },
        mediaQueries.compact({
            paddingTop: px(vars.sizing.mobile.height / 2 + 20),
        }),
    );

    const negativeSpacer = style(
        "negativeSpacer",
        {
            marginTop: px(-vars.sizing.height),
        },
        mediaQueries.compact({
            marginTop: px(-vars.sizing.mobile.height),
        }),
    );

    const spacer = style(
        "spacer",
        {
            height: px(vars.sizing.height),
        },
        mediaQueries.compact({
            height: px(vars.sizing.mobile.height),
        }),
    );

    const bar = style(
        "bar",
        {
            display: "flex",
            justifyContent: "flex-start",
            flexWrap: "nowrap",
            alignItems: "center",
            height: px(vars.sizing.height),
            width: percent(100),
            $nest: {
                "&.isHome": {
                    justifyContent: "space-between",
                },
            },
        },
        mediaQueries.compact({ height: px(vars.sizing.mobile.height) }),
    );

    const logoOffsetDesktop = vars.logo.offsetVertical.amount
        ? {
              transform: translateY(`${unit(vars.logo.offsetVertical.amount)}`),
          }
        : {};

    const logoOffsetMobile = vars.logo.offsetVertical.mobile.amount
        ? {
              transform: translateY(`${unit(vars.logo.offsetVertical.mobile.amount)}`),
          }
        : {};

    const logoContainer = style(
        "logoContainer",
        {
            display: "inline-flex",
            alignSelf: "center",
            color: colorOut(vars.colors.fg),
            marginRight: unit(vars.logo.offsetRight),
            justifyContent: vars.logo.justifyContent,
            ...logoOffsetDesktop,
            $nest: {
                "&&": {
                    color: colorOut(vars.colors.fg),
                },
                "&.focus-visible": {
                    $nest: {
                        "&.headerLogo-logoFrame": {
                            outline: `5px solid ${vars.buttonContents.state.bg}`,
                            background: colorOut(vars.buttonContents.state.bg),
                            borderRadius: vars.button.borderRadius,
                        },
                    },
                },
            },
        },
        mediaQueries.compact({
            height: px(vars.sizing.mobile.height),
            marginRight: unit(0),
            ...logoOffsetMobile,
        }),
    );

    const logoFlexBasis = style("logoFlexBasis", {
        flexBasis: vars.endElements.flexBasis,
    });

    const meBox = style("meBox", {
        justifyContent: "flex-end",
    });

    const nav = style(
        "nav",
        {
            display: "flex",
            flexWrap: "wrap",
            height: px(vars.sizing.height),
            color: "inherit",
            flexGrow: 1,
            justifyContent: vars.navAlignment.alignment === "left" ? "flex-start" : "center",
            $nest: {
                "&.titleBar-guestNav": {
                    flex: "initial",
                },
            },
        },
        mediaQueries.compact({ height: px(vars.sizing.mobile.height) }),
    );

    const locales = style(
        "locales",
        {
            height: px(vars.sizing.height),
            $nest: {
                "&.buttonAsText": {
                    $nest: {
                        "&:hover": {
                            color: "inherit",
                        },
                        "&:focus": {
                            color: "inherit",
                        },
                    },
                },
            },
        },
        mediaQueries.compact({ height: px(vars.sizing.mobile.height) }),
    );

    const messages = style("messages", {
        color: vars.colors.fg.toString(),
    });

    const notifications = style("notifications", {
        color: "inherit",
    });

    const compactSearch = style(
        "compactSearch",
        {
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            marginLeft: "auto",
            minWidth: unit(formElementVars.sizing.height),
            flexBasis: px(formElementVars.sizing.height),
            maxWidth: percent(100),
            height: unit(vars.sizing.height),
            $nest: {
                "&.isOpen": {
                    flex: 1,
                },
            },
        },
        mediaQueries.compact({ height: px(vars.sizing.mobile.height) }),
    );

    const compactSearchResults = style("compactSearchResults", {
        position: "absolute",
        top: unit(formElementVars.sizing.height),
        width: percent(100),
        $nest: {
            "&:empty": {
                display: "none",
            },
        },
    });

    const extraMeBoxIcons = style("extraMeBoxIcons", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-end",
        marginLeft: "auto",
        $nest: {
            [`& + .${compactSearch}`]: {
                marginLeft: 0,
            },
            li: {
                listStyle: "none",
            },
        },
    });

    const topElement = style(
        "topElement",
        {
            color: vars.colors.fg.toString(),
            padding: `0 ${px(vars.sizing.spacer / 2)}`,
            margin: `0 ${px(vars.sizing.spacer / 2)}`,
            borderRadius: px(vars.button.borderRadius),
        },
        mediaQueries.compact({
            fontSize: px(vars.button.mobile.fontSize),
        }),
    );

    const localeToggle = style(
        "localeToggle",
        {
            height: px(vars.sizing.height),
        },
        mediaQueries.compact({
            height: px(vars.sizing.mobile.height),
        }),
    );

    const languages = style("languages", {
        marginLeft: "auto",
    });

    const button = style(
        "button",
        {
            ...buttonResetMixin(),
            height: px(vars.button.size),
            minWidth: px(vars.button.size),
            maxWidth: percent(100),
            padding: px(0),
            color: colorOut(vars.colors.fg),
            $nest: {
                "&&": {
                    ...allButtonStates(
                        {
                            allStates: {
                                color: colorOut(vars.colors.fg),
                                $nest: {
                                    "& .meBox-buttonContent": {
                                        backgroundColor: colorOut(vars.buttonContents.state.bg),
                                    },
                                },
                            },
                            keyboardFocus: {
                                outline: 0,
                                color: colorOut(vars.colors.fg),
                                $nest: {
                                    "& .meBox-buttonContent": {
                                        borderColor: colorOut(vars.colors.fg),
                                        backgroundColor: colorOut(vars.buttonContents.state.bg),
                                    },
                                },
                            },
                        },
                        {
                            "& .meBox-buttonContent": {
                                ...borders({
                                    width: 1,
                                    color: rgba(0, 0, 0, 0),
                                }),
                            },
                            "&.isOpen": {
                                color: colorOut(vars.colors.fg),
                                $nest: {
                                    "& .meBox-buttonContent": {
                                        backgroundColor: colorOut(vars.buttonContents.state.bg),
                                    },
                                    "&:focus": {
                                        color: colorOut(vars.colors.fg),
                                    },
                                    "&.focus-visible": {
                                        color: colorOut(vars.colors.fg),
                                    },
                                },
                            },
                        },
                    ),
                },
            },
        },
        mediaQueries.compact({
            height: px(vars.sizing.mobile.height),
            width: px(vars.sizing.mobile.width),
            minWidth: px(vars.sizing.mobile.width),
        }),
    );

    const linkButton = generateButtonClass(vars.linkButton);

    const buttonOffset = style("buttonOffset", {
        transform: `translateX(6px)`,
    });

    const centeredButton = style("centeredButton", {
        ...flex.middle(),
    });

    const searchCancel = style("searchCancel", {
        ...buttonResetMixin(),
        ...userSelect(),
        height: px(formElementVars.sizing.height),
        $nest: {
            "&.focus-visible": {
                $nest: {
                    "&.meBox-buttonContent": {
                        borderRadius: px(vars.button.borderRadius),
                        backgroundColor: vars.buttonContents.state.bg.toString(),
                    },
                },
            },
        },
    });

    const tabButtonActive = {
        color: globalVars.mainColors.primary.toString(),
        $nest: {
            ".titleBar-tabButtonContent": {
                color: vars.colors.fg.toString(),
                backgroundColor: colorOut(modifyColorBasedOnLightness({ color: vars.colors.fg, weight: 1 })),
                borderRadius: px(vars.button.borderRadius),
            },
        },
    };

    const tabButton = style("tabButton", {
        display: "block",
        height: percent(100),
        padding: px(0),
        $nest: {
            "&:active": tabButtonActive,
            "&:hover": tabButtonActive,
            "&:focus": tabButtonActive,
        },
    });

    const dropDownContents = style("dropDownContents", {
        $nest: {
            "&&&": {
                minWidth: unit(vars.dropDownContents.minWidth),
                maxHeight: unit(vars.dropDownContents.maxHeight),
            },
        },
    });

    const count = style("count", {
        height: px(vars.count.size),
        fontSize: px(vars.count.fontSize),
        backgroundColor: vars.count.bg.toString(),
        color: vars.count.fg.toString(),
    });

    const rightFlexBasis = style(
        "rightFlexBasis",
        {
            display: "flex",
            height: px(vars.sizing.height),
            flexWrap: "nowrap",
            justifyContent: "flex-end",
            alignItems: "center",
            flexBasis: vars.endElements.flexBasis,
        },
        mediaQueries.compact({
            flexShrink: 1,
            flexBasis: px(vars.endElements.mobile.flexBasis),
            height: px(vars.sizing.mobile.height),
        }),
    );

    const leftFlexBasis = style("leftFlexBasis", {
        ...flex.middleLeft(),
        flexShrink: 1,
        flexBasis: px(vars.endElements.mobile.flexBasis),
    });

    const signIn = style("signIn", {
        marginLeft: unit(vars.guest.spacer),
        marginRight: unit(vars.guest.spacer),
        $nest: {
            "&&&": {
                color: colorOut(vars.signIn.fg),
                borderColor: colorOut(vars.colors.fg),
            },
        },
    });

    const register = style("register", {
        marginLeft: unit(vars.guest.spacer),
        marginRight: unit(vars.guest.spacer),
        backgroundColor: colorOut(vars.resister.bg),
        $nest: {
            "&&": {
                // Ugly solution, but not much choice until: https://github.com/vanilla/knowledge/issues/778
                ...allButtonStates({
                    allStates: {
                        borderColor: colorOut(vars.resister.borderColor),
                        color: colorOut(vars.resister.fg),
                    },
                    noState: {
                        backgroundColor: colorOut(vars.resister.bg),
                    },
                    hover: {
                        color: colorOut(vars.resister.fg),
                        backgroundColor: colorOut(vars.resister.states.bg),
                    },
                    focus: {
                        color: colorOut(vars.resister.fg),
                        backgroundColor: colorOut(vars.resister.states.bg),
                    },
                    active: {
                        color: colorOut(vars.resister.fg),
                        backgroundColor: colorOut(vars.resister.states.bg),
                    },
                }),
            },
        },
    });

    const clearButtonClass = style("clearButtonClass", {
        opacity: 0.7,
        $nest: {
            "&&": {
                color: colorOut(vars.colors.fg),
            },
            "&:hover, &:focus": {
                opacity: 1,
            },
        },
    });

    const guestButton = style("guestButton", {
        minWidth: unit(vars.button.guest.minWidth),
        borderRadius: unit(vars.button.borderRadius),
    });

    const desktopNavWrap = style("desktopNavWrap", {
        position: "relative",
        flexGrow: 1,
        $nest: addGradientsToHintOverflow(globalVars.gutter.half * 4, vars.colors.bg) as any,
    });

    const logoCenterer = style("logoCenterer", {
        ...absolutePosition.middleOfParent(true),
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
    });

    const hamburger = style("hamburger", {
        marginRight: unit(12),
        marginLeft: negativeUnit(globalVars.buttonIcon.offset),
        $nest: {
            "&&": {
                ...allButtonStates({
                    allStates: {
                        color: colorOut(vars.colors.fg),
                    },
                }),
            },
        },
    });

    const isSticky = style("isSticky", {
        ...sticky(),
        top: 0,
        zIndex: 10,
    });

    const logoAnimationWrap = style("logoAnimationWrap", {
        display: "inline-flex",
        alignItems: "center",
    });

    const overlay = style("overlay", {
        ...absolutePosition.fullSizeOfParent(),
        background: vars.overlay.background,
    });

    const signInIconOffset = style("signInIconOffset", {
        marginRight: negativeUnit(globalVars.buttonIcon.offset + 3),
    });

    const titleBarContainer = style("titleBarContainer", {});

    return {
        root,
        bg1,
        bg2,
        container,
        bgContainer,
        bgImage,
        negativeSpacer,
        bannerPadding,
        spacer,
        bar,
        logoContainer,
        meBox,
        nav,
        locales,
        messages,
        notifications,
        compactSearch,
        topElement,
        localeToggle,
        languages,
        button,
        buttonOffset,
        linkButton,
        searchCancel,
        tabButton,
        dropDownContents,
        count,
        extraMeBoxIcons,
        rightFlexBasis,
        leftFlexBasis,
        signIn,
        register,
        centeredButton,
        compactSearchResults,
        clearButtonClass,
        guestButton,
        logoFlexBasis,
        desktopNavWrap,
        logoCenterer,
        hamburger,
        isSticky,
        logoAnimationWrap,
        overlay,
        swoop,
        signInIconOffset,
        titleBarContainer,
    };
});

export const titleBarLogoClasses = useThemeCache(() => {
    const vars = titleBarVariables();
    const style = styleFactory("titleBarLogo");

    const logoFrame = style("logoFrame", { display: "inline-flex", alignSelf: "center" });

    const logo = style("logo", {
        display: "block",
        maxHeight: px(vars.sizing.height - vars.logo.heightOffset),
        maxWidth: unit(vars.logo.maxWidth),
        width: "auto",
        $nest: {
            "&.isCentred": {
                margin: "auto",
            },
        },
    });

    const mobileLogo = style("mobileLogo", {
        display: "flex",
        justifyContent: vars.mobileLogo.justifyContent,
        maxHeight: px(vars.sizing.mobile.height - (vars.logo.mobile.heightOffset ?? vars.logo.heightOffset)),
        maxWidth: unit(vars.logo.mobile.maxWidth ?? vars.logo.maxWidth),
    });

    const isCenter = style("isCenter", {
        position: "absolute",
        left: percent(50),
        transform: translate(`-50%`, `-50%`),
    });

    return {
        logoFrame,
        logo,
        mobileLogo,
        isCenter,
    };
});

export const addGradientsToHintOverflow = (width: number | string, color: ColorHelper) => {
    return {
        "&:after": {
            ...absolutePosition.topRight(),
            background: linearGradient(
                "right",
                `${colorOut(color.fade(0))} 0%`,
                `${colorOut(color.fade(0.3))} 20%`,
                `${colorOut(color)} 90%`,
            ),
        },
        "&:before": {
            ...absolutePosition.topLeft(),
            background: linearGradient(
                "left",
                `${colorOut(color.fade(0))} 0%`,
                `${colorOut(color.fade(0.3))} 20%`,
                `${colorOut(color)} 90%`,
            ),
        },
        "&:before, &:after": {
            ...pointerEvents(),
            content: quote(``),
            height: percent(100),
            width: unit(width),
            zIndex: 1,
        },
    };
};
