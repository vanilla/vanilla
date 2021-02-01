/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { BorderRadiusProperty } from "csstype";
import { TLength } from "@library/styles/styleShim";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import merge from "lodash/merge";
import { getValueIfItExists } from "@library/forms/borderStylesCalculator";
import {
    IBorderRadiusOptions,
    IBorderRadiusOutput,
    IRadiusShorthand,
    IRadiusValue,
    ISimpleBorderStyle,
} from "@library/styles/cssUtilsTypes";

export enum BorderType {
    BORDER = "border",
    SEPARATOR = "separator",
    NONE = "none",
    SHADOW = "shadow",
    SHADOW_AS_BORDER = "shadow_as_border", // Note that is applied on a different element
}

export type radiusValue = BorderRadiusProperty<TLength> | string;

export interface IMixedRadiusDeclaration extends IRadiusShorthand, IBorderRadiusOutput {}

type IRadiusInput = IMixedRadiusDeclaration | IRadiusValue;

const typeIsStringOrNumber = (variable: unknown): variable is number | string => {
    if (variable !== null) {
        const type = typeof variable;
        return type === "string" || type === "number";
    } else {
        return false;
    }
};

const setAllRadii = (radius: BorderRadiusProperty<TLength>, options?: IBorderRadiusOptions) => {
    return {
        borderTopRightRadius: styleUnit(radius, options),
        borderBottomRightRadius: styleUnit(radius, options),
        borderBottomLeftRadius: styleUnit(radius, options),
        borderTopLeftRadius: styleUnit(radius, options),
    };
};

export const EMPTY_BORDER_RADIUS = {
    borderTopRightRadius: undefined,
    borderBottomRightRadius: undefined,
    borderBottomLeftRadius: undefined,
    borderTopLeftRadius: undefined,
};

/**
 * Main utility function for generation proper border radiuses. Supports numerous shorthand properties.
 *
 * @param radii
 * @param options
 */
