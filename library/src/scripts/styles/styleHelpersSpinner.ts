/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { keyframes } from "@library/styles/styleShim";
import { ColorHelper, deg, percent, quote } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ContentProperty, DisplayProperty, PositionProperty } from "csstype";
import { defaultTransition } from "@library/styles/styleHelpersAnimation";
import { styleUnit } from "@library/styles/styleUnit";
import { CSSObject } from "@emotion/css";

const spinnerOffset = 73;
const spinnerLoaderAnimation = keyframes({
    "0%": { transform: `rotate(${deg(spinnerOffset)})` },
    "100%": { transform: `rotate(${deg(360 + spinnerOffset)})` },
});

export interface ISpinnerProps {
    color?: ColorHelper;
    dimensions?: string | number;
    thickness?: string | number;
    size?: string | number;
    speed?: string;
}

const DEFAULT_SPEED = "0.7s";

export function spinnerLoaderAnimationProperties(): CSSObject {
    return {
        ...defaultTransition("opacity"),
        animationName: spinnerLoaderAnimation,
        animationDuration: DEFAULT_SPEED,
        animationIterationCount: "infinite",
        animationTimingFunction: "ease-in-out",
    };
}

export const spinnerLoader = (props: ISpinnerProps) => {
    const globalVars = globalVariables();
    const spinnerVars = {
        color: props.color || globalVars.mainColors.primary,
        size: props.size || 18,
        thickness: props.thickness || 3,
        ...props,
    };
    return {
        position: "relative" as PositionProperty,
        content: quote("") as ContentProperty,
        display: "block" as DisplayProperty,
        width: styleUnit(spinnerVars.size),
        height: styleUnit(spinnerVars.size),
        borderRadius: percent(50),
        borderTop: `${styleUnit(spinnerVars.thickness)} solid ${spinnerVars.color.toString()}`,
        borderRight: `${styleUnit(spinnerVars.thickness)} solid ${spinnerVars.color.fade(0.3).toString()}`,
        borderBottom: `${styleUnit(spinnerVars.thickness)} solid ${spinnerVars.color.fade(0.3).toString()}`,
        borderLeft: `${styleUnit(spinnerVars.thickness)} solid ${spinnerVars.color.fade(0.3).toString()}`,
        transform: "translateZ(0)",
        ...spinnerLoaderAnimationProperties(),
    };
};
