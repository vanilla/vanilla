/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, ColorValues } from "@library/styles/styleHelpersColors";
import { BorderRadiusProperty, BorderStyleProperty, BorderWidthProperty } from "csstype";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { unit, ifExistsWithFallback } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import merge from "lodash/merge";
import {ColorHelper} from "csx";
import {
    checkIfKeyExistsAndIsDefined,
    getValueIfItExists,
    setAllBorderRadii
} from "@library/forms/borderStylesCalculator";

export interface ISingleBorderStyle {
    color?: ColorValues;
    width?: BorderWidthProperty<TLength>;
    style?: BorderStyleProperty;
}

export interface IBordersWithRadius extends ISingleBorderStyle {
    radius?: radiusValue;
}

export type radiusValue = BorderRadiusProperty<TLength> | string;

export interface IBorderStylesAll extends ISingleBorderStyle  {
    radius?: radiusValue;
}

export interface IBorderStylesBySideTop extends ISingleBorderStyle  {
    radius?: ITopBorderRadii;
}

export interface IBorderStylesBySideBottom extends ISingleBorderStyle  {
    radius?: IBottomBorderRadii;
}

export interface IBorderStylesBySideRight extends ISingleBorderStyle  {
    radius?: IRightBorderRadii;
}

export interface IBorderStylesBySideLeft extends ISingleBorderStyle  {
    radius?: ILeftBorderRadii;
}


export interface IBorderStyles extends ISingleBorderStyle  {
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

export interface ITopBorderRadii {left?: radiusValue; right?: radiusValue};
export interface IBottomBorderRadii {left?: radiusValue, right?: radiusValue};
export interface ILeftBorderRadii {top?: radiusValue, bottom?: radiusValue};
export interface IRightBorderRadii {top?: radiusValue, bottom?: radiusValue};

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
    topRight?: BorderRadiusValue;
    topLeft?: BorderRadiusValue;
    bottomRight?: BorderRadiusValue;
    bottomLeft?: BorderRadiusValue;
}

/*
    This interface is used to gather all the styles and overwrites.
*/
export interface IBorderStylesWIP {
    all?: {
        color?: ColorValues,
        width?: BorderWidthProperty<TLength> | number,
        style?: BorderStyleProperty,
    } | BorderStyleProperty;
    top?: {
        color?: ColorValues,
        width?: BorderWidthProperty<TLength> | number,
        style?: BorderStyleProperty,
    };
    right?: {
        color?: ColorValues,
        width?: BorderWidthProperty<TLength> | number,
        style?: BorderStyleProperty,
    };
    bottom?: {
        color?: ColorValues,
        width?: BorderWidthProperty<TLength> | number,
        style?: BorderStyleProperty,
    };
    left?: {
        color?: ColorValues,
        width?: BorderWidthProperty<TLength> | number,
        style?: BorderStyleProperty,
    };
    radius?: {
        topRight?: BorderRadiusValue,
        bottomRight?: BorderRadiusValue,
        topLeft?: BorderRadiusValue,
        bottomLeft?: BorderRadiusValue,
    };
}

