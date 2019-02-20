/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { style } from "typestyle";
import { color, ColorHelper, deg, percent, px, quote } from "csx";
import {
    componentThemeVariables,
    debugHelper,
    flexHelper,
    getColorDependantOnLightness,
    spinnerLoader,
} from "@library/styles/styleHelpers";

export function buttonStyles(theme?: object) {
    const globalVars = globalVariables(theme);
    const themeVars = componentThemeVariables(theme, "button");
    const padding = {
        top: 2,
        bottom: 3,
        side: 12,
        ...themeVars.subComponentStyles("padding"),
    };

    const sizing = {
        minWidth: 96,
        ...themeVars.subComponentStyles("sizing"),
    };

    const border = {
        radius: globalVars.border.radius,
        ...themeVars.subComponentStyles("border"),
    };

    return { padding, sizing, border };
}

type Unit = string | number;

export interface IButtonType {
    fg: string;
    bg: string;
    spinner: string;
    border: {
        color: string;
        width: Unit;
        style: string;
        radius: Unit;
    };
    hover: {
        color: string;
        backgroundColor: string;
        borderColor: string;
    };
    focus: {
        color: string;
        backgroundColor: string;
        borderColor: string;
    };
    active: {
        color: string;
        backgroundColor: string;
        borderColor: string;
    };
    focusAccessible: {
        color: string;
        backgroundColor: string;
        borderColor: string;
    };
}

export function buttonVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const themeVars = componentThemeVariables(theme, "button");

    const standard: IButtonType = {
        fg: globalVars.mainColors.fg.toString(),
        bg: globalVars.mainColors.bg.toString(),
        spinner: globalVars.mainColors.primary.toString(),
        border: {
            color: globalVars.mixBgAndFg(0.24).toString(),
            width: px(1),
            style: "solid",
            radius: globalVars.border.radius,
        },
        hover: {
            color: globalVars.mainColors.fg.toString(),
            backgroundColor: globalVars.mainColors.bg.darken(0.1).toString(),
            borderColor: globalVars
                .mixBgAndFg(0.4)
                .darken(0.1)
                .toString(),
        },
        active: {
            color: globalVars.mainColors.fg.toString(),
            backgroundColor: globalVars.mainColors.bg.darken(0.1).toString(),
            borderColor: globalVars
                .mixBgAndFg(0.4)
                .darken(0.1)
                .toString(),
        },
        focus: {
            color: globalVars.mainColors.fg.toString(),
            backgroundColor: globalVars.mainColors.bg.darken(0.1).toString(),
            borderColor: globalVars
                .mixBgAndFg(0.8)
                .darken(0.1)
                .toString(),
        },
        focusAccessible: {
            color: globalVars.mainColors.fg.toString(),
            backgroundColor: globalVars.mainColors.bg.darken(0.3).toString(),
            borderColor: globalVars.mainColors.bg.darken(1).toString(),
        },
        ...themeVars.subComponentStyles("basic"),
    };

    const primary: IButtonType = {
        fg: globalVars.elementaryColors.white.toString(),
        bg: globalVars.mainColors.primary.toString(),
        spinner: globalVars.elementaryColors.white.toString(),
        border: {
            color: globalVars.mainColors.primary.toString(),
            width: px(1),
            style: "solid",
            radius: globalVars.border.radius,
        },
        hover: {
            color: globalVars.elementaryColors.white.toString(),
            backgroundColor: globalVars.mainColors.secondary.toString(),
            borderColor: globalVars.mainColors.primary.toString(),
        },
        active: {
            color: globalVars.elementaryColors.white.toString(),
            backgroundColor: globalVars.mainColors.secondary.toString(),
            borderColor: globalVars.mainColors.primary.toString(),
        },
        focus: {
            color: globalVars.elementaryColors.white.toString(),
            backgroundColor: globalVars.mainColors.secondary.toString(),
            borderColor: globalVars.mainColors.primary.toString(),
        },
        focusAccessible: {
            color: globalVars.elementaryColors.white.toString(),
            backgroundColor: globalVars.mainColors.secondary.toString(),
            borderColor: globalVars.mainColors.primary.toString(),
        },
        ...themeVars.subComponentStyles("primary"),
    };

    const transparentButtonColor = globalVars.mainColors.bg;
    const transparent: IButtonType = {
        fg: transparentButtonColor.toString(),
        bg: "transparent",
        spinner: globalVars.mainColors.primary.toString(),
        border: {
            color: transparentButtonColor.toString(),
            width: px(1),
            style: "solid",
            radius: globalVars.border.radius.toString(),
        },
        hover: {
            color: transparentButtonColor.toString(),
            backgroundColor: globalVars.elementaryColors.white.fade(0.1).toString(),
            borderColor: transparentButtonColor.toString(),
        },
        active: {
            color: transparentButtonColor.toString(),
            backgroundColor: globalVars.elementaryColors.white.fade(0.1).toString(),
            borderColor: transparentButtonColor.toString(),
        },
        focus: {
            color: transparentButtonColor.toString(),
            backgroundColor: globalVars.elementaryColors.white.fade(0.1).toString(),
            borderColor: transparentButtonColor.toString(),
        },
        focusAccessible: {
            color: transparentButtonColor.toString(),
            backgroundColor: globalVars.elementaryColors.white.fade(0.5).toString(),
            borderColor: transparentButtonColor.toString(),
        },
        ...themeVars.subComponentStyles("transparent"),
    };

    return { standard, primary, transparent };
}

