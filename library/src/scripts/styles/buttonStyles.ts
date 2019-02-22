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
    fg: ColorHelper;
    bg: ColorHelper;
    spinner: ColorHelper;
    border: {
        color: ColorHelper;
        width: Unit;
        style: string;
        radius: Unit;
    };
    hover: {
        fg: ColorHelper;
        bg: ColorHelper;
        borderColor: ColorHelper;
    };
    focus: {
        fg: ColorHelper;
        bg: ColorHelper;
        borderColor: ColorHelper;
    };
    active: {
        fg: ColorHelper;
        bg: ColorHelper;
        borderColor: ColorHelper;
    };
    focusAccessible: {
        fg: ColorHelper;
        bg: ColorHelper;
        borderColor: ColorHelper;
    };
}

export function buttonVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const themeVars = componentThemeVariables(theme, "button");

    const standard: IButtonType = {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        spinner: globalVars.mainColors.primary,
        border: {
            color: globalVars.mixBgAndFg(0.24),
            width: px(1),
            style: "solid",
            radius: globalVars.border.radius,
        },
        hover: {
            fg: globalVars.mainColors.fg,
            bg: globalVars.mainColors.bg.darken(0.1),
            borderColor: globalVars.mixBgAndFg(0.4).darken(0.1),
        },
        active: {
            fg: globalVars.mainColors.fg,
            bg: globalVars.mainColors.bg.darken(0.1),
            borderColor: globalVars.mixBgAndFg(0.4).darken(0.1),
        },
        focus: {
            fg: globalVars.mainColors.fg,
            bg: globalVars.mainColors.bg.darken(0.1),
            borderColor: globalVars.mixBgAndFg(0.8).darken(0.1),
        },
        focusAccessible: {
            fg: globalVars.mainColors.fg,
            bg: globalVars.mainColors.bg.darken(0.3),
            borderColor: globalVars.mainColors.bg.darken(1),
        },
        ...themeVars.subComponentStyles("basic"),
    };

    const primary: IButtonType = {
        fg: globalVars.elementaryColors.white,
        bg: globalVars.mainColors.primary,
        spinner: globalVars.elementaryColors.white,
        border: {
            color: globalVars.mainColors.primary,
            width: px(1),
            style: "solid",
            radius: globalVars.border.radius,
        },
        hover: {
            fg: globalVars.elementaryColors.white,
            bg: globalVars.mainColors.secondary,
            borderColor: globalVars.mainColors.primary,
        },
        active: {
            fg: globalVars.elementaryColors.white,
            bg: globalVars.mainColors.secondary,
            borderColor: globalVars.mainColors.primary,
        },
        focus: {
            fg: globalVars.elementaryColors.white,
            bg: globalVars.mainColors.secondary,
            borderColor: globalVars.mainColors.primary,
        },
        focusAccessible: {
            fg: globalVars.elementaryColors.white,
            bg: globalVars.mainColors.secondary,
            borderColor: globalVars.mainColors.primary,
        },
        ...themeVars.subComponentStyles("primary"),
    };

    const transparentButtonColor = globalVars.mainColors.bg;
    const transparent: IButtonType = {
        fg: transparentButtonColor,
        bg: color("transparent"),
        spinner: globalVars.mainColors.primary,
        border: {
            color: transparentButtonColor,
            width: px(1),
            style: "solid",
            radius: globalVars.border.radius,
        },
        hover: {
            fg: transparentButtonColor,
            bg: globalVars.elementaryColors.white.fade(0.1),
            borderColor: transparentButtonColor,
        },
        active: {
            fg: transparentButtonColor,
            bg: globalVars.elementaryColors.white.fade(0.1),
            borderColor: transparentButtonColor,
        },
        focus: {
            fg: transparentButtonColor,
            bg: globalVars.elementaryColors.white.fade(0.1),
            borderColor: transparentButtonColor,
        },
        focusAccessible: {
            fg: transparentButtonColor,
            bg: globalVars.elementaryColors.white.fade(0.5),
            borderColor: transparentButtonColor,
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
        color: buttonType.fg.toString(),
        backgroundColor: buttonType.bg.toString(),
        borderColor: buttonType.border.color.toString(),
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
                        backgroundColor: buttonType.hover.bg.toString(),
                        borderColor: buttonType.hover.borderColor.toString(),
                        color: buttonType.hover.fg.toString(),
                    },
                    "&:focus": {
                        zIndex,
                        backgroundColor: buttonType.focus.bg.toString(),
                        borderColor: buttonType.focus.borderColor.toString(),
                        color: buttonType.focus.fg.toString(),
                    },
                    "&:active": {
                        zIndex,
                        backgroundColor: buttonType.active.bg.toString(),
                        borderColor: buttonType.active.borderColor.toString(),
                        color: buttonType.active.fg.toString(),
                    },
                    "&.focus-visible": {
                        zIndex,
                        backgroundColor: buttonType.focus.bg.toString(),
                        borderColor: buttonType.focus.borderColor.toString(),
                        color: buttonType.focus.fg.toString(),
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
