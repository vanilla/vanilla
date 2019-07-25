/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
    allButtonStates,
    borders,
    colorOut,
    emphasizeLightness,
    flexHelper,
    modifyColorBasedOnLightness,
    unit,
    userSelect,
    absolutePosition,
    pointerEvents,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { ColorHelper, percent, px, quote, viewHeight } from "csx";
import backLinkClasses from "@library/routing/links/backLinkStyles";
import { NestedCSSProperties } from "typestyle/lib/types";

export const titleBarVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const makeThemeVars = variableFactory("titleBar");

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
        maxHeight: viewHeight(90),
    });

    const endElements = makeThemeVars("endElements", {
        flexBasis: buttonSize * 4,
        mobile: {
            flexBasis: button.mobile.width * 2,
        },
    });

    const compactSearch = makeThemeVars("compactSearch", {
        maxWidth: 672,
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
        bg: modifyColorBasedOnLightness(globalVars.mainColors.primary, 0.1, true),
        hover: {
            bg: modifyColorBasedOnLightness(globalVars.mainColors.primary, 0.2, true),
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
        bg: modifyColorBasedOnLightness(colors.bg, 0.1).desaturate(0.2, true),
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
        bottomRow,
    };
});

export const titleBarClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = titleBarVariables();
    const formElementVars = formElementsVariables();
    const headerColors = vars.colors;
    const mediaQueries = layoutVariables().mediaQueries();
    const flex = flexHelper();
    const style = styleFactory("titleBar");

    const root = style({
        maxWidth: percent(100),
        backgroundColor: headerColors.bg.toString(),
        color: headerColors.fg.toString(),
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
        },
        ...mediaQueries.oneColumnDown({
            height: px(vars.sizing.mobile.height),
        }).$nest,
    });

    const spacer = style(
        "spacer",
        {
            height: px(vars.sizing.height),
        },
        mediaQueries.oneColumnDown({
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
        mediaQueries.oneColumnDown({ height: px(vars.sizing.mobile.height) }),
    );

    const logoContainer = style(
        "logoContainer",
        {
            display: "inline-flex",
            alignSelf: "center",
            color: colorOut(vars.colors.fg),
            marginRight: unit(globalVars.gutter.size),
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
        mediaQueries.oneColumnDown({
            height: px(vars.sizing.mobile.height),
            marginRight: unit(0),
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
        },
        mediaQueries.oneColumnDown({ height: px(vars.sizing.mobile.height) }),
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
        mediaQueries.oneColumnDown({ height: px(vars.sizing.mobile.height) }),
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
                    width: unit(vars.compactSearch.maxWidth),
                    flexBasis: "auto",
                },
            },
        },
        mediaQueries.oneColumnDown({ height: px(vars.sizing.mobile.height) }),
    );

    const topElement = style(
        "topElement",
        {
            color: vars.colors.fg.toString(),
            padding: `0 ${px(vars.sizing.spacer / 2)}`,
            margin: `0 ${px(vars.sizing.spacer / 2)}`,
            borderRadius: px(vars.button.borderRadius),
        },
        mediaQueries.oneColumnDown({
            fontSize: px(vars.button.mobile.fontSize),
        }),
    );

    const localeToggle = style(
        "localeToggle",
        {
            height: px(vars.sizing.height),
        },
        mediaQueries.oneColumnDown({
            height: px(vars.sizing.mobile.height),
        }),
    );

    const languages = style("languages", {
        marginLeft: "auto",
    });

    const button = style(
        "button",
        {
            color: vars.colors.fg.toString(),
            height: px(vars.button.size),
            minWidth: px(vars.button.size),
            maxWidth: percent(100),
            padding: px(0),
            $nest: {
                "&&": {
                    ...allButtonStates(
                        {
                            active: {
                                color: colorOut(vars.colors.fg),
                                $nest: {
                                    "& .meBox-buttonContent": {
                                        backgroundColor: colorOut(vars.buttonContents.state.bg),
                                    },
                                },
                            },
                            hover: {
                                color: colorOut(vars.colors.fg),
                                $nest: {
                                    "& .meBox-buttonContent": {
                                        backgroundColor: colorOut(vars.buttonContents.state.bg),
                                    },
                                },
                            },
                            accessibleFocus: {
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
                                    color: "transparent",
                                }),
                            },
                            "&.isOpen": {
                                color: colorOut(vars.colors.fg),
                                $nest: {
                                    "& .meBox-buttonContent": {
                                        backgroundColor: colorOut(vars.buttonContents.state.bg),
                                    },
                                },
                            },
                        },
                    ),
                },
            },
        },
        mediaQueries.oneColumnDown({
            height: px(vars.sizing.mobile.height),
            width: px(vars.sizing.mobile.width),
            minWidth: px(vars.sizing.mobile.width),
        }),
    );

    const buttonOffset = style("buttonOffset", {
        transform: `translateX(6px)`,
    });

    const centeredButtonClass = style("centeredButtonClass", {
        ...flex.middle(),
    });

    const searchCancel = style("searchCancel", {
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
                backgroundColor: colorOut(modifyColorBasedOnLightness(vars.colors.fg, 1)),
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

    const scroll = style("scroll", {
        position: "relative",
        top: 0,
        left: 0,
        height: percent(100),
        ...(scrollWithNoScrollBar() as NestedCSSProperties),
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
        mediaQueries.oneColumnDown({
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
        mediaQueries.oneColumnDown({
            flexShrink: 1,
            flexBasis: px(vars.endElements.mobile.flexBasis),
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
        backgroundColor: colorOut(vars.resister.bg),
        $nest: {
            "&&": {
                // Ugly solution, but not much choice until: https://github.com/vanilla/knowledge/issues/778
                ...allButtonStates({
                    allStates: {
                        borderColor: colorOut(vars.resister.borderColor, true),
                        color: colorOut(vars.resister.fg),
                    },
                    noState: {
                        backgroundColor: colorOut(vars.resister.bg, true),
                    },
                    hover: {
                        color: colorOut(vars.resister.fg),
                        backgroundColor: colorOut(vars.resister.states.bg, true),
                    },
                    focus: {
                        color: colorOut(vars.resister.fg),
                        backgroundColor: colorOut(vars.resister.states.bg, true),
                    },
                    active: {
                        color: colorOut(vars.resister.fg),
                        backgroundColor: colorOut(vars.resister.states.bg, true),
                    },
                }),
            },
        },
    });

    const compactSearchResults = style("compactSearchResults", {
        position: "absolute",
        top: unit(formElementVars.sizing.height),
        maxWidth: px(vars.compactSearch.maxWidth),
        width: percent(100),
        $nest: {
            "&:empty": {
                display: "none",
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
        ...(addGradientsToHintOverflow(globalVars.gutter.half * 4, vars.colors.bg) as any),
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
        buttonOffset,
        searchCancel,
        tabButton,
        dropDownContents,
        count,
        scroll,
        rightFlexBasis,
        leftFlexBasis,
        signIn,
        register,
        centeredButtonClass,
        compactSearchResults,
        clearButtonClass,
        guestButton,
        logoFlexBasis,
        desktopNavWrap,
    };
});

export const titleBarLogoClasses = useThemeCache(() => {
    const vars = titleBarVariables();
    const style = styleFactory("titleBarLogo");
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

    return { logoFrame, logo };
});

export const titleBarHomeClasses = useThemeCache(() => {
    const vars = titleBarVariables();
    const globalVars = globalVariables();
    const style = styleFactory("titleBarHome");
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style({
        minHeight: vars.sizing.mobile.height * 2,
    });

    const left = style("left", {
        height: px(1),
        width: px(vars.button.size),
        flexBasis: vars.button.size,
    });

    const bottom = style(
        "bottom",
        {
            position: "relative",
            backgroundColor: colorOut(vars.bottomRow.bg),
            height: unit(vars.sizing.height),
            width: percent(100),
            ...(addGradientsToHintOverflow(globalVars.gutter.half * 4, vars.colors.bg) as any),
        },
        mediaQueries.oneColumnDown({
            height: px(vars.sizing.mobile.height),
            ...(addGradientsToHintOverflow(globalVars.gutter.half * 4, vars.bottomRow.bg) as any),
        }),
    );

    return {
        root,
        bottom,
        left,
    };
});

export const scrollWithNoScrollBar = (nestedStyles?: NestedCSSProperties) => {
    return {
        overflow: ["-moz-scrollbars-none", "auto"],
        "-ms-overflow-style": "none",
        $nest: {
            "&::-webkit-scrollbar": {
                display: "none",
            },
            ...nestedStyles,
        },
    };
};

export const addGradientsToHintOverflow = (width: number | string, color: ColorHelper) => {
    const gradient = (direction: "right" | "left") => {
        return `linear-gradient(to ${direction}, ${colorOut(color.fade(0))} 0%, ${colorOut(
            color.fade(0.3),
        )} 20%, ${colorOut(color)} 90%)`;
    };
    return {
        $nest: {
            "&:after": {
                ...absolutePosition.topRight(),
                background: gradient("right"),
            },
            "&:before": {
                ...absolutePosition.topLeft(),
                background: gradient("left"),
            },
            "&:before, &:after": {
                ...pointerEvents(),
                content: quote(``),
                height: percent(100),
                width: unit(width),
                zIndex: 1,
            },
        },
    };
};
