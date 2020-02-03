/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { keyframes } from "typestyle";
import { ColorHelper, deg, percent, quote } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ContentProperty, DisplayProperty, PositionProperty } from "csstype";
import { debugHelper, defaultTransition, unit } from "@library/styles/styleHelpers";
import { NestedCSSProperties } from "typestyle/lib/types";

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

export function spinnerLoaderAnimationProperties(): NestedCSSProperties {
    return {
        ...defaultTransition("opacity"),
        animationName: spinnerLoaderAnimation,
        animationDuration: DEFAULT_SPEED,
        animationIterationCount: "infinite",
        animationTimingFunction: "ease-in-out",
    };
}

export const spinnerLoader = (props: ISpinnerProps) => {
    const debug = debugHelper("spinnerLoader");
    const globalVars = globalVariables();
    const spinnerVars = {
        color: props.color || globalVars.mainColors.primary,
        size: props.size || 18,
        thickness: props.thickness || 3,
        ...props,
    };
    return {
        ...debug.name("spinner"),
        position: "relative" as PositionProperty,
        content: quote("") as ContentProperty,
        display: "block" as DisplayProperty,
        width: unit(spinnerVars.size),
        height: unit(spinnerVars.size),
        borderRadius: percent(50),
        borderTop: `${unit(spinnerVars.thickness)} solid ${spinnerVars.color.toString()}`,
        borderRight: `${unit(spinnerVars.thickness)} solid ${spinnerVars.color.fade(0.3).toString()}`,
        borderBottom: `${unit(spinnerVars.thickness)} solid ${spinnerVars.color.fade(0.3).toString()}`,
        borderLeft: `${unit(spinnerVars.thickness)} solid ${spinnerVars.color.fade(0.3).toString()}`,
        transform: "translateZ(0)",
        ...spinnerLoaderAnimationProperties(),
    };
};
