/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { linearGradient, percent, px } from "csx";

export const collapsableContentClasses = useThemeCache(() => {
    const vars = globalVariables();
    const style = styleFactory("collapsableContent");

    const root = style({
        background: colorOut(vars.mainColors.bg),
        position: "relative",
    });

    const heightContainer = style("heightContainer", {
        position: "relative",
        display: "block",
        overflow: "hidden",
    });

    const collapser = style("collapser", {
        $nest: {
            "&&": {
                position: "absolute",
                bottom: 0,
                right: 0,
                left: 0,
                height: 40,
                width: percent(100),
                background: linearGradient(
                    "to bottom",
                    colorOut(vars.elementaryColors.white.fade(0))!,
                    colorOut(vars.elementaryColors.white.fade(0.8))!,
                    colorOut(vars.elementaryColors.white)!,
                ),
            },
        },
    });

    const collapserIcon = style("collapserIcon", {
        $nest: {
            "&&": {
                height: px(10),
            },
        },
    });

    return { heightContainer, root, collapser, collapserIcon };
});
