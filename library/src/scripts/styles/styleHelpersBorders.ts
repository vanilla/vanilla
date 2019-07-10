/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, ColorValues } from "@library/styles/styleHelpersColors";
import { BorderRadiusProperty, BorderStyleProperty, BorderWidthProperty } from "csstype";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { unit } from "@library/styles/styleHelpers";
import { globalVariables, IGlobalBorderStyles } from "@library/styles/globalStyleVars";
import merge from "lodash/merge";
import { ColorHelper } from "csx";
import { getValueIfItExists } from "@library/forms/borderStylesCalculator";

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
    borderTopRightRadius?: IBorderRadiusValue;
    borderTopLeftRadius?: IBorderRadiusValue;
    borderBottomRightRadius?: IBorderRadiusValue;
    borderBottomLeftRadius?: IBorderRadiusValue;
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
    if (variable != null) {
        const type = typeof variable;
        return type === "string" || type === "number";
    } else {
        return false;
    }
};

const setAllRadii = (radius: BorderRadiusProperty<TLength>) => {
    return {
        borderTopRightRadius: radius,
        borderBottomRightRadius: radius,
        borderBottomLeftRadius: radius,
        borderTopLeftRadius: radius,
    };
};

/**
 * Main utility function for generation proper border radiuses. Supports numerous shorthand properties.
 *
 * @param radii
 * @param debug
 */
export const standardizeBorderRadius = (radii: IRadiusInput, debug = false): IRadiusValue => {
    if (radii == null) {
        return;
    }

    const output: IBorderRadiusOutput = {};

    if (typeIsStringOrNumber(radii)) {
        const value = unit(radii as number | string);
        return {
            borderTopRightRadius: value,
            borderBottomRightRadius: value,
            borderBottomLeftRadius: value,
            borderTopLeftRadius: value,
        };
    }

    // Otherwise we need to check all of the values.
    const all = getValueIfItExists(radii, "all");
    const top = getValueIfItExists(radii, "top");
    const bottom = getValueIfItExists(radii, "bottom");
    const left = getValueIfItExists(radii, "left");
    const right = getValueIfItExists(radii, "right");

    if (typeIsStringOrNumber(all)) {
        merge(output, {
            borderTopRightRadius: unit(all),
            borderBottomRightRadius: unit(all),
            borderBottomLeftRadius: unit(all),
            borderTopLeftRadius: unit(all),
        });
    }

    if (top !== undefined) {
        const isShorthand = typeIsStringOrNumber(top);

        if (isShorthand) {
            const value = isShorthand ? unit(top) : top;
            merge(output, {
                borderTopRightRadius: value,
                borderTopLeftRadius: value,
            });
        } else {
            merge(
                output,
                right !== undefined ? { borderTopRightRadius: unit(right) } : {},
                left !== undefined ? { borderTopLeftRadius: unit(left) } : {},
            );
        }
    }

    if (bottom !== undefined) {
        const isShorthand = typeIsStringOrNumber(bottom);

        if (isShorthand) {
            const value = isShorthand ? unit(bottom) : bottom;
            merge(output, {
                borderBottomRightRadius: value,
                borderBottomLeftRadius: value,
            });
        } else {
            merge(
                output,
                right !== undefined ? { borderBottomRightRadius: unit(right) } : {},
                left !== undefined ? { borderBottomLeftRadius: unit(left) } : {},
            );
        }
    }

    if (left !== undefined) {
        const isShorthand = typeIsStringOrNumber(left);

        if (isShorthand) {
            const value = isShorthand ? unit(left) : left;
            merge(output, {
                borderTopLeftRadius: value,
                borderBottomLeftRadius: value,
            });
        } else {
            const topStyles = top !== undefined ? { borderBottomRightRadius: unit(top) } : {};
            const bottomStyles = bottom !== undefined ? { borderBottomLeftRadius: unit(bottom) } : {};
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
            const value = isShorthand ? unit(right) : right;
            merge(output, {
                borderTopRightRadius: value,
                borderBottomRightRadius: value,
            });
        } else {
            const topStyles = top !== undefined ? { borderBottomRightRadius: unit(top) } : {};
            const bottomStyles = bottom !== undefined ? { borderBottomLeftRadius: unit(bottom) } : {};
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
            borderTopRightRadius: unit(borderTopRightRadius),
        });
    }
    const borderTopLeftRadius = getValueIfItExists(radii, "borderTopLeftRadius");
    if (borderTopLeftRadius !== undefined) {
        merge(output, {
            borderTopLeftRadius: unit(borderTopLeftRadius),
        });
    }
    const borderBottomRightRadius = getValueIfItExists(radii, "borderBottomRightRadius");
    if (borderBottomRightRadius !== undefined) {
        merge(output, {
            borderBottomRightRadius: unit(borderBottomRightRadius),
        });
    }
    const borderBottomLeftRadius = getValueIfItExists(radii, "borderBottomLeftRadius");
    if (borderBottomLeftRadius !== undefined) {
        merge(output, {
            borderBottomLeftRadius: unit(borderBottomLeftRadius),
        });
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
): NestedCSSProperties => {
    const output: NestedCSSProperties = {};
    const style = getValueIfItExists(detailedStyles, "style", fallbackVariables.style);
    const color = getValueIfItExists(detailedStyles, "color", fallbackVariables.color);
    const width = getValueIfItExists(detailedStyles, "width", fallbackVariables.width);
    const radius = getValueIfItExists(detailedStyles, "radius", fallbackVariables.radius);

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

        merge(output, standardizeBorderRadius(detailedRadius));
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
