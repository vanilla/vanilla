import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, getColorDependantOnLightness } from "@library/styles/styleHelpers";
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export const vanillaMenuVariables = (theme?: object) => {
    const globalVars = globalVariables(theme);
    const themeVars = componentThemeVariables(theme, "vanillaMenu");

    const guest = {
        spacer: 8,
        ...themeVars.subComponentStyles("guest"),
    };
    const signIn = {
        bg: getColorDependantOnLightness(
            globalVars,
            globalVars.mainColors.fg,
            globalVars.mainColors.primary,
            0.1,
        ).toString(),
        hover: {
            bg: getColorDependantOnLightness(
                globalVars,
                globalVars.mainColors.fg,
                globalVars.mainColors.primary,
                0.2,
            ).toString(),
        },
        ...themeVars.subComponentStyles("signIn"),
    };
    const register = {
        bg: globalVars.mainColors.bg,
        hover: {
            bg: globalVars.mainColors.bg.fade(90),
        },
        ...themeVars.subComponentStyles("register"),
    };
    return { guest, signIn, register };
};
