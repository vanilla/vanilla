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

interface ISingleBorderStyle {
    color?: ColorValues;
    width?: BorderWidthProperty<TLength>;
    style?: BorderStyleProperty;
}

export interface IBordersSameAllSidesStyles extends ISingleBorderStyle {
    radius?: BorderRadiusProperty<TLength>;
}

type radiusType = BorderRadiusProperty<TLength> | IBorderRadii;

interface IBorderStyles extends ISingleBorderStyle {
    all?: ISingleBorderStyle;
    topBottom?: ISingleBorderStyle;
    leftRight?: ISingleBorderStyle;
    top?: ISingleBorderStyle;
    bottom?: ISingleBorderStyle;
    left?: ISingleBorderStyle;
    right?: ISingleBorderStyle;
    radius?: radiusType;
}

interface IBorderRadii {
    all?: BorderRadiusProperty<TLength> | number;
    top?: BorderRadiusProperty<TLength> | number;
    bottom?: BorderRadiusProperty<TLength> | number;
    left?: BorderRadiusProperty<TLength> | number;
    right?: BorderRadiusProperty<TLength> | number;
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

export const borders = (props: IBorderStyles = {}, debug: boolean = false) => {
    const globalVars = globalVariables();

    const output: NestedCSSProperties = {
        borderLeft: undefined,
        borderRight: undefined,
        borderTop: undefined,
        borderBottom: undefined,
    };

    // Set border radii
    let globalRadiusFound = false;
    let specificRadiusFound = false;
    if (props.radius !== undefined) {
        if (typeof props.radius === "string" || typeof props.radius === "number") {
            output.borderRadius = unit(props.radius as BorderRadiusProperty<TLength>);
            globalRadiusFound = true;
        } else {
            if (props.radius.all) {
                globalRadiusFound = true;
                output.borderRadius = unit(props.radius as BorderRadiusProperty<TLength>);
            } else {
                if (props.radius.top) {
                    specificRadiusFound = true;
                    output.borderTopRightRadius = unit(props.radius.top);
                    output.borderTopLeftRadius = unit(props.radius.top);
                }
                if (props.radius.bottom) {
                    specificRadiusFound = true;
                    output.borderBottomRightRadius = unit(props.radius.bottom);
                    output.borderBottomLeftRadius = unit(props.radius.bottom);
                }
                if (props.radius.right) {
                    specificRadiusFound = true;
                    output.borderTopRightRadius = unit(props.radius.right);
                    output.borderBottomRightRadius = unit(props.radius.right);
                }
                if (props.radius.left) {
                    specificRadiusFound = true;
                    output.borderTopLeftRadius = unit(props.radius.left);
                    output.borderBottomLeftRadius = unit(props.radius.left);
                }
                if (props.radius.topRight) {
                    specificRadiusFound = true;
                    output.borderTopRightRadius = unit(props.radius.topRight);
                }
                if (props.radius.topLeft) {
                    specificRadiusFound = true;
                    output.borderTopLeftRadius = unit(props.radius.topLeft);
                }
                if (props.radius.bottomRight) {
                    specificRadiusFound = true;
                    output.borderBottomLeftRadius = unit(props.radius.bottomRight);
                }
                if (props.radius.topLeft) {
                    specificRadiusFound = true;
                    output.borderBottomRightRadius = unit(props.radius.bottomLeft);
                }
            }
        }
    }
    // Set fallback border radius if none found
    if (!globalRadiusFound && !specificRadiusFound) {
        output.borderRadius = unit(globalVars.border.radius);
    }

    // Set border styles
    let borderSet = false;
    if (props.all) {
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

    return output;
};

export const singleBorder = (styles?: ISingleBorderStyle) => {
    const vars = globalVariables();
    const borderStyles = styles !== undefined ? styles : {};
    return `${borderStyles.style ? borderStyles.style : vars.border.style} ${
        borderStyles.color ? colorOut(borderStyles.color) : colorOut(vars.border.color)
    } ${borderStyles.width ? unit(borderStyles.width) : unit(vars.border.width)}` as any;
};
