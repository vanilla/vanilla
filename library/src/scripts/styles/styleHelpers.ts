/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { log } from "@vanilla/utils";
import {
    AlignItemsProperty,
    AppearanceProperty,
    BackgroundAttachmentProperty,
    BackgroundImageProperty,
    BackgroundPositionProperty,
    BackgroundRepeatProperty,
    BackgroundSizeProperty,
    BorderRadiusProperty,
    BorderStyleProperty,
    BorderTopRightRadiusProperty,
    BorderWidthProperty,
    BottomProperty,
    ContentProperty,
    DisplayProperty,
    FlexWrapProperty,
    FontFamilyProperty,
    FontSizeProperty,
    FontWeightProperty,
    JustifyContentProperty,
    LeftProperty,
    LineHeightProperty,
    MaxWidthProperty,
    ObjectFitProperty,
    OverflowXProperty,
    PointerEventsProperty,
    PositionProperty,
    RightProperty,
    TextAlignLastProperty,
    TextOverflowProperty,
    TextShadowProperty,
    TextTransformProperty,
    UserSelectProperty,
    WhiteSpaceProperty,
} from "csstype";
import { ColorHelper, deg, important, percent, px, quote, url, viewHeight, viewWidth } from "csx";
import { keyframes } from "typestyle";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { assetUrl, themeAsset } from "@library/utility/appUtils";
import { ColorValues } from "@library/forms/buttonStyles";

const fontFallbacks = [
    "-apple-system",
    "BlinkMacSystemFont",
    "HelveticaNeue-Light",
    "Segoe UI",
    "Helvetica Neue",
    "Helvetica",
    "Raleway",
    "Arial",
    "sans-serif",
    "Apple Color Emoji",
    "Segoe UI Emoji",
    "Segoe UI Symbol",
];

export const colorOut = (colorValue: ColorValues | string, makeImportant = false) => {
    if (!colorValue) {
        return undefined;
    } else {
        const output = typeof colorValue === "string" ? colorValue : colorValue.toString();
        return makeImportant ? important(output) : output;
    }
};

export function flexHelper() {
    const middle = (wrap = false) => {
        return {
            display: "flex" as DisplayProperty,
            alignItems: "center" as AlignItemsProperty,
            justifyContent: "center" as JustifyContentProperty,
            flexWrap: (wrap ? "wrap" : "nowrap") as FlexWrapProperty,
        };
    };

    const middleLeft = (wrap = false) => {
        return {
            display: "flex" as DisplayProperty,
            alignItems: "center" as AlignItemsProperty,
            justifyContent: "flex-start" as JustifyContentProperty,
            flexWrap: wrap ? "wrap" : ("nowrap" as FlexWrapProperty),
        };
    };

    return { middle, middleLeft };
}

export function srOnly() {
    return {
        position: important("absolute"),
        display: important("block"),
        width: important(px(1).toString()),
        height: important(px(1).toString()),
        padding: important(px(0).toString()),
        margin: important(px(-1).toString()),
        overflow: important("hidden"),
        clip: important(`rect(0, 0, 0, 0)`),
        border: important(px(0).toString()),
    };
}

export function fakeBackgroundFixed() {
    return {
        content: quote(""),
        display: "block",
        position: "fixed",
        top: px(0),
        left: px(0),
        width: viewWidth(100),
        height: viewHeight(100),
    };
}

export function fontFamilyWithDefaults(fontFamilies: string[]): string {
    return fontFamilies
        .concat(fontFallbacks)
        .map(font => (font.includes(" ") && !font.includes('"') ? `"${font}"` : font))
        .join(", ");
}

export function fullSizeOfParent(): NestedCSSProperties {
    return {
        position: "absolute",
        display: "block",
        top: px(0),
        left: px(0),
        width: percent(100),
        height: percent(100),
    };
}

export function centeredBackgroundProps() {
    return {
        backgroundPosition: `50% 50%`,
        backgroundRepeat: "no-repeat",
    };
}

export function centeredBackground() {
    const style = styleFactory("centeredBackground");
    return style(centeredBackgroundProps());
}

