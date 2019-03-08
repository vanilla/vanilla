/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { backgroundImage, toStringColor } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { cssRule } from "typestyle";
import { globalVariables } from "@library/styles/globalStyleVars";
import get from "lodash/get";
import { percent, viewHeight } from "csx";

export const bodyCSS = useThemeCache(() => {
    const globalVars = globalVariables();
    cssRule("html, body", {
        backgroundColor: toStringColor(globalVars.body.bg),
        color: toStringColor(globalVars.mainColors.fg),
    });
});

export const bodyClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("fullBackground");
    const image = get(globalVars, "body.backgroundImage.image", undefined);
    const root = style({
        display: !image ? "none" : "block",
        ...backgroundImage({ image }),
        backgroundColor: toStringColor(globalVars.body.bg),
        position: "fixed",
        top: 0,
        left: 0,
        width: percent(100),
        height: viewHeight(100),
    });

    return { root };
});
