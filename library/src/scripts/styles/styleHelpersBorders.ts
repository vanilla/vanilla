/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, ColorValues } from "@library/styles/styleHelpersColors";
import {
    BorderRadiusProperty,
    BorderStyleProperty,
    BorderTopColorProperty,
    BorderTopStyleProperty,
    BorderTopWidthProperty,
    BorderWidthProperty,
} from "csstype";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { unit } from "@library/styles/styleHelpers";
import { globalVariables, IGlobalBorderStyles } from "@library/styles/globalStyleVars";
import merge from "lodash/merge";
import { ColorHelper } from "csx";
import { getValueIfItExists, setAllBorderRadii } from "@library/forms/borderStylesCalculator";

export interface ISimpleBorderStyle {
    color?: ColorValues | ColorHelper;
    width?: BorderWidthProperty<TLength>;
    style?: BorderStyleProperty;
}

export interface IBordersWithRadius extends ISimpleBorderStyle {
    radius?: radiusValue;
}

type radiusValue = BorderRadiusProperty<TLength> | string;

export type IRadiusValue = IBorderRadiusValue | IRadiusShorthand | IBorderRadiusOutput;

interface IRadiusFlex {
    radius?: IRadiusValue;
}

export interface IRadiusShorthand {
    all?: IBorderRadiusValue;
    top?: IBorderRadiusValue;
    bottom?: IBorderRadiusValue;
    left?: IBorderRadiusValue;
    right?: IBorderRadiusValue;
}

export interface IBorderRadiusOutput {
    topRightRadius?: IBorderRadiusValue;
    topLeftRadius?: IBorderRadiusValue;
    bottomRightRadius?: IBorderRadiusValue;
    bottomLeftRadius?: IBorderRadiusValue;
}

type IRadiusInput = IRadiusShorthand | IBorderRadiusOutput | IRadiusValue;

export type IBorderRadiusValue = BorderRadiusProperty<TLength> | number | string | undefined;

export interface IBorderStyles extends ISimpleBorderStyle, IRadiusFlex {
    all?: ISimpleBorderStyle;
    topBottom?: ISimpleBorderStyle;
    leftRight?: ISimpleBorderStyle;
    top?: ISimpleBorderStyle;
    bottom?: ISimpleBorderStyle;
    left?: ISimpleBorderStyle;
    right?: ISimpleBorderStyle;
}

const typeIsStringOrNumber = (variable: unknown): variable is number | string => {
    if (variable) {
        const type = typeof variable;
        return type === "string" || type === "number";
    } else {
        return false;
    }
};

const setAllRadii = (radius: BorderRadiusProperty<TLength>) => {
    return {
        topRightRadius: radius,
        bottomRightRadius: radius,
        bottomLeftRadius: radius,
        topLeftRadius: radius,
    };
};

/**
 * Main utility function for generation proper border radiuses. Supports numerous shorthand properties.
 *
 * @param radii
 * @param debug
 */
