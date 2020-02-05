/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { allLinkStates, colorOut, importantUnit, margins, paddings, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { percent } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles";

export const linkEmbedCSS = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("linkEmbed");

    cssOut(".embedLink-excerpt", {});
});
