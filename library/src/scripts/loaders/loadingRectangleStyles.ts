/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { rgba, percent, linearGradient } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { keyframes } from "@emotion/css";

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
export const loadingRectangleClass = useThemeCache(
    (height?: string | number, width: string | number = "100%", inline?: boolean) => {
        const vars = loadingRectangleVariables();
        return style({
            display: inline ? "inline-block" : "block",
            borderRadius: 2,
            background: ColorsUtils.colorOut(vars.colors.bg),
            height: height ? styleUnit(height) : "1em",
            width: styleUnit(width),
            animationName: vars.loadingAnimation,
            animationDuration: "4s",
            animationIterationCount: "infinite",
            maxWidth: percent(100),
        });
    },
);

export const loadingSpacerClass = useThemeCache((height?: string | number) => {
    return style({
        display: "block",
        height: height ? styleUnit(height) : "1em",
        width: percent(100),
    });
});

export const loadingCircleClass = useThemeCache((_height?: string | number, inline?: boolean) => {
    const vars = loadingRectangleVariables();
    const height = styleUnit(_height ?? 50);
    return style({
        display: inline ? "inline-block" : "block",
        height,
        width: height,
        borderRadius: height,
        background: ColorsUtils.colorOut(vars.colors.bg),
    });
});