export const standardizeBorderRadius = (radii: IRadiusInput, options?: IBorderRadiusOptions): IBorderRadiusOutput => {
    const output: IBorderRadiusOutput = {};
    const { debug } = options || {};

    if (typeof radii === "object" && Object.keys(radii).length === 0) {
        return output;
    }

    if (typeIsStringOrNumber(radii)) {
        // direct value
        const value = styleUnit(radii as number | string);
        return {
            borderTopRightRadius: styleUnit(value, options),
            borderBottomRightRadius: styleUnit(value, options),
            borderBottomLeftRadius: styleUnit(value, options),
            borderTopLeftRadius: styleUnit(value, options),
        };
    }

    // Otherwise we need to check all of the values.
    const all = getValueIfItExists(radii, "all", getValueIfItExists(radii, "radius"));
    const top = getValueIfItExists(radii, "top");
    const bottom = getValueIfItExists(radii, "bottom");
    const left = getValueIfItExists(radii, "left");
    const right = getValueIfItExists(radii, "right");

    if (typeIsStringOrNumber(all)) {
        merge(output, {
            borderTopRightRadius: styleUnit(all, options),
            borderBottomRightRadius: styleUnit(all, options),
            borderBottomLeftRadius: styleUnit(all, options),
            borderTopLeftRadius: styleUnit(all, options),
        });
    }

    if (top !== undefined) {
        const isShorthand = typeIsStringOrNumber(top);

        if (isShorthand) {
            const value = !isShorthand ? styleUnit(top, options) : top;
            merge(output, {
                borderTopRightRadius: styleUnit(value, options),
                borderTopLeftRadius: styleUnit(value, options),
            });
        } else {
            merge(
                output,
                right !== undefined ? { borderTopRightRadius: styleUnit(right, options) } : {},
                left !== undefined ? { borderTopLeftRadius: styleUnit(left, options) } : {},
            );
        }
    }

    if (bottom !== undefined) {
        const isShorthand = typeIsStringOrNumber(bottom);

        if (isShorthand) {
            const value = !isShorthand ? styleUnit(bottom, options) : bottom;
            merge(output, {
                borderBottomRightRadius: styleUnit(value, options),
                borderBottomLeftRadius: styleUnit(value, options),
            });
        } else {
            merge(
                output,
                right !== undefined ? { borderBottomRightRadius: styleUnit(right, options) } : {},
                left !== undefined ? { borderBottomLeftRadius: styleUnit(left, options) } : {},
            );
        }
    }

    if (left !== undefined) {
        const isShorthand = typeIsStringOrNumber(left);

        if (isShorthand) {
            const value = !isShorthand ? styleUnit(left, options) : left;
            merge(output, {
                borderTopLeftRadius: styleUnit(value, options),
                borderBottomLeftRadius: styleUnit(value, options),
            });
        } else {
            const topStyles = top !== undefined ? { borderTopLeftRadius: styleUnit(top, options) } : {};
            const bottomStyles = bottom !== undefined ? { borderBottomLeftRadius: styleUnit(bottom, options) } : {};
            merge(
                output,
                !typeIsStringOrNumber(topStyles) ? topStyles : {},
                !typeIsStringOrNumber(bottomStyles) ? bottomStyles : {},
            );
        }
    }
    if (right !== undefined) {
        const isShorthand = typeIsStringOrNumber(right);

        if (isShorthand) {
            const value = !isShorthand ? styleUnit(right, options) : right;
            merge(output, {
                borderTopRightRadius: styleUnit(value, options),
                borderBottomRightRadius: styleUnit(value, options),
            });
        } else {
            const topStyles = top !== undefined ? { borderTopRightRadius: styleUnit(top, options) } : {};
            const bottomStyles = bottom !== undefined ? { borderBottomRightRadius: styleUnit(bottom, options) } : {};
            merge(
                output,
                !typeIsStringOrNumber(topStyles) ? topStyles : {},
                !typeIsStringOrNumber(bottomStyles) ? bottomStyles : {},
            );
        }
    }

    const borderTopRightRadius = getValueIfItExists(radii, "borderTopRightRadius");
    if (borderTopRightRadius !== undefined) {
        merge(output, {
            borderTopRightRadius: styleUnit(borderTopRightRadius, options),
        });
    }
    const borderTopLeftRadius = getValueIfItExists(radii, "borderTopLeftRadius");
    if (borderTopLeftRadius !== undefined) {
        merge(output, {
            borderTopLeftRadius: styleUnit(borderTopLeftRadius, options),
        });
    }
    const borderBottomRightRadius = getValueIfItExists(radii, "borderBottomRightRadius");
    if (borderBottomRightRadius !== undefined) {
        merge(output, {
            borderBottomRightRadius: styleUnit(borderBottomRightRadius, options),
        });
    }
    const borderBottomLeftRadius = getValueIfItExists(radii, "borderBottomLeftRadius");
    if (borderBottomLeftRadius !== undefined) {
        merge(output, {
            borderBottomLeftRadius: styleUnit(borderBottomLeftRadius, options),
        });
    }

    return output;
};

export const borderRadii = (radii: IRadiusValue, options?: IBorderRadiusOptions) => {
    const { fallbackRadii = globalVariables().border.radius, isImportant = false, debug = false } = options || {};

    const output: IBorderRadiusOutput = {};

    if (typeIsStringOrNumber(fallbackRadii)) {
        merge(output, setAllRadii(fallbackRadii, { isImportant }));
    } else {
        merge(output, typeIsStringOrNumber(fallbackRadii) ? fallbackRadii : fallbackRadii);
    }

    const hasRadiusShorthand = typeIsStringOrNumber(radii);
    const hasRadiusShorthandFallback = typeIsStringOrNumber(fallbackRadii);

    // Make sure we have a value before overwriting.
    if (hasRadiusShorthand) {
        merge(output, setAllRadii(radii as any, { isImportant }));
    } else if (hasRadiusShorthandFallback) {
        merge(output, setAllRadii(fallbackRadii as any, { isImportant }));
    } else {
        // our fallback must be an object.
        merge(output, standardizeBorderRadius(fallbackRadii as any, { isImportant }));
    }
    merge(output, standardizeBorderRadius(radii as any, { isImportant }));
    return output;
};

export const singleBorder = (styles?: ISimpleBorderStyle) => {
    const vars = globalVariables();
    const borderStyles = styles !== undefined ? styles : {};
    return `${borderStyles.style ? borderStyles.style : vars.border.style} ${
        borderStyles.color ? ColorsUtils.colorOut(borderStyles.color) : ColorsUtils.colorOut(vars.border.color)
    } ${borderStyles.width ? styleUnit(borderStyles.width) : styleUnit(vars.border.width)}` as any;
};
