/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, ColorValues } from "@library/styles/styleHelpersColors";
import {
    BorderBottomStyleProperty,
    BorderLeftStyleProperty,
    BorderRadiusProperty,
    BorderRightStyleProperty,
    BorderStyleProperty,
    BorderTopColorProperty,
    BorderTopStyleProperty,
    BorderTopWidthProperty,
    BorderWidthProperty,
    Color,
} from "csstype";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { unit, ifExistsWithFallback } from "@library/styles/styleHelpers";
import { globalVariables, IGlobalBorderStyles } from "@library/styles/globalStyleVars";
import merge from "lodash/merge";
import { border, ColorHelper } from "csx";
import { getValueIfItExists, setAllBorderRadii } from "@library/forms/borderStylesCalculator";

export interface ISimpleBorderStyle {
    color?: ColorValues | ColorHelper;
    width?: BorderWidthProperty<TLength>;
    style?: BorderStyleProperty;
}

export interface IBordersWithRadius extends ISimpleBorderStyle {
    radius?: radiusValue;
}

export type radiusValue = BorderRadiusProperty<TLength> | string;

// export interface IBorderStylesAll extends ISimpleBorderStyle {
//     radius?: radiusValue;
// }
//
// export interface IBorderStylesBySideTop extends ISimpleBorderStyle {
//     radius?: radiusValue;
// }
//
// export interface IBorderStylesBySideBottom extends ISimpleBorderStyle {
//     radius?: radiusValue;
// }
//
// export interface IBorderStylesBySideRight extends ISimpleBorderStyle {
//     radius?: radiusValue;
// }
//
// export interface IBorderStylesBySideLeft extends ISimpleBorderStyle {
//     radius?: radiusValue;
// }

// export type borderType = IBordersWithRadius | IBorderStyles;

export type IRadiusDetailed = IRadiusShorthand | IBorderRadiusOutput;

export type IRadiusValue = IBorderRadiusValue | IRadiusShorthand | IBorderRadiusOutput;

export interface IRadiusFlex {
    radius: IRadiusValue;
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



export interface IFinalBorderStyles extends IBorderRadiusOutput {
    borderTopColor: BorderTopColorProperty;
    borderRightColor: BorderTopColorProperty;
    borderBottomColor: BorderTopColorProperty;
    borderLeftColor: BorderTopColorProperty;

    borderTopWidth: BorderTopWidthProperty<TLength>;
    borderRightWidth: BorderTopWidthProperty<TLength>;
    borderBottomWidth: BorderTopWidthProperty<TLength>;
    borderLeftWidth: BorderTopWidthProperty<TLength>;

    borderTopStyle: BorderTopStyleProperty;
    borderRightStyle: BorderTopStyleProperty;
    borderBottomStyle: BorderTopStyleProperty;
    borderLeftStyle: BorderTopStyleProperty;
}

/*
    This interface is used to gather all the styles and overwrites.
*/
// export interface IBorderStylesWIP {
//     all?:
//         | {
//               color?: ColorValues;
//               width?: BorderWidthProperty<TLength> | number;
//               style?: BorderStyleProperty;
//           }
//         | BorderStyleProperty;
//     top?: {
//         color?: ColorValues;
//         width?: BorderWidthProperty<TLength> | number;
//         style?: BorderStyleProperty;
//     };
//     right?: {
//         color?: ColorValues;
//         width?: BorderWidthProperty<TLength> | number;
//         style?: BorderStyleProperty;
//     };
//     bottom?: {
//         color?: ColorValues;
//         width?: BorderWidthProperty<TLength> | number;
//         style?: BorderStyleProperty;
//     };
//     left?: {
//         color?: ColorValues;
//         width?: BorderWidthProperty<TLength> | number;
//         style?: BorderStyleProperty;
//     };
//     radius?: {
//         topRightRadius?: IBorderRadiusValue;
//         bottomRightRadius?: IBorderRadiusValue;
//         topLeftRadius?: IBorderRadiusValue;
//         bottomLeftRadius?: IBorderRadiusValue;
//     };
// }

export const setAllRadii = (radius: BorderRadiusProperty<TLength>) => {
    return {
        topRightRadius: radius,
        bottomRightRadius: radius,
        bottomLeftRadius: radius,
        topLeftRadius: radius,
    };
};

export const borderRadiiStandardizeObject = (radii: IRadiusDetailed) => {]
    let output: IBorderRadiusOutput = {};

    const all = getValueIfItExists(radii, "all");
    if (all) {
        merge(output,setAllBorderRadii(unit(all)));
    }

    const top = getValueIfItExists(radii, "top");
    if (top) {
        merge(output, {
            topRightRadius: unit(top),
            topLeftRadius: unit(top),
        });
    }

