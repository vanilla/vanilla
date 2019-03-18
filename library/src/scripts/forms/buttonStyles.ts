/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    borders,
    colorOut,
    defaultTransition,
    flexHelper,
    fonts,
    IBorderStyles,
    IFont,
    modifyColorBasedOnLightness,
    spinnerLoader,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { TLength } from "typestyle/lib/types";
import { componentThemeVariables, styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { ColorHelper, important, percent, px } from "csx";

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
    border?: IBorderStyles;
    hover: {
        fg?: ColorValues;
        bg?: ColorValues;
        border?: IBorderStyles;
        font?: IFont;
    };
    focus: {
        fg?: ColorValues;
        bg?: ColorValues;
        border?: IBorderStyles;
        font?: IFont;
    };
    active: {
        fg?: ColorValues;
        bg?: ColorValues;
        border?: IBorderStyles;
        font?: IFont;
    };
    focusAccessible: {
        fg?: ColorValues;
        bg?: ColorValues;
        border?: IBorderStyles;
        font?: IFont;
    };
}

export const buttonVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("button");

    const standard: IButtonType = makeThemeVars("basic", {
        spinnerColor: globalVars.mainColors.fg,
        colors: {
            fg: globalVars.mainColors.fg,
            bg: globalVars.mainColors.bg,
        },
        border: {
            color: globalVars.mixBgAndFg(0.24),
        },
        hover: {
            border: {
                color: globalVars.mainColors.fg,
            },
        },
        active: {
            border: {
                color: globalVars.mainColors.fg,
            },
        },
        focus: {
            border: {
                color: globalVars.mainColors.fg,
            },
        },
        focusAccessible: {
            border: {
                color: globalVars.mainColors.fg,
            },
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
            fg: globalVars.elementaryColors.white,
            bg: globalVars.mainColors.primary,
        },
        spinnerColor: globalVars.elementaryColors.white,
        border: {
            color: globalVars.mainColors.primary,
        },
        hover: {
            fg: globalVars.elementaryColors.white,
            bg: globalVars.mainColors.secondary,
            border: {
                color: globalVars.mainColors.primary,
            },
        },
        active: {
            fg: globalVars.elementaryColors.white,
            bg: globalVars.mainColors.secondary,
            border: {
                color: globalVars.mainColors.primary,
            },
        },
        focus: {
            fg: globalVars.elementaryColors.white,
            bg: globalVars.mainColors.secondary,
            border: {
                color: globalVars.mainColors.primary,
            },
        },
        focusAccessible: {
            fg: globalVars.elementaryColors.white,
            bg: globalVars.mainColors.secondary,
            border: {
                color: globalVars.mainColors.primary,
            },
        },
    });

    const transparent: IButtonType = makeThemeVars("transparent", {
        colors: {
            fg: globalVars.mainColors.fg,
            bg: transparentColor,
        },
        border: {
            color: modifyColorBasedOnLightness(globalVars.mainColors.fg, globalVars.mainColors.fg, 1, true),
        },
        hover: {
            bg: modifyColorBasedOnLightness(globalVars.mainColors.fg, globalVars.mainColors.fg, 0.9),
        },
        active: {
            bg: modifyColorBasedOnLightness(globalVars.mainColors.fg, globalVars.mainColors.fg, 0.9),
        },
        focus: {
            bg: modifyColorBasedOnLightness(globalVars.mainColors.fg, globalVars.mainColors.fg, 0.9),
        },
        focusAccessible: {
            bg: modifyColorBasedOnLightness(globalVars.mainColors.fg, globalVars.mainColors.fg, 0.9),
        },
    });

    const translucid: IButtonType = makeThemeVars("translucid", {
        colors: {
            fg: globalVars.mainColors.bg,
            bg: modifyColorBasedOnLightness(globalVars.mainColors.bg, globalVars.mainColors.bg, 1).fade(0.1),
        },
        spinnerColor: globalVars.mainColors.bg,
        border: {
            color: globalVars.mainColors.bg,
        },
        hover: {
            bg: modifyColorBasedOnLightness(globalVars.mainColors.bg, globalVars.mainColors.bg, 1).fade(0.2),
            border: {
                color: globalVars.mainColors.bg,
            },
        },
        active: {
            bg: modifyColorBasedOnLightness(globalVars.mainColors.bg, globalVars.mainColors.bg, 1).fade(0.2),
            border: {
                color: globalVars.mainColors.bg,
            },
        },
        focus: {
            bg: modifyColorBasedOnLightness(globalVars.mainColors.bg, globalVars.mainColors.bg, 1).fade(0.2),
            border: {
                color: globalVars.mainColors.bg,
            },
        },
        focusAccessible: {
            bg: modifyColorBasedOnLightness(globalVars.mainColors.bg, globalVars.mainColors.bg, 1).fade(0.2),
            border: {
                color: globalVars.mainColors.bg,
            },
        },
    });

    const inverted: IButtonType = makeThemeVars("inverted", {
        colors: {
            fg: globalVars.mainColors.primary,
            bg: globalVars.mainColors.fg,
        },
        spinnerColor: globalVars.elementaryColors.white,
        border: {
            color: globalVars.mainColors.fg,
        },
        hover: {
            bg: globalVars.mainColors.fg.fade(0.9),
        },
        active: {
            bg: globalVars.mainColors.fg.fade(0.8),
        },
        focus: { bg: globalVars.mainColors.fg.fade(0.8) },
        focusAccessible: { bg: globalVars.mainColors.fg.fade(0.8) },
    });

    return {
        standard,
        primary,
        transparent,
        compact,
        compactPrimary,
        inverted,
        translucid,
    };
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
    const style = styleFactory(`button-${buttonName}`);
    const zIndex = setZIndexOnState ? 1 : undefined;
    const buttonDimensions = buttonTypeVars.sizing || false;

    return style({
        ...defaultTransition("border"),
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
        minWidth: buttonGlobals.sizing.minWidth,
        minHeight: buttonGlobals.sizing.minHeight,
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
                        color: colorOut(buttonTypeVars.hover.fg),
                        ...borders(buttonTypeVars.hover.border),
                        ...fonts(buttonTypeVars.hover && buttonTypeVars.hover.font ? buttonTypeVars.hover.font : {}),
                    },
                    "&:focus": {
                        zIndex,
                        backgroundColor: colorOut(buttonTypeVars.focus.bg),
                        ...borders(buttonTypeVars.focus.border),
                        color: colorOut(buttonTypeVars.focus.fg),
                        ...fonts(buttonTypeVars.focus && buttonTypeVars.focus.font ? buttonTypeVars.focus.font : {}),
                    },
                    "&:active": {
                        zIndex,
                        backgroundColor: colorOut(buttonTypeVars.active.bg),
                        ...borders(buttonTypeVars.active.border),
                        color: colorOut(buttonTypeVars.active.fg),
                        ...fonts(buttonTypeVars.active && buttonTypeVars.active.font ? buttonTypeVars.active.font : {}),
                    },
                    "&.focus-visible": {
                        zIndex,
                        backgroundColor: colorOut(buttonTypeVars.focus.bg),
                        ...borders(buttonTypeVars.focusAccessible.border),
                        color: colorOut(buttonTypeVars.focusAccessible.fg),
                        ...fonts(
                            buttonTypeVars.focusAccessible && buttonTypeVars.focusAccessible.font
                                ? buttonTypeVars.focusAccessible.font
                                : {},
                        ),
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
    TRANSLUCID = "translucid",
    INVERTED = "inverted",
    CUSTOM = "custom",
    TAB = "tab",
    TEXT = "text",
    ICON = "icon",
}

export const buttonClasses = useThemeCache(() => {
    const vars = buttonVariables();
    return {
        primary: generateButtonClass(vars.primary, ButtonTypes.PRIMARY),
        standard: generateButtonClass(vars.standard, ButtonTypes.STANDARD),
        transparent: generateButtonClass(vars.transparent, ButtonTypes.TRANSPARENT),
        compact: generateButtonClass(vars.compact, ButtonTypes.COMPACT),
        compactPrimary: generateButtonClass(vars.compactPrimary, ButtonTypes.COMPACT_PRIMARY),
        translucid: generateButtonClass(vars.translucid, ButtonTypes.TRANSLUCID),
        inverted: generateButtonClass(vars.inverted, ButtonTypes.INVERTED),
        tab: "buttonAsTab",
        icon: "buttonAsIcon",
        text: "buttonAsText",
        custom: "",
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

export const buttonLoaderClasses = (buttonType: ButtonTypes) => {
    const themeVars = componentThemeVariables("buttonLoader");
    const globalVars = globalVariables();
    const flexUtils = flexHelper();
    const style = styleFactory("buttonLoader");
    const buttonVars = buttonVariables();
    let typeVars;

    switch (buttonType) {
        case ButtonTypes.PRIMARY:
            typeVars = buttonVars.primary;
            break;
        default:
            typeVars = buttonVars.standard;
            break;
    }

    const root = style({
        ...flexUtils.middle(),
        padding: unit(4),
        height: percent(100),
        width: percent(100),
        $nest: {
            "&:after": spinnerLoader({
                color: typeVars.spinnerColor || (globalVars.mainColors.primary as any),
                dimensions: 20,
            }),
        },
        ...themeVars.subComponentStyles("root"),
    });
    return { root };
};
