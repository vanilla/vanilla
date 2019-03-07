/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
    componentThemeVariables,
    flexHelper,
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

export type ColorHelperAndTransparent = ColorHelper | "transparent";

export interface IButtonType {
    fg: ColorHelperAndTransparent;
    bg: ColorHelperAndTransparent;
    spinnerColor: ColorHelper;
    border: {
        color: ColorHelperAndTransparent;
        width: WidthProperty<TLength>;
        style: BorderStyleProperty;
        radius: BorderRadiusProperty<TLength>;
    };
    hover: {
        fg: ColorHelperAndTransparent;
        bg: ColorHelperAndTransparent;
        borderColor: ColorHelperAndTransparent;
    };
    focus: {
        fg: ColorHelperAndTransparent;
        bg: ColorHelperAndTransparent;
        borderColor: ColorHelperAndTransparent;
    };
    active: {
        fg: ColorHelperAndTransparent;
        bg: ColorHelperAndTransparent;
        borderColor: ColorHelperAndTransparent;
    };
    focusAccessible: {
        fg: ColorHelperAndTransparent;
        bg: ColorHelperAndTransparent;
        borderColor: ColorHelperAndTransparent;
    };
}

export const buttonVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("button");

    const standard: IButtonType = makeThemeVars("basic", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        spinnerColor: globalVars.mainColors.primary,
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
    });

    const primary: IButtonType = makeThemeVars("primary", {
        fg: globalVars.elementaryColors.white,
        bg: globalVars.mainColors.primary,
        spinnerColor: globalVars.elementaryColors.white,
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
    });

    const transparentButtonColor = globalVars.mainColors.bg;
    const transparent: IButtonType = makeThemeVars("transparent", {
        fg: transparentButtonColor,
        bg: "transparent" as any,
        spinnerColor: globalVars.mainColors.primary,
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
        borderColor: toStringColor(buttonType.border.color),
        borderRadius: unit(buttonType.border.radius),
        borderStyle: buttonType.border.style,
        borderWidth: unit(buttonType.border.width),
        $nest: {
            "&:not([disabled])": {
                $nest: {
                    "&:not(.focus-visible)": {
                        outline: 0,
                    },
                    "&:hover": {
                        zIndex,
                        backgroundColor: toStringColor(buttonType.hover.bg),
                        borderColor: toStringColor(buttonType.hover.borderColor),
                        color: toStringColor(buttonType.hover.fg),
                    },
                    "&:focus": {
                        zIndex,
                        backgroundColor: toStringColor(buttonType.focus.bg),
                        borderColor: toStringColor(buttonType.focus.borderColor),
                        color: toStringColor(buttonType.focus.fg),
                    },
                    "&:active": {
                        zIndex,
                        backgroundColor: toStringColor(buttonType.active.bg),
                        borderColor: toStringColor(buttonType.active.borderColor),
                        color: toStringColor(buttonType.active.fg),
                    },
                    "&.focus-visible": {
                        zIndex,
                        backgroundColor: toStringColor(buttonType.focus.bg),
                        borderColor: toStringColor(buttonType.focus.borderColor),
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
}

export const buttonClasses = useThemeCache(() => {
    const vars = buttonVariables();
    return {
        primary: generateButtonClass(vars.primary, "primary"),
        standard: generateButtonClass(vars.standard, "standard"),
        transparent: generateButtonClass(vars.transparent, "transparent"),
    };
});

export const buttonLoaderClasses = memoize((buttonType: IButtonType) => {
    const themeVars = componentThemeVariables("buttonLoader");
    const flexUtils = flexHelper();
    const style = styleFactory("buttonLoader");
    const root = style({
        ...flexUtils.middle(),
        padding: px(4),
        height: percent(100),
        width: percent(100),
        $nest: {
            "&:after": spinnerLoader({
                color: buttonType.spinnerColor,
                dimensions: 20,
            }),
        },
        ...themeVars.subComponentStyles("root"),
    });
    return { root };
});
