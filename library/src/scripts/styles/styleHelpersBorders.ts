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
    BorderTopStyleProperty,
    BorderWidthProperty,
} from "csstype";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { unit, ifExistsWithFallback } from "@library/styles/styleHelpers";
import { globalVariables, IGlobalBorderStyles } from "@library/styles/globalStyleVars";
import merge from "lodash/merge";
import { ColorHelper } from "csx";
import { getValueIfItExists, setAllBorderRadii } from "@library/forms/borderStylesCalculator";

export interface ISingleBorderStyle {
    color?: ColorValues | ColorHelper;
    width?: BorderWidthProperty<TLength>;
    style?: BorderStyleProperty;
}

export interface IBordersWithRadius extends ISingleBorderStyle {
    radius?: radiusValue;
}

export type radiusValue = BorderRadiusProperty<TLength> | string;

export interface IBorderStylesAll extends ISingleBorderStyle {
    radius?: radiusValue;
}

export interface IBorderStylesBySideTop extends ISingleBorderStyle {
    radius?: ITopBorderRadii;
}

export interface IBorderStylesBySideBottom extends ISingleBorderStyle {
    radius?: IBottomBorderRadii;
}

export interface IBorderStylesBySideRight extends ISingleBorderStyle {
    radius?: IRightBorderRadii;
}

export interface IBorderStylesBySideLeft extends ISingleBorderStyle {
    radius?: ILeftBorderRadii;
}

export interface IBorderStyles extends ISingleBorderStyle {
    all?: IBorderStylesAll;
    topBottom?: ISingleBorderStyle;
    leftRight?: ISingleBorderStyle;
    top?: IBorderStylesBySideTop;
    bottom?: IBorderStylesBySideBottom;
    left?: IBorderStylesBySideRight;
    right?: IBorderStylesBySideLeft;
    radius?: radiusValue | IBorderRadiiDeclaration;
}

export type borderType = IBordersWithRadius | IBorderStyles;

// export interface IBorderRadiiDown {
//     left?: BorderRadiusValue,
//     right: BorderRadiusValue,
// }
//
// export interface IBorderRadiiBottom {
//     left?: BorderRadiusValue,
//     right: BorderRadiusValue,
// }
//
// export interface IBorderRadiiRight {
//     top?: BorderRadiusValue,
//     bottom: BorderRadiusValue,
// }
//
// export interface IBorderRadiiTopBottom {
//     top?: BorderRadiusValue,
//     bottom: BorderRadiusValue,
// }

export interface ITopBorderRadii {
    left?: radiusValue;
    right?: radiusValue;
}
export interface IBottomBorderRadii {
    left?: radiusValue;
    right?: radiusValue;
}
export interface ILeftBorderRadii {
    top?: radiusValue;
    bottom?: radiusValue;
}
export interface IRightBorderRadii {
    top?: radiusValue;
    bottom?: radiusValue;
}

export type BorderRadiusValue = BorderRadiusProperty<TLength> | number | undefined;

export interface IRadiusShorthand {
    all?: BorderRadiusValue;
    top?: BorderRadiusValue | ITopBorderRadii;

    bottom?: BorderRadiusValue | IBottomBorderRadii;
    left?: BorderRadiusValue | ILeftBorderRadii;
    right?: BorderRadiusValue | IRightBorderRadii;
}

export interface IBorderRadiiDeclaration extends IBorderRadiusOutput, IRadiusShorthand {}

export interface IBorderRadiusOutput {
    topRight?: BorderRadiusValue | number;
    topLeft?: BorderRadiusValue | number;
    bottomRight?: BorderRadiusValue | number;
    bottomLeft?: BorderRadiusValue | number;
}

/*
    This interface is used to gather all the styles and overwrites.
*/
export interface IBorderStylesWIP {
    all?:
        | {
              color?: ColorValues;
              width?: BorderWidthProperty<TLength> | number;
              style?: BorderStyleProperty;
          }
        | BorderStyleProperty;
    top?: {
        color?: ColorValues;
        width?: BorderWidthProperty<TLength> | number;
        style?: BorderStyleProperty;
    };
    right?: {
        color?: ColorValues;
        width?: BorderWidthProperty<TLength> | number;
        style?: BorderStyleProperty;
    };
    bottom?: {
        color?: ColorValues;
        width?: BorderWidthProperty<TLength> | number;
        style?: BorderStyleProperty;
    };
    left?: {
        color?: ColorValues;
        width?: BorderWidthProperty<TLength> | number;
        style?: BorderStyleProperty;
    };
    radius?: {
        topRight?: BorderRadiusValue;
        bottomRight?: BorderRadiusValue;
        topLeft?: BorderRadiusValue;
        bottomLeft?: BorderRadiusValue;
    };
}