export const standardizeBorderRadius = (radii: IRadiusInput, debug = false): IRadiusValue => {
    if (debug) {
        const doBreak = "here";
        window.console.log("=========================== debugging ====================", radii);
    }

    const output: IBorderRadiusOutput = {};

    if (typeIsStringOrNumber(radii)) {
        const value = unit(radii as number | string);
        merge(output, {
            topRightRadius: value,
            bottomRightRadius: value,
            bottomLeftRadius: value,
            topLeftRadius: value,
        });
    } else {
        const all = getValueIfItExists(radii, "all");
        if (all !== undefined) {
            merge(output, setAllBorderRadii(unit(all)));
        }

        const top = getValueIfItExists(radii, "top");
        if (top !== undefined) {
            const isShorthand = typeIsStringOrNumber(top);
            const value = isShorthand ? unit(top) : top;
            const right = getValueIfItExists(value, "right");
            const left = getValueIfItExists(value, "left");

            if (typeIsStringOrNumber(top)) {
                merge(output, {
                    topRightRadius: value,
                    topLeftRadius: value,
                });
            } else {
                merge(
                    output,
                    right !== undefined ? { topRightRadius: unit(right) } : {},
                    left !== undefined ? { topLeftRadius: unit(left) } : {},
                );
            }
        }

        const bottom = getValueIfItExists(radii, "bottom");
        if (bottom !== undefined) {
            const isShorthand = typeIsStringOrNumber(bottom);
            const value = isShorthand ? unit(bottom) : bottom;
            const right = getValueIfItExists(value, "right");
            const left = getValueIfItExists(value, "left");

            if (typeIsStringOrNumber(bottom)) {
                merge(output, {
                    bottomRightRadius: value,
                    bottomLeftRadius: value,
                });
            } else {
                merge(
                    output,
                    right !== undefined ? { bottomRightRadius: unit(right) } : {},
                    left !== undefined ? { bottomLeftRadius: unit(left) } : {},
                );
            }
        }

        const left = getValueIfItExists(radii, "left");
        if (left !== undefined) {
            const isShorthand = typeIsStringOrNumber(left);
            const value = isShorthand ? unit(left) : left;
            const top = getValueIfItExists(value, "top");
            const bottom = getValueIfItExists(value, "bottom");

            if (typeIsStringOrNumber(left)) {
                merge(output, {
                    topLeftRadius: value,
                    bottomLeftRadius: value,
                });
            } else {
                const topStyles = top !== undefined ? { bottomRightRadius: unit(top) } : {};
                const bottomStyles = bottom !== undefined ? { bottomLeftRadius: unit(bottom) } : {};
                merge(
                    output,
                    !typeIsStringOrNumber(topStyles) ? topStyles : {},
                    !typeIsStringOrNumber(bottomStyles) ? bottomStyles : {},
                );
            }
        }

        const right = getValueIfItExists(radii, "right");
        if (right !== undefined) {
            const isShorthand = typeIsStringOrNumber(right);
            const value = isShorthand ? unit(right) : right;
            const top = getValueIfItExists(value, "top");
            const bottom = getValueIfItExists(value, "bottom");

            if (typeIsStringOrNumber(right)) {
                merge(output, {
                    topLeftRadius: value,
                    bottomLeftRadius: value,
                });
            } else {
                const topStyles = top !== undefined ? { bottomRightRadius: unit(top) } : {};
                const bottomStyles = bottom !== undefined ? { bottomLeftRadius: unit(bottom) } : {};
                merge(
                    output,
                    !typeIsStringOrNumber(topStyles) ? topStyles : {},
                    !typeIsStringOrNumber(bottomStyles) ? bottomStyles : {},
                );
            }
        }

        const topRightRadius = getValueIfItExists(radii, "topRightRadius");
        if (topRightRadius !== undefined) {
            merge(output, {
                topRightRadius: unit(topRightRadius),
            });
        }
        const topLeftRadius = getValueIfItExists(radii, "topLeftRadius");
        if (topLeftRadius !== undefined) {
            merge(output, {
                topLeftRadius: unit(topLeftRadius),
            });
        }
        const bottomRightRadius = getValueIfItExists(radii, "bottomRightRadius");
        if (bottomRightRadius !== undefined) {
            merge(output, {
                bottomRightRadius: unit(bottomRightRadius),
            });
        }
        const bottomLeftRadius = getValueIfItExists(radii, "bottomLeftRadius");
        if (bottomLeftRadius !== undefined) {
            merge(output, {
                bottomLeftRadius: unit(bottomLeftRadius),
            });
        }
    }

    return output;
};

export const borderRadii = (radii: IRadiusValue, fallbackRadii = globalVariables().border.radius) => {
    const output: IBorderRadiusOutput = {};

    if (typeIsStringOrNumber(fallbackRadii)) {
        merge(output, setAllRadii(unit(fallbackRadii as any) as any));
    } else {
        merge(output, typeIsStringOrNumber(fallbackRadii) ? fallbackRadii : fallbackRadii);
    }

    const hasRadiusShorthand = typeIsStringOrNumber(radii);
    const hasRadiusShorthandFallback = typeIsStringOrNumber(fallbackRadii);

    // Make sure we have a value before overwriting.
    if (hasRadiusShorthand) {
        merge(output, setAllRadii(unit(radii as any) as any));
    } else if (hasRadiusShorthandFallback) {
        merge(output, setAllRadii(unit(fallbackRadii as any) as any));
    } else {
        // our fallback must be an object.
        merge(output, standardizeBorderRadius(fallbackRadii as any));
    }
    merge(output, standardizeBorderRadius(radii as any));
    return output as NestedCSSProperties;
};

const setAllBorders = (
    color: ColorValues,
    width: BorderWidthProperty<TLength>,
    style: BorderStyleProperty,
    radius?: IBorderRadiusOutput,
) => {
    return {
        borderTopColor: color,
        borderRightColor: color,
        borderBottomColor: color,
        borderLeftColor: color,
        borderTopWidth: unit(width),
        borderRightWidth: unit(width),
        borderBottomWidth: unit(width),
        borderLeftWidth: unit(width),
        borderTopStyle: style,
        borderRightStyle: style,
        borderBottomStyle: style,
        borderLeftStyle: style,
        ...radius,
    };
};

