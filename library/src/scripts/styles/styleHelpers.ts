/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";
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
    BorderWidthProperty,
    BottomProperty,
    ContentProperty,
    DisplayProperty,
    FlexWrapProperty,
    FontSizeProperty,
    FontWeightProperty,
    JustifyContentProperty,
    LeftProperty,
    LineHeightProperty,
    MaxWidthProperty,
    ObjectFitProperty,
    OverflowXProperty,
    PositionProperty,
    RightProperty,
    TextAlignLastProperty,
    TextOverflowProperty,
    TextShadowProperty,
    UserSelectProperty,
    WhiteSpaceProperty,
} from "csstype";
import { ColorHelper, deg, important, percent, px, quote, url, viewHeight, viewWidth } from "csx";
import { keyframes } from "typestyle";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { assetUrl, themeAsset } from "@library/utility/appUtils";
import get from "lodash/get";
import { ColorValues } from "@library/forms/buttonStyles";

export const colorOut = (colorValue: ColorValues) => {
    if (!colorValue) {
        return undefined;
    } else {
        return typeof colorValue === "string" ? colorValue : colorValue.toString();
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

    const spacer = () => {
        return {
            flex: 1,
        };
    };

    return { middle, middleLeft, spacer };
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

export function fullSizeOfParent() {
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

export const textInputSizing = (height: number, fontSize: number, paddingTop: number, fullBorderWidth: number) => {
    return {
        fontSize: unit(fontSize),
        width: percent(100),
        height: unit(height),
        lineHeight: inputLineHeight(height, paddingTop, fullBorderWidth),
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
 * Color modification based on colors lightness.
 * @param referenceColor - The reference colour to determine if we're in a dark or light context.
 * @param colorToModify - The color you wish to modify
 * @param percentage - The amount you want to mix the two colors
 * @param flip - By default we darken light colours and lighten darks, but if you want to get the opposite result, use this param
 */
export const modifyColorBasedOnLightness = (
    referenceColor: ColorHelper,
    colorToModify: ColorHelper,
    weight: number,
    flip: boolean = false,
) => {
    if (weight > 1 || weight < 0) {
        throw new Error("mixAmount must be a value between 0 and 1 inclusively.");
    }
    if (referenceColor.lightness() >= 0.5 && !flip) {
        return colorToModify.darken(weight) as ColorHelper;
    } else {
        return colorToModify.lighten(weight) as ColorHelper;
    }
};

/*
 * Color modification based on saturation.
 * @param referenceColor - The reference colour to determine if we're in a dark or light context.
 * @param colorToModify - The color you wish to modify
 * @param percentage - The amount you want to mix the two colors
 * @param flip - By default we darken light colours and lighten darks, but if you want to get the opposite result, use this param
 */
export const modifyColorSaturationBasedOnLightness = (
    referenceColor: ColorHelper,
    colorToModify: ColorHelper,
    weight: number,
    flip: boolean = false,
) => {
    if (weight > 1 || weight < 0) {
        throw new Error("mixAmount must be a value between 0 and 1 inclusively.");
    }
    if (referenceColor.saturation() <= 0.5 && !flip) {
        // Saturate
        return colorToModify.desaturate(weight) as ColorHelper;
    } else {
        // Darken color
        return colorToModify.saturate(weight) as ColorHelper;
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

export interface IBorderStyles extends ISingleBorderStyle {
    radius?: BorderRadiusProperty<TLength>;
}

export const borders = (props: IBorderStyles = {}) => {
    const vars = globalVariables();
    return {
        borderColor: props.color ? colorOut(props.color as any) : colorOut(vars.border.color),
        borderWidth: props.width ? unit(props.width) : unit(vars.border.width),
        borderStyle: props.style ? props.style : vars.border.style,
        borderRadius: props.radius ? props.radius : vars.border.radius,
    };
};

export const singleBorder = (styles: ISingleBorderStyle = {}) => {
    const vars = globalVariables();
    return `${styles.style ? styles.style : vars.border.style} ${
        styles.color ? colorOut(styles.color) : colorOut(vars.border.color)
    } ${styles.width ? unit(styles.width) : unit(vars.border.width)}` as any;
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
    const visited = get(styles, "visited", {});
    output.$nest["&:visited"] = { ...styles.allStates, ...visited };
    return output;
};

export const allButtonStates = (styles: IButtonStates) => {
    const allStates = get(styles, "allStates", {});
    const noState = get(styles, "noState", {});
    const hover = get(styles, "hover", {});
    const focus = get(styles, "focus", {});
    const focusNotKeyboard = get(styles, "focusNotKeyboard", {});
    const accessibleFocus = get(styles, "accessibleFocus", focus);
    const active = get(styles, "active", {});

    return {
        ...allStates,
        ...noState,
        $nest: {
            "&:hover": { ...allStates, ...hover },
            "&:focus": { ...allStates, ...focus },
            "&:focus:not(.focus-visible)": { ...allStates, ...focusNotKeyboard },
            "&&.focus-visible": { ...allStates, ...accessibleFocus },
            "&:active": { ...allStates, ...active },
        },
    };
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

export interface IMargins {
    top?: string | number;
    right?: string | number;
    bottom?: string | number;
    left?: string | number;
}

export const margins = (styles: IMargins): NestedCSSProperties => {
    return {
        marginTop: unit(styles.top),
        marginRight: unit(styles.right),
        marginBottom: unit(styles.bottom),
        marginLeft: unit(styles.left),
    };
};

export interface IPaddings {
    top?: string | number;
    right?: string | number;
    bottom?: string | number;
    left?: string | number;
    horizontal?: string | number;
    vertical?: string | number;
}

export const paddings = (styles: IPaddings) => {
    const paddingVals = {} as NestedCSSProperties;

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
        const { x, y, blur, spread, inset, shadowColor } = this.props;
        return {
            dropShadow: `${x ? x + " " : ""}${y ? y + " " : ""}${blur ? blur + " " : ""}${
                spread ? spread + " " : ""
            }${shadowColor}${inset ? " inset" : ""}`,
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

export const setAllLinkColors = (overwrites?: ILinkStates) => {
    const vars = globalVariables();
    // We want to default to the standard styles and only overwrite what we want/need
    const linkColors = vars.links.colors;

    if (overwrites) {
        overwrites = {
            default: overwrites.default ? overwrites.default : overwrites.allStates ? overwrites.allStates : undefined,
            hover: overwrites.hover ? overwrites.hover : overwrites.allStates ? overwrites.allStates : undefined,
            focus: overwrites.focus ? overwrites.focus : overwrites.allStates ? overwrites.allStates : undefined,
            accessibleFocus: overwrites.accessibleFocus
                ? overwrites.accessibleFocus
                : overwrites.allStates
                ? overwrites.allStates
                : undefined,
            active: overwrites.accessibleFocus
                ? overwrites.active
                : overwrites.allStates
                ? overwrites.allStates
                : undefined,
        };
    }

    const styles: ILinkStates = {
        default: {
            color: colorOut(linkColors.default),
        },
        hover: {
            color: colorOut(linkColors.hover),
        },
        focus: {
            color: colorOut(linkColors.focus),
        },
        accessibleFocus: {
            color: colorOut(linkColors.accessibleFocus),
        },
        active: {
            color: colorOut(linkColors.active),
        },
        ...overwrites,
    };

    return {
        ...styles.default,
        $nest: {
            "&:hover": styles.hover,
            "&:focus": styles.focus,
            "&.focus-visible": styles.accessibleFocus,
            "&:active": styles.active,
            "&:visited": styles.visited,
        },
    };
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
    weight?: FontWeightProperty;
    lineHeight?: LineHeightProperty<TLength>;
    shadow?: TextShadowProperty;
    align?: TextAlignLastProperty;
}

export const fonts = (props: IFont) => {
    if (props) {
        const size = get(props, "size", undefined);
        const fontWeight = get(props, "weight", undefined);
        const fg = get(props, "color", undefined);
        const lineHeight = get(props, "lineHeight", undefined);
        const textAlign = get(props, "align", undefined);
        const textShadow = get(props, "shadow", undefined);
        return {
            color: fg ? colorOut(fg) : undefined,
            fontSize: size ? unit(size) : undefined,
            fontWeight,
            lineHeight: lineHeight ? unit(lineHeight) : undefined,
            textAlign,
            textShadow,
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
export const states = (styles: IActionStates) => {
    const allStates = get(styles, "allStates", {});
    const hover = get(styles, "hover", {});
    const focus = get(styles, "focus", {});
    const focusNotKeyboard = get(styles, "focusNotKeyboard", focus);
    const accessibleFocus = get(styles, "accessibleFocus", focus);
    const active = get(styles, "active", {});

    return {
        "&:hover": { ...allStates, ...hover },
        "&:focus": { ...allStates, ...focus },
        "&:focus:not(.focus-visible)": { ...allStates, ...focusNotKeyboard },
        "&.focus-visible": { ...allStates, ...accessibleFocus },
        "&:active": { ...allStates, ...active },
    };
};
