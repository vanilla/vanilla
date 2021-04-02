/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { EMPTY_LEGACY_BUTTON_PRESET, IButtonType } from "@library/forms/styleHelperButtonInterface";
import { IThemeVariables } from "@library/theming/themeReducer";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Variables } from "@library/styles/Variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ButtonPreset } from "./ButtonPreset";
import { ensureColorHelper } from "@library/styles/styleHelpers";

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

interface IInitialButtonVars extends Partial<IButtonType> {
    presetName: NonNullable<IButtonType["presetName"]>;
}

const generateButtonVarsFromPreset = function (
    name: IButtonType["name"],
    varFactory: ReturnType<typeof variableFactory>,
    initialButtonVars: IInitialButtonVars,
    forcedVars?: IThemeVariables,
): IButtonType {
    const globalVars = globalVariables(forcedVars);
    const { almostBlack, white } = globalVars.elementaryColors;
    const buttonGlobals = buttonGlobalVariables(forcedVars);
    const formElementVars = formElementsVariables(forcedVars);

    const _preset = initialButtonVars.preset; //LEGACY

    const { presetName } = varFactory(name, {
        ...initialButtonVars,
    });

    if (presetName === ButtonPreset.SOLID) {
        const {
            colors: { fg, bg },
        } = varFactory(name, {
            colors: {
                fg: _preset?.fg ?? globalVars.mainColors.primaryContrast,
                bg: _preset?.bg ?? globalVars.mainColors.primary,
            },
        });

        const {
            state: {
                colors: { bg: bgState },
            },
        } = varFactory(name, {
            state: {
                colors: {
                    bg: _preset?.bgState ?? ColorsUtils.offsetLightness(bg, 0.05),
                },
            },
        });

        const {
            state: {
                colors: { fg: fgState },
            },
        } = varFactory(name, {
            state: {
                colors: {
                    fg: _preset?.fgState ?? (ColorsUtils.isLightColor(bgState) ? almostBlack : white),
                },
            },
        });

        const { borders } = varFactory(name, {
            borders: {
                ...buttonGlobals.border,
                color: _preset?.borders ?? bg,
            },
        });

        const {
            state: { borders: bordersState },
        } = varFactory(name, {
            state: {
                borders: {
                    ...borders,
                    color: _preset?.borderState ?? bgState,
                },
            },
        });

        const { disabled } = varFactory(name, {
            disabled: {
                colors: {
                    fg,
                    bg,
                },
                borders,
                opacity: formElementVars.disabled.opacity,
            },
        });

        const buttonVars = varFactory(name, {
            name,
            presetName,
            colors: {
                fg,
                bg,
            },
            borders,
            useShadow: false,
            opacity: undefined,
            state: {
                colors: {
                    fg: fgState,
                    bg: bgState,
                },
                borders: bordersState,
                opacity: undefined,
            },
            disabled,
        });

        return buttonVars;
    }

    if (presetName === ButtonPreset.OUTLINE) {
        const {
            colors: { fg, bg },
            state: {
                colors: { fg: fgState, bg: bgState },
            },
        } = varFactory(name, {
            colors: {
                fg: _preset?.fg ?? globalVars.mainColors.fg,
                bg: _preset?.bg ?? globalVars.mainColors.bg,
            },
            state: {
                colors: {
                    bg: _preset?.bgState ?? globalVars.mainColors.primary,
                    fg: _preset?.fgState ?? globalVars.mainColors.primaryContrast,
                },
            },
        });

        const { borders } = varFactory(name, {
            borders: {
                ...buttonGlobals.border,
                color:
                    _preset?.borders ??
                    ensureColorHelper(fg!).mix(globalVars.mainColors.bg, buttonGlobals.constants.borderMixRatio),
            },
        });

        const {
            state: { borders: bordersState },
        } = varFactory(name, {
            state: {
                borders: {
                    ...borders,
                    color: _preset?.borderState ?? bgState,
                },
            },
        });

        const { disabled } = varFactory(name, {
            disabled: {
                colors: {
                    fg,
                    bg,
                },
                borders,
                opacity: formElementVars.disabled.opacity,
            },
        });

        const buttonVars = varFactory(name, {
            name,
            presetName,
            colors: {
                fg,
                bg,
            },
            borders,
            useShadow: false,
            opacity: undefined,
            state: {
                colors: {
                    fg: fgState,
                    bg: bgState,
                },
                borders: bordersState,
                opacity: undefined,
            },
            disabled,
        });

        return buttonVars;
    }

    return {
        ...initialButtonVars,
        name,
    };
};

export const buttonVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const makeThemeVars = variableFactory("button", forcedVars);
    const buttonGlobals = buttonGlobalVariables(forcedVars);

    const primaryLegacyInit = makeThemeVars("primary", {
        preset: EMPTY_LEGACY_BUTTON_PRESET, //LEGACY
    });

    const primaryInit = makeThemeVars("primary", {
        ...primaryLegacyInit,
        presetName: primaryLegacyInit.preset.style ?? ButtonPreset.SOLID,
    });

    const primary = makeThemeVars("primary", {
        ...generateButtonVarsFromPreset("primary", makeThemeVars, primaryInit, forcedVars),
    });

    const standardLegacyInit = makeThemeVars("standard", {
        preset: EMPTY_LEGACY_BUTTON_PRESET, //LEGACY
    });

    const standardInit = makeThemeVars("standard", {
        ...standardLegacyInit,
        presetName: standardLegacyInit.preset.style ?? ButtonPreset.OUTLINE,
    });

    const standard = makeThemeVars("standard", {
        ...generateButtonVarsFromPreset("standard", makeThemeVars, standardInit, forcedVars),
    });

    const transparent: IButtonType = makeThemeVars("transparent", {
        name: ButtonTypes.TRANSPARENT,
        presetName: ButtonPreset.ADVANCED,
        colors: {
            fg: globalVars.mainColors.bg,
            bg: globalVars.mainColors.fg.fade(0.1),
        },
        borders: {
            ...globalVars.borderType.formElements.buttons,
            color: globalVars.mainColors.bg,
        },
        state: {
            colors: {
                bg: globalVars.mainColors.fg.fade(0.2),
            },
        },
    });

    const translucid: IButtonType = makeThemeVars("translucid", {
        name: ButtonTypes.TRANSLUCID,
        presetName: ButtonPreset.ADVANCED,
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
                bg: globalVars.mainColors.bg.fade(0.8),
            },
        },
    });

    const notStandard: IButtonType = makeThemeVars("notStandard", {
        name: ButtonTypes.NOT_STANDARD,
        presetName: ButtonPreset.ADVANCED,
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
        presetName: ButtonPreset.ADVANCED,
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
