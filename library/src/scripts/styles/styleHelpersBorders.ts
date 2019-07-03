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

export interface IRadiusDeclaration {
    all?: BorderRadiusValue;
    top?: BorderRadiusValue | ITopBorderRadii;
    bottom?: BorderRadiusValue | IBottomBorderRadii;
    left?: BorderRadiusValue | ILeftBorderRadii;
    right?: BorderRadiusValue | IRightBorderRadii;
    topRight?: BorderRadiusValue;
    topLeft?: BorderRadiusValue;
    bottomLeft?: BorderRadiusValue;
    bottomRight?: BorderRadiusValue;
}

export interface IBorderRadiiDeclaration extends IBorderRadiusOutput, IRadiusDeclaration {
    radius?: IRadiusDeclaration | BorderRadiusValue;
}

export interface IBorderRadiusOutput {
    borderTopRightRadius?: BorderRadiusValue;
    borderTopLeftRadius?: BorderRadiusValue;
    borderBottomRightRadius?: BorderRadiusValue;
    borderBottomLeftRadius?: BorderRadiusValue;
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
export interface IBorderFinalStyles extends IBorderRadiusOutput {
    borderTop?: BorderRadiusValue;
    borderRight?: BorderRadiusValue;
    borderBottom?: BorderRadiusValue;
    borderLeft?: BorderRadiusValue;
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

export const borders = (props: IBorderStyles = {}, debug: boolean = false) => {
    const globalVars = globalVariables();
    //
    // if (debug) {
    //     window.console.log("coming in: ", props);
    // }

    const output: NestedCSSProperties = {
        borderLeft: undefined,
        borderRight: undefined,
        borderTop: undefined,
        borderBottom: undefined,
    };


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

    // Set border radii
    let globalRadiusFound = false;
    let specificRadiusFound = false;
    if (props.radius !== undefined) {
        if (typeof props.radius === "string" || typeof props.radius === "number") {
            output.borderRadius = unit(props.radius as BorderRadiusProperty<TLength>);
            globalRadiusFound = true;
        } else {
            if (props.radius.all !== undefined) {
                globalRadiusFound = true;
                output.borderRadius = unit(props.radius as BorderRadiusProperty<TLength>);
            }
            if (props.radius.top !== undefined) {
                specificRadiusFound = true;
                output.borderTopRightRadius = unit(props.radius.topRight);
                output.borderTopLeftRadius = unit(props.radius.topLeft);
                if (props.radius.topRight) {
                    output.borderTopRightRadius = unit(props.radius.topRight);
                }
                if (props.radius.topLeft) {
                    output.borderTopLeftRadius = unit(props.radius.topLeft);
                }
            }
            if (props.radius.bottom !== undefined) {
                specificRadiusFound = true;
                if (props.radius.bottomRight) {
                    output.borderBottomRightRadius = unit(props.radius.bottomRight);
                }
                if (props.radius.bottomLeft) {
                    output.borderBottomLeftRadius = unit(props.radius.bottomLeft);
                }
            }
            if (props.radius.right !== undefined) {
                specificRadiusFound = true;
                if (props.radius.topRight) {
                    output.borderTopRightRadius = unit(props.radius.topRight);
                }
                if (props.radius.bottomRight) {
                    output.borderBottomRightRadius = unit(props.radius.bottomRight);
                }
            }
            if (props.radius.left !== undefined) {
                specificRadiusFound = true;
                if (props.radius.topLeft) {
                    output.borderTopLeftRadius = unit(props.radius.topLeft);
                }
                if (props.radius.bottomLeft) {
                    output.borderBottomLeftRadius = unit(props.radius.bottomLeft);
                }
            }
            if (props.radius.topRight !== undefined) {
                specificRadiusFound = true;
                output.borderTopRightRadius = unit(props.radius.topRight);
            }
            if (props.radius.topLeft !== undefined) {
                specificRadiusFound = true;
                output.borderTopLeftRadius = unit(props.radius.topLeft);
            }
            if (props.radius.bottomRight !== undefined) {
                specificRadiusFound = true;
                output.borderBottomLeftRadius = unit(props.radius.bottomRight);
            }
            if (props.radius.topLeft !== undefined) {
                specificRadiusFound = true;
                output.borderBottomRightRadius = unit(props.radius.bottomLeft);
            }
        }
    }
    // Set fallback border radius if none found
    if (!globalRadiusFound && !specificRadiusFound) {
        output.borderRadius = unit(globalVars.border.radius);
    }

    // Set border styles
    let borderSet = false;
    if (props.all !== undefined) {
        output.borderTop = singleBorder(props.all);
        output.borderRight = singleBorder(props.all);
        output.borderBottom = singleBorder(props.all);
        output.borderLeft = singleBorder(props.all);
        borderSet = true;
    }

    if (props.topBottom) {
        output.borderTop = singleBorder(props.topBottom);
        output.borderBottom = singleBorder(props.topBottom);
        borderSet = true;
    }

    if (props.leftRight) {
        output.borderLeft = singleBorder(props.leftRight);
        output.borderRight = singleBorder(props.leftRight);
        borderSet = true;
    }

    if (props.top) {
        output.borderTop = singleBorder(props.top);
        borderSet = true;
    }

    if (props.bottom) {
        output.borderBottom = singleBorder(props.bottom);
        borderSet = true;
    }

    if (props.right) {
        output.borderRight = singleBorder(props.right);
        borderSet = true;
    }

    if (props.left) {
        output.borderLeft = singleBorder(props.left);
        borderSet = true;
    }

    // If nothing was found, look for globals and fallback to global styles.
    if (!borderSet) {
        output.borderStyle = props.style ? props.style : globalVars.border.style;
        output.borderColor = props.color ? colorOut(props.color) : colorOut(globalVars.border.color);
        output.borderWidth = props.width ? unit(props.width) : unit(globalVars.border.width);
    }

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