export function inputLineHeight(height: number, paddingTop: number, fullBorderWidth: number) {
    return unit(height - (2 * paddingTop + fullBorderWidth));
}

export const textInputSizingFromSpacing = (fontSize: number, paddingTop: number, fullBorderWidth: number) => {
    return {
        fontSize: unit(fontSize),
        width: percent(100),
        lineHeight: 1.5,
        ...paddings({
            top: unit(paddingTop),
            bottom: unit(paddingTop),
            left: unit(paddingTop * 2),
            right: unit(paddingTop * 2),
        }),
    };
};

export const textInputSizingFromFixedHeight = (height: number, fontSize: number, fullBorderWidth: number) => {
    const paddingTop = (height - fullBorderWidth - fontSize * 1.5) / 2;
    return {
        fontSize: unit(fontSize),
        width: percent(100),
        lineHeight: 1.5,
        ...paddings({
            top: unit(paddingTop),
            bottom: unit(paddingTop),
            left: unit(paddingTop * 2),
            right: unit(paddingTop * 2),
        }),
    };
};

// must be nested
export const placeholderStyles = (styles: NestedCSSProperties) => {
    return {
        "&::-webkit-input-placeholder": {
            $unique: true,
            ...styles,
        },
        "&::-moz-placeholder": {
            $unique: true,
            ...styles,
        },
        "&::-ms-input-placeholder": {
            $unique: true,
            ...styles,
        },
    };
};

/*
 * Helper to generate human readable classes generated from TypeStyle
 * @param componentName - The component's name.
 */
export const debugHelper = (componentName: string) => {
    return {
        name: (subElementName?: string) => {
            if (subElementName) {
                return { $debugName: `${componentName}-${subElementName}` };
            } else {
                return { $debugName: componentName };
            }
        },
    };
};

/*
 * Check if it's a light color or dark color based on lightness
 * @param color - The color we're checking
 */
export const isLightColor = (color: ColorHelper) => {
    return color.lightness() >= 0.5;
};

/*
 * Color modification based on colors lightness.
 * @param color - The color we're checking and modifying
 * @param weight - The amount you want to mix the two colors (value from 0 to 1)
 * @param flip - By default we darken light colours and lighten dark colors, but if you want to get the opposite result, use this param
 * Note, however, that we do not check if you've reached a maximum. Example: If you want to darken pure black, you get back pure black.
 */
