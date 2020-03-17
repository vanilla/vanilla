/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    allButtonStates,
    colorOut,
    flexHelper,
    unit,
    userSelect,
    spinnerLoaderAnimationProperties,
    offsetLightness,
} from "@library/styles/styleHelpers";
import { NestedCSSProperties } from "typestyle/lib/types";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { important, percent, px, rgba } from "csx";
import merge from "lodash/merge";
import generateButtonClass from "./styleHelperButtonGenerator";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { IThemeVariables } from "@library/theming/themeReducer";

export enum ButtonPreset {
    SOLID = "solid",
    OUTLINE = "outline",
    TRANSPARENT = "transparent",
    ADVANCED = "advanced",
    HIDE = "hide",
}

export const buttonGlobalVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    // Fetch external global variables
    const globalVars = globalVariables(forcedVars);
    const formElVars = formElementsVariables(forcedVars);
    const makeThemeVars = variableFactory("buttonGlobals", forcedVars);

    let colors = makeThemeVars("colors", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        primary: globalVars.mainColors.primary,
        primaryContrast: globalVars.mainColors.primaryContrast,
        standard: globalVars.mainColors.secondary,
        standardContrast: globalVars.mainColors.primaryContrast,
    });

    const font = makeThemeVars("font", {
        size: globalVars.fonts.size.medium,
        weight: globalVars.fonts.weights.semiBold,
    });

    const padding = makeThemeVars("padding", {
        top: 2,
        bottom: 3,
        side: 12,
    });

    const sizing = makeThemeVars("sizing", {
        minHeight: formElVars.sizing.height,
        minWidth: 104,
        compactHeight: 24,
    });

    const border = makeThemeVars("border", globalVars.border);

    const constants = makeThemeVars("constants", {
        borderMixRatio: 0.24,
    });

    return {
        padding,
        sizing,
        border,
        font,
        constants,
        colors,
    };
});

