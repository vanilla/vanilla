import { getColorDependantOnLightness } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export const vanillaMenuVariables = () => {
    const globalVars = globalVariables();
    const guest = {
        spacer: 8,
    };
    const signIn = {
        bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.primary, 0.1).toString(),
        hover: {
            bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.primary, 0.2).toString(),
        },
    };
    const register = {
        bg: globalVars.mainColors.bg,
        hover: {
            bg: globalVars.mainColors.bg.fade(90),
        },
    };
    return { guest, signIn, register };
};
