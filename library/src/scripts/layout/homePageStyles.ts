/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { IBackground } from "@library/styles/styleHelpers";

export const homePageVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("homePage");

    // See IBackground for options
    const backgroundImage: IBackground = makeThemeVars("backgroundImage", {});

    return {
        backgroundImage,
    };
});
