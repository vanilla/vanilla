/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { allLinkStates, importantUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { percent } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";

export const linkEmbedCSS = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("linkEmbed");

    cssOut(".embedLink-excerpt", {});
});
