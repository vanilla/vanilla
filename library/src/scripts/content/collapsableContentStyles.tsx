/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { absolutePosition, colorOut, defaultTransition, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { linearGradient, percent, px, translateY } from "csx";

export const collapsableContentClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("collapsableContent");

    const root = style({
        background: colorOut(globalVars.mainColors.bg),
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
                "-webkit-appearance": "none",
                "-moz-appearance": "none",
                appearance: "none",
                border: "none",
                borderRadius: 0,
                width: percent(100),
                height: unit(globalVars.icon.sizes.default),
                padding: 0,
                margin: 0,
            },
        },
    });

    const footer = style("footer", {
        position: "relative",
        height: unit(globalVars.icon.sizes.default),
    });

    const collapserIcon = style("collapserIcon", {
        ...defaultTransition("transform"),
    });

    const gradient = style("gradient", {
        ...absolutePosition.topLeft(),
        width: percent(100),
        height: 75,
        background: linearGradient(
            "to bottom",
            colorOut(globalVars.mainColors.bg.fade(0))!,
            colorOut(globalVars.mainColors.bg)!,
        ),
        transform: `translateY(-100%)`,
    });

    return { heightContainer, root, collapser, collapserIcon, footer, gradient };
});
