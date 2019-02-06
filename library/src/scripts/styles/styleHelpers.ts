/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, ColorHelper } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { FlexWrapProperty } from "csstype";

/*
 * Helper function to get variable with fallback
 */
export const getVar = (haystack: {}, key: string, fallback: string | number) => {
    return haystack[key] ? haystack[key] : fallback;
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
    const core = globalVariables();
    if (weight > 1 || weight < 0) {
        throw new Error("mixAmount must be a value between 0 and 1 inclusively.");
    }

    if (referenceColor.lightness() >= 0.5 && flip) {
        // Lighten color
        return colorToModify.mix(core.elementaryColors.white, 1 - weight);
    } else {
        // Darken color
        return colorToModify.mix(core.elementaryColors.black, 1 - weight);
    }
};

export const mixBgAndFg = weight => {
    const coreVars = globalVariables();
    return coreVars.mainColors.fg.mix(coreVars.mainColors.bg, weight);
};

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

export function debugHelper(componentName: string) {
    return {
        name: (subElementName?: string) => {
            if (subElementName) {
                return { $debugName: `${componentName}-${subElementName}` };
            } else {
                return { $debugName: componentName };
            }
        },
    };
}