export const buttonVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const makeThemeVars = variableFactory("button", forcedVars);
    const vars = buttonGlobalVariables(forcedVars);

    const standardPresetInit = makeThemeVars("standard", {
        preset: {
            style: ButtonPreset.OUTLINE,
            border: globalVars.mixBgAndFg(vars.constants.borderMixRatio),
        },
    });

    const standardPresetInit1 = makeThemeVars("standard", {
        preset: {
            ...standardPresetInit.preset,
            bg:
                standardPresetInit.preset.style === ButtonPreset.OUTLINE
                    ? globalVars.mainColors.bg
                    : standardPresetInit.preset.border,
            fg:
                standardPresetInit.preset.style === ButtonPreset.OUTLINE
                    ? globalVars.mainColors.fg
                    : offsetLightness(globalVars.mainColors.fg, 0.1),
        },
    });

    const standardPresetInit2 = makeThemeVars("standard", {
        preset: {
            ...standardPresetInit1.preset,
            bgState: globalVars.mainColors.secondary,
            fgState: globalVars.mainColors.secondaryContrast,
        },
    });

    const standardPreset = makeThemeVars("standard", {
        preset: {
            ...standardPresetInit2.preset,
            borderState: standardPresetInit2.preset.bgState,
        },
    });

    const standard: IButtonType = makeThemeVars("standard", {
        name: ButtonTypes.STANDARD,
        preset: standardPreset.preset,
        colors: {
            fg: standardPreset.preset.fg,
            bg: standardPreset.preset.bg,
        },
        borders: {
            color: standardPreset.preset.border,
            radius: globalVars.border.radius,
        },
        state: {
            borders: {
                ...globalVars.borderType.formElements.buttons,
                color: standardPreset.preset.borderState,
            },
            colors: {
                bg: standardPreset.preset.bgState,
                fg: standardPreset.preset.fgState,
            },
        },
    });

    const primaryPresetInit = makeThemeVars("primary", {
        preset: {
            style: ButtonPreset.SOLID,
            bg: globalVars.mainColors.primary,
            fg: globalVars.mainColors.primaryContrast,
        },
    });

    // const primaryPresetInit1 = makeThemeVars("primary", {
    //     preset: {
    //         ...standardPresetInit.preset,
    //         bg:
    //             primaryPresetInit.preset.style === ButtonPreset.OUTLINE
    //                 ? globalVars.mainColors.bg
    //                 : primaryPresetInit.preset.border,
    //         fg:
    //             primaryPresetInit.preset.style === ButtonPreset.OUTLINE
    //                 ? globalVars.mainColors.fg
    //                 : offsetLightness(globalVars.mainColors.fg, 0.1),
    //     },
    // });

    const primaryPresetInit2 = makeThemeVars("primary", {
        preset: {
            ...primaryPresetInit.preset,
            border: primaryPresetInit.preset.bg,
            bg:
                primaryPresetInit.preset.style === ButtonPreset.SOLID
                    ? globalVars.mainColors.primary
                    : globalVars.mainColors.bg,
            fg:
                primaryPresetInit.preset.style === ButtonPreset.SOLID
                    ? globalVars.mainColors.primaryContrast
                    : primaryPresetInit.preset.bg,
            bgState: globalVars.mainColors.secondary,
            fgState: globalVars.mainColors.secondaryContrast,
        },
    });

    const primaryPreset = makeThemeVars("primary", {
        preset: {
            ...primaryPresetInit2.preset,
            borderState: primaryPresetInit2.preset.bgState,
        },
    });

    const primary = makeThemeVars("primary", {
        name: ButtonTypes.PRIMARY,
        preset: primaryPreset.preset,
        colors: {
            fg: primaryPreset.preset.fg,
            bg: primaryPreset.preset.bg,
        },
        borders: {
            color: primaryPreset.preset.border,
            radius: globalVars.border.radius,
        },
        state: {
            colors: {
                bg: primaryPreset.preset.bgState,
                fg: primaryPreset.preset.fgState,
            },
            borders: {
                color: primaryPreset.preset.borderState,
            },
        },
    });

    const transparent: IButtonType = makeThemeVars("transparent", {
        name: ButtonTypes.TRANSPARENT,
        preset: { style: ButtonPreset.ADVANCED },
        colors: {
            fg: globalVars.mainColors.bg,
            bg: globalVars.mainColors.fg.fade(0.1),
        },
        borders: {
            ...globalVars.borderType.formElements.buttons,
            color: globalVars.mainColors.bg,
        },
        hover: {
            colors: {
                bg: globalVars.mainColors.fg.fade(0.2),
            },
        },
        active: {
            colors: {
                bg: globalVars.mainColors.fg.fade(0.2),
            },
        },
        focus: {
            colors: {
                bg: globalVars.mainColors.fg.fade(0.2),
            },
        },
        focusAccessible: {
            colors: {
                bg: globalVars.mainColors.fg.fade(0.2),
            },
        },
    });

    const translucid: IButtonType = makeThemeVars("translucid", {
        name: ButtonTypes.TRANSLUCID,
        preset: { style: ButtonPreset.ADVANCED },
        colors: {
            bg: globalVars.mainColors.bg,
            fg: globalVars.mainColors.primary,
        },
        borders: {
            ...globalVars.borderType.formElements.buttons,
            color: globalVars.mainColors.bg,
        },
        hover: {
            colors: {
                bg: globalVars.mainColors.bg.fade(0.8),
            },
        },
        active: {
            colors: {
                bg: globalVars.mainColors.bg.fade(0.8),
            },
        },
        focus: {
            colors: {
                bg: globalVars.mainColors.bg.fade(0.8),
            },
        },
        focusAccessible: {
            colors: {
                bg: globalVars.mainColors.bg.fade(0.8),
            },
        },
    });

    return {
        standard,
        primary,
        transparent,
        translucid,
    };
});

export const buttonSizing = (minHeight, minWidth, fontSize, paddingHorizontal, formElementVars, debug?: boolean) => {
    const borderWidth = formElementVars.borders ? formElementVars.borders : buttonGlobalVariables().border.width;
    return {
        minHeight: unit(minHeight ? minHeight : formElementVars.sizing.minHeight),
        minWidth: minWidth ? unit(minWidth) : undefined,
        fontSize: unit(fontSize),
        padding: `${unit(0)} ${px(paddingHorizontal)}`,
        lineHeight: unit(formElementVars.sizing.height - borderWidth * 2),
    };
};

export const buttonResetMixin = (): NestedCSSProperties => ({
    ...userSelect(),
    "-webkit-appearance": "none",
    appearance: "none",
    border: 0,
    padding: 0,
    background: "none",
    cursor: "pointer",
    color: "inherit",
    textDecoration: important("none"),
});

export const overwriteButtonClass = (
    buttonTypeVars: IButtonType,
    overwriteVars: IButtonType,
    setZIndexOnState = false,
) => {
    const buttonVars = merge(buttonTypeVars, overwriteVars);
    // append names for debugging purposes
    buttonVars.name = `${buttonTypeVars.name}-${overwriteVars.name}`;
    return generateButtonClass(buttonVars, setZIndexOnState);
};

