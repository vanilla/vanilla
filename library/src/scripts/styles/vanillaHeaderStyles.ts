/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, px } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";

import {
    componentThemeVariables,
    debugHelper,
    flexHelper,
    getColorDependantOnLightness,
    unit,
} from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { layoutVariables } from "@library/styles/layoutStyles";

export function vanillaHeaderVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const themeVars = componentThemeVariables(theme, "vanillaHeader");

    const sizing = {
        height: 48,
        spacer: 12,
        mobile: {
            height: 44,
            width: formElementVars.sizing.height,
        },
        ...themeVars.subComponentStyles("sizing"),
    };

    const colors = {
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.primary,
        ...themeVars.subComponentStyles("colors"),
    };

    const guest = {
        spacer: 8,
        ...themeVars.subComponentStyles("guest"),
    };

    const buttonSize = 40;
    const buttonMobileSize = formElementVars.sizing.height;
    const button = {
        borderRadius: 3,
        size: buttonSize,
        guest: {
            minWidth: 86,
        },
        mobile: {
            fontSize: 16,
            width: buttonMobileSize,
        },
        ...themeVars.subComponentStyles("button"),
    };

    const count = {
        size: 18,
        fontSize: 10,
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.primary,
        ...themeVars.subComponentStyles("count"),
    };

    const dropDownContents = {
        minWidth: 350,
        ...themeVars.subComponentStyles("dropDownContents"),
    };

    const endElements = {
        flexBasis: buttonSize * 4,
        mobile: {
            flexBasis: buttonMobileSize * 2,
        },
        ...themeVars.subComponentStyles("endElements"),
    };

    const compactSearch = {
        maxWidth: 672,
        mobile: {
            width: buttonMobileSize,
        },
        ...themeVars.subComponentStyles("compactSearch"),
    };

    const buttonContents = {
        hover: {
            bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.primary, 0.1, true),
        },
        active: {
            bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.primary, 0.2, true),
        },
        ...themeVars.subComponentStyles("buttonContents"),
    };

    const signIn = {
        bg: getColorDependantOnLightness(
            globalVars.mainColors.primary,
            globalVars.mainColors.primary,
            0.1,
            true,
        ).toString(),
        hover: {
            bg: getColorDependantOnLightness(
                globalVars.mainColors.primary,
                globalVars.mainColors.primary,
                0.2,
                true,
            ).toString(),
        },
        ...themeVars.subComponentStyles("signIn"),
    };

    const resister = {
        bg: globalVars.mainColors.bg,
        hover: {
            bg: globalVars.mainColors.bg.fade(0.9).toString(),
        },
        ...themeVars.subComponentStyles("register"),
    };

    const mobileDropDown = style({
        height: px(sizing.mobile.height),
        ...themeVars.subComponentStyles("mobileDropDown"),
    });

    const meBox = {
        sizing: {
            buttonContents: 32,
        },
    };

    return {
        sizing,
        colors,
        signIn,
        resister,
        guest,
        button,
        count,
        dropDownContents,
        endElements,
        compactSearch,
        buttonContents,
        mobileDropDown,
        meBox,
    };
}

