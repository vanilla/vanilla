/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { toStringColor } from "@library/styles/styleHelpers";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { cssRule, style } from "typestyle";
import { globalVariables } from "@library/styles/globalStyleVars";

export const bodyStyles = useThemeCache(() => {
    const globalVars = globalVariables();
    cssRule("body", {
        backgroundColor: toStringColor(globalVars.body.bg),
    });
});
