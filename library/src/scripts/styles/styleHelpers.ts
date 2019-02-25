/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorHelper, important, percent, px, quote, viewHeight, viewWidth, color, deg } from "csx";
import {
    BackgroundImageProperty,
    BorderRadiusProperty,
    BorderStyleProperty,
    BorderWidthProperty,
    FlexWrapProperty,
    LeftProperty,
    RightProperty,
    BottomProperty,
    PositionProperty,
    DisplayProperty,
    AlignItemsProperty,
    JustifyContentProperty,
    ContentProperty,
    ObjectFitProperty,
} from "csstype";
import { globalVariables } from "@library/styles/globalStyleVars";
import { style, keyframes } from "typestyle";
import { TLength, NestedCSSProperties } from "typestyle/lib/types";
import { formElementsVariables } from "@library/components/forms/formElementStyles";

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

export function fullSizeOfParent() {
    return {
        display: "block",
        position: "absolute",
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
    return style(centeredBackgroundProps());
}

export function backgroundCover(backgroundImage: BackgroundImageProperty) {
    return style({
        ...centeredBackgroundProps(),
        backgroundSize: "cover",
        backgroundImage: backgroundImage.toString(),
    });
}

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

/**
 * A better helper to generate human readable classes generated from TypeStyle.
 *
 * This works like debugHelper but automatically. The generated function behaves just like `style()`
 * but can automatically adds a debug name & allows the first argument to be a string subcomponent name.
 *
 * @example
 * const style = styleFactory("myComponent");
 * const myClass = style({ color: "red" }); // .myComponent-sad421s
 * const mySubClass = style("subcomponent", { color: "red" }) // .myComponent-subcomponent-23sdaf43
 *
 */
export function styleFactory(componentName: string) {
    function styleCreator(subcomponentName: string, ...objects: Array<NestedCSSProperties | undefined>);
    function styleCreator(...objects: Array<NestedCSSProperties | undefined>);
    function styleCreator(...objects: Array<NestedCSSProperties | undefined | string>) {
        if (objects.length === 0) {
            return style();
        }

        let debugName = componentName;
        let styleObjs: Array<NestedCSSProperties | undefined> = objects as any;
        if (typeof objects[0] === "string") {
            const [subcomponentName, ...restObjects] = styleObjs;
            debugName += `-${subcomponentName}`;
            styleObjs = restObjects;
        }

        return style({ $debugName: debugName }, ...styleObjs);
    }

    return styleCreator;
}

/*
 * Color modification based on colors lightness.
 * @param referenceColor - The reference colour to determine if we're in a dark or light context.
 * @param colorToModify - The color you wish to modify
 * @param percentage - The amount you want to mix the two colors
 * @param flip - By default we darken light colours and lighten darks, but if you want to get the opposite result, use this param
 */
export const getColorDependantOnLightness = (
    referenceColor: ColorHelper,
    colorToModify: ColorHelper,
    weight: number,
    flip: boolean = false,
) => {
    if (weight > 1 || weight < 0) {
        throw new Error("mixAmount must be a value between 0 and 1 inclusively.");
    }

    if (referenceColor.lightness() >= 0.5 || flip) {
        // Lighten color
        return colorToModify.mix(color("#000"), 1 - weight) as ColorHelper;
    } else {
        // Darken color
        return colorToModify.mix(color("#fff"), 1 - weight) as ColorHelper;
    }
};

/*
 * Helper to overwrite styles
 * @param theme - The theme overwrites.
 * @param componentName - The name of the component to overwrite
 */
export const componentThemeVariables = (theme: any | undefined, componentName: string) => {
    // const themeVars = get(theme, componentName, {});
    const themeVars = (theme && theme[componentName]) || {};

    const subComponentStyles = (subElementName: string): object => {
        return (themeVars && themeVars[subElementName]) || {};
        // return get(themeVars, subElementName, {});
    };

    return {
        subComponentStyles,
    };
};

export const inheritHeightClass = () => {
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

interface IBorderStyles {
    color?: ColorHelper | "transparent";
    width?: BorderWidthProperty<TLength>;
    style?: BorderStyleProperty;
    radius?: BorderRadiusProperty<TLength>;
}

export const borderStyles = (styles: IBorderStyles) => {
    const vars = globalVariables();
    return {
        borderColor: styles.color ? styles.color.toString() : vars.border.color.toString(),
        borderWidth: unit(styles.width),
        borderStyle: styles.style ? styles.style : vars.border.style,
        borderRadius: unit(styles.radius),
    };
};

export const allLinkStates = (styles: object) => {
    return {
        ...styles,
        $nest: {
            "&:hover": styles,
            "&:active": styles,
            "&:visited": styles,
        },
    };
};

export const unit = (val: string | number | undefined, unitFunction = px) => {
    if (typeof val === "string") {
        return val;
    } else if (!!val && !isNaN(val)) {
        return unitFunction(val as number);
    } else {
        return undefined;
    }
};

interface IMargins {
    top?: string | number;
    right?: string | number;
    bottom?: string | number;
    left?: string | number;
}

export const margins = (styles: IMargins) => {
    return {
        marginTop: unit(styles.top),
        marginRight: unit(styles.right),
        marginBottom: unit(styles.bottom),
        marginLeft: unit(styles.left),
    };
};

interface IPaddings {
    top?: string | number;
    right?: string | number;
    bottom?: string | number;
    left?: string | number;
}

export const paddings = (styles: IPaddings) => {
    return {
        paddingTop: unit(styles.top),
        paddingRight: unit(styles.right),
        paddingBottom: unit(styles.bottom),
        paddingLeft: unit(styles.left),
    };
};

export interface ISpinnerProps {
    color?: ColorHelper;
    dimensions?: string | number;
    thickness?: string | number;
    size?: string | number;
    speed?: string;
}

export const spinnerLoader = (props: ISpinnerProps) => {
    const vars = globalVariables();
    const debug = debugHelper("spinnerLoader");
    const spinnerVars = {
        color: vars.mainColors.primary,
        size: 18,
        thickness: 3,
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
        userSelect: important("none"),
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
    };
};

export const toStringColor = (colorValue: ColorHelper | "transparent") => {
    return typeof colorValue === "string" ? colorValue : colorValue.toString();
};

export interface ILinkStates {
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

    const styles: ILinkStates = {
        default: {
            color: toStringColor(linkColors.default),
        },
        hover: {
            color: toStringColor(linkColors.hover),
        },
        focus: {
            color: toStringColor(linkColors.focus),
        },
        accessibleFocus: {
            color: toStringColor(linkColors.accessibleFocus),
        },
        active: {
            color: toStringColor(linkColors.active),
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
