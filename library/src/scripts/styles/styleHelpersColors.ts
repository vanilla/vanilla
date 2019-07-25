/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorHelper, important } from "csx";

export type ColorValues = ColorHelper | "transparent" | undefined;

export const colorOut = (colorValue: ColorValues | string, makeImportant = false) => {
    if (!colorValue) {
        return undefined;
    } else {
        const output = typeof colorValue === "string" ? colorValue : colorValue.toString();
        return makeImportant ? important(output) : output;
    }
};

/*
 * Check if it's a light color or dark color based on lightness
 * @param color - The color we're checking
 */
export const isLightColor = (color: ColorHelper) => {
    return color.lightness() >= 0.5;
};

/*
 * Color modification based on colors lightness.
 * @param color - The color we're checking and modifying
 * @param weight - The amount you want to mix the two colors (value from 0 to 1)
 * @param flip - By default we darken light colours and lighten dark colors, but if you want to get the opposite result, use this param
 * Note, however, that we do not check if you've reached a maximum. Example: If you want to darken pure black, you get back pure black.
 */
export const modifyColorBasedOnLightness = (color: ColorHelper, weight: number, flip: boolean = false) => {
    let output;
    if (weight > 1 || weight < 0) {
        throw new Error("mixAmount must be a value between 0 and 1 inclusively.");
    }
    const isLight = isLightColor(color);
    if ((isLight && !flip) || (!isLight && flip)) {
        output = color.darken(weight) as ColorHelper;
    } else {
        output = color.lighten(weight) as ColorHelper;
    }
    return output;
};

/*
 * Color modification based on colors lightness. This function will make darks darker and lights lighter. Note, however, that if we pass
 * pure white or pure black, the modification goes in the opposite direction, to maintain contrast if "flipIfMax" is true.
 * This function is meant for smart defaults and works best with smaller weights. Not really meant for theming. There is a curve
 * to the weight compensate for the fact that subtle weights works well for light colors, but not for dark ones (roughly 10 times
 * less for pure black). This curve starts with colors .4 lightness or less and is accentuated more as we get closer to pure black.
 * @param color - The color we're checking and modifying
 * @param weight - The amount you want to mix the two colors (value from 0 to 1)
 * @param flipIfMax - Modify in the opposite direction if we're darker than black or whiter than white.
 */
export const emphasizeLightness = (color: ColorHelper, weight: number, flipIfMax: boolean = true) => {
    const colorLightness = color.lightness();
    let weightOffset = 1;
    if (colorLightness < 0.4) {
        weightOffset = Math.abs(colorLightness - 0.5) * 20;
    }

    const weightCurved = weight * weightOffset;
    const colorDarker = color.darken(weightCurved) as ColorHelper;
    const colorLighter = color.lighten(weightCurved) as ColorHelper;

    if (isLightColor(color)) {
        if (colorLightness + weightCurved > 1 && flipIfMax) {
            return colorDarker;
        } else {
            return colorLighter;
        }
    } else {
        if (colorLightness - weightCurved > 0 && flipIfMax) {
            return colorDarker;
        } else {
            return colorLighter;
        }
    }
};

/*
 * Color modification based on saturation.
 * @param referenceColor - The reference colour to determine if we're in a dark or light context.
 * @param colorToModify - The color you wish to modify
 * @param percentage - The amount you want to mix the two colors
 * @param flip - By default we darken light colours and lighten darks, but if you want to get the opposite result, use this param
 */
export const modifyColorSaturationBasedOnLightness = (color: ColorHelper, weight: number, flip: boolean = false) => {
    if (weight > 1 || weight < 0) {
        throw new Error("mixAmount must be a value between 0 and 1 inclusively.");
    }

    const isSaturated = color.lightness() >= 0.5;

    if ((isSaturated && !flip) || (!isSaturated && flip)) {
        // Desaturate
        return color.desaturate(weight) as ColorHelper;
    } else {
        // Saturate
        return color.saturate(weight) as ColorHelper;
    }
};