    const bottom = getValueIfItExists(radii, "bottom");
    if (bottom) {
        merge(output, {
            bottomRightRadius: unit(bottom),
            bottomLeftRadius: unit(bottom),
        });
    }

    const left = getValueIfItExists(radii, "left");
    if (bottom) {
        merge(output, {
            topLeftRadius: unit(left),
            bottomLeftRadius: unit(left),
        });
    }

    const right = getValueIfItExists(radii, "right");
    if (right) {
        merge(output, {
            topLeftRadius: unit(right),
            bottomLeftRadius: unit(right),
        });
    }

    const topRightRadius = getValueIfItExists(radii, "topRightRadius");
    if (topRightRadius) {
        merge(output, {
            topRightRadius: unit(topRightRadius),
        });
    }
    const topLeftRadius = getValueIfItExists(radii, "topLeftRadius");
    if (topLeftRadius) {
        merge(output, {
            topLeftRadius: unit(topLeftRadius),
        });
    }
    const bottomRightRadius = getValueIfItExists(radii, "bottomRightRadius");
    if (bottomRightRadius) {
        merge(output, {
            bottomRightRadius: unit(bottomRightRadius),
        });
    }
    const bottomLeftRadius = getValueIfItExists(radii, "bottomLeftRadius");
    if (bottomLeftRadius) {
        merge(output, {
            bottomLeftRadius: unit(bottomLeftRadius),
        });
    }
    return output;

};

export const borderRadii = (radii: IRadiusValue, fallbackRadii = globalVariables().border.radius) => {
    let output: IBorderRadiusOutput = {};
    const hasRadiusShorthand = typeIsStringOrNumber(radii);
    const hasRadiusShorthandFallback = typeIsStringOrNumber(fallbackRadii);

    // Make sure we have a value before overwriting.
    if (hasRadiusShorthand) {
        merge(output, setAllRadii(unit(radii as any) as any));
    } else if (hasRadiusShorthandFallback) {
        merge(output, setAllRadii(unit(fallbackRadii as any) as any));
    } else {
        // our fallback must be an object.
        merge(output, borderRadiiStandardizeObject(fallbackRadii as any));
    }
    merge(output, borderRadiiStandardizeObject(radii as any));
    return output;
};

const typeIsStringOrNumber = variable => {
    if (variable) {
        const type = typeof variable;
        return type === "string" || type === "number";
    } else {
        return false;
    }
};

const getSingleBorderStyle = (color, width, style) => {
    const result: ISimpleBorderStyle = {};
    if (color) {
        result.color = color;
    }
    if (width) {
        result.width = width;
    }
    if (style) {
        result.style = style;
    }
    return result;
};


export const setAllBorders = (
    color: ColorValues,
    width: BorderWidthProperty<TLength>,
    style: BorderStyleProperty,
    radius: IBorderRadiusOutput,
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
        borderRadius: typeIsStringOrNumber(radius) ? unit(radius) : radius,
    };
};
/*
    Exports a standardized border style format from a flexible format (IBorderStyles)
 */
