/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IBackground } from "@library/styles/cssUtilsTypes";
import { Variables } from "@library/styles/Variables";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const homePageVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("homePage");

    // See IBackground for options
    const backgroundImage: IBackground = makeThemeVars("backgroundImage", Variables.background({}));

    return {
        backgroundImage,
    };
});
