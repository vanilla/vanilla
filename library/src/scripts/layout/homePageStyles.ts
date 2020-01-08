/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent, viewHeight } from "csx";
import { cssRule, forceRenderStyles } from "typestyle";
import {
    colorOut,
    background,
    fontFamilyWithDefaults,
    margins,
    paddings,
    fonts,
    IBackground,
} from "@library/styles/styleHelpers";

export const homePageVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("homePage");

    // See IBackground for options
    const backgroundImage: IBackground = makeThemeVars("backgroundImage", {});

    return {
        backgroundImage,
    };
});