export const standardizeBorderStyle = (
    borderStyles: IBorderStyles | ISimpleBorderStyle | undefined | {} = {},
    fallbackVariables: IGlobalBorderStyles = globalVariables().border,
    debug: boolean = false,
) => {
    let output: IBorderFinalStyles = {
        // borderTopColor: fallbackVariables.color,
        // width: fallbackVariables.width,
        // style: fallbackVariables.style,
    };

    if (debug) {
        window.console.log("borderStyles - standardized: ", borderStyles);
    }

    merge(output, standardizeBorderRadius(getValueIfItExists(borderStyles, "radius")));

    if (debug) {
        console.log("after standardize border radius: ", output);
    }

    if (borderStyles) {
        let outputCount = 0;

        // 0
        debug && console.log(outputCount++ + " - output: ", output);

        // All (global styles, includes border radius
        const all = getValueIfItExists(borderStyles, "all");
        if (all) {
            const color = getValueIfItExists(all, "color");
            const width = getValueIfItExists(all, "width");
            const style = getValueIfItExists(all, "style");

            if (color || width || style) {
                const allStyles: any = {};

                if (color) {
                    allStyles.color = color;
                }

                if (width) {
                    allStyles.width = width;
                }

                if (style) {
                    allStyles.style = style;
                }
                merge(output, singleBorderStyle(allStyles));
            }

            const radius = getValueIfItExists(all, "radius");
            if (radius && typeIsStringOrNumber(radius)) {
                merge(output, radius);
            }
        }

        // 2
        debug && console.log(outputCount++ + " - output: ", output);

        // Top Bottom border styles (does not include border radius,
        // since it doesn't really make sense, it would be global, like "all")
        const topBottom = getValueIfItExists(borderStyles, "topBottom");
        if (topBottom) {
            const color = topBottom.color;
            const width = topBottom.width;
            const style = topBottom.style;
            const topBottomStyles = getSingleBorderStyle(color, width, style);
            if (color || width || style) {
                merge(output, {
                    top: topBottomStyles,
                    bottom: topBottomStyles,
                });
            }
        }

        // 3
        debug && console.log(outputCount++ + " - output: ", output);

        // Left Right border styles (does not include border radius,
        // since it doesn't really make sense, it would be global, like "all")
        const leftRight = getValueIfItExists(borderStyles, "leftRight");
        if (leftRight) {
            const color = leftRight.color;
            const width = leftRight.width;
            const style = leftRight.style;
            const leftRightStyles = getSingleBorderStyle(color, width, style);
            if (color || width || style) {
                merge(output, {
                    left: leftRightStyles,
                    right: leftRightStyles,
                });
            }
        }

        debug && console.log(outputCount++ + " - output: ", output);
        // Top
        const top = getValueIfItExists(borderStyles, "top");
        if (top) {
            const color = top.color;
            const width = top.width;
            const style = top.style;
            const topStyles = getSingleBorderStyle(color, width, style);
            if (color || width || style) {
                merge(output, {
                    top: topStyles,
                    bottom: topStyles,
                });
            }

            debug && console.log(outputCount++ + " - output: ", output);

            const radius = getValueIfItExists(top, "radius");
            const radiusType = typeof radius;
            if (radiusType) {
                merge(output, {
                    top: topStyles,
                    radius: {
                        topRightBorderRadius: radius,
                        topLeftBorderRadius: radius,
                    },
                });
            } else {
                merge(output, {
                    top: topStyles,
                });
            }
        }
        debug && console.log(outputCount++ + " - output: ", output);

        // Right
        const right = getValueIfItExists(borderStyles, "right");
        if (right) {
            const color = right.color;
            const width = right.width;
            const style = right.style;
            const rightStyles = getSingleBorderStyle(color, width, style);
            if (color || width || style) {
                merge(output, {
                    right: rightStyles,
                });
            }

            debug && console.log(outputCount++ + " - output: ", output);
            const radius = getValueIfItExists(right, "radius");
            const radiusType = typeof radius;
            if (radiusType) {
                merge(output, {
                    right: rightStyles,
                    radius: {
                        topRightBorderRadius: radius,
                        bottomRightBorderRadius: radius,
                    },
                });
            } else {
                merge(output, { right: rightStyles });
            }
        }
        debug && console.log(outputCount++ + " - output: ", output);

        // Bottom
        const bottom = getValueIfItExists(borderStyles, "bottom");
        if (bottom) {
            const color = bottom.color;
            const width = bottom.width;
            const style = bottom.style;
            const bottomStyles = getSingleBorderStyle(color, width, style);
            if (color || width || style) {
                merge(output, {
                    bottom: bottomStyles,
                });
            }

            debug && console.log(outputCount++ + " - output: ", output);

            const radius = getValueIfItExists(bottom, "radius");
            const radiusType = typeof radius;
            if (radiusType) {
                merge(output, {
                    bottom: bottomStyles,
                    radius: {
                        bottomRightBorderRadius: radius,
                        bottomLeftBorderRadius: radius,
                    },
                });
            } else {
                merge(output, { bottom: bottomStyles });
            }
        }
        debug && console.log(outputCount++ + " - output: ", output);

        // Left
        const left = getValueIfItExists(borderStyles, "left");
        if (left) {
            const color = left.color;
            const width = left.width;
            const style = left.style;
            const leftStyles = getSingleBorderStyle(color, width, style);
            if (color || width || style) {
                merge(output, {
                    left: leftStyles,
                });
            }

            debug && console.log(outputCount++ + " - output: ", output);
            const radiusType = typeof left;
            if (radiusType) {
                merge(output, {
                    radius: {
                        topLeftBorderRadius: left,
                        bottomLeftBorderRadius: left,
                    },
                });
            } else {
                merge(output, { left: leftStyles });
            }
        }
        debug && console.log(outputCount++ + " - output: ", output);

        // Explicit radius values take precedence over shorthand declarations.
        const radiusFromBorderStyles = getValueIfItExists(borderStyles, "radius");

        if (typeIsStringOrNumber(radiusFromBorderStyles)) {
            merge(output, {
                radius: {
                    topRightRadius: radiusFromBorderStyles,
                    topLeftRadius: radiusFromBorderStyles,
                    bottomRightRadius: radiusFromBorderStyles,
                    bottomLeftRadius: radiusFromBorderStyles,
                },
            });
            debug && console.log(outputCount++ + " - output: ", output);
        } else {
            const topRightBorderRadius = getValueIfItExists(radiusFromBorderStyles, "topRightBorderRadius");
            const bottomRightBorderRadius = getValueIfItExists(radiusFromBorderStyles, "bottomRightBorderRadius");
            const bottomLeftBorderRadius = getValueIfItExists(radiusFromBorderStyles, "bottomLeftBorderRadius");
            const topLeftBorderRadius = getValueIfItExists(radiusFromBorderStyles, "topLeftBorderRadius");
            const radii = {} as IBorderRadiusOutput;
            if (topRightBorderRadius) {
                radii.topRightRadius = topRightBorderRadius;
            }
            if (bottomRightBorderRadius) {
                radii.bottomRightRadius = bottomRightBorderRadius;
            }
            if (bottomLeftBorderRadius) {
                radii.bottomLeftRadius = bottomLeftBorderRadius;
            }
            if (topLeftBorderRadius) {
                radii.bottomLeftRadius = topLeftBorderRadius;
            }

            if (Object.keys(radii).length > 0) {
                merge(output, {
                    radius: radii,
                });
            }
            debug && console.log(outputCount++ + " - output: ", output);
        }
    }

    debug && console.log("FINAL: ", output);
    return output;
};

