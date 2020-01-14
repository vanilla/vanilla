/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { rgba, percent, linearGradient } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { keyframes } from "typestyle";

export const loadingRectangeVariables = useThemeCache(() => {
    const makeVars = variableFactory("loadingRectatngle");

    const colors = makeVars("colors", {
        bg: linearGradient(
            "to right",
            globalVariables().mixBgAndFg(0.15),
            globalVariables().mixBgAndFg(0.2),
            globalVariables().mixBgAndFg(0.25),
        ),
    });

    const loadingAnimation = keyframes({
        "0%": { opacity: 0.8 },
        "50%": { opacity: 1 },
        "100%": { opacity: 0.8 },
    });

    return {
        colors,
        loadingAnimation,
    };
});

const style = styleFactory("loadingRectangle");
export const loadingRectangeClass = useThemeCache((height: string | number, width: string | number = "100%") => {
    const vars = loadingRectangeVariables();
    return style({
        display: "block",
        background: colorOut(vars.colors.bg),
        height: unit(height),
        width: unit(width),
        animationName: vars.loadingAnimation,
        animationDuration: "4s",
        animationIterationCount: "infinite",
    });
});

export const loadingSpacerClass = useThemeCache((height: string | number) => {
    return style({
        display: "block",
        height: unit(height),
        width: percent(100),
    });
});
