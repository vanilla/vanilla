/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { style } from "typestyle";
import { percent, px } from "csx";
import { componentThemeVariables, debugHelper } from "@library/styles/styleHelpers";

export function buttonStyles(theme?: object) {
    const globalVars = globalVariables(theme);
    const themeVars = componentThemeVariables(theme, "button");
    const padding = {
        top: 2,
        bottom: 3,
        side: 6,
        ...themeVars.subComponentStyles("padding"),
    };

    const sizing = {
        minWidth: 464,
        ...themeVars.subComponentStyles("sizing"),
    };

    const border = {
        radius: globalVars.border.radius,
        ...themeVars.subComponentStyles("border"),
    };

    return { padding, sizing, border };
}

interface IButtonType {
    fg: string;
    bg: string;
    borderColor: string;
    border: {
        color: string;
        width: string;
        style: string;
        radius: string;
    };
    hover: {
        fg: string;
        bg: string;
        borderColor: string;
    };
    focus: {
        fg: string;
        bg: string;
        borderColor: string;
    };
    active: {
        fg: string;
        bg: string;
        borderColor: string;
    };
}

export function buttonTypes(theme: object) {
    const globalVars = globalVariables(theme);
    const themeVars = componentThemeVariables(theme, "button");
    const colors = globalVars.mainColors;

    const basic: IButtonType = {
        fg: colors.fg.toString(),
        bg: colors.bg.toString(),
        border: {
            color: globalVars.mixBgAndFg(0.24).toString(),
            width: px(1),
            style: "solid",
            radius: px(globalVars.border.radius),
        },
        hover: {
            color: colors.fg.toString(),
            backgroundColor: colors.bg.darken(0.1).toString(),
            borderColor: globalVars
                .mixBgAndFg(0.4)
                .darken(0.1)
                .toString(),
        },
        select: {
            color: colors.fg.toString(),
            backgroundColor: colors.bg.darken(0.1).toString(),
            borderColor: globalVars
                .mixBgAndFg(0.4)
                .darken(0.1)
                .toString(),
        },
        focus: {
            color: colors.fg.toString(),
            backgroundColor: colors.bg.darken(0.1).toString(),
            borderColor: globalVars
                .mixBgAndFg(0.8)
                .darken(0.1)
                .toString(),
        },
        focusAccessible: {
            color: colors.fg.toString(),
            backgroundColor: colors.bg.darken(0.3).toString(),
            borderColor: colors.bg.darken(1).toString(),
        },
        ...themeVars.subComponentStyles("basic"),
    };

    const primary: IButtonType = {
        fg: globalVars.elementaryColors.white.toString(),
        bg: colors.primary.toString(),
        border: {
            color: colors.primary.toString(),
            width: px(1),
            style: "solid",
            radius: px(globalVars.border.radius),
        },
        hover: {
            color: globalVars.elementaryColors.white.toString(),
            backgroundColor: colors.secondary.toString(),
            borderColor: colors.primary.toString(),
        },
        select: {
            color: globalVars.elementaryColors.white.toString(),
            backgroundColor: colors.secondary.toString(),
            borderColor: colors.primary.toString(),
        },
        focus: {
            color: globalVars.elementaryColors.white.toString(),
            backgroundColor: colors.secondary.toString(),
            borderColor: colors.primary.toString(),
        },
        focusAccessible: {
            color: globalVars.elementaryColors.white.toString(),
            backgroundColor: colors.secondary.toString(),
            borderColor: colors.primary.toString(),
        },
        ...themeVars.subComponentStyles("primary"),
    };

    return { basic, primary };
}

export function buttonSizing(height, minWidth, fontSize, paddingHorizontal, globalVars) {
    return {
        minHeight: height,
        fontSize,
        padding: `${0} ${paddingHorizontal}`,
        lineHeight: globalVars.icon.sizes.default,
    };
}

export enum ButtonStates {
    HOVER = "hover",
    ACTIVE = "focus",
    FOCUS = "active",
    FOCUS_ACCESSIBLE = "focus accessible",
}

export function generateButtonStateStyles(buttonType: IButtonType, key: ButtonStates, setZIndexOnHover = false) {
    return {
        zIndex: setZIndexOnHover ? setZIndexOnHover : undefined,
        ...buttonType[key],
    };
}

export function generateButtonClass(buttonType: IButtonType, theme?: object, setZIndexOnHover = false) {
    const globalVars = globalVariables(theme);
    const formElVars = formElementsVariables(theme);
    const vars = buttonStyles(theme);

    return style({
        textOverflow: "ellipsis",
        overflow: "hidden",
        maxWidth: percent(100),
        ...buttonSizing(
            formElVars.sizing.height,
            vars.sizing.minWidth,
            globalVars.fonts.size.medium,
            vars.padding.side,
            globalVars,
        ),
        display: "inline-flex",
        position: "relative",
        textAlign: "center",
        whiteSpace: "nowrap",
        verticalAlign: "middle",
        touchAction: "manipulation",
        minWidth: vars.sizing.minWidth,
        userSelect: "none",
        cursor: "pointer",
        color: buttonType.fg,
        backgroundColor: buttonType.bg,
        borderColor: buttonType.border.color,
        borderRadius: buttonType.border.radius,
        borderStyle: buttonType.border.style,
        borderWidth: buttonType.border.width,
        $nest: {
            "&:not([disabled])": {
                $nest: {
                    ":not(.focus-visible)": {
                        outline: 0,
                    },
                    "&:hover": generateButtonStateStyles(buttonType, ButtonStates.HOVER, setZIndexOnHover),
                    "&:focus": generateButtonStateStyles(buttonType, ButtonStates.FOCUS, setZIndexOnHover),
                    "&:active": generateButtonStateStyles(buttonType, ButtonStates.ACTIVE, setZIndexOnHover),
                    "&&:.focus-visible": generateButtonStateStyles(
                        buttonType,
                        ButtonStates.FOCUS_ACCESSIBLE,
                        setZIndexOnHover,
                    ),
                },
            },
        },
    });
}

export function buttonClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const debug = debugHelper("button");

    const primary = style({
        color: globalVars.mainColors.bg.toString(),
        backgroundColor: globalVars.mainColors.primary.toString(),
        borderColor: globalVars.mainColors.primary.toString(),
        $nest: {
            ".buttonLoader::after": {
                $nest: {},
            },
        },
        ...debug.name("primary"),
    });

    return { primary };
}
