/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
    borders,
    componentThemeVariables,
    flexHelper,
    getColorDependantOnLightness,
    spinnerLoader,
    toStringColor,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { BorderRadiusProperty, BorderStyleProperty, WidthProperty } from "csstype";
import { ColorHelper, percent, px } from "csx";
import memoize from "lodash/memoize";
import { TLength } from "typestyle/lib/types";
import get from "lodash/get";

export const buttonStyles = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = componentThemeVariables("button");
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
});

export type ColorValues = ColorHelper | "transparent" | undefined;

export const transparentColor = "transparent" as ColorValues;

export interface IButtonType {
    fg: ColorValues;
    bg?: ColorValues;
    border: {
        color: ColorValues;
        width?: WidthProperty<TLength>;
        style?: BorderStyleProperty;
        radius?: BorderRadiusProperty<TLength>;
    };
    hover: {
        fg: ColorValues;
        bg?: ColorValues;
        border?: {
            color: ColorValues;
            style?: BorderStyleProperty;
            radius?: BorderRadiusProperty<TLength>;
        };
    };
    focus: {
        fg: ColorValues;
        bg?: ColorValues;
        border?: {
            color: ColorValues;
            style?: BorderStyleProperty;
            radius?: BorderRadiusProperty<TLength>;
        };
    };
    active: {
        fg: ColorValues;
        bg?: ColorValues;
        border?: {
            color: ColorValues;
            style?: BorderStyleProperty;
            radius?: BorderRadiusProperty<TLength>;
        };
    };
    focusAccessible: {
        fg: ColorValues;
        bg?: ColorValues;
        border?: {
            color: ColorValues;
            style?: BorderStyleProperty;
            radius?: BorderRadiusProperty<TLength>;
        };
    };
}

export const buttonVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("button");

    const standard: IButtonType = makeThemeVars("basic", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        border: {
            color: globalVars.mixBgAndFg(0.24),
        },
        hover: {
            fg: globalVars.mainColors.fg,
            bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.fg, 0.1),
            borderColor: globalVars.mixBgAndFg(0.1),
        },
        active: {
            fg: globalVars.mainColors.fg,
            bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.fg, 0.1),
            borderColor: globalVars.mixBgAndFg(0.1),
        },
        focus: {
            fg: globalVars.mainColors.fg,
            bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.fg, 0.1),
            borderColor: globalVars.mixBgAndFg(0.1),
        },
        focusAccessible: {
            fg: globalVars.mainColors.fg,
            bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.fg, 0.1),
            borderColor: globalVars.mixBgAndFg(0.1),
        },
    });

    const compact: IButtonType = makeThemeVars("compact", {
        fg: globalVars.mainColors.fg,
        border: {
            color: transparentColor,
        },
        hover: {
            fg: globalVars.mainColors.primary,
        },
        active: {
            fg: globalVars.mainColors.primary,
        },
        focus: {
            fg: globalVars.mainColors.primary,
        },
        focusAccessible: {
            fg: globalVars.mainColors.primary,
        },
    });

    const primary: IButtonType = makeThemeVars("primary", {
        fg: globalVars.elementaryColors.white,
        bg: globalVars.mainColors.primary,
        spinnerColor: globalVars.elementaryColors.white,
        border: {
            color: globalVars.mainColors.primary,
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
    });

    const transparentButtonColor = globalVars.mainColors.bg;
    const transparent: IButtonType = makeThemeVars("transparent", {
        fg: transparentButtonColor,
        bg: transparentColor,
        border: {
            color: transparentButtonColor,
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
    });

    return { standard, primary, transparent };
});

export const buttonSizing = (height, minWidth, fontSize, paddingHorizontal, formElementVars) => {
    return {
        minHeight: px(formElementVars.sizing.height),
        fontSize: px(fontSize),
        padding: `${px(0)} ${px(paddingHorizontal)}`,
        lineHeight: px(formElementVars.sizing.height - formElementVars.border.width * 2),
    };
};

export const generateButtonClass = (buttonType: IButtonType, buttonName: string, setZIndexOnState = false) => {
    const globalVars = globalVariables();
    const formElVars = formElementsVariables();
    const vars = buttonStyles();
    const style = styleFactory("button");
    const zIndex = setZIndexOnState ? 1 : undefined;

    return style({
        textOverflow: "ellipsis",
        overflow: "hidden",
        maxWidth: percent(100),
        ...borders(buttonType.border),
        ...userSelect(),
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
        minWidth: unit(vars.sizing.minWidth),
        cursor: "pointer",
        color: toStringColor(buttonType.fg),
        backgroundColor: toStringColor(buttonType.bg),
        $nest: {
            "&:not([disabled])": {
                $nest: {
                    "&:not(.focus-visible)": {
                        outline: 0,
                    },
                    "&:hover": {
                        zIndex,
                        backgroundColor: toStringColor(buttonType.hover.bg),
                        border: get(buttonType, "hover.border", {}),
                        color: toStringColor(buttonType.hover.fg),
                        ...borders(buttonType.border),
                    },
                    "&:focus": {
                        zIndex,
                        backgroundColor: toStringColor(buttonType.focus.bg),
                        border: get(buttonType, "focus.border", {}),
                        color: toStringColor(buttonType.focus.fg),
                    },
                    "&:active": {
                        zIndex,
                        backgroundColor: toStringColor(buttonType.active.bg),
                        border: get(buttonType, "hover.active", {}),
                        color: toStringColor(buttonType.active.fg),
                    },
                    "&.focus-visible": {
                        zIndex,
                        backgroundColor: toStringColor(buttonType.focus.bg),
                        border: get(buttonType, "hover.accessibleFocus", {}),
                        color: toStringColor(buttonType.focus.fg),
                    },
                },
            },
        },
    });
};

export enum ButtonTypes {
    STANDARD = "standard",
    PRIMARY = "primary",
    TRANSPARENT = "transparent",
    COMPACT = "compact",
}

export const buttonClasses = useThemeCache(() => {
    const vars = buttonVariables();
    return {
        primary: generateButtonClass(vars.primary, "primary"),
        standard: generateButtonClass(vars.standard, "standard"),
        transparent: generateButtonClass(vars.transparent, "transparent"),
        compact: generateButtonClass(vars.transparent, "compact"),
    };
});

export const buttonLoaderClasses = memoize((buttonType: IButtonType) => {
    const themeVars = componentThemeVariables("buttonLoader");
    const globalVars = globalVariables();
    const flexUtils = flexHelper();
    const style = styleFactory("buttonLoader");
    const root = style({
        ...flexUtils.middle(),
        padding: unit(4),
        height: percent(100),
        width: percent(100),
        $nest: {
            "&:after": spinnerLoader({
                color: get(buttonType, "spinnerColor", globalVars.mainColors.primary),
                dimensions: 20,
            }),
        },
        ...themeVars.subComponentStyles("root"),
    });
    return { root };
});
