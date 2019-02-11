/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorHelper, important, percent, px, quote, viewHeight, viewWidth, color } from "csx";
import { BackgroundImageProperty, FlexWrapProperty } from "csstype";
import { style } from "typestyle";

export function flexHelper() {
    const middle = (wrap = false) => {
        return {
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            flexWrap: wrap ? "wrap" : ("nowrap" as FlexWrapProperty),
        };
    };

    const middleLeft = (wrap = false) => {
        return {
            display: "flex",
            alignItems: "center",
            justifyContent: "flex-start",
            flexWrap: wrap ? "wrap" : ("nowrap" as FlexWrapProperty),
        };
    };

    return { middle, middleLeft };
}

export function srOnly() {
    return {
        position: important("absolute"),
        display: important("block"),
        width: important(px(1).toString()),
        height: important(px(1).toString()),
        padding: important(px(0).toString()),
        margin: important(px(-1).toString()),
        overflow: important("hidden"),
        clip: important(`rect(0, 0, 0, 0)`),
        border: important(px(0).toString()),
    };
}

export function fakeBackgroundFixed() {
    return {
        content: quote(""),
        display: "block",
        position: "fixed",
        top: px(0),
        left: px(0),
        width: viewWidth(100),
        height: viewHeight(100),
    };
}

export function fullSizeOfParent() {
    return {
        display: "block",
        position: "absolute",
        top: px(0),
        left: px(0),
        width: percent(100),
        height: percent(100),
    };
}

export function centeredBackgroundProps() {
    return {
        backgroundPosition: `50% 50%`,
        backgroundRepeat: "no-repeat",
    };
}

export function centeredBackground() {
    return style(centeredBackgroundProps());
}

export function backgroundCover(backgroundImage: BackgroundImageProperty) {
    return style({
        ...centeredBackgroundProps(),
        backgroundSize: "cover",
        backgroundImage: backgroundImage.toString(),
    });
}

/*
 * Helper to generate human readable classes generated from TypeStyle
 * @param componentName - The component's name.
 */
export const debugHelper = (componentName: string) => {
    return {
        name: (subElementName?: string) => {
            if (subElementName) {
                return { $debugName: `${componentName}-${subElementName}` };
            } else {
                return { $debugName: componentName };
            }
        },
    };
};

/*
 * Color modification based on colors lightness.
 * @param referenceColor - The reference colour to determine if we're in a dark or light context.
 * @param colorToModify - The color you wish to modify
 * @param percentage - The amount you want to mix the two colors
 * @param flip - By default we darken light colours and lighten darks, but if you want to get the opposite result, use this param
 */
export const getColorDependantOnLightness = (
    referenceColor: ColorHelper,
    colorToModify: ColorHelper,
    weight: number,
    flip: boolean = false,
) => {
    if (weight > 1 || weight < 0) {
        throw new Error("mixAmount must be a value between 0 and 1 inclusively.");
    }

    if (referenceColor.lightness() >= 0.5 && !flip) {
        // Lighten color
        return colorToModify.mix(color("#000"), 1 - weight);
    } else {
        // Darken color
        return colorToModify.mix(color("#fff"), 1 - weight);
    }
};

/*
 * Helper to overwrite styles
 * @param theme - The theme overwrites.
 * @param componentName - The name of the component to overwrite
 */
export const componentThemeVariables = (theme: any | undefined, componentName: string) => {
    // const themeVars = get(theme, componentName, {});
    const themeVars = (theme && theme[componentName]) || {};

    const subComponentStyles = (subElementName: string) => {
        return (themeVars && themeVars[subElementName]) || {};
        // return get(themeVars, subElementName, {});
    };

    return {
        subComponentStyles,
    };
};

export const inheritHeightClass = () => {
    return style({
        display: "flex",
        flexDirection: "column",
        flexGrow: 1,
    });
};