// This is the final outputted format before we generate the actual styles.
export interface IBorderFinalStyles {
    top?: ISingleBorderStyle;
    right?: ISingleBorderStyle;
    bottom?: ISingleBorderStyle;
    left?: ISingleBorderStyle;
    radius?: IBorderRadiusOutput | number;
}

export const borderRadii = (props: IBorderRadiiDeclaration) => {
    return {
        borderTopLeftRadius: unit(ifExistsWithFallback([props.all, props.top, props.left, props.topLeft, undefined])),
        borderTopRightRadius: unit(
            ifExistsWithFallback([props.all, props.top, props.right, props.topRight, undefined]),
        ),
        borderBottomLeftRadius: unit(
            ifExistsWithFallback([props.all, props.bottom, props.left, props.bottomLeft, undefined]),
        ),
        borderBottomRightRadius: unit(
            ifExistsWithFallback([props.all, props.bottom, props.right, props.bottomRight, undefined]),
        ),
    };
};

// const borderStylesFallbacks = (fallbacks: any[], ultimateFallback, unitFunction?: (value: any) => string) => {
//     let output = ultimateFallback;
//     const convert = unitFunction ? unitFunction : value => value.toString();
//     try {
//         const BreakException = {};
//         fallbacks.forEach((style, key) => {
//             if (!!style) {
//                 output = style;
//                 throw BreakException;
//             }
//         });
//     } catch (e) {
//         // break out of loop
//     }
//     return convert(output);
// };

// export const mergeIfNoGlobal = (globalStyles: IBorderStyles | undefined, overwriteStyles: IBorderStyles | undefined) => {
//     if (globalStyles) {
//         return merge(globalStyles, overwriteStyles);
//     } else {
//         return overwriteStyles;
//     }
// };

const typeIsStringOrNumber = variable => {
    const type = typeof variable;
    return type === "string" || type === "number";
};

