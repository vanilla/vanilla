/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */
import { logDebugConditionnal, logError } from "@vanilla/utils";
import { color, ColorHelper, important } from "csx";
import { stringIsLinearGradient } from "@library/styles/styleUtils";
export class ColorsUtils {
    constructor() {
        throw new Error("Not to be instantiated");
    }

    /**
     * Check if it's a light color or dark color.
     * Calculation is based off of this formula. http://alienryderflex.com/hsp.html
     * @param color - The color we're checking
     */
    static isLightColor = (referenceColor: ColorHelper | string): boolean => {
        const color = ColorsUtils.ensureColorHelper(referenceColor);
        const r = color.red();
        const b = color.blue();
        const g = color.green();
        const result = Math.sqrt(0.299 * r * r + 0.587 * g * g + 0.114 * b * b);
        // Values in the range of 128-150 seem to give acceptable results.
        return result >= 150;
    };

    static ensureColorHelper = (colorValue: string | ColorHelper): ColorHelper => {
        return typeof colorValue === "string" ? color(colorValue) : colorValue;
    };

    static colorOut(
        colorValue?: ColorHelper | string,
        options?: {
            debug?: boolean;
            makeImportant?: boolean;
        },
    ): string | undefined {
        const { debug = false, makeImportant } = options || {};

        logDebugConditionnal(debug, "ColorsUtils.colorOut - colorValue: ", colorValue);

        if (!colorValue) {
            return undefined;
        }

        if (stringIsLinearGradient(colorValue)) {
            logDebugConditionnal(debug, "ColorsUtils.colorOut - linear gradient detected - colorValue: ", colorValue);
            // @ts-ignore
            return colorValue.toString();
        } else {
            const output = typeof colorValue === "string" ? color(colorValue) : colorValue;
            // @ts-ignore
            return makeImportant ? important(output.toString()) : output.toString();
        }
    }

    /*
     * Maintain good contrast by flipping ratios when we're in a dark theme
     * @param weight - The weight for the color mix
     * @param bgColor - Check background color to determine if we're in a dark theme
     */
    static getRatioBasedOnDarkness = (weight: number, c: ColorHelper | string): number => {
        if (weight > 1) {
            logError("The weight cannot be greater than 1.");
            weight = 1;
        } else if (weight < 0) {
            logError("The weight cannot be smaller than 0.");
            weight = 0;
        }

        if (ColorsUtils.isLightColor(ColorsUtils.ensureColorHelper(c))) {
            return weight;
        } else {
            return 1 - weight;
        }
    };

    /*
     * Color modification based on colors lightness.
     * @param color - The color we're checking and modifying
     * @param weight - The amount you want to mix the two colors (value from 0 to 1)
     * @param inverse - By default we darken light colours and lighten dark colors, but if you want to get the inverse result, use this param
     * @param flipWeightForDark - For dark themes, flip ratio. Example, a weight of ".2" for a light theme would be ".8" for a dark theme.
     * @param debug - Turn on debug logs
     * Note, however, that we do not check if you've reached a maximum. Example: If you want to darken pure black, you get back pure black.
     */
    static modifyColorBasedOnLightness = (props: {
        color: ColorHelper | string;
        weight: number;
        inverse?: boolean;
        flipWeightForDark?: boolean;
        debug?: boolean;
    }): ColorHelper => {
        const { color: referenceColor, inverse = false, flipWeightForDark = false, debug = false } = props;
        const colorObj = ColorsUtils.ensureColorHelper(referenceColor);
        let weight = props.weight;
        logDebugConditionnal(debug, "=== start modifyColorBasedOnLightness debug ===");
        logDebugConditionnal(debug, "initial color: ", color);
        logDebugConditionnal(debug, "initial weight: ", weight);
        logDebugConditionnal(debug, "inverse: ", inverse);
        logDebugConditionnal(debug, "flipWeightForDark: ", flipWeightForDark);
        logDebugConditionnal(debug, ": ");

        let output;
        if (weight > 1 || weight < 0) {
            throw new Error("mixAmount must be a value between 0 and 1 inclusively.");
        }

        const isLight = ColorsUtils.isLightColor(colorObj);
        logDebugConditionnal(debug, "is light: ", isLight);
        if (flipWeightForDark && !isLight) {
            weight = 1 - weight;
        }
        logDebugConditionnal(debug, "final weight: ", weight);
        if ((isLight && !inverse) || (!isLight && inverse)) {
            output = colorObj.darken(weight);
        } else {
            output = colorObj.lighten(weight);
        }
        logDebugConditionnal(debug, "output: ", output);
        logDebugConditionnal(debug, "output: ");
        logDebugConditionnal(debug, "=== end modifyColorBasedOnLightness debug ===");
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
    static offsetLightness = (
        referenceColorValue: ColorHelper | string,
        weight: number,
        flipIfMax: boolean = true,
        debug: boolean = false,
    ): ColorHelper => {
        const colorValue = ColorsUtils.ensureColorHelper(referenceColorValue);
        const colorLightness = colorValue.lightness();
        let weightOffset = 1;
        if (!ColorsUtils.isLightColor(colorValue)) {
            weightOffset = Math.abs(colorLightness - 0.45) * 20; // Seems darker colors need more contrast than light colors to get the same impact
        }

        const weightCurved = weight * weightOffset;
        const colorDarker = colorValue.darken(weightCurved) as ColorHelper;
        const colorLighter = colorValue.lighten(weightCurved) as ColorHelper;

        if (ColorsUtils.isLightColor(colorValue)) {
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
    static modifyColorSaturationBasedOnLightness = (
        c: ColorHelper | string,
        weight: number,
        flip: boolean = false,
    ): ColorHelper => {
        const colorObj = ColorsUtils.ensureColorHelper(c);
        if (weight > 1 || weight < 0) {
            throw new Error("mixAmount must be a value between 0 and 1 inclusively.");
        }

        const isSaturated = colorObj.lightness() >= 0.5;

        if ((isSaturated && !flip) || (!isSaturated && flip)) {
            // Desaturate
            return colorObj.desaturate(weight);
        } else {
            // Saturate
            return colorObj.saturate(weight);
        }
    };
}
