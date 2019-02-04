/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import get from "lodash/get";
import { color, ColorHelper } from "csx";
import { globalVariables } from "@library/styles/globals";

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
    percentage: number,
    flip: boolean = false,
) => {
    const core = globalVariables();
    if (percentage > 100 || percentage < 0) {
        throw new Error("mixAmount must be a value between 0 and 100 inclusively.");
    }
    const black = core.elementaryColors.black;
    const white = core.elementaryColors.white;
    const mixAmount = percentage / 10;

    if (colorToModify.lightness() >= 0.5 && !flip) {
        // Lighten color
        return colorToModify.mix(black, mixAmount);
    } else {
        // Darken color
        return colorToModify.mix(white, mixAmount);
    }
};
