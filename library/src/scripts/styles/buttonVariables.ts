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
    modifyColorBasedOnLightness,
    spinnerLoader,
    colorOut,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { BorderRadiusProperty, BorderStyleProperty, WidthProperty } from "csstype";
import { ColorHelper, important, px, percent } from "csx";
import memoize from "lodash/memoize";
import { TLength } from "typestyle/lib/types";
import get from "lodash/get";

export const buttonGlobalVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElVars = formElementsVariables();
    const makeThemeVars = variableFactory("button");

    const colors = makeThemeVars("color", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
    });

    const padding = makeThemeVars("padding", {
        top: 2,
        bottom: 3,
        side: 12,
    });

    const font = makeThemeVars("font", {
        size: globalVars.fonts.size.medium,
    });

    const sizing = makeThemeVars("sizing", {
        minHeight: formElVars.sizing.height,
        minWidth: 96,
    });

    const border = makeThemeVars("border", {
        radius: globalVars.border.radius,
    });

    return {
        padding,
        sizing,
        border,
        font,
        colors,
    };
});

export type ColorValues = ColorHelper | "transparent" | undefined;

export const transparentColor = "transparent" as ColorValues;

export interface IButtonType {
    colors?: {
        fg?: ColorValues;
        bg?: ColorValues;
    };
    sizing?: {
        minHeight?: TLength;
        minWidth?: TLength;
    };
    padding?: {
        top?: TLength;
        bottom?: TLength;
        side?: TLength;
    };
    font?: {
        size?: TLength;
    };
    border: {
        color: ColorValues;
        width?: WidthProperty<TLength>;
        style?: BorderStyleProperty;
        radius?: BorderRadiusProperty<TLength>;
    };
    hover: {
        fg?: ColorValues;
        bg?: ColorValues;
        border?: {
            color: ColorValues;
            style?: BorderStyleProperty;
            radius?: BorderRadiusProperty<TLength>;
        };
    };
    focus: {
        fg?: ColorValues;
        bg?: ColorValues;
        border?: {
            color: ColorValues;
            style?: BorderStyleProperty;
            radius?: BorderRadiusProperty<TLength>;
        };
    };
    active: {
        fg?: ColorValues;
        bg?: ColorValues;
        border?: {
            color: ColorValues;
            style?: BorderStyleProperty;
            radius?: BorderRadiusProperty<TLength>;
        };
    };
    focusAccessible: {
        fg?: ColorValues;
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
        colors: {
            fg: globalVars.mainColors.fg,
            bg: globalVars.mainColors.bg,
        },
        border: {
            color: globalVars.mixBgAndFg(0.24),
        },
        hover: {
            fg: globalVars.mainColors.fg,
            bg: modifyColorBasedOnLightness(globalVars.mainColors.fg, globalVars.mainColors.fg, 0.1),
            borderColor: globalVars.mixBgAndFg(0.1),
        },
        active: {
            fg: globalVars.mainColors.fg,
            bg: modifyColorBasedOnLightness(globalVars.mainColors.fg, globalVars.mainColors.fg, 0.1),
            borderColor: globalVars.mixBgAndFg(0.1),
        },
        focus: {
            fg: globalVars.mainColors.fg,
            bg: modifyColorBasedOnLightness(globalVars.mainColors.fg, globalVars.mainColors.fg, 0.1),
            borderColor: globalVars.mixBgAndFg(0.1),
        },
        focusAccessible: {
            fg: globalVars.mainColors.fg,
            bg: modifyColorBasedOnLightness(globalVars.mainColors.fg, globalVars.mainColors.fg, 0.1),
            borderColor: globalVars.mixBgAndFg(0.1),
        },
    });

    const compact: IButtonType = makeThemeVars("compact", {
        colors: {
            fg: globalVars.mainColors.fg,
            bg: globalVars.mainColors.bg,
        },
        sizing: {
            minHeight: 24,
        },
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

    const compactPrimary: IButtonType = makeThemeVars("compactPrimary", {
        colors: {
            fg: globalVars.mainColors.primary.fade(0.7),
            bg: globalVars.mainColors.bg,
        },
        sizing: {
            minHeight: 24,
        },
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
        colors: {
            fg: globalVars.mainColors.bg,
            bg: globalVars.mainColors.primary,
        },
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
        colors: {
            fg: transparentButtonColor,
            bg: transparentColor,
        },
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

    return { standard, primary, transparent, compact, compactPrimary };
});

export const buttonSizing = (height, minWidth, fontSize, paddingHorizontal, formElementVars) => {
    return {
        minHeight: unit(formElementVars.sizing.minHeight),
        fontSize: unit(fontSize),
        padding: `${unit(0)} ${px(paddingHorizontal)}`,
        lineHeight: unit(formElementVars.sizing.height - formElementVars.border.width * 2),
    };
};

export const generateButtonClass = (buttonTypeVars: IButtonType, buttonName: string, setZIndexOnState = false) => {
    const globalVars = globalVariables();
    const formElVars = formElementsVariables();
    const buttonGlobals = buttonGlobalVariables();
    const style = styleFactory("button");
    const zIndex = setZIndexOnState ? 1 : undefined;
    const buttonDimensions = buttonTypeVars.sizing || false;

    return style({
        textOverflow: "ellipsis",
        overflow: "hidden",
        maxWidth: percent(100),
        ...borders(buttonTypeVars.border),
        ...userSelect(),
        ...buttonSizing(
            buttonDimensions && buttonDimensions.minHeight
                ? buttonDimensions.minHeight
                : buttonGlobals.sizing.minHeight,
            buttonDimensions && buttonDimensions.minWidth ? buttonDimensions.minWidth : buttonGlobals.sizing.minWidth,
            buttonTypeVars.font && buttonTypeVars.font.size ? buttonTypeVars.font.size : buttonGlobals.font.size,
            buttonTypeVars.padding && buttonTypeVars.padding.side
                ? buttonTypeVars.padding.side
                : buttonGlobals.padding.side,
            formElVars,
        ),
        display: "inline-block",
        position: "relative",
        textAlign: "center",
        whiteSpace: "nowrap",
        verticalAlign: "middle",
        touchAction: "manipulation",
        cursor: "pointer",
        color: colorOut(
            buttonTypeVars.colors && buttonTypeVars.colors.fg ? buttonTypeVars.colors.fg : buttonGlobals.colors.fg,
        ),
        backgroundColor: colorOut(
            buttonTypeVars.colors && buttonTypeVars.colors.bg ? buttonTypeVars.colors.bg : buttonGlobals.colors.bg,
        ),
        $nest: {
            "&:not([disabled])": {
                $nest: {
                    "&:not(.focus-visible)": {
                        outline: 0,
                    },
                    "&:hover": {
                        zIndex,
                        backgroundColor: colorOut(buttonTypeVars.hover.bg),
                        border: get(buttonTypeVars, "hover.border", {}),
                        color: colorOut(buttonTypeVars.hover.fg),
                        ...borders(buttonTypeVars.border),
                    },
                    "&:focus": {
                        zIndex,
                        backgroundColor: colorOut(buttonTypeVars.focus.bg),
                        border: get(buttonTypeVars, "focus.border", {}),
                        color: colorOut(buttonTypeVars.focus.fg),
                    },
                    "&:active": {
                        zIndex,
                        backgroundColor: colorOut(buttonTypeVars.active.bg),
                        border: get(buttonTypeVars, "hover.active", {}),
                        color: colorOut(buttonTypeVars.active.fg),
                    },
                    "&.focus-visible": {
                        zIndex,
                        backgroundColor: colorOut(buttonTypeVars.focus.bg),
                        border: get(buttonTypeVars, "hover.accessibleFocus", {}),
                        color: colorOut(buttonTypeVars.focus.fg),
                    },
                },
            },
            "&[disabled]": {
                opacity: 0.5,
            },
            "&:focus, &.focus-visible": {
                zIndex: 1,
            },
        },
    });
};

export enum ButtonTypes {
    STANDARD = "standard",
    PRIMARY = "primary",
    TRANSPARENT = "transparent",
    COMPACT = "compact",
    COMPACT_PRIMARY = "compactPrimary",
}

export const buttonClasses = useThemeCache(() => {
    const vars = buttonVariables();
    return {
        primary: generateButtonClass(vars.primary, "primary"),
        standard: generateButtonClass(vars.standard, "standard"),
        transparent: generateButtonClass(vars.transparent, "transparent"),
        compact: generateButtonClass(vars.compact, "compact"),
        compactPrimary: generateButtonClass(vars.compactPrimary, "compactPrimary"),
    };
});

export const buttonUtilityClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const style = styleFactory("buttonUtils");

    const pushLeft = style("pushLeft", {
        marginRight: important("auto"),
    });

    const pushRight = style("pushRight", {
        marginLeft: important("auto"),
    });

    const buttonIcon = style("icon", {
        alignItems: "center",
        display: "flex",
        height: unit(formElementVars.sizing.height),
        minWidth: unit(formElementVars.sizing.height),
        width: unit(formElementVars.sizing.height),
        justifyContent: "center",
        padding: 0,
        color: "inherit",
    });

    const buttonAsText = style("asText", {
        minWidth: important(0),
        padding: important(0),
        overflow: "hidden",
        textAlign: "left",
        lineHeight: globalVars.lineHeights.base,
        color: "inherit",
    });

    return {
        pushLeft,
        buttonAsText,
        pushRight,
        buttonIcon,
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