const singleBorderStyle = (
    borderStyles: ISimpleBorderStyle,
    fallbackVariables: IGlobalBorderStyles = globalVariables().border,
) => {
    if (!borderStyles) {
        return;
    }
    const { color, width, style } = borderStyles;
    const output: ISimpleBorderStyle = {};
    output.color = colorOut(borderStyles.color ? borderStyles.color : color) as ColorValues;
    output.width = unit(borderStyles.width ? borderStyles.width : width) as BorderWidthProperty<TLength>;
    output.style = borderStyles.style ? borderStyles.style : (style as BorderStyleProperty);

    if (Object.keys(output).length > 0) {
        return output;
    } else {
        return;
    }
};

export const borders = (
    detailedStyles?: IBorderStyles | ISimpleBorderStyle | undefined,
    fallbackVariables: IGlobalBorderStyles = globalVariables().border,
    debug = false,
): NestedCSSProperties => {
    const output: NestedCSSProperties = {};
    const style = getValueIfItExists(detailedStyles, "style", fallbackVariables.style);
    const color = getValueIfItExists(detailedStyles, "color", fallbackVariables.color);
    const width = getValueIfItExists(detailedStyles, "width", fallbackVariables.width);
    const radius = getValueIfItExists(detailedStyles, "radius", fallbackVariables.radius);

    debug && window.console.log("radius: ", radius);

    if (style !== undefined || color !== undefined || width !== undefined || radius !== undefined) {
        merge(output, setAllBorders(color, width, style, typeIsStringOrNumber(radius) ? radius : undefined));
    }

    // Now we are sure to not have simple styles anymore.
    detailedStyles = detailedStyles as IBorderStyles;
    if (detailedStyles) {
        const top = getValueIfItExists(detailedStyles, "top");
        if (top !== undefined) {
            const topStyles = singleBorderStyle(top, fallbackVariables);
            if (topStyles !== undefined) {
                output.borderTopWidth = getValueIfItExists(topStyles, "width", fallbackVariables.width);
                output.borderTopStyle = getValueIfItExists(topStyles, "style", fallbackVariables.style);
                output.borderTopColor = getValueIfItExists(topStyles, "color", fallbackVariables.color);
            }
        }

        const right = getValueIfItExists(detailedStyles, "right");
        if (right !== undefined) {
            const rightStyles = singleBorderStyle(right, fallbackVariables);
            if (rightStyles !== undefined) {
                output.borderRightWidth = getValueIfItExists(rightStyles, "width", fallbackVariables.width);
                output.borderRightStyle = getValueIfItExists(rightStyles, "style", fallbackVariables.style);
                output.borderRightColor = getValueIfItExists(rightStyles, "color", fallbackVariables.color);
            }
        }

        const bottom = getValueIfItExists(detailedStyles, "bottom");
        if (bottom !== undefined) {
            const bottomStyles = singleBorderStyle(bottom, fallbackVariables);
            if (bottomStyles !== undefined) {
                output.borderBottomWidth = getValueIfItExists(bottomStyles, "width", fallbackVariables.width);
                output.borderBottomStyle = getValueIfItExists(bottomStyles, "style", fallbackVariables.style);
                output.borderBottomColor = getValueIfItExists(bottomStyles, "color", fallbackVariables.color);
            }
        }

        const left = getValueIfItExists(detailedStyles, "left");
        if (left !== undefined) {
            const leftStyles = singleBorderStyle(left, fallbackVariables);
            if (leftStyles !== undefined) {
                output.borderLeftWidth = getValueIfItExists(leftStyles, "width", fallbackVariables.width);
                output.borderLeftStyle = getValueIfItExists(leftStyles, "style", fallbackVariables.style);
                output.borderLeftColor = getValueIfItExists(leftStyles, "color", fallbackVariables.color);
            }
        }

        const detailedRadius = getValueIfItExists(detailedStyles, "radius");
        debug && window.console.log(">>>>> detailedRadius before radius: ", detailedRadius);

        debug &&
            console.log(
                ">>>> standardizeBorderRadius(detailedRadius)): ",
                standardizeBorderRadius(detailedRadius, true),
            );
        merge(output, standardizeBorderRadius(detailedRadius));

        debug && window.console.log("detailedStyles: ", detailedStyles);
        debug && window.console.log("detailedRadius: ", detailedRadius);
    }

    if (debug) {
        console.log("output: ", output);
    }

    return output;
};

export const singleBorder = (styles?: ISimpleBorderStyle) => {
    const vars = globalVariables();
    const borderStyles = styles !== undefined ? styles : {};
    return `${borderStyles.style ? borderStyles.style : vars.border.style} ${
        borderStyles.color ? colorOut(borderStyles.color) : colorOut(vars.border.color)
    } ${borderStyles.width ? unit(borderStyles.width) : unit(vars.border.width)}` as any;
};
