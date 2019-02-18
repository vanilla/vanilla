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
    MarginTopProperty,
    MarginRightProperty,
    MarginBottomProperty,
    MarginLeftProperty,
    PaddingTopProperty,
    PaddingRightProperty,
    PaddingBottomProperty,
    PaddingLeftProperty,
    TopProperty,
    LeftProperty,
    RightProperty,
    BottomProperty,
    PositionProperty,
} from "csstype";
import { globalVariables } from "@library/styles/globalStyleVars";
import { style, keyframes } from "typestyle";
import { TLength } from "typestyle/lib/types";
import { borderRadius } from "react-select/lib/theme";

export function flexHelper() {
    const middle = (wrap = false) => {
        return {
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            flexWrap: wrap ? "wrap" : ("nowrap" as FlexWrapProperty),
        };
    };

    const middleLeft = (wrap = false) => {
        return {
            display: "flex",
            alignItems: "center",
            justifyContent: "flex-start",
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
        return colorToModify.mix(color("#000"), 1 - weight);
    } else {
        // Darken color
        return colorToModify.mix(color("#fff"), 1 - weight);
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

    const subComponentStyles = (subElementName: string) => {
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

const vars = globalVariables();

export const defaultTransition = (...properties) => {
    const propLength = properties.length;
    return `${properties.map((prop, index) => {
        return `${prop} ${vars.animation.defaultTiming} ${vars.animation.defaultEasing}${
            index === propLength ? ", " : ""
        }`;
    })}`;
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
    return {
        borderColor: styles.color ? styles.color.toString() : vars.border.color.toString(),
        borderWidth: addUnitIfNumber(styles.width),
        borderStyle: styles.style ? styles.style : vars.border.style,
        borderRadius: addUnitIfNumber(styles.radius),
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

export const addUnitIfNumber = (val: string | number | undefined, unitFunction = px) => {
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
        marginTop: addUnitIfNumber(styles.top),
        marginRight: addUnitIfNumber(styles.right),
        marginBottom: addUnitIfNumber(styles.bottom),
        marginLeft: addUnitIfNumber(styles.left),
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
        paddingTop: addUnitIfNumber(styles.top),
        paddingRight: addUnitIfNumber(styles.right),
        paddingBottom: addUnitIfNumber(styles.bottom),
        paddingLeft: addUnitIfNumber(styles.left),
    };
};

export const spinnerLoader = (
    spinnerColor: ColorHelper = vars.mainColors.primary,
    dimensions = px(18),
    thicknesss = px(3),
    speed = "0.7s",
) => {
    const debug = debugHelper("spinnerLoader");
    return {
        ...debug.name("spinner"),
        position: "relative",
        content: quote(""),
        transition: defaultTransition("opacity"),
        display: "block",
        width: dimensions,
        height: dimensions,
        borderRadius: percent(50),
        borderTop: `${thicknesss} solid ${spinnerColor.toString()}`,
        borderRight: `${thicknesss} solid ${spinnerColor.fade(0.3).toString()}`,
        borderBottom: `${thicknesss} solid ${spinnerColor.fade(0.3).toString()}`,
        borderLeft: `${thicknesss} solid ${spinnerColor.fade(0.3).toString()}`,
        transform: "translateZ(0)",
        animation: `spillerLoader ${speed} infinite ease-in-out`,
        animationName: spinnerLoaderAnimation,
        animationDuration: speed,
        animationIterationCount: "infinite",
        animationTimingFunction: "ease-in-out",
    };
};

export function fullSizeOfParent() {
    return {
        display: "block",
        position: "absolute" as PositionProperty,
        top: px(0),
        left: px(0),
        width: percent(100),
        height: percent(100),
    };
}

export const absolutePosition = {
    topRight: (top: string | number = "0", right: RightProperty<TLength> = px(0)) => {
        return {
            position: "absolute" as PositionProperty,
            top,
            right,
        };
    },
    topLeft: (top: string | number = "0", left: LeftProperty<TLength> = px(0)) => {
        return {
            position: "absolute" as PositionProperty,
            top,
            left,
        };
    },
    bottomRight: (bottom: BottomProperty<TLength> = px(0), right: RightProperty<TLength> = px(0)) => {
        return {
            position: "absolute" as PositionProperty,
            bottom,
            right,
        };
    },
    bottomLeft: (bottom: BottomProperty<TLength> = px(0), left: LeftProperty<TLength> = px(0)) => {
        return {
            position: "absolute" as PositionProperty,
            bottom,
            left,
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
};