export enum ButtonTypes {
    STANDARD = "standard",
    PRIMARY = "primary",
    TRANSPARENT = "transparent",
    TRANSLUCID = "translucid",
    CUSTOM = "custom",
    RESET = "reset",
    TEXT = "text",
    TEXT_PRIMARY = "textPrimary",
    ICON = "icon",
    ICON_COMPACT = "iconCompact",
    TITLEBAR_LINK = "titleBarLink",
    DASHBOARD_STANDARD = "dashboardStandard",
    DASHBOARD_PRIMARY = "dashboardPrimary",
    DASHBOARD_SECONDARY = "dashboardSecondary",
    DASHBOARD_LINK = "dashboardLink",
}

export const buttonClasses = useThemeCache(() => {
    const vars = buttonVariables();
    return {
        primary: generateButtonClass(vars.primary),
        standard: generateButtonClass(vars.standard),
        transparent: generateButtonClass(vars.transparent),
        translucid: generateButtonClass(vars.translucid),
        icon: buttonUtilityClasses().buttonIcon,
        iconCompact: buttonUtilityClasses().buttonIconCompact,
        text: buttonUtilityClasses().buttonAsText,
        textPrimary: buttonUtilityClasses().buttonAsTextPrimary,
        custom: "",
    };
});

export const buttonUtilityClasses = useThemeCache(() => {
    const vars = buttonGlobalVariables();
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const style = styleFactory("buttonUtils");
    const mediaQueries = layoutVariables().mediaQueries();

    const pushLeft = style("pushLeft", {
        marginRight: important("auto"),
    });

    const pushRight = style("pushRight", {
        marginLeft: important("auto"),
    });

    const iconMixin = (dimension: number): NestedCSSProperties => ({
        ...buttonResetMixin(),
        alignItems: "center",
        display: "flex",
        height: unit(dimension),
        minWidth: unit(dimension),
        width: unit(dimension),
        justifyContent: "center",
        border: "none",
        padding: 0,
        background: "transparent",
        ...allButtonStates({
            allStates: {
                color: colorOut(globalVars.mainColors.secondary),
            },
            hover: {
                color: colorOut(globalVars.mainColors.primary),
            },
            focusNotKeyboard: {
                outline: 0,
            },
            accessibleFocus: {
                outline: "initial",
            },
        }),
        color: "inherit",
    });

    const buttonIcon = style(
        "icon",
        iconMixin(formElementVars.sizing.height),
        mediaQueries.oneColumnDown({
            height: vars.sizing.compactHeight,
        }),
    );

    const buttonIconCompact = style("iconCompact", iconMixin(vars.sizing.compactHeight));

    const asTextStyles: NestedCSSProperties = {
        ...buttonResetMixin(),
        minWidth: important(0),
        padding: 0,
        overflow: "hidden",
        textAlign: "left",
        lineHeight: globalVars.lineHeights.base,
        fontWeight: globalVars.fonts.weights.semiBold,
        whiteSpace: "nowrap",
    };

    const buttonAsText = style("asText", asTextStyles, {
        color: "inherit",
        $nest: {
            "&:not(.focus-visible)": {
                outline: 0,
            },
            "&:focus, &:active, &:hover": {
                color: colorOut(globalVars.mainColors.secondary),
            },
        },
    });

    const buttonAsTextPrimary = style("asTextPrimary", asTextStyles, {
        $nest: {
            "&&": {
                color: colorOut(globalVars.mainColors.primary),
            },
            "&&:not(.focus-visible)": {
                outline: 0,
            },
            "&&:hover, &&:focus, &&:active": {
                color: colorOut(globalVars.mainColors.secondary),
            },
        },
    });

    const buttonIconRightMargin = style("buttonIconRightMargin", {
        marginRight: unit(6),
    });

    const buttonIconLeftMargin = style("buttonIconLeftMargin", {
        marginLeft: unit(6),
    });

    const reset = style("reset", buttonResetMixin());

    return {
        pushLeft,
        buttonAsText,
        buttonAsTextPrimary,
        pushRight,
        iconMixin,
        buttonIconCompact,
        buttonIcon,
        buttonIconRightMargin,
        buttonIconLeftMargin,
        reset,
    };
});

export const buttonLoaderClasses = useThemeCache(() => {
    const flexUtils = flexHelper();
    const style = styleFactory("buttonLoader");

    const root = useThemeCache((alignment: "left" | "center" = "center") =>
        style({
            ...(alignment === "center" ? flexUtils.middle() : flexUtils.middleLeft),
            padding: unit(4),
            height: percent(100),
            width: percent(100),
        }),
    );

    const reducedPadding = style("reducedPadding", {
        $nest: {
            "&&": {
                padding: unit(3),
            },
        },
    });

    const svg = style("svg", spinnerLoaderAnimationProperties());
    return { root, svg, reducedPadding };
});
