/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    buttonStates,
    colorOut,
    flexHelper,
    modifyColorBasedOnLightness,
    unit,
    userSelect,
    emphasizeLightness,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { ColorHelper, percent, px, color } from "csx";
import { layoutVariables } from "@library/layout/layoutStyles";

export const vanillaHeaderVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const makeThemeVars = variableFactory("vanillaHeader");

    const sizing = makeThemeVars("sizing", {
        height: 48,
        spacer: 12,
        mobile: {
            height: 44,
            width: formElementVars.sizing.height,
        },
    });

    const colors = makeThemeVars("colors", {
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.primary,
    });

    const guest = makeThemeVars("guest", {
        spacer: 8,
    });

    const buttonSize = 40;
    const buttonMobileSize = formElementVars.sizing.height;
    const button = makeThemeVars("button", {
        borderRadius: 3,
        size: buttonSize,
        guest: {
            minWidth: 86,
        },
        mobile: {
            fontSize: 16,
            width: buttonMobileSize,
        },
        state: {
            bg: emphasizeLightness(colors.bg, 0.04),
        },
    });

    const count = makeThemeVars("count", {
        size: 18,
        fontSize: 10,
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.primary,
    });

    const dropDownContents = makeThemeVars("dropDownContents", {
        minWidth: 350,
    });

    const endElements = makeThemeVars("endElements", {
        flexBasis: buttonSize * 4,
        mobile: {
            flexBasis: buttonMobileSize * 2,
        },
    });

    const compactSearch = makeThemeVars("compactSearch", {
        maxWidth: 672,
        mobile: {
            width: buttonMobileSize,
        },
    });

    const buttonContents = makeThemeVars("buttonContents", {
        state: {
            bg: button.state.bg,
        },
    });

    const signIn = makeThemeVars("signIn", {
        fg: colors.fg,
        bg: modifyColorBasedOnLightness(globalVars.mainColors.primary, 0.1, true),
        hover: {
            bg: modifyColorBasedOnLightness(globalVars.mainColors.primary, 0.2, true),
        },
    });

    const resister = makeThemeVars("register", {
        fg: colors.bg,
        hover: {
            bg: globalVars.mainColors.bg.fade(0.9),
        },
    });

    const mobileDropDown = makeThemeVars("mobileDropdown", {
        height: px(sizing.mobile.height),
    });

    const meBox = makeThemeVars("meBox", {
        sizing: {
            buttonContents: 32,
        },
    });

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
});