const getSingleBorderStyle = (color, width, style) => {
    const result: ISingleBorderStyle = {};
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

/*
    Exports a standardized border style format from a flexible format (IBorderStyles)
 */
export const standardizeBorderStyle = (borderStyles: IBorderStyles = {}, debug: boolean = false) => {
    let output: IBorderFinalStyles = {};

    if (debug) {
        window.console.log("borderStyles - standardized: ", borderStyles);
    }

    if (borderStyles) {
        let outputCount = 0;

        debug && console.log(outputCount++ + " - output: ", output);

        // Start of global values
        // Color
        const globalColor = getValueIfItExists(borderStyles, "color") as ColorValues;

        if (globalColor) {
            merge(output, {
                top: {
                    color: globalColor,
                },
                right: {
                    color: globalColor,
                },
                bottom: {
                    color: globalColor,
                },
                left: {
                    color: globalColor,
                },
            });
        }

        debug && console.log(outputCount++ + " - output: ", output);

        //Width
        const globalWidth = getValueIfItExists(borderStyles, "width") as ColorValues;
        if (globalWidth) {
            merge(output, {
                top: {
                    width: globalWidth,
                },
                right: {
                    width: globalWidth,
                },
                bottom: {
                    width: globalWidth,
                },
                left: {
                    width: globalWidth,
                },
            });
        }

        debug && console.log(outputCount++ + " - output: ", output);
        //Width
        const globalBorderStyle = getValueIfItExists(borderStyles, "style") as ColorValues;
        if (globalBorderStyle) {
            merge(output, {
                top: {
                    style: globalBorderStyle,
                },
                right: {
                    style: globalBorderStyle,
                },
                bottom: {
                    style: globalBorderStyle,
                },
                left: {
                    style: globalBorderStyle,
                },
            });
        }
        // End of global values

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
                        topRight: radius,
                        topLeft: radius,
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
                        topRight: radius,
                        bottomRight: radius,
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
                        bottomRight: radius,
                        bottomLeft: radius,
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
                        topLeft: left,
                        bottomLeft: left,
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
                    topRight: radiusFromBorderStyles,
                    topLeft: radiusFromBorderStyles,
                    bottomRight: radiusFromBorderStyles,
                    bottomLeft: radiusFromBorderStyles,
                },
            });
            debug && console.log(outputCount++ + " - output: ", output);
        } else {
            const topRight = getValueIfItExists(radiusFromBorderStyles, "topRight");
            const bottomRight = getValueIfItExists(radiusFromBorderStyles, "bottomRight");
            const bottomLeft = getValueIfItExists(radiusFromBorderStyles, "bottomLeft");
            const topLeft = getValueIfItExists(radiusFromBorderStyles, "topLeft");
            const radii = {} as IBorderRadiusOutput;
            if (topRight) {
                radii.topRight = topRight;
            }
            if (bottomRight) {
                radii.bottomRight = bottomRight;
            }
            if (bottomLeft) {
                radii.bottomLeft = bottomLeft;
            }
            if (topLeft) {
                radii.bottomLeft = topLeft;
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

    if (Object.keys(output).length > 0) {
        return output;
    } else {
        return undefined;
    }
};

export const singleBorderStyle = (
    borderStyles: ISingleBorderStyle,
    fallbackVariables: IGlobalBorderStyles = globalVariables().border,
) => {
    if (!borderStyles) {
        return;
    }
    const { color, width, style } = borderStyles;
    let output: ISingleBorderStyle = {};
    output.color = colorOut(borderStyles.color ? borderStyles.color : color) as ColorValues;
    output.width = unit(borderStyles.width ? borderStyles.width : width) as BorderWidthProperty<TLength>;
    output.style = borderStyles.style ? borderStyles.style : (style as BorderStyleProperty);

    if (Object.keys(output).length > 0) {
        return output;
    } else {
        return;
    }
};

export interface IBorders extends IBorderFinalStyles, ISingleBorderStyle {}

export const borders = (
    borderStyles?: IBordersWithRadius | IBorderFinalStyles | undefined,
    fallbackVariables: IGlobalBorderStyles = globalVariables().border,
) => {
    let output: NestedCSSProperties = {};
    const globalStyles = borderStyles as ISingleBorderStyle;
    const detailedStyles = borderStyles as IBorderFinalStyles;

    const formatedGlobalStyles = singleBorder(globalStyles);
    if (formatedGlobalStyles) {
        output = {
            top: formatedGlobalStyles,
            right: formatedGlobalStyles,
            bottom: formatedGlobalStyles,
            left: formatedGlobalStyles,
        };
    }

    if (detailedStyles && Object.keys(detailedStyles).length > 0) {
        if (detailedStyles.top) {
            const topStyles = singleBorderStyle(detailedStyles.top, fallbackVariables);
            if (topStyles) {
                output.borderTopWidth = topStyles.width;
                output.borderTopStyle = topStyles.style as any;
                output.borderTopColor = topStyles.color as any;
            }
        }

        if (detailedStyles.right) {
            const rightStyles = singleBorderStyle(detailedStyles.right, fallbackVariables);
            if (rightStyles) {
                output.borderRightWidth = rightStyles.width;
                output.borderRightStyle = rightStyles.style as any;
                output.borderRightColor = rightStyles.color as any;
            }
        }
        if (detailedStyles.bottom) {
            const bottomStyles = singleBorderStyle(detailedStyles.bottom, fallbackVariables);
            if (bottomStyles) {
                output.borderBottomWidth = bottomStyles.width;
                output.borderBottomStyle = bottomStyles.style as any;
                output.borderBottomColor = bottomStyles.color as any;
            }
        }
        if (detailedStyles.left) {
            const leftStyles = singleBorderStyle(detailedStyles.left, fallbackVariables);
            if (leftStyles) {
                output.borderLeftWidth = leftStyles.width;
                output.borderLeftStyle = leftStyles.style as any;
                output.borderLeftColor = leftStyles.color as any;
            }
        }
        const globalRadius = getValueIfItExists(globalStyles, "radius");
        if (globalRadius) {
            output.borderTopRightRadius = unit(globalRadius);
            output.borderBottomRightRadius = unit(globalRadius);
            output.borderTopRightRadius = unit(globalRadius);
            output.borderTopRightRadius = unit(globalRadius);
        }

        const detailedRadius = getValueIfItExists(detailedStyles, "radius");
        if (detailedRadius) {
            if (detailedRadius.topRight) {
                output.borderTopRightRadius = unit(detailedRadius.topRight);
            }
            if (detailedRadius.bottomRight) {
                output.borderBottomRightRadius = unit(detailedRadius.bottomRight);
            }
            if (detailedRadius.bottomRight) {
                output.borderTopRightRadius = unit(detailedRadius.bottomRight);
            }
            if (detailedRadius.topLeft) {
                output.borderTopRightRadius = unit(detailedRadius.topLeft);
            }
        }
    }
    return output;
};

export const singleBorder = (styles?: ISingleBorderStyle) => {
    const vars = globalVariables();
    const borderStyles = styles !== undefined ? styles : {};
    return `${borderStyles.style ? borderStyles.style : vars.border.style} ${
        borderStyles.color ? colorOut(borderStyles.color) : colorOut(vars.border.color)
    } ${borderStyles.width ? unit(borderStyles.width) : unit(vars.border.width)}` as any;
};