export const singleBorderStyle = (
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
    detailedStyles?: IBorderFinalStyles | ISimpleBorderStyle | undefined,
    fallbackVariables: IGlobalBorderStyles = globalVariables().border,
    debug = false,
): NestedCSSProperties => {
    let output: NestedCSSProperties = {};

    const style = getValueIfItExists(detailedStyles, "style", fallbackVariables.style);
    const color = getValueIfItExists(detailedStyles, "color", fallbackVariables.color);
    const width = getValueIfItExists(detailedStyles, "width", fallbackVariables.width);
    const radius = getValueIfItExists(detailedStyles, "radius", fallbackVariables.radius);
    if (style || color || width) {
        merge(output, setAllBorders(color, width, style, radius));
    }

    // Now we are sure to not have simple styles anymore.
    detailedStyles = detailedStyles as IBorderFinalStyles;
    if (detailedStyles) {
        if (detailedStyles && Object.keys(detailedStyles).length > 0) {
            if (detailedStyles.top) {
                const topStyles = singleBorderStyle(detailedStyles.top, fallbackVariables);
                if (topStyles) {
                    output.borderTopWidth = getValueIfItExists(topStyles, "width", fallbackVariables.width);
                    output.borderTopStyle = getValueIfItExists(topStyles, "style", fallbackVariables.style);
                    output.borderTopColor = getValueIfItExists(topStyles, "color", fallbackVariables.color);
                }
            }

            if (detailedStyles.right) {
                const rightStyles = singleBorderStyle(detailedStyles.right, fallbackVariables);
                if (rightStyles) {
                    output.borderRightWidth = getValueIfItExists(rightStyles, "width", fallbackVariables.width);
                    output.borderRightStyle = getValueIfItExists(rightStyles, "style", fallbackVariables.style);
                    output.borderRightColor = getValueIfItExists(rightStyles, "color", fallbackVariables.color);
                }
            }
            if (detailedStyles.bottom) {
                const bottomStyles = singleBorderStyle(detailedStyles.bottom, fallbackVariables);
                if (bottomStyles) {
                    output.borderBottomWidth = getValueIfItExists(bottomStyles, "width", fallbackVariables.width);
                    output.borderBottomStyle = getValueIfItExists(bottomStyles, "style", fallbackVariables.style);
                    output.borderBottomColor = getValueIfItExists(bottomStyles, "color", fallbackVariables.color);
                }
            }
            if (detailedStyles.left) {
                const leftStyles = singleBorderStyle(detailedStyles.left, fallbackVariables);
                if (leftStyles) {
                    output.borderLeftWidth = getValueIfItExists(leftStyles, "width", fallbackVariables.width);
                    output.borderLeftStyle = getValueIfItExists(leftStyles, "style", fallbackVariables.style);
                    output.borderLeftColor = getValueIfItExists(leftStyles, "color", fallbackVariables.color);
                }
            }

            const detailedRadius = getValueIfItExists(detailedStyles, "radius");
            if (detailedRadius) {
                if (detailedRadius.borderTopRightRadius) {
                    output.borderTopRightRadius = unit(detailedRadius.borderTopRightRadius);
                }
                if (detailedRadius.borderBottomRightRadius) {
                    output.borderBottomRightRadius = unit(detailedRadius.borderBottomRightRadius);
                }
                if (detailedRadius.borderBottomLeftRadius) {
                    output.borderBottomLeftRadius = unit(detailedRadius.borderBottomLeftRadius);
                }
                if (detailedRadius.borderTopLeftRadius) {
                    output.borderTopLeftRadius = unit(detailedRadius.borderTopLeftRadius);
                }
            }
        }
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
