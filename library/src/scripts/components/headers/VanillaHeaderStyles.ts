/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, percent, px } from "csx";
import { globals } from "@library/styles/globals";
import { getColorDependantOnLightness } from "@library/styles/styleHelpers";
import { layoutStyles } from "@library/styles/layoutStyles";
import { style } from "typestyle";

export function vanillaHeaderVariables() {
    const globalVars = globals();

    const sizing = {
        height: 48,
        spacer: 12,
        mobile: {
            height: 44,
        },
    };

    const colors = {
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.primary,
    };

    const guest = {
        spacer: px(8),
    };

    const buttonSize = 40;
    const button = {
        borderRadius: 3,
        size: px(buttonSize),
        mobile: {
            fontSize: 16,
        },
    };

    const count = {
        size: 18,
        fontSize: 10,
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.primary,
    };

    const dropDownContents = {
        minWidth: px(350),
    };

    const endElements = {
        flexBasis: px(buttonSize * 4),
        mobile: {
            flexBasis: px(buttonSize * 2),
        },
    };

    const compactSearch = {
        maxWidth: px(672),
    };

    const buttonContents = {
        hover: {
            bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.primary, 10),
        },
        active: {
            bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.primary, 10, true),
        },
    };

    const signIn = {
        bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.primary, 10),
        hover: {
            bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.primary, 20),
        },
    };
    const resister = {
        bg: globalVars.mainColors.bg,
        hover: {
            bg: globalVars.mainColors.bg.fade(0.9),
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
    };
}

export default function vanillaHeaderClasses() {
    const vars = vanillaHeaderVariables();
    const headerColors = vars.colors;
    const mediaQueries = layoutStyles().mediaQueries();

    const root = style(
        {
            backgroundColor: headerColors.bg.toString(),
            color: headerColors.fg.toString(),
            $nest: {
                "&isFixed": {
                    position: "fixed",
                    top: 0,
                    left: 0,
                    right: 0,
                    zIndex: 1,
                },
            },
        },
        mediaQueries.oneColumn({
            height: vars.sizing.height.toString(),
        }),
    );

    const spacer = style({ height: vars.sizing.height });

    const bar = style(
        {
            display: "flex",
            justifyContent: "space-between",
            flexWrap: "nowrap",
            alignItems: "center",
            height: vars.sizing.height,
            width: percent(100),
            $nest: {
                "&.isHome": {
                    justifyContent: "space-between",
                },
            },
        },
        mediaQueries.oneColumn({ height: vars.sizing.mobile.height }),
    );

    const logoContainer = style(
        {
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
        mediaQueries.oneColumn({ height: vars.sizing.mobile.height }),
    );

    const meBox = style({
        justifyContent: "flex-end",
    });

    const nav = style({
        display: "flex",
        flexWrap: "wrap",
        height: percent(100),
        color: "inherit",
    });

    const locales = style(
        {
            height: vars.sizing.height,
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
        mediaQueries.oneColumn({ height: vars.sizing.mobile.height }),
    );

    const messages = style({
        color: vars.colors.bg.toString(),
    });

    const notifications = style({
        color: "inherit",
    });

    const compactSearch = style({
        marginLeft: "auto",
    });

    const topElement = style(
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
    };
}

export function vanillaHeaderLogoClasses() {
    const vars = vanillaHeaderVariables();
    const logoFrame = style({ display: "inline-flex" });
    const logo = style({
        display: "block",
        height: `calc(${vars.sizing.height} - 18px}`,
        width: "auto",
        $nest: {
            "&.isCentred": {
                margin: "auto",
            },
        },
    });

    return { logoFrame, logo };
}