export const vanillaHeaderClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = vanillaHeaderVariables();
    const formElementVars = formElementsVariables();
    const headerColors = vars.colors;
    const mediaQueries = layoutVariables().mediaQueries();
    const flex = flexHelper();
    const style = styleFactory("vanillaHeader");

    const root = style(
        {
            maxWidth: percent(100),
            backgroundColor: headerColors.bg.toString(),
            color: headerColors.fg.toString(),
            $nest: {
                "& .searchBar__control": {
                    color: vars.colors.fg.toString(),
                    cursor: "pointer",
                },
                "&& .suggestedTextInput-clear.searchBar-clear": {
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
        "spacer",
        {
            height: px(vars.sizing.height),
        },
        mediaQueries.oneColumn({
            height: px(vars.sizing.mobile.height),
        }),
    );

    const bar = style(
        "bar",
        {
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
        "logoContainer",
        {
            display: "inline-flex",
            alignSelf: "center",
            flexBasis: vars.endElements.flexBasis,
            color: colorOut(vars.colors.fg),
            $nest: {
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
        mediaQueries.oneColumn({ height: px(vars.sizing.mobile.height) }),
    );

    const meBox = style("meBox", {
        justifyContent: "flex-end",
    });

    const nav = style("nav", {
        display: "flex",
        flexWrap: "wrap",
        height: percent(100),
        color: "inherit",
    });

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
        mediaQueries.oneColumn({ height: px(vars.sizing.mobile.height) }),
    );

    const messages = style("messages", {
        color: vars.colors.fg.toString(),
    });

    const notifications = style("notifications", {
        color: "inherit",
    });

    const compactSearch = style("compactSearch", {
        marginLeft: "auto",
        maxWidth: px(vars.compactSearch.maxWidth),
    });

    const topElement = style(
        "topElement",
        {
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
        "localeToggle",
        {
            height: px(vars.sizing.height),
        },
        mediaQueries.oneColumn({
            height: px(vars.sizing.mobile.height),
        }),
    );

    const languages = style("languages", {
        marginLeft: "auto",
    });

    const meBoxStateStyles = style("meBoxStateStyles", {
        borderRadius: px(vars.button.borderRadius),
        backgroundColor: colorOut(vars.buttonContents.state.bg),
    });

    const button = style(
        "button",
        {
            color: vars.colors.fg.toString(),
            height: px(vars.sizing.height),
            minWidth: px(formElementVars.sizing.height),
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
                            backgroundColor: colorOut(vars.buttonContents.state.bg),
                        },
                        ".meBox-buttonContent": {
                            backgroundColor: colorOut(vars.buttonContents.state.bg),
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

    const centeredButtonClass = style("centeredButtonClass", {
        ...flex.middle(),
    });

    const searchCancel = style("searchCancel", {
        ...userSelect(),
        height: px(formElementVars.sizing.height),
        $nest: {
            "&.focus-visible": {
                $nest: {
                    "&.meBox-contentHover": {
                        borderRadius: px(vars.button.borderRadius),
                        backgroundColor: vars.buttonContents.state.bg.toString(),
                    },
                },
            },
        },
    });

    const tabButtonActive = style("tabButtonActive", {
        color: globalVars.mainColors.primary.toString(),
        $nest: {
            ".vanillaHeader-tabButtonContent": {
                color: vars.colors.fg.toString(),
                backgroundColor: colorOut(modifyColorBasedOnLightness(vars.colors.fg, 1)),
                borderRadius: px(vars.button.borderRadius),
            },
        },
    });

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
                minWidth: px(vars.dropDownContents.minWidth),
            },
        },
    });

    const count = style("count", {
        height: px(vars.count.size),
        fontSize: px(vars.count.fontSize),
        backgroundColor: vars.count.bg.toString(),
        color: vars.count.fg.toString(),
    });

    const horizontalScroll = style("horizontalScroll", {
        overflowX: "auto",
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
        mediaQueries.oneColumn({
            flexShrink: 1,
            flexBasis: px(vars.endElements.mobile.flexBasis),
            height: px(vars.sizing.mobile.height),
        }),
    );

    const leftFlexBasis = style(
        "leftFlexBasis",
        {
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
        $nest: {
            "&&&": {
                backgroundColor: colorOut(vars.colors.fg),
                color: colorOut(vars.resister.fg),
                borderColor: colorOut(vars.colors.fg),
            },
        },
    });

    const compactSearchResults = style(
        "compactSearchResults",
        {
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

    const clearButtonClass = style("clearButtonClass", {
        color: vars.colors.fg.toString(),
    });

    const guestButton = style("guestButton", {
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
});

export const vanillaHeaderLogoClasses = useThemeCache(() => {
    const vars = vanillaHeaderVariables();
    const style = styleFactory("vanillaHeaderLogo");
    const logoFrame = style("logoFrame", { display: "inline-flex" });

    const logo = style("logo", {
        display: "block",
        height: px(vars.sizing.height - 18),
        width: "auto",
        $nest: {
            "&.isCentred": {
                margin: "auto",
            },
        },
    });

    const link = style("link", {
        textDecoration: "none",
    });

    return { logoFrame, logo, link };
});

export const vanillaHeaderHomeClasses = useThemeCache(() => {
    const vars = vanillaHeaderVariables();
    const globalVars = globalVariables();
    const style = styleFactory("vanillaHeaderHome");

    const root = style({
        minHeight: vars.sizing.mobile.height * 2,
    });

    const bottom = style("bottom", {
        backgroundColor: globalVars.mainColors.fg.fade(0.1).toString(),
    });

    const left = style("left", {
        height: px(1),
        width: px(vars.button.size),
        flexBasis: vars.button.size,
    });

    return { root, bottom, left };
});
