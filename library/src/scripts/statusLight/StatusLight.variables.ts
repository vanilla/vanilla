/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const StatusLightVariables = useThemeCache(() => {
    const globalVars = globalVariables();

    const makeThemeVars = variableFactory("statusLight");

    const sizing = makeThemeVars("sizing", {
        width: 8,
    });

    const colors = makeThemeVars("colors", {
        active: globalVars.mainColors.primary,
        inactive: "linear-gradient(90deg, #E6E6E7 0%, #D5D6D8 100%)", //fixme: this should be defined in terms of global variables
    });

    return { sizing, colors };
});