export default function vanillaHeaderClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const vars = vanillaHeaderVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const headerColors = vars.colors;
    const mediaQueries = layoutVariables(theme).mediaQueries();
    const flex = flexHelper();
    const debug = debugHelper("vanillaHeader");

    const root = style(
        {
            ...debug.name(),
            backgroundColor: headerColors.bg.toString(),
            color: headerColors.fg.toString(),
            $nest: {
                "&.isFixed": {
                    ...debug.name("fixed"),
                    position: "fixed",
                    top: 0,
                    left: 0,
                    right: 0,
                    zIndex: 1,
                },
                "& .searchBar__control": {
                    ...debug.name("control"),
                    color: vars.colors.fg.toString(),
                    cursor: "pointer",
                },
                "&& .suggestedTextInput-clear.searchBar-clear": {
                    ...debug.name("clear"),
                    color: vars.colors.fg.toString(),
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
                    ...debug.name("placeholder"),
                    color: vars.colors.fg.fade(0.8).toString(),
                    cursor: "pointer",
                },
            },
        },
        mediaQueries.oneColumn({
            height: px(vars.sizing.mobile.height),
        }),
    );

    const spacer = style(
        {
            ...debug.name("spacer"),
            backgroundColor: headerColors.bg.toString(),
            height: px(vars.sizing.height),
        },
        mediaQueries.oneColumn({
            height: px(vars.sizing.mobile.height),
        }),
    );

    const bar = style(
        {
            ...debug.name("bar"),
            display: "flex",
            justifyContent: "space-between",
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
        mediaQueries.oneColumn({ height: px(vars.sizing.mobile.height) }),
    );

    const logoContainer = style(
        {
            ...debug.name("logoContainer"),
            display: "inline-flex",
            alignSelf: "center",
            flexBasis: vars.endElements.flexBasis,
            color: vars.colors.fg.toString(),
            $nest: {
                "&.focus-visible": {
                    $nest: {
                        "&.headerLogo-logoFrame": {
                            outline: `5px solid ${vars.buttonContents.hover.bg}`,
                            background: vars.buttonContents.hover.bg.toString(),
                            borderRadius: vars.button.borderRadius,
                        },
                    },
                },
            },
        },
        mediaQueries.oneColumn({ height: px(vars.sizing.mobile.height) }),
    );

    const meBox = style({
        ...debug.name("meBox"),
        justifyContent: "flex-end",
    });

    const nav = style({
        ...debug.name("nav"),
        display: "flex",
        flexWrap: "wrap",
        height: percent(100),
        color: "inherit",
    });

    const locales = style(
        {
            ...debug.name("locales"),
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
        mediaQueries.oneColumn({ height: px(vars.sizing.mobile.height) }),
    );

    const messages = style({
        ...debug.name("messages"),
        color: vars.colors.fg.toString(),
    });

    const notifications = style({
        ...debug.name("notifications"),
        color: "inherit",
    });

    const compactSearch = style({
        ...debug.name("compactSearch"),
        marginLeft: "auto",
        maxWidth: px(vars.compactSearch.maxWidth),
    });

    const topElement = style(
        {
            ...debug.name("topElement"),
            color: vars.colors.fg.toString(),
            padding: `0 ${px(vars.sizing.spacer / 2)}`,
            margin: `0 ${px(vars.sizing.spacer / 2)}`,
            borderRadius: px(vars.button.borderRadius),
        },
        mediaQueries.oneColumn({
            fontSize: px(vars.button.mobile.fontSize),
            whiteSpace: "nowrap",
        }),
    );

    const localeToggle = style(
        {
            ...debug.name("localeToggle"),
            height: px(vars.sizing.height),
        },
        mediaQueries.oneColumn({
            height: px(vars.sizing.mobile.height),
        }),
    );

    const languages = style({
        ...debug.name("languages"),
        marginLeft: "auto",
    });

    const meBoxStateStyles = {
        ...debug.name("meBoxStateStyles"),
        borderRadius: px(vars.button.borderRadius),
        backgroundColor: vars.buttonContents.hover.bg.toString(),
    };

    const button = style(
        {
            ...debug.name("button"),
            color: vars.colors.fg.toString(),
            height: px(vars.sizing.height),
            minWidth: px(vars.button.size),
            maxWidth: percent(100),
            padding: px(0),
            $nest: {
                "&:active": {
                    color: vars.colors.fg.toString(),
                    $nest: {
                        ".meBox-contentHover": meBoxStateStyles,
                        ".meBox-buttonContent": meBoxStateStyles,
                    },
                },
                "&:hover": {
                    color: vars.colors.fg.toString(),
                    $nest: {
                        ".meBox-contentHover": meBoxStateStyles,
                        ".meBox-buttonContent": meBoxStateStyles,
                    },
                },
                "&.focus-visible": {
                    color: vars.colors.fg.toString(),
                    $nest: {
                        ".meBox-contentHover": meBoxStateStyles,
                        ".meBox-buttonContent": meBoxStateStyles,
                    },
                },
                "&.isOpen": {
                    $nest: {
                        ".meBox-contentHover": {
                            backgroundColor: vars.buttonContents.active.bg.toString(),
                        },
                        ".meBox-buttonContent": {
                            backgroundColor: vars.buttonContents.active.bg.toString(),
                        },
                    },
                },
            },
        },
        mediaQueries.oneColumn({
            height: px(vars.sizing.mobile.height),
            width: px(vars.sizing.mobile.width),
            minWidth: px(vars.sizing.mobile.width),
        }),
    );

    const centeredButtonClass = style({
        ...flex.middle(),
    });

    const searchCancel = style({
        ...debug.name("searchCancel"),
        height: px(formElementVars.sizing.height),
        userSelect: "none",
        $nest: {
            "&.focus-visible": {
                $nest: {
                    "&.meBox-contentHover": {
                        borderRadius: px(vars.button.borderRadius),
                        backgroundColor: vars.buttonContents.hover.bg.toString(),
                    },
                },
            },
        },
    });

    const tabButtonActive = {
        ...debug.name("tabButtonActive"),
        color: globalVars.mainColors.primary.toString(),
        $nest: {
            ".vanillaHeader-tabButtonContent": {
                color: vars.colors.fg.toString(),
                backgroundColor: getColorDependantOnLightness(vars.colors.fg, vars.colors.bg, 1).toString(),
                borderRadius: px(vars.button.borderRadius),
            },
        },
    };

    const tabButton = style({
        ...debug.name("tabButton"),
        display: "block",
        height: percent(100),
        padding: px(0),
        $nest: {
            "&:active": tabButtonActive,
            "&:hover": tabButtonActive,
            "&:focus": tabButtonActive,
        },
    });

    const dropDownContents = style({
        ...debug.name("dropDownContents"),
        minWidth: px(vars.dropDownContents.minWidth),
    });

    const count = {
        height: px(vars.count.size),
        fontSize: px(vars.count.fontSize),
        backgroundColor: vars.count.bg.toString(),
        color: vars.count.fg.toString(),
    };

    const horizontalScroll = {
        overflowX: "auto",
    };

    const rightFlexBasis = style(
        {
            ...debug.name("rightFlexBasis"),
            display: "flex",
            height: px(vars.sizing.height),
            flexWrap: "nowrap",
            justifyContent: "flex-end",
            alignItems: "center",
            flexBasis: vars.endElements.flexBasis,
        },
        mediaQueries.oneColumn({
            flexShrink: 1,
            flexBasis: px(vars.endElements.mobile.flexBasis),
            height: px(vars.sizing.mobile.height),
        }),
    );

    const leftFlexBasis = style(
        {
            ...debug.name("leftFlexBasis"),
            ...flex.middleLeft(),
            flexBasis: vars.endElements.flexBasis,
        },
        mediaQueries.oneColumn({
            flexShrink: 1,
            flexBasis: px(vars.endElements.mobile.flexBasis),
        }),
        mediaQueries.xs({
            flexBasis: px(formElementVars.sizing.height),
        }),
    );

    const signIn = style({
        ...debug.name("signIn"),
        $nest: {
            "&:not([disabled])": {
                color: vars.colors.fg.toString(),
                backgroundColor: vars.signIn.bg.toString(),
                border: `solid ${vars.colors.fg.toString()} 1px`,
                marginLeft: unit(vars.guest.spacer * 1.5),
                marginRight: unit(vars.guest.spacer),
                $nest: {
                    "&:hover": {
                        border: `solid ${vars.colors.fg} 1px`,
                        backgroundColor: vars.signIn.hover.bg.toString(),
                        color: vars.colors.fg.toString(),
                    },
                    "&.focus-visible": {
                        fontWeight: globalVars.fonts.weights.semiBold,
                    },
                    "&:focus": {
                        fontWeight: globalVars.fonts.weights.semiBold,
                    },
                },
            },
            ".vanillaHeaderNav-linkContent": {
                $nest: {
                    "&:after": {
                        fontWeight: globalVars.fonts.weights.semiBold,
                    },
                },
            },
        },
    });

    const register = style({
        ...debug.name("register"),
        $nest: {
            "&:not([disabled])": {
                color: vars.colors.bg.toString(),
                backgroundColor: vars.colors.fg.toString(),
                border: `solid ${vars.colors.fg} 1px;`,
                marginLeft: unit(vars.guest.spacer),
                $nest: {
                    "&:hover": {
                        color: vars.colors.bg.toString(),
                        border: `solid ${vars.colors.fg} 1px;`,
                        backgroundColor: vars.resister.hover.bg.toString(),
                    },
                    "&.focus-visible": {
                        fontWeight: globalVars.fonts.weights.semiBold,
                    },
                    "&:focus": {
                        fontWeight: globalVars.fonts.weights.semiBold,
                    },
                },
            },
            ".vanillaHeaderNav-linkContent": {
                $nest: {
                    "&:after": {
                        display: "none",
                    },
                },
            },
        },
    });

    const compactSearchResults = style(
        {
            ...debug.name("compactSearchResults"),
            top: (vars.sizing.height - formElementVars.sizing.height + formElementVars.border.width) / 2,
            display: "flex",
            position: "relative",
            margin: "auto",
            maxWidth: px(vars.compactSearch.maxWidth),
        },
        mediaQueries.oneColumn({
            top: (vars.sizing.mobile.height - formElementVars.sizing.height + formElementVars.border.width) / 2,
        }),
    );

    const clearButtonClass = style({
        color: vars.colors.fg.toString(),
    });

    const guestButton = style({
        minWidth: unit(vars.button.guest.minWidth),
    });

    return {
        root,
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
        searchCancel,
        tabButton,
        dropDownContents,
        count,
        horizontalScroll,
        rightFlexBasis,
        leftFlexBasis,
        signIn,
        register,
        centeredButtonClass,
        compactSearchResults,
        clearButtonClass,
        guestButton,
    };
}

export function vanillaHeaderLogoClasses(theme?: object) {
    const vars = vanillaHeaderVariables(theme);
    const logoFrame = style({ display: "inline-flex" });
    const debug = debugHelper("vanillaHeaderLogo");

    const logo = style({
        ...debug.name("logo"),
        display: "block",
        height: px(vars.sizing.height - 18),
        width: "auto",
        $nest: {
            "&.isCentred": {
                margin: "auto",
            },
        },
    });

    const link = style({
        ...debug.name("link"),
        textDecoration: "none",
    });

    return { logoFrame, logo, link };
}

export function vanillaHeaderHomeClasses(theme?: object) {
    const vars = vanillaHeaderVariables(theme);
    const globalVars = globalVariables(theme);
    const debug = debugHelper("vanillaHeaderHome");

    const root = style({
        ...debug.name(),
        minHeight: vars.sizing.mobile.height * 2,
    });

    const bottom = style({
        ...debug.name("bottom"),
        backgroundColor: globalVars.mainColors.fg.fade(0.1).toString(),
    });

    const left = style({
        ...debug.name("left"),
        height: px(1),
        width: px(vars.button.size),
        flexBasis: vars.button.size,
    });

    return { root, bottom, left };
}
