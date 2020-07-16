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
import { important, percent, px } from "csx";
import merge from "lodash/merge";
import generateButtonClass from "./styleHelperButtonGenerator";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { IThemeVariables } from "@library/theming/themeReducer";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { paddingOffsetBasedOnBorderRadius } from "@library/forms/paddingOffsetFromBorderRadius";

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
        horizontal: 12,
        fullBorderRadius: {
            extraHorizontalPadding: 8, // Padding when you have fully rounded border radius. Will be applied based on the amount of border radius. Set to "undefined" to turn off
        },
    });

    const sizing = makeThemeVars("sizing", {
        minHeight: formElVars.sizing.height,
        minWidth: 104,
        compactHeight: 24,
    });

    const border = makeThemeVars("border", globalVars.borderType.formElements.buttons);

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
    const buttonGlobals = buttonGlobalVariables();

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
            border:
                standardPresetInit.preset.style === ButtonPreset.OUTLINE
                    ? standardPresetInit2.preset.border
                    : standardPresetInit2.preset.bg,
            borderState:
                standardPresetInit.preset.style === ButtonPreset.OUTLINE
                    ? standardPresetInit2.preset.fgState
                    : standardPresetInit2.preset.bgState,
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
            ...globalVars.borderType.formElements.buttons,
            radius: buttonGlobals.border.radius,
            color: standardPreset.preset.border,
        },
        state: {
            borders: {
                ...globalVars.borderType.formElements.buttons,
                radius: buttonGlobals.border.radius,
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
            ...globalVars.borderType.formElements.buttons,
            radius: buttonGlobals.border.radius,
            color: primaryPreset.preset.border,
        },
        state: {
            colors: {
                bg: primaryPreset.preset.bgState,
                fg: primaryPreset.preset.fgState,
            },
            borders: {
                radius: buttonGlobals.border.radius,
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

    const radioPresetInit1 = makeThemeVars("radio", {
        sizing: {
            minHeight: formElementsVariables().sizing.height - 4,
        },
    });

    const radioPresetInit2 = makeThemeVars("radio", {
        ...radioPresetInit1,
        borders: {
            ...globalVars.border,
            radius: radioPresetInit1.sizing.minHeight / 2,
        },
        state: {
            colors: {
                fg: globalVars.mainColors.primary,
            },
            borders: {
                color: globalVars.mainColors.primary,
                radius: radioPresetInit1.sizing.minHeight / 2,
            },
        },
        //special case
        active: {
            color: colorOut(globalVars.mixBgAndFg(0.1)),
        },
    });

    const radio: IButtonType = makeThemeVars("radio", {
        name: ButtonTypes.RADIO,
        preset: { style: ButtonPreset.ADVANCED },
        colors: {
            fg: globalVars.mainColors.fg,
            bg: globalVars.mainColors.bg,
        },
        padding: {
            horizontal: 12,
        },
        borders: radioPresetInit2.borders,
        state: radioPresetInit2.state,
        sizing: {
            minHeight: radioPresetInit2.sizing.minHeight,
        },
        extraNested: {
            ["&.isActive"]: {
                borderColor: colorOut(radioPresetInit2.active.color),
                backgroundColor: colorOut(radioPresetInit2.active.color),
            },
        },
        skipDynamicPadding: true,
    });

    return {
        standard,
        primary,
        transparent,
        translucid,
        radio,
    };
});

export const buttonSizing = (props: {
    minHeight;
    minWidth;
    fontSize;
    paddingHorizontal;
    formElementVars;
    borderRadius;
    skipDynamicPadding;
    debug?: boolean;
}) => {
    const buttonGlobals = buttonGlobalVariables();
    const {
        minHeight = buttonGlobals.sizing.minHeight,
        minWidth = buttonGlobals.sizing.minWidth,
        fontSize = buttonGlobals.font.size,
        paddingHorizontal = buttonGlobals.padding.horizontal,
        formElementVars,
        borderRadius,
        skipDynamicPadding,
        debug = false,
    } = props;

    const borderWidth = formElementVars.borders ? formElementVars.borders : buttonGlobals.border.width;
    const height = minHeight ?? formElementVars.sizing.minHeight;

    const paddingOffsets = !skipDynamicPadding
        ? paddingOffsetBasedOnBorderRadius({
              radius: borderRadius,
              extraPadding: buttonGlobals.padding.fullBorderRadius.extraHorizontalPadding,
              height,
          })
        : {
              right: 0,
              left: 0,
          };

    return {
        minHeight: unit(height),
        minWidth: minWidth ? unit(minWidth) : undefined,
        fontSize: unit(fontSize),
        padding: `0px ${px(paddingHorizontal + paddingOffsets.right ?? 0)} 0px ${px(
            paddingHorizontal + paddingOffsets.left ?? 0,
        )}`,
        lineHeight: unit(height - borderWidth * 2),
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
    textAlign: "inherit",
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
        radio: generateButtonClass(vars.radio),
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
            clickFocus: {
                outline: 0,
            },
            keyboardFocus: {
                outline: "initial",
            },
        }),
        color: "inherit",
    });

    const buttonIcon = style(
        "buttonIcon",
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
                color: colorOut(globalVars.links.colors.default),
            },
            "&&:not(.focus-visible)": {
                outline: 0,
            },
            "&&:hover, &&:focus, &&:active": {
                color: colorOut(globalVars.links.colors.active),
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
            $nest: {
                [`& + .suggestedTextInput-parentTag`]: {
                    display: "none",
                },
            },
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
