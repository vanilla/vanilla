/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { EMPTY_LEGACY_BUTTON_PRESET, IButton } from "@library/forms/styleHelperButtonInterface";
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

    /**
     * @varGroup buttonGlobals
     * @commonTitle Button Globals
     * @description Variables affecting buttons globally
     */

    const makeThemeVars = variableFactory("buttonGlobals", forcedVars);

    /**
     * @varGroup buttonGlobals.colors
     * @commonTitle Colors
     */
    let colors = makeThemeVars("colors", {
        /**
         * @var buttonGlobals.colors.fg
         * @title Foreground color
         * @type string
         * @format hex-color
         */
        fg: globalVars.mainColors.fg,
        /**
         * @var buttonGlobals.colors.bg
         * @title Background color
         * @type string
         * @format hex-color
         */
        bg: globalVars.mainColors.bg,
        /**
         * @var buttonGlobals.colors.primary
         * @title Primary color
         * @type string
         * @format hex-color
         */
        primary: globalVars.mainColors.primary,
        /**
         * @var buttonGlobals.colors.primaryContrast
         * @title Primary contrast color
         * @type string
         * @format hex-color
         */
        primaryContrast: globalVars.mainColors.primaryContrast,
    });

    /**
     * @varGroup buttonGlobals.font
     * @expand font
     */
    const font = makeThemeVars(
        "font",
        Variables.font({
            ...globalVars.fontSizeAndWeightVars("medium", "semiBold"),
        }),
    );

    /**
     * @varGroup buttonGlobals.padding
     * @commonTitle Padding
     */
    const padding = makeThemeVars("padding", {
        /**
         * @var buttonGlobals.padding.top
         * @title Top
         * @type number
         */
        top: 2,
        /**
         * @var buttonGlobals.padding.bottom
         * @title Bottom
         * @type number
         */
        bottom: 3,
        /**
         * @var buttonGlobals.padding.horizontal
         * @title Horizontal
         * @type number
         */
        horizontal: 12,
        fullBorderRadius: {
            extraHorizontalPadding: 8,
        },
    });

    /**
     * @varGroup buttonGlobals.sizing
     * @commonTitle Sizing
     */
    const sizing = makeThemeVars("sizing", {
        /**
         * @var buttonGlobals.sizing.minHeight
         * @title Minimum height
         * @type number
         */
        minHeight: formElVars.sizing.height,
        /**
         * @var buttonGlobals.sizing.minWidth
         * @title Minimum width
         * @type number
         */
        minWidth: 104,
        /**
         * @var buttonGlobals.sizing.compactHeight
         * @title Compact height
         * @type number
         */
        compactHeight: 24,

        /**
         * @var buttonGlobals.sizing.medHeight
         * @title Medium Height
         * @type number
         */
        medHeight: 32,
    });

    /**
     * @varGroup buttonGlobals.border
     * @commonTitle Border
     * @expand border
     */
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

interface IInitialButtonVars extends Partial<IButton> {
    presetName: NonNullable<IButton["presetName"]>;
}

