/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { rgba, percent, linearGradient } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { keyframes } from "typestyle";

export const loadingRectangleVariables = useThemeCache(() => {
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
export const loadingRectangleClass = useThemeCache((height: string | number, width: string | number = "100%") => {
    const vars = loadingRectangleVariables();
    return style({
        display: "block",
        borderRadius: 2,
        background: colorOut(vars.colors.bg),
        height: unit(height),
        width: unit(width),
        animationName: vars.loadingAnimation,
        animationDuration: "4s",
        animationIterationCount: "infinite",
        maxWidth: percent(100),
    });
});

export const loadingSpacerClass = useThemeCache((height: string | number) => {
    return style({
        display: "block",
        height: unit(height),
        width: percent(100),
    });
});

export const loadingCircleClass = useThemeCache((height: string | number) => {
    const vars = loadingRectangleVariables();
    return style({
        height: unit(50),
        width: unit(50),
        background: colorOut(vars.colors.bg),
        margin: 20,
        borderRadius: 50,
    });
});
