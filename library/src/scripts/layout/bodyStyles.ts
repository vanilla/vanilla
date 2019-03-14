/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { background, colorOut } from "../styles/styleHelpers";
import { styleFactory, useThemeCache } from "../styles/styleUtils";
import { cssRule } from "typestyle";
import { globalVariables } from "../styles/globalStyleVars";
import { percent, viewHeight } from "csx";

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
        },
        background(image),
    );

    return { root };
});