const generateButtonVarsFromPreset = function (
    name: IButton["name"],
    varFactory: ReturnType<typeof variableFactory>,
    initialButtonVars: IInitialButtonVars,
    forcedVars?: IThemeVariables,
): IButton {
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
                fg: _preset?.fg ?? buttonGlobals.colors.primaryContrast,
                bg: _preset?.bg ?? buttonGlobals.colors.primary,
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
                fg: _preset?.fg ?? buttonGlobals.colors.fg,
                bg: _preset?.bg ?? buttonGlobals.colors.bg,
            },
            state: {
                colors: {
                    bg: _preset?.bgState ?? buttonGlobals.colors.primary,
                    fg: _preset?.fgState ?? buttonGlobals.colors.primaryContrast,
                },
            },
        });

        const { borders } = varFactory(name, {
            borders: {
                ...buttonGlobals.border,
                color:
                    _preset?.borders ??
                    ensureColorHelper(fg!).mix(buttonGlobals.colors.bg, buttonGlobals.constants.borderMixRatio),
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

    const outlineInit = makeThemeVars("outline", {
        preset: {
            fg: buttonGlobals.colors.primary,
            borders: buttonGlobals.colors.primary,
            fgState: buttonGlobals.colors.bg,
        },
        presetName: ButtonPreset.OUTLINE,
    });

    /**
     * @varGroup button.outline
     * @commonTitle Outlined button
     * @expand button
     */
    const outline = makeThemeVars(
        "outline",
        Variables.button({
            ...generateButtonVarsFromPreset("outline", makeThemeVars, outlineInit, forcedVars),
        }),
    );

    const primaryLegacyInit = makeThemeVars("primary", {
        preset: EMPTY_LEGACY_BUTTON_PRESET, //LEGACY
    });

    const primaryInit = makeThemeVars("primary", {
        ...primaryLegacyInit,
        presetName: primaryLegacyInit.preset.style ?? ButtonPreset.SOLID,
    });

    /**
     * @varGroup button.primary
     * @commonTitle Primary button
     * @expand button
     */
    const primary = makeThemeVars(
        "primary",
        Variables.button({
            ...generateButtonVarsFromPreset("primary", makeThemeVars, primaryInit, forcedVars),
        }),
    );

    const standardLegacyInit = makeThemeVars("standard", {
        preset: EMPTY_LEGACY_BUTTON_PRESET, //LEGACY
    });

    const standardInit = makeThemeVars("standard", {
        ...standardLegacyInit,
        presetName: standardLegacyInit.preset.style ?? ButtonPreset.OUTLINE,
    });

    /**
     * @varGroup button.standard
     * @commonTitle Standard button
     * @expand button
     */
    const standard = makeThemeVars(
        "standard",
        Variables.button({
            ...generateButtonVarsFromPreset("standard", makeThemeVars, standardInit, forcedVars),
        }),
    );

    /**
     * @varGroup button.transparent
     * @commonTitle Transparent button
     * @expand button
     */
    const transparent = makeThemeVars(
        "transparent",
        Variables.button({
            name: ButtonTypes.TRANSPARENT,
            colors: {
                fg: buttonGlobals.colors.bg,
                bg: buttonGlobals.colors.fg.fade(0.1),
            },
            borders: {
                ...buttonGlobals.border,
                color: buttonGlobals.colors.bg,
            },
            state: {
                colors: {
                    bg: buttonGlobals.colors.fg.fade(0.2),
                },
            },
        }),
    );

    /**
     * @varGroup button.translucid
     * @commonTitle Translucid button
     * @expand button
     */
    const translucid = makeThemeVars(
        "translucid",
        Variables.button({
            name: ButtonTypes.TRANSLUCID,
            colors: {
                bg: buttonGlobals.colors.bg,
                fg: buttonGlobals.colors.primary,
            },
            borders: {
                ...buttonGlobals.border,
                color: buttonGlobals.colors.bg,
            },
            state: {
                colors: {
                    bg: buttonGlobals.colors.bg.fade(0.8),
                },
            },
        }),
    );

    /**
     * @varGroup button.notStandard
     * @commonTitle Non-standard button
     * @expand button
     */
    const notStandard = makeThemeVars(
        "notStandard",
        Variables.button({
            name: ButtonTypes.NOT_STANDARD,
            colors: {
                bg: buttonGlobals.colors.bg,
                fg: buttonGlobals.colors.primary,
            },
            borders: {
                ...buttonGlobals.border,
                color: buttonGlobals.colors.bg,
            },
            state: {
                colors: {
                    bg: buttonGlobals.colors.bg,
                    fg: buttonGlobals.colors.primary,
                },
                borders: {
                    radius: buttonGlobals.border.radius,
                    color: buttonGlobals.colors.bg,
                },
            },
        }),
    );

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
                fg: buttonGlobals.colors.primary,
            },
            borders: {
                color: buttonGlobals.colors.primary,
                radius: radioPresetInit1.sizing.minHeight / 2,
            },
        },
        //special case
        active: {
            color: ColorsUtils.colorOut(globalVars.mixBgAndFg(0.1)),
        },
    });

    const radio = makeThemeVars(
        "radio",
        Variables.button({
            name: ButtonTypes.RADIO,
            colors: {
                fg: buttonGlobals.colors.fg,
                bg: buttonGlobals.colors.bg,
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
        }),
    );

    return {
        standard,
        primary,
        outline,
        transparent,
        translucid,
        notStandard,
        radio,
    };
});
