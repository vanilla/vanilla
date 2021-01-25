/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { IThemeVariables } from "@library/theming/themeReducer";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Variables } from "@library/styles/Variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ButtonPreset } from "./ButtonPreset";
import { getThemeVariables } from "@library/theming/getThemeVariables";

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

    const font = makeThemeVars(
        "font",
        Variables.font({
            size: globalVars.fonts.size.medium,
            weight: globalVars.fonts.weights.semiBold,
        }),
    );

    const padding = makeThemeVars("padding", {
        top: 2,
        bottom: 3,
        horizontal: 12,
        fullBorderRadius: {
            extraHorizontalPadding: 8,
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

    const primaryPresetInit = makeThemeVars("primary", {
        preset: {
            style: ButtonPreset.SOLID,
            bg: globalVars.mainColors.primary,
            fg: globalVars.mainColors.primaryContrast,
        },
    });

    const bgState = ColorsUtils.offsetLightness(primaryPresetInit.preset.bg, 0.05);
    const primaryPresetInit2 = makeThemeVars("primary", {
        preset: {
            ...primaryPresetInit.preset,
            borders: primaryPresetInit.preset.bg,
            bg:
                primaryPresetInit.preset.style === ButtonPreset.SOLID
                    ? globalVars.mainColors.primary
                    : globalVars.mainColors.bg,
            fg:
                primaryPresetInit.preset.style === ButtonPreset.SOLID
                    ? globalVars.mainColors.primaryContrast
                    : primaryPresetInit.preset.bg,
            bgState: bgState,
            fgState: ColorsUtils.isLightColor(bgState)
                ? globalVars.elementaryColors.almostBlack
                : globalVars.elementaryColors.white,
        },
    });

    const primaryPreset = makeThemeVars("primary", {
        preset: {
            ...primaryPresetInit2.preset,
            borders:
                primaryPresetInit.preset.style === ButtonPreset.OUTLINE
                    ? globalVars.mixBgAndFg(vars.constants.borderMixRatio)
                    : primaryPresetInit2.preset.bg,
            borderState:
                primaryPresetInit.preset.style === ButtonPreset.OUTLINE
                    ? primaryPresetInit2.preset.fgState
                    : primaryPresetInit2.preset.bgState,
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
            color: primaryPreset.preset.borders,
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

    const standardPresetInit = makeThemeVars("standard", {
        preset: {
            style: ButtonPreset.OUTLINE,
            borders: globalVars.mixBgAndFg(vars.constants.borderMixRatio),
        },
    });

    const standardPresetInit1 = makeThemeVars("standard", {
        preset: {
            ...standardPresetInit.preset,
            bg:
                standardPresetInit.preset.style === ButtonPreset.OUTLINE
                    ? globalVars.mainColors.bg
                    : standardPresetInit.preset.borders,
            fg:
                standardPresetInit.preset.style === ButtonPreset.OUTLINE
                    ? globalVars.mainColors.fg
                    : ColorsUtils.offsetLightness(globalVars.mainColors.fg, 0.1),
        },
    });

    const standardPresetInit2 = makeThemeVars("standard", {
        preset: {
            ...standardPresetInit1.preset,
            bgState: primary.preset.bgState,
            fgState: primary.preset.fgState,
        },
    });

    const standardPreset = makeThemeVars("standard", {
        preset: {
            ...standardPresetInit2.preset,
            borders:
                standardPresetInit.preset.style === ButtonPreset.OUTLINE
                    ? standardPresetInit2.preset.borders
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
            color: standardPreset.preset.borders,
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

    const notStandard: IButtonType = makeThemeVars("notStandard", {
        name: ButtonTypes.NOT_STANDARD,
        preset: { style: ButtonPreset.ADVANCED },
        colors: {
            bg: globalVars.mainColors.bg,
            fg: globalVars.mainColors.primary,
        },
        borders: {
            ...globalVars.borderType.formElements.buttons,
            color: globalVars.mainColors.bg,
        },
        state: {
            colors: {
                bg: globalVars.mainColors.bg,
                fg: globalVars.mainColors.primary,
            },
            borders: {
                radius: buttonGlobals.border.radius,
                color: globalVars.mainColors.bg,
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
            color: ColorsUtils.colorOut(globalVars.mixBgAndFg(0.1)),
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
                borderColor: ColorsUtils.colorOut(radioPresetInit2.active.color),
                backgroundColor: ColorsUtils.colorOut(radioPresetInit2.active.color),
            },
        },
        skipDynamicPadding: true,
    });

    return {
        standard,
        primary,
        transparent,
        translucid,
        notStandard,
        radio,
    };
});