// This is the final outputted format before we generate the actual styles.
export interface IBorderFinalStyles  {
    top?: ISingleBorderStyle;
    right?: ISingleBorderStyle;
    bottom?: ISingleBorderStyle;
    left?: ISingleBorderStyle;
    radius?: IBorderRadiusOutput,
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

const typeIsStringOrNumber = (variable) => {
    const type = typeof variable;
    return type === "string" || type === "number";
}

/*
    Exports a standardized border style format from a flexible format (IBorderStyles)
 */

export const borders = (borderStyles: IBorderStyles = {}, debug: boolean = false) => {
    const globalVars = globalVariables();
    //
    // if (debug) {
    //     window.console.log("coming in: ", props);
    // }

    const output: IBorderFinalStyles = {};

    // Start of global values
    // Color
    const globalColor = getValueIfItExists(borderStyles, "color") as ColorValues;
    if(globalColor) {
        merge(output, {
            top: {
                color: globalColor,
            },
            right: {
                color: globalColor,
            },
            bottom:{
                color: globalColor,
            },
            left:{
                color: globalColor,
            },
        });
    }

    //Width
    const globalWidth = getValueIfItExists(borderStyles, "color") as ColorValues;
    if(globalWidth) {
        merge(output, {
            top: {
                width: globalWidth,
            },
            right: {
                width: globalWidth,
            },
            bottom:{
                width: globalWidth,
            },
            left:{
                width: globalWidth,
            },
        });
    }

    //Width
    const globalBorderStyle = getValueIfItExists(borderStyles, "style") as ColorValues;
    if(globalBorderStyle) {
        merge(output, {
            top: {
                width: globalBorderStyle,
            },
            right: {
                width: globalBorderStyle,
            },
            bottom:{
                width: globalBorderStyle,
            },
            left:{
                width: globalBorderStyle,
            },
        });
    }
    // End of global values

    // All (global styles, includes border radius
    const all = getValueIfItExists(borderStyles, "all");
    if (all) {
        const color = getValueIfItExists(all, "color");
        const width = getValueIfItExists(all, "width");
        const style = getValueIfItExists(all, "style");
        const singleBorder = {
            color,
            width,
            style,
        };
        const radius = getValueIfItExists(all, "radius");
        const radiusType = typeof radius;
        if (radius) {
            merge(output, typeIsStringOrNumber(radius) ? setAllBorderRadii(radius) : radius);
        }
    }

    // Top Bottom border styles (does not include border radius,
    // since it doesn't really make sense, it would be global, like "all")
    const topBottom = getValueIfItExists(borderStyles, "topBottom");
    if (topBottom) {
        const color = topBottom.color;
        const width = topBottom.width;
        const style = topBottom.style;
        const topBottomStyles = {} as ISingleBorderStyle;
        if (color) {
            topBottomStyles.color = color;
        }
        if (width) {
            topBottomStyles.width = width;
        }
        if (style) {
            topBottomStyles.style = style;
        }
        merge(output, {top: topBottomStyles, bottom: topBottomStyles});
    }


    // Left Right border styles (does not include border radius,
    // since it doesn't really make sense, it would be global, like "all")
    const leftRight = getValueIfItExists(borderStyles, "leftRight");
    if (leftRight) {
        const color = leftRight.color;
        const width = leftRight.width;
        const style = leftRight.style;
        const leftRightStyles = {} as ISingleBorderStyle;
        if (color) {
            leftRightStyles.color = color;
        }
        if (width) {
            leftRightStyles.width = width;
        }
        if (style) {
            leftRightStyles.style = style;
        }
        merge(output, {top: leftRightStyles, bottom: leftRightStyles});
    }

    // Top
    const top = getValueIfItExists(borderStyles, "top");
    if (top) {
        const color = top.color;
        const width = top.width;
        const style = top.style;
        const topStyles = {} as ISingleBorderStyle;
        if (color) {
            top.color = color;
        }
        if (width) {
            top.width = width;
        }
        if (style) {
            top.style = style;
        }

        const radius = getValueIfItExists(top, "radius");
        const radiusType = typeof radius;
        if (radius) {
            merge(output, {
                top: topStyles,
                radius: {
                    topRight: radius,
                    topLeft: radius,
                }
            });
        } else {
            merge(output, {top: topStyles});
        }
    }

    // Right
    const right = getValueIfItExists(borderStyles, "right");
    if (right) {
        const color = right.color;
        const width = right.width;
        const style = right.style;
        const rightStyles = {} as ISingleBorderStyle;
        if (color) {
            right.color = color;
        }
        if (width) {
            right.width = width;
        }
        if (style) {
            right.style = style;
        }

        const radius = getValueIfItExists(right, "radius");
        const radiusType = typeof radius;
        if (radius) {
            merge(output, {
                right: rightStyles,
                radius: {
                    topRight: radius,
                    bottomRight: radius,
                }
            });
        } else {
            merge(output, {right: rightStyles});
        }
    }


    // Bottom
    const bottom = getValueIfItExists(borderStyles, "bottom");
    if (bottom) {
        const color = bottom.color;
        const width = bottom.width;
        const style = bottom.style;
        const bottomStyles = {} as ISingleBorderStyle;
        if (color) {
            bottom.color = color;
        }
        if (width) {
            bottom.width = width;
        }
        if (style) {
            bottom.style = style;
        }

        const radius = getValueIfItExists(bottom, "radius");
        const radiusType = typeof radius;
        if (radius) {
            merge(output, {
                bottom: bottomStyles,
                radius: {
                    bottomRight: radius,
                    bottomLeft: radius,
                }
            });
        } else {
            merge(output, {bottom: bottomStyles});
        }
    }

    // Left
    const left = getValueIfItExists(borderStyles, "left");
    if (left) {
        const color = left.color;
        const width = left.width;
        const style = left.style;
        const leftStyles = {} as ISingleBorderStyle;
        if (color) {
            left.color = color;
        }
        if (width) {
            left.width = width;
        }
        if (style) {
            left.style = style;
        }

        const radius = getValueIfItExists(left, "radius");
        const radiusType = typeof radius;
        if (radius) {
            merge(output, {
                left: leftStyles,
                radius: {
                    topleft: radius,
                    bottomleft: radius,
                }
            });
        } else {
            merge(output, {left: leftStyles});
        }
    }

    // Explicit radius values take precedence over shorthand declarations.
    const borderRadii = getValueIfItExists(borderStyles, "radius");

    if (typeIsStringOrNumber(borderRadii)) {
        merge(output, {
            radius: {
                topRight: borderRadii,
                topLeft: borderRadii,
                bottomRight: borderRadii,
                bottomLeft: borderRadii,
            },
        });
    } else {
        const topRight = getValueIfItExists(borderRadii, "topRight");
        const bottomRight = getValueIfItExists(borderRadii, "bottomRight");
        const bottomLeft = getValueIfItExists(borderRadii, "bottomLeft");
        const topLeft = getValueIfItExists(borderRadii, "topLeft");
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
    }

    //radius?: radiusValue | IBorderRadiiDeclaration;

    /*
    Final Output:
    top?: BorderRadiusValue;
    right?: BorderRadiusValue;
    bottom?: BorderRadiusValue;
    left?: BorderRadiusValue;
    radius?: {
        topRightRadius?: BorderRadiusValue;
        topLeftRadius?: BorderRadiusValue;
        bottomRightRadius?: BorderRadiusValue;
        bottomLeftRadius?: BorderRadiusValue;
    },
    */


    // if (typeof borderStyles === "string") {
    //     const shorthandStyles = borders(borderStyles);
    //     if (shorthandStyles) {
    //         output.bord
    //     }
    // }

    //
    // if(debug) {
    //     window.console.log("border radii: ", props.radius);
    // }

    // // Set border radii
    // let globalRadiusFound = false;
    // let specificRadiusFound = false;
    // if (props.radius !== undefined) {
    //     if (typeof props.radius === "string" || typeof props.radius === "number") {
    //         output.borderRadius = unit(props.radius as BorderRadiusProperty<TLength>);
    //         globalRadiusFound = true;
    //     } else {
    //         if (props.radius.all !== undefined) {
    //             globalRadiusFound = true;
    //             output.borderRadius = unit(props.radius as BorderRadiusProperty<TLength>);
    //         }
    //         if (props.radius.top !== undefined) {
    //             specificRadiusFound = true;
    //             output.topRightRadius = unit(props.radius.topRight);
    //             output.topLeftRadius = unit(props.radius.topLeft);
    //             if (props.radius.topRight) {
    //                 output.topRightRadius = unit(props.radius.topRight);
    //             }
    //             if (props.radius.topLeft) {
    //                 output.topLeftRadius = unit(props.radius.topLeft);
    //             }
    //         }
    //         if (props.radius.bottom !== undefined) {
    //             specificRadiusFound = true;
    //             if (props.radius.bottomRight) {
    //                 output.bottomRightRadius = unit(props.radius.bottomRight);
    //             }
    //             if (props.radius.bottomLeft) {
    //                 output.bottomLeftRadius = unit(props.radius.bottomLeft);
    //             }
    //         }
    //         if (props.radius.right !== undefined) {
    //             specificRadiusFound = true;
    //             if (props.radius.topRight) {
    //                 output.topRightRadius = unit(props.radius.topRight);
    //             }
    //             if (props.radius.bottomRight) {
    //                 output.bottomRightRadius = unit(props.radius.bottomRight);
    //             }
    //         }
    //         if (props.radius.left !== undefined) {
    //             specificRadiusFound = true;
    //             if (props.radius.topLeft) {
    //                 output.topLeftRadius = unit(props.radius.topLeft);
    //             }
    //             if (props.radius.bottomLeft) {
    //                 output.bottomLeftRadius = unit(props.radius.bottomLeft);
    //             }
    //         }
    //         if (props.radius.topRight !== undefined) {
    //             specificRadiusFound = true;
    //             output.topRightRadius = unit(props.radius.topRight);
    //         }
    //         if (props.radius.topLeft !== undefined) {
    //             specificRadiusFound = true;
    //             output.topLeftRadius = unit(props.radius.topLeft);
    //         }
    //         if (props.radius.bottomRight !== undefined) {
    //             specificRadiusFound = true;
    //             output.bottomLeftRadius = unit(props.radius.bottomRight);
    //         }
    //         if (props.radius.topLeft !== undefined) {
    //             specificRadiusFound = true;
    //             output.bottomRightRadius = unit(props.radius.bottomLeft);
    //         }
    //     }
    // }
    // Set fallback border radius if none found
    // if (!globalRadiusFound && !specificRadiusFound) {
    //     output.borderRadius = unit(globalVars.border.radius);
    // }
    //
    // // Set border styles
    // let borderSet = false;
    // if (props.all !== undefined) {
    //     output.borderTop = singleBorder(props.all);
    //     output.borderRight = singleBorder(props.all);
    //     output.borderBottom = singleBorder(props.all);
    //     output.borderLeft = singleBorder(props.all);
    //     borderSet = true;
    // }
    //
    // if (props.topBottom) {
    //     output.borderTop = singleBorder(props.topBottom);
    //     output.borderBottom = singleBorder(props.topBottom);
    //     borderSet = true;
    // }
    //
    // if (props.leftRight) {
    //     output.borderLeft = singleBorder(props.leftRight);
    //     output.borderRight = singleBorder(props.leftRight);
    //     borderSet = true;
    // }
    //
    // if (props.top) {
    //     output.borderTop = singleBorder(props.top);
    //     borderSet = true;
    // }
    //
    // if (props.bottom) {
    //     output.borderBottom = singleBorder(props.bottom);
    //     borderSet = true;
    // }
    //
    // if (props.right) {
    //     output.borderRight = singleBorder(props.right);
    //     borderSet = true;
    // }
    //
    // if (props.left) {
    //     output.borderLeft = singleBorder(props.left);
    //     borderSet = true;
    // }
    //
    // // If nothing was found, look for globals and fallback to global styles.
    // if (!borderSet) {
    //     output.borderStyle = props.style ? props.style : globalVars.border.style;
    //     output.borderColor = props.color ? colorOut(props.color) : colorOut(globalVars.border.color);
    //     output.borderWidth = props.width ? unit(props.width) : unit(globalVars.border.width);
    // }

    // if (debug) {
    //     window.console.log("going out: ", output);
    // }

    return output;
};

export const singleBorder = (styles?: ISingleBorderStyle) => {
    const vars = globalVariables();
    const borderStyles = styles !== undefined ? styles : {};
    return `${borderStyles.style ? borderStyles.style : vars.border.style} ${
        borderStyles.color ? colorOut(borderStyles.color) : colorOut(vars.border.color)
    } ${borderStyles.width ? unit(borderStyles.width) : unit(vars.border.width)}` as any;
};
