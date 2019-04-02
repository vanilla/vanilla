/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { percent, viewHeight } from "csx";
import { cssRule } from "typestyle";
import { colorOut, background } from "@library/styles/styleHelpers";

export const bodyCSS = useThemeCache(() => {
    const globalVars = globalVariables();
    cssRule("html, body", {
        backgroundColor: colorOut(globalVars.body.backgroundImage.color),
        color: colorOut(globalVars.mainColors.fg),
    });
});

export const bodyClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("fullBackground");
    const image = globalVars.body.backgroundImage;
    const root = style(
        {
            display: !image ? "none" : "block",
            position: "fixed",
            top: 0,
            left: 0,
            width: percent(100),
            height: viewHeight(100),
            zIndex: -1,
        },
        background(image),
    );

    return { root };
});
