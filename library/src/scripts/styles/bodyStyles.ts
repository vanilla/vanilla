/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { backgroundCover, toStringColor } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { cssRule } from "typestyle";
import { globalVariables } from "@library/styles/globalStyleVars";
import get from "lodash/get";

export const bodyCSS = useThemeCache(() => {
    const globalVars = globalVariables();
    cssRule("body", {
        backgroundColor: toStringColor(globalVars.body.bg),
    });
});

export const bodyClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("body");
    const root = style({
        ...backgroundCover(get(globalVars, "body.backgroundImage.image", null)),
    });

    return { root };
});
