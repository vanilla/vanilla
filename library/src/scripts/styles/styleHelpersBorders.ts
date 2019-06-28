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
import {Omit} from "@library/@types/utils";

export interface ISingleBorderStyle {
    color?: ColorValues;
    width?: BorderWidthProperty<TLength>;
    style?: BorderStyleProperty;
}

export interface IBordersWithRadius extends ISingleBorderStyle {
    radius?: radiusValue;
}


export type radiusValue = BorderRadiusProperty<TLength> | string;


export interface borderStylesBySideTop extends ISingleBorderStyle  {
    radius?: radiusValue | ITopBorderRadii,
}
export interface borderStylesBySideBottom extends ISingleBorderStyle  {
    radius?: radiusValue | IBottomBorderRadii,
}

export interface borderStylesBySideRight extends ISingleBorderStyle  {
    radius?: radiusValue | IRightBorderRadii,
}
export interface borderStylesBySideLeft extends ISingleBorderStyle  {
    radius?: radiusValue | ILeftBorderRadii,
}

export interface IBorderStyles extends ISingleBorderStyle {
    all?: ISingleBorderStyle;
    topBottom?: ISingleBorderStyle;
    leftRight?: ISingleBorderStyle;
    top?: borderStylesBySideTop;
    bottom?: borderStylesBySideBottom;
    left?: borderStylesBySideRight;
    right?: borderStylesBySideLeft;
    radius?: radiusValue | IBorderRadii;
}

export type borderType = IBordersWithRadius | IBorderStyles;

// export interface IBorderRadiiDown {
//     left?: BorderRadiusProperty<TLength> | number,
//     right: BorderRadiusProperty<TLength> | number,
// }
//
// export interface IBorderRadiiBottom {
//     left?: BorderRadiusProperty<TLength> | number,
//     right: BorderRadiusProperty<TLength> | number,
// }
//
// export interface IBorderRadiiRight {
//     top?: BorderRadiusProperty<TLength> | number,
//     bottom: BorderRadiusProperty<TLength> | number,
// }
//
// export interface IBorderRadiiTopBottom {
//     top?: BorderRadiusProperty<TLength> | number,
//     bottom: BorderRadiusProperty<TLength> | number,
// }

export interface ITopBorderRadii {left?: radiusValue; right?: radiusValue};
export interface IBottomBorderRadii {left?: radiusValue, right?: radiusValue};
export interface ILeftBorderRadii {top?: radiusValue, bottom?: radiusValue};
export interface IRightBorderRadii {top?: radiusValue, bottom?: radiusValue};

export interface IBorderRadii {
    all?: BorderRadiusProperty<TLength> | number;
    top?: BorderRadiusProperty<TLength> | number | ITopBorderRadii;
    bottom?: BorderRadiusProperty<TLength> | number | IBottomBorderRadii;
    left?: BorderRadiusProperty<TLength> | number | ILeftBorderRadii;
    right?: BorderRadiusProperty<TLength> | number | IRightBorderRadii;
    topRight?: BorderRadiusProperty<TLength> | number;
    topLeft?: BorderRadiusProperty<TLength> | number;
    bottomLeft?: BorderRadiusProperty<TLength> | number;
    bottomRight?: BorderRadiusProperty<TLength> | number;
}

export const borderRadii = (props: IBorderRadii) => {
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

const borderStylesFallbacks = (fallbacks: any[], ultimateFallback, unitFunction?: (value: any) => string) => {
    let output = ultimateFallback;
    const convert = unitFunction ? unitFunction : value => value.toString();
    try {
        const BreakException = {};
        fallbacks.forEach((style, key) => {
            if (!!style) {
                output = style;
                throw BreakException;
            }
        });
    } catch (e) {
        // break out of loop
    }
    return convert(output);
};

export const mergeIfNoGlobal = (globalStyles: IBorderStyles | undefined, overwriteStyles: IBorderStyles | undefined) => {
    if (globalStyles) {
        return merge(globalStyles, overwriteStyles);
    } else {
        return overwriteStyles;
    }
};

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