export const modifyColorBasedOnLightness = (color: ColorHelper, weight: number, flip: boolean = false) => {
    let output;
    if (weight > 1 || weight < 0) {
        throw new Error("mixAmount must be a value between 0 and 1 inclusively.");
    }
    const isLight = isLightColor(color);
    if ((isLight && !flip) || (!isLight && flip)) {
        output = color.darken(weight) as ColorHelper;
    } else {
        output = color.lighten(weight) as ColorHelper;
    }
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
export const emphasizeLightness = (color: ColorHelper, weight: number, flipIfMax: boolean = true) => {
    const colorLightness = color.lightness();
    let weightOffset = 1;
    if (colorLightness < 0.4) {
        weightOffset = Math.abs(colorLightness - 0.5) * 20;
    }

    const weightCurved = weight * weightOffset;
    const colorDarker = color.darken(weightCurved) as ColorHelper;
    const colorLighter = color.lighten(weightCurved) as ColorHelper;

    if (isLightColor(color)) {
        if (colorLightness + weightCurved > 1 && flipIfMax) {
            return colorDarker;
        } else {
            return colorLighter;
        }
    } else {
        if (colorLightness - weightCurved > 0 && flipIfMax) {
            return colorDarker;
        } else {
            return colorLighter;
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
export const modifyColorSaturationBasedOnLightness = (color: ColorHelper, weight: number, flip: boolean = false) => {
    if (weight > 1 || weight < 0) {
        throw new Error("mixAmount must be a value between 0 and 1 inclusively.");
    }

    const isSaturated = color.lightness() >= 0.5;

    if ((isSaturated && !flip) || (!isSaturated && flip)) {
        // Desaturate
        return color.desaturate(weight) as ColorHelper;
    } else {
        // Saturate
        return color.saturate(weight) as ColorHelper;
    }
};

export const inheritHeightClass = () => {
    const style = styleFactory("inheritHeight");
    return style({
        display: "flex",
        flexDirection: "column",
        flexGrow: 1,
    });
};

export const defaultTransition = (...properties) => {
    const vars = globalVariables();
    properties = properties.length === 0 ? ["all"] : properties;
    return {
        transition: `${properties.map((prop, index) => {
            return `${prop} ${vars.animation.defaultTiming} ${vars.animation.defaultEasing}${
                index === properties.length ? ", " : ""
            }`;
        })}`,
    };
};

const spinnerOffset = 73;
const spinnerLoaderAnimation = keyframes({
    "0%": { transform: `rotate(${deg(spinnerOffset)})` },
    "100%": { transform: `rotate(${deg(360 + spinnerOffset)})` },
});

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

const ifExistsWithFallback = checkProp => {
    if (checkProp && checkProp.length > 0) {
        const next = checkProp.pop();
        return next ? next : ifExistsWithFallback(checkProp);
    } else {
        return undefined;
    }
};

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

export interface IButtonStates {
    allStates?: object; // Applies to all
    noState?: object; // Applies to stateless link
    hover?: object;
    focus?: object;
    focusNotKeyboard?: object; // Focused, not through keyboard
    accessibleFocus?: object; // Optionally different state for keyboard accessed element. Will default to "focus" state if not set.
    active?: object;
}
export interface ILinkStates extends IButtonStates {
    visited?: object;
}

export const allLinkStates = (styles: ILinkStates) => {
    const output = allButtonStates(styles);
    const visited = styles.visited !== undefined ? styles.visited : {};
    output.$nest["&:visited"] = { ...styles.allStates, ...visited };
    return output;
};

export const allButtonStates = (styles: IButtonStates, nested?: object, debugMode?: boolean) => {
    const allStates = styles.allStates !== undefined ? styles.allStates : {};
    const noState = styles.noState !== undefined ? styles.noState : {};

    const output = {
        ...allStates,
        ...noState,
        $nest: {
            "&:hover": { ...allStates, ...styles.hover },
            "&:focus": { ...allStates, ...styles.focus },
            "&:focus:not(.focus-visible)": { ...allStates, ...styles.focusNotKeyboard },
            "&&.focus-visible": { ...allStates, ...styles.accessibleFocus },
            "&:active": { ...allStates, ...styles.active },
            ...nested,
        },
    };

    if (debugMode) {
        log("allButtonStates: ");
        log("style: ", styles);
        log("nested: ", nested);
        log("output: ", output);
    }

    return output;
};

export const unit = (val: string | number | undefined, unitFunction = px) => {
    if (typeof val === "string") {
        return val;
    } else if (val !== undefined && val !== null && !isNaN(val)) {
        return unitFunction(val as number);
    } else {
        return val;
    }
};

export const negative = val => {
    if (typeof val === "string") {
        val = val.trim();
        if (val.startsWith("-")) {
            return val.substring(1, val.length).trim();
        } else {
            return `-${val}`;
        }
    } else if (!!val && !isNaN(val)) {
        return val * -1;
    } else {
        return val;
    }
};

export interface IMargins {
    top?: string | number | undefined;
    right?: string | number | undefined;
    bottom?: string | number | undefined;
    left?: string | number | undefined;
    horizontal?: string | number | undefined;
    vertical?: string | number | undefined;
    all?: string | number | undefined;
}

export const margins = (styles: IMargins): NestedCSSProperties => {
    const marginVals = {} as NestedCSSProperties;

    if (styles.all !== undefined) {
        marginVals.marginTop = unit(styles.all);
        marginVals.marginRight = unit(styles.all);
        marginVals.marginBottom = unit(styles.all);
        marginVals.marginLeft = unit(styles.all);
    }

    if (styles.vertical !== undefined) {
        marginVals.marginTop = unit(styles.vertical);
        marginVals.marginBottom = unit(styles.vertical);
    }

    if (styles.horizontal !== undefined) {
        marginVals.marginLeft = unit(styles.horizontal);
        marginVals.marginRight = unit(styles.horizontal);
    }

    if (styles.top !== undefined) {
        marginVals.marginTop = unit(styles.top);
    }

    if (styles.right !== undefined) {
        marginVals.marginRight = unit(styles.right);
    }

    if (styles.bottom !== undefined) {
        marginVals.marginBottom = unit(styles.bottom);
    }

    if (styles.left !== undefined) {
        marginVals.marginLeft = unit(styles.left);
    }

    return marginVals as NestedCSSProperties;
};

export interface IPaddings {
    top?: string | number;
    right?: string | number;
    bottom?: string | number;
    left?: string | number;
    horizontal?: string | number;
    vertical?: string | number;
    all?: string | number;
}

export const paddings = (styles: IPaddings) => {
    const paddingVals = {} as NestedCSSProperties;

    if (!styles) {
        return paddingVals;
    }

    if (styles.all !== undefined) {
        paddingVals.paddingTop = unit(styles.all);
        paddingVals.paddingRight = unit(styles.all);
        paddingVals.paddingBottom = unit(styles.all);
        paddingVals.paddingLeft = unit(styles.all);
    }

    if (styles.vertical !== undefined) {
        paddingVals.paddingTop = unit(styles.vertical);
        paddingVals.paddingBottom = unit(styles.vertical);
    }

    if (styles.horizontal !== undefined) {
        paddingVals.paddingLeft = unit(styles.horizontal);
        paddingVals.paddingRight = unit(styles.horizontal);
    }

    if (styles.top !== undefined) {
        paddingVals.paddingTop = unit(styles.top);
    }

    if (styles.right !== undefined) {
        paddingVals.paddingRight = unit(styles.right);
    }

    if (styles.bottom !== undefined) {
        paddingVals.paddingBottom = unit(styles.bottom);
    }

    if (styles.left !== undefined) {
        paddingVals.paddingLeft = unit(styles.left);
    }

    return paddingVals as NestedCSSProperties;
};

export interface ISpinnerProps {
    color?: ColorHelper;
    dimensions?: string | number;
    thickness?: string | number;
    size?: string | number;
    speed?: string;
}

export const spinnerLoader = (props: ISpinnerProps) => {
    const debug = debugHelper("spinnerLoader");
    const globalVars = globalVariables();
    const spinnerVars = {
        color: props.color || globalVars.mainColors.primary,
        size: props.size || 18,
        thickness: props.thickness || 3,
        speed: "0.7s",
        ...props,
    };
    return {
        ...debug.name("spinner"),
        position: "relative" as PositionProperty,
        content: quote("") as ContentProperty,
        ...defaultTransition("opacity"),
        display: "block" as DisplayProperty,
        width: unit(spinnerVars.size),
        height: unit(spinnerVars.size),
        borderRadius: percent(50),
        borderTop: `${unit(spinnerVars.thickness)} solid ${spinnerVars.color.toString()}`,
        borderRight: `${unit(spinnerVars.thickness)} solid ${spinnerVars.color.fade(0.3).toString()}`,
        borderBottom: `${unit(spinnerVars.thickness)} solid ${spinnerVars.color.fade(0.3).toString()}`,
        borderLeft: `${unit(spinnerVars.thickness)} solid ${spinnerVars.color.fade(0.3).toString()}`,
        transform: "translateZ(0)",
        animation: `spillerLoader ${spinnerVars.speed} infinite ease-in-out`,
        animationName: spinnerLoaderAnimation,
        animationDuration: spinnerVars.speed,
        animationIterationCount: "infinite",
        animationTimingFunction: "ease-in-out",
    };
};

export const absolutePosition = {
    topRight: (top: string | number = "0", right: RightProperty<TLength> = px(0)) => {
        return {
            position: "absolute" as PositionProperty,
            top: unit(top),
            right: unit(right),
        };
    },
    topLeft: (top: string | number = "0", left: LeftProperty<TLength> = px(0)) => {
        return {
            position: "absolute" as PositionProperty,
            top: unit(top),
            left: unit(left),
        };
    },
    bottomRight: (bottom: BottomProperty<TLength> = px(0), right: RightProperty<TLength> = px(0)) => {
        return {
            position: "absolute" as PositionProperty,
            bottom: unit(bottom),
            right: unit(right),
        };
    },
    bottomLeft: (bottom: BottomProperty<TLength> = px(0), left: LeftProperty<TLength> = px(0)) => {
        return {
            position: "absolute" as PositionProperty,
            bottom: unit(bottom),
            left: unit(left),
        };
    },
    middleOfParent: () => {
        return {
            position: "absolute" as PositionProperty,
            display: "block",
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            maxHeight: percent(100),
            maxWidth: percent(100),
            margin: "auto",
        };
    },
    middleLeftOfParent: (left: LeftProperty<TLength> = px(0)) => {
        return {
            position: "absolute" as PositionProperty,
            display: "block",
            top: 0,
            left,
            bottom: 0,
            maxHeight: percent(100),
            maxWidth: percent(100),
            margin: "auto 0",
        };
    },
    middleRightOfParent: (right: RightProperty<TLength> = px(0)) => {
        return {
            position: "absolute" as PositionProperty,
            display: "block",
            top: 0,
            right,
            bottom: 0,
            maxHeight: percent(100),
            maxWidth: percent(100),
            margin: "auto 0",
        };
    },
    fullSizeOfParent: () => {
        return {
            display: "block",
            position: "absolute" as PositionProperty,
            top: px(0),
            left: px(0),
            width: percent(100),
            height: percent(100),
        };
    },
};

export function sticky(): NestedCSSProperties {
    return {
        position: ["-webkit-sticky", "sticky"],
    };
}

export interface IDropShadowShorthand {
    nonColorProps: string;
    color: string;
}

export interface IDropShadow {
    x: string;
    y: string;
    blur: string;
    spread: string;
    inset: boolean;
    color: string;
}

export const dropShadow = (vals: IDropShadowShorthand | IDropShadow | "none" | "initial" | "inherit") => {
    if (typeof vals !== "object") {
        return { dropShadow: vals };
    } else if ("nonColorProps" in vals) {
        return { dropShadow: `${vals.nonColorProps} ${vals.color.toString()}` };
    } else {
        const { x, y, blur, spread, inset, color } = vals;
        return {
            dropShadow: `${x ? x + " " : ""}${y ? y + " " : ""}${blur ? blur + " " : ""}${
                spread ? spread + " " : ""
            }${color}${inset ? " inset" : ""}`,
        };
    }
};

export const disabledInput = () => {
    const formElementVars = formElementsVariables();
    return {
        pointerEvents: important("none"),
        ...userSelect("none", true),
        cursor: important("default"),
        opacity: important((formElementVars.disabled.opacity as any).toString()),
    };
};

export const objectFitWithFallback = () => {
    return {
        position: "absolute" as PositionProperty,
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
        margin: "auto",
        height: "auto",
        width: percent(100),
        $nest: {
            "@supports (object-fit: cover)": {
                objectFit: "cover" as ObjectFitProperty,
                objectPosition: "center",
                height: percent(100),
            },
        },
    } as NestedCSSProperties;
};

export interface ILinkStates {
    allStates?: object; // Applies to all
    default?: object;
    hover?: object;
    focus?: object;
    accessibleFocus?: object;
    active?: object;
    visited?: object;
}

const linkStyleFallbacks = (
    specificOverwrite: undefined | ColorHelper | string,
    defaultOverwrite: undefined | ColorHelper | string,
    globalDefault: undefined | ColorHelper | string,
) => {
    if (specificOverwrite) {
        return specificOverwrite as ColorValues;
    } else if (defaultOverwrite) {
        return defaultOverwrite as ColorValues;
    } else {
        return globalDefault as ColorValues;
    }
};

interface ILinkColorOverwrites {
    default?: ColorValues;
    hover?: ColorValues;
    focus?: ColorValues;
    accessibleFocus?: ColorValues;
    active?: ColorValues;
    visited?: ColorValues;
    allStates?: ColorValues;
}

export const setAllLinkColors = (overwriteValues?: ILinkColorOverwrites) => {
    const vars = globalVariables();
    // We want to default to the standard styles and only overwrite what we want/need
    const linkColors = vars.links.colors;
    const overwrites = overwriteValues ? overwriteValues : {};
    const mergedColors = {
        default: linkStyleFallbacks(overwrites.default, overwrites.allStates, linkColors.default),
        hover: linkStyleFallbacks(overwrites.hover, overwrites.allStates, linkColors.hover),
        focus: linkStyleFallbacks(overwrites.focus, overwrites.allStates, linkColors.focus),
        accessibleFocus: linkStyleFallbacks(
            overwrites.accessibleFocus,
            overwrites.allStates,
            linkColors.accessibleFocus,
        ),
        active: linkStyleFallbacks(overwrites.active, overwrites.allStates, linkColors.active),
        visited: linkStyleFallbacks(overwrites.visited, overwrites.allStates, linkColors.visited),
    };

    const styles = {
        default: {
            color: colorOut(mergedColors.default),
        },
        hover: {
            color: colorOut(mergedColors.hover),
        },
        focus: {
            color: colorOut(mergedColors.focus),
        },
        accessibleFocus: {
            color: colorOut(mergedColors.accessibleFocus),
        },
        active: {
            color: colorOut(mergedColors.active),
        },
        visited: {
            color: colorOut(mergedColors.visited),
        },
    };

    const final = {
        color: styles.default.color,
        nested: {
            "&&:hover": styles.hover,
            "&&:focus": styles.focus,
            "&&.focus-visible": styles.accessibleFocus,
            "&&:active": styles.active,
            "&:visited": styles.visited,
        },
    };

    return final;
};

export const singleLineEllipsis = () => {
    return {
        whiteSpace: "nowrap" as WhiteSpaceProperty,
        textOverflow: "ellipsis" as TextOverflowProperty,
        overflowX: "hidden" as OverflowXProperty,
        maxWidth: percent(100) as MaxWidthProperty<TLength>,
    };
};
export const longWordEllipsis = () => {
    return {
        textOverflow: "ellipsis" as TextOverflowProperty,
        overflowX: "hidden" as OverflowXProperty,
        maxWidth: percent(100) as MaxWidthProperty<TLength>,
    };
};

export const appearance = (value: AppearanceProperty = "none", isImportant: boolean = false) => {
    const val = (isImportant ? important(value) : value) as any;
    return {
        "-webkit-appearance": val,
        "-moz-appearance": val,
        appearance: val,
    };
};

export const userSelect = (value: UserSelectProperty = "none", isImportant: boolean = false) => {
    const val = (isImportant ? important(value) : value) as any;
    return {
        "-webkit-user-select": val,
        "-moz-user-select": val,
        "-ms-user-select": val,
        userSelect: val,
    };
};

export interface IFont {
    color?: ColorValues;
    size?: FontSizeProperty<TLength>;
    weight?: FontWeightProperty | number;
    lineHeight?: LineHeightProperty<TLength>;
    shadow?: TextShadowProperty;
    align?: TextAlignLastProperty;
    family?: FontFamilyProperty[];
    transform?: TextTransformProperty;
}

export const fonts = (props: IFont) => {
    if (props) {
        const fontSize = props.size !== undefined ? unit(props.size) : undefined;
        const fontWeight = props.weight !== undefined ? props.weight : undefined;
        const color = props.color !== undefined ? colorOut(props.color) : undefined;
        const lineHeight = props.lineHeight !== undefined ? props.lineHeight : undefined;
        const textAlign = props.align !== undefined ? props.align : undefined;
        const textShadow = props.shadow !== undefined ? props.shadow : undefined;
        const fontFamily = props.family !== undefined ? fontFamilyWithDefaults(props.family) : undefined;
        const textTransform = props.transform !== undefined ? props.transform : undefined;
        return {
            color,
            fontSize,
            fontWeight,
            lineHeight,
            textAlign,
            textShadow,
            fontFamily,
            textTransform,
        } as NestedCSSProperties;
    } else {
        return {} as NestedCSSProperties;
    }
};

export interface IBackground {
    color: ColorValues;
    attachment?: BackgroundAttachmentProperty;
    position?: BackgroundPositionProperty<TLength>;
    repeat?: BackgroundRepeatProperty;
    size?: BackgroundSizeProperty<TLength>;
    image?: BackgroundImageProperty;
    fallbackImage?: BackgroundImageProperty;
}

export const getBackgroundImage = (image?: BackgroundImageProperty, fallbackImage?: BackgroundImageProperty) => {
    // Get either image or fallback
    const workingImage = image ? image : fallbackImage;
    if (!workingImage) {
        return;
    }

    if (workingImage.charAt(0) === "~") {
        // Relative path to theme folder
        return themeAsset(workingImage.substr(1, workingImage.length - 1));
    }

    if (workingImage.startsWith('"data:image/')) {
        return workingImage;
    }

    // Fallback to a general asset URL.
    const assetImage = assetUrl(workingImage);
    return assetImage;
};

export const background = (props: IBackground) => {
    const image = getBackgroundImage(props.image, props.fallbackImage);
    return {
        backgroundColor: props.color ? colorOut(props.color) : undefined,
        backgroundAttachment: props.attachment || undefined,
        backgroundPosition: props.position || `50% 50%`,
        backgroundRepeat: props.repeat || "no-repeat",
        backgroundSize: props.size || "cover",
        backgroundImage: image ? url(image) : undefined,
    };
};

export interface IStates {
    hover?: object;
    focus?: object;
    active?: object;
    accessibleFocus?: object;
}

export interface IStatesAll {
    allStates?: object;
}

// Similar to ILinkStates, but can be button or link, so we don't have link specific states here and not specific to colors
export interface IActionStates {
    noState?: object;
    allStates?: object; // Applies to all
    hover?: object;
    focus?: object;
    focusNotKeyboard?: object; // Focused, not through keyboard?: object;
    accessibleFocus?: object; // Optionally different state for keyboard accessed element. Will default to "focus" state if not set.
    active?: object;
}

/*
 * Helper to write CSS state styles. Note this one is for buttons or links
 * *** You must use this inside of a "$nest" ***
 */
export const buttonStates = (styles: IActionStates, nest?: object) => {
    const allStates = styles.allStates !== undefined ? styles.allStates : {};
    const hover = styles.hover !== undefined ? styles.hover : {};
    const focus = styles.focus !== undefined ? styles.focus : {};
    const focusNotKeyboard = styles.focusNotKeyboard !== undefined ? styles.focusNotKeyboard : {};
    const accessibleFocus = styles.accessibleFocus !== undefined ? styles.accessibleFocus : {};
    const active = styles.active !== undefined ? styles.active : {};
    const noState = styles.noState !== undefined ? styles.noState : {};

    return {
        "&": { ...allStates, ...noState },
        "&:hover": { ...allStates, ...hover },
        "&:focus": { ...allStates, ...focus },
        "&:focus:not(.focus-visible)": { ...allStates, ...focusNotKeyboard },
        "&.focus-visible": { ...allStates, ...accessibleFocus },
        "&&:active": { ...allStates, ...active },
        ...nest,
    };
};

export const pointerEvents = (value: PointerEventsProperty = "none") => {
    return {
        pointerEvents: important(value),
    };
};

export const pointerEventsClass = (value: PointerEventsProperty = "none") => {
    const style = styleFactory("pointerEvents");
    return style(pointerEvents(value));
};

export const visibility = useThemeCache(() => {
    const style = styleFactory("visibility");
    const onEmpty = (nest?: object) => {
        return style("onEmpty", {
            $nest: {
                "&:empty": {
                    display: "none",
                },
                ...nest,
            },
        });
    };

    const displayNone = style("displayNone", {
        display: important("none"),
    });

    return {
        onEmpty,
        displayNone,
    };
});
