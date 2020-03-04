/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, ColorHelper, important } from "csx";
import { logError } from "@vanilla/utils/src/debugUtils";

export type ColorValues = ColorHelper | undefined;

export const colorOut = (colorValue: ColorValues | string, makeImportant = false) => {
    if (!colorValue) {
        return undefined;
    } else {
        if (
            colorValue
                .toString()
                .trim()
                .startsWith("linear-gradient(")
        ) {
            return colorValue.toString();
        } else {
            const output = typeof colorValue === "string" ? color(colorValue) : colorValue;
            return makeImportant ? important(output.toString()) : output.toString();
        }
    }
};

export const ensureColorHelper = (colorValue: string | ColorHelper) => {
    return typeof colorValue === "string" ? color(colorValue) : colorValue;
};

export const importantColorOut = (colorValue: ColorValues | string) => {
    return colorOut(colorValue, true);
};

/*
 * Check if it's a light color or dark color based on lightness
 * @param color - The color we're checking
 */
export const isLightColor = (color: ColorHelper) => {
    return color.lightness() >= 0.4;
};

/*
 * Maintain good contrast by flipping ratios when we're in a dark theme
 * @param weight - The weight for the color mix
 * @param bgColor - Check background color to determine if we're in a dark theme
 */
export const getRatioBasedOnDarkness = (weight: number, bgColor: ColorHelper) => {
    if (weight > 1) {
        logError("The weight cannot be greater than 1.");
        weight = 1;
    } else if (weight < 0) {
        logError("The weight cannot be smaller than 0.");
        weight = 0;
    }

    if (isLightColor(bgColor)) {
        return weight;
    } else {
        return 1 - weight;
    }
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
export const offsetLightness = (
    colorValue: ColorHelper,
    weight: number,
    flipIfMax: boolean = true,
    debug: boolean = false,
) => {
    const colorLightness = colorValue.lightness();
    let weightOffset = 1;
    if (!isLightColor(colorValue)) {
        weightOffset = Math.abs(colorLightness - 0.45) * 20; // Seems darker colors need more contrast than light colors to get the same impact
    }

    const weightCurved = weight * weightOffset;
    const colorDarker = colorValue.darken(weightCurved) as ColorHelper;
    const colorLighter = colorValue.lighten(weightCurved) as ColorHelper;

    if (isLightColor(colorValue)) {
        if (colorLightness + weightCurved > 1 && flipIfMax) {
            return colorLighter;
        } else {
            return colorDarker;
        }
    } else {
        if (colorLightness - weightCurved > 0 && !flipIfMax) {
            return colorLighter;
        } else {
            return colorDarker;
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