export function buttonSizing(height, minWidth, fontSize, paddingHorizontal, formElementVars) {
    return {
        minHeight: px(formElementVars.sizing.height),
        fontSize: px(fontSize),
        padding: `${px(0)} ${px(paddingHorizontal)}`,
        lineHeight: px(formElementVars.sizing.height - formElementVars.border.width * 2),
    };
}

export function generateButtonClass(
    buttonType: IButtonType,
    buttonName: string,
    theme?: object,
    setZIndexOnState = false,
) {
    const globalVars = globalVariables(theme);
    const formElVars = formElementsVariables(theme);
    const vars = buttonStyles(theme);
    const debug = debugHelper("button");
    const zIndex = setZIndexOnState ? 1 : undefined;

    return style({
        ...debug.name(buttonName),
        textOverflow: "ellipsis",
        overflow: "hidden",
        maxWidth: percent(100),
        ...buttonSizing(
            formElVars.sizing.height,
            vars.sizing.minWidth,
            globalVars.fonts.size.medium,
            vars.padding.side,
            formElVars,
        ),
        display: "inline-block",
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
                    "&:not(.focus-visible)": {
                        outline: 0,
                    },
                    "&:hover": {
                        zIndex,
                        backgroundColor: buttonType.hover.backgroundColor,
                        borderColor: buttonType.hover.borderColor,
                        color: buttonType.hover.color,
                    },
                    "&:focus": {
                        zIndex,
                        backgroundColor: buttonType.focus.backgroundColor,
                        borderColor: buttonType.focus.borderColor,
                        color: buttonType.focus.color,
                    },
                    "&:active": {
                        zIndex,
                        backgroundColor: buttonType.active.backgroundColor,
                        borderColor: buttonType.active.borderColor,
                        color: buttonType.active.color,
                    },
                    "&.focus-visible": {
                        zIndex,
                        backgroundColor: buttonType.focus.backgroundColor,
                        borderColor: buttonType.focus.borderColor,
                        color: buttonType.focus.color,
                    },
                },
            },
        },
    });
}

export enum ButtonTypes {
    STANDARD = "standard",
    PRIMARY = "primary",
    TRANSPARENT = "transparent",
}

export function buttonClasses(theme?: object) {
    const vars = buttonVariables(theme);
    return {
        primary: generateButtonClass(vars.primary, "primary"),
        standard: generateButtonClass(vars.standard, "standard"),
        transparent: generateButtonClass(vars.transparent, "transparent"),
    };
}

export function buttonLoaderClasses(buttonType: IButtonType, theme?: object) {
    const globalVars = globalVariables(theme);
    const themeVars = componentThemeVariables(theme, "buttonLoader");
    const flexUtils = flexHelper();
    const debug = debugHelper("buttonLoader");
    const root = style({
        ...debug.name(),
        ...flexUtils.middle(),
        padding: px(4),
        height: percent(100),
        width: percent(100),
        $nest: {
            "&::after": spinnerLoader(buttonType.spinner, px(20)) as any,
        },
        ...themeVars.subComponentStyles("root"),
    });
    return { root };
}
