/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { CSSObject } from "@emotion/css";
import { style } from "@library/styles/styleShim";
import merge from "lodash/merge";
import { color, rgba, rgb, hsla, hsl, ColorHelper } from "csx";
import { logDebug, logWarning, logError, notEmpty } from "@vanilla/utils";
import { getThemeVariables } from "@library/theming/getThemeVariables";
import { isArray } from "util";
import { IThemeVariables } from "@library/theming/themeReducer";

// Re-export for compatibility.
export { useThemeCache } from "@library/styles/themeCache";

export const DEBUG_STYLES = Symbol.for("Debug");

// Use this type to allow people to to pass conditional styles in.
type CSSObjectOrFalsy = CSSObject | null | false | undefined;

/**
 * A better helper to generate human readable classes generated from Emotion.
 *
 * This works like debugHelper but automatically. The generated function behaves just like `style()`
 * but can automatically adds a debug name & allows the first argument to be a string subcomponent name.
 *
 * Additionally passing the first parameter as true will log out out debug information about the styles.
 *
 * @example
 * const style = styleFactory("myComponent");
 * const myClass = style({ color: "red" }); // .myComponent-sad421s
 * const mySubClass = style("subcomponent", { color: "red" }) // .myComponent-subcomponent-23sdaf43
 * const withDebugMode = style(true, "subcomponent", {color: "red"}).
 */
export function styleFactory(componentName: string) {
    function styleCreator(subcomponentName: string, ...objects: CSSObjectOrFalsy[]): string;
    function styleCreator(debug: symbol, subcomponentName: string, ...objects: CSSObjectOrFalsy[]): string;
    function styleCreator(...objects: CSSObjectOrFalsy[]): string;
    function styleCreator(...objects: Array<CSSObjectOrFalsy | string | symbol>): string {
        objects = objects.filter((val) => !!val);
        if (objects.length === 0) {
            return style();
        }
        let debugName = componentName;
        let shouldLogDebug = false;
        let styleObjs: CSSObject[] = objects as any;
        if (objects[0] === DEBUG_STYLES) {
            styleObjs.shift();
            shouldLogDebug = true;
        }
        if (typeof objects[0] === "string") {
            const [subcomponentName, ...restObjects] = styleObjs;
            debugName += `-${subcomponentName}`;
            styleObjs = restObjects;
        }

        styleObjs.forEach((obj) => (obj["label"] = debugName));

        if (shouldLogDebug) {
            logWarning(`Debugging component ${debugName}`);
            logDebug(styleObjs);
        }

        return style(...styleObjs);
    }

    return styleCreator;
}

/**
 * A helper class for declaring variables while mixing server defined variables from context.
 *
 * The function returned from the factory
 * - will search the API based theme for an item of the same key.
 * - Normalize the items {@see normalizeVariables}
 * - Merge in all subtrees. (theme variables override your defaults).
 *
 * @param componentName The base name of the component being styled.
 * @param themeVars Optionally force a particular set of variables to be used.
 *
 * @example
 *
 * // The stuff returned through the API response
 * const serverVars = {
 *      "globalVars": {
 *          "links": {
 *              "colors": {
 *                  "default": "red",
 *                  "hover": "#444444",
 *              }
 *          }
 *      }
 * };
 *
 * // Your declaration
 * const makeThemeVars = variableFactory("globalVars");
 * const subVars = makeThemeVars("links", { colors: {
 *      default: mainColors.primary, // These are `ColorHelpers`
 *      hover: mainColors.primary.darken(0.2), // They mixed variables will be automatically converted
 * }});
 */
export function variableFactory(
    componentNames: string | string[],
    themeVars?: IThemeVariables,
    mergeWithGlobals = false,
) {
    if (!themeVars) {
        themeVars = getThemeVariables();
    } else if (mergeWithGlobals) {
        themeVars = merge(getThemeVariables(), themeVars);
    }

    componentNames = typeof componentNames === "string" ? [componentNames] : componentNames;

    const componentThemeVars = componentNames
        .map((name) => themeVars?.[name] ?? {})
        .reduce((prev, curr) => {
            return merge(prev, curr);
        }, {});

    return function makeThemeVars<T extends object>(subElementName: string, declaredVars: T, overrides?: any): T {
        const customVars = componentThemeVars?.[subElementName] ?? null;
        let result = declaredVars;
        if (customVars != null) {
            result = normalizeVariables(customVars, result);
        }

        if (overrides != null) {
            result = normalizeVariables(stripUndefinedKeys(overrides), result);
        }
        return result;
    };
}

function stripUndefinedKeys(obj: any) {
    if (typeof obj === "object") {
        const newObj = {};
        for (const [key, value] of Object.entries(obj)) {
            if (value !== undefined) {
                newObj[key] = value;
            }
        }
        return newObj;
    }

    return obj;
}

const rgbRegex = /^rgb\((\d{1,3}%?),\s*(\d{1,3}%?),\s*(\d{1,3}%?)\)$/;
const rgbaRegex = /^rgba\((\d{1,3}%?),\s*(\d{1,3}%?),\s*(\d{1,3}%?),\s*(\d*(?:\.\d+)?)\)$/;
const hslRegex = /^hsl\((\d+),\s*([\d.]+)%,\s*([\d.]+)%\)$/;
const hslaRegex = /^hsla\((\d+),\s*([\d.]+)%,\s*([\d.]+)%,\s*(\d*(?:\.\d+)?)\)/;
const hexRegex = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;

/**
 * Take some Object/Value from the variable factory and wrap it in it's proper wrapper.
 *
 * Iterates through all children and does the following:
 *
 * - Strings starting with `#` get wrapped in `color()`;
 */
function normalizeVariables(customVariable: any, defaultVariable: any) {
    try {
        if (Array.isArray(customVariable) && isArray(defaultVariable)) {
            // We currently can't pre-process arrays.
            return customVariable;
        }
        if (
            defaultVariable instanceof ColorHelper ||
            (defaultVariable === undefined && typeof customVariable === "string" && customVariable.startsWith("#"))
        ) {
            if (customVariable instanceof ColorHelper) {
                return customVariable;
            } else if (typeof customVariable === "string" && customVariable.startsWith("linear-gradient")) {
                return customVariable;
            } else {
                const color = colorStringToInstance(customVariable, defaultVariable instanceof ColorHelper);
                return color;
            }
        } else if (
            typeof customVariable === "object" &&
            typeof defaultVariable === "object" &&
            defaultVariable !== null
        ) {
            const newObj: any = {};
            for (const [key, defaultValue] of Object.entries(defaultVariable)) {
                const mergedValue = key in customVariable ? customVariable[key] : defaultValue;
                newObj[key] = normalizeVariables(mergedValue, defaultValue);
            }
            return newObj;
        } else {
            return customVariable;
        }
    } catch (e) {
        logError("Error while evaluation custom variable", customVariable, e);
        return defaultVariable;
    }
}

/**
 * Check if string is valid color in hex format
 * @param colorString
 */
export function stringIsHexColor(colorValue) {
    return typeof colorValue === "string" && colorValue.match(hexRegex);
}

/**
 * Check if string is valid color in rgb format
 * @param colorString
 */
export function stringIsRgbColor(colorValue) {
    return typeof colorValue === "string" && colorValue.match(rgbRegex);
}

/**
 * Check if string is valid color in rgba format
 * @param colorString
 */
export function stringIsRgbaColor(colorValue) {
    return typeof colorValue === "string" && colorValue.match(rgbaRegex);
}

/**
 * Check if string is valid color in hsl format
 * @param colorString
 */
export function stringIsHslColor(colorValue) {
    return typeof colorValue === "string" && colorValue.match(hslRegex);
}

/**
 * Check if string is valid color in hsla format
 * @param colorString
 */
export function stringIsHslaColor(colorValue) {
    return typeof colorValue === "string" && colorValue.match(hslaRegex);
}

/**
 * Check if string is supported color format
 * @param colorString
 */
export function stringIsValidColor(colorValue) {
    return (
        typeof colorValue === "string" &&
        (stringIsRgbColor(colorValue) ||
            stringIsHexColor(colorValue) ||
            stringIsHslColor(colorValue) ||
            stringIsHslaColor(colorValue) ||
            stringIsRgbaColor(colorValue))
    );
}
/**
 * Check if string is linear gradient
 * @param colorString
 */
export function stringIsLinearGradient(colorValue) {
    return (
        typeof colorValue === "string" &&
        !stringIsValidColor(colorValue) &&
        colorValue.toString().trim().startsWith("linear-gradient(")
    );
}

/**
 * Check if string or ColorHelper is valid
 * @param colorString
 */
export const isValidColor = (colorValue) => {
    return colorValue && (colorValue instanceof ColorHelper || stringIsValidColor(colorValue));
};

/**
 * Takes either a custome error message string or a boolean, true gives default message
 * @param error
 * @param defaultMessage
 */
export const getDefaultOrCustomErrorMessage = (error, defaultMessage: string) => {
    return typeof error === "string" ? error : defaultMessage;
};

/**
 * Convert a color string into an instance.
 * @param colorString
 */
export function colorStringToInstance(colorString: string, throwOnFailure: boolean = false) {
    if (stringIsHexColor(colorString)) {
        // It's a colour.
        return color(colorString);
    } else if (stringIsRgbColor(colorString)) {
        const result = rgbRegex.exec(colorString)!;

        const r = parseInt(result[1], 10);
        const g = parseInt(result[2], 10);
        const b = parseInt(result[3], 10);
        return rgb(r, g, b);
    } else if (stringIsRgbaColor(colorString)) {
        const result = rgbaRegex.exec(colorString)!;

        const r = parseInt(result[1], 10);
        const g = parseInt(result[2], 10);
        const b = parseInt(result[3], 10);
        const a = parseFloat(result[4]);

        return rgba(r, g, b, a);
    } else if (stringIsHslColor(colorString)) {
        const result = hslRegex.exec(colorString)!;

        const h = parseInt(result[1], 10);
        const s = parseInt(result[2], 10);
        const l = parseInt(result[3], 10);
        return hsl(h, s, l);
    } else if (stringIsHslaColor(colorString)) {
        const result = hslaRegex.exec(colorString)!;

        const h = parseInt(result[1], 10);
        const s = parseInt(result[2], 10);
        const l = parseInt(result[3], 10);
        const a = parseFloat(result[4]);

        return hsla(h, s, l, a);
    } else {
        if (throwOnFailure) {
            throw new Error(`Invalid color detected: ${colorString}`);
        }

        return colorString;
    }
}

/**
 * Helper to overwrite styles
 * @param theme - The theme overwrites.
 * @param componentName - The name of the component to overwrite
 *
 * @deprecated
 */
export const componentThemeVariables = (componentName: string) => {
    const themeVars = getThemeVariables();
    const componentVars = (themeVars && themeVars[componentName]) || {};

    const subComponentStyles = (subElementName: string): object => {
        return (componentVars && componentVars[subElementName]) || {};
    };

    return {
        subComponentStyles,
    };
};

/**
 * Print a set of variables for debugging.
 */
export function printDebugVars(vars: any) {
    logDebug(JSON.stringify(getDebugVars(vars)));
}

function getDebugVars(vars: any): any {
    let result = {} as any;

    for (const [key, value] of Object.entries(vars)) {
        if (value instanceof ColorHelper) {
            result[key] =
                value.opacity() < 1
                    ? `rgba(${value
                          .toRGBA()
                          .red()}, ${value.toRGBA().green()}, ${value.toRGBA().blue()}, ${value.toRGBA().alpha()})`
                    : value.toHexString();
        } else if (typeof value === "object" && value) {
            result[key] = getDebugVars(value);
        } else {
            result[key] = value;
        }
    }

    return result;
}

/**
 * For classes that allow overwriting variables, it can be difficult to trace where bugs come from.
 * This function appends a string to the class name with the file it's from.
 * Example: You've got a class: "searchBar-closeButton_fhrk8tj"
 * If you pass a source to this function, the classnames becomes: "searchBar-closeButton:fromTitleBar_fhrk8tj"
 */
export function appendSource(className: string, source?: string) {
    return `${className}${source ? "--" + source : ""}`;
}

export function getPixelNumber(val: string | number | undefined, fallback: number = 0): number {
    if (val == undefined) {
        return fallback;
    }
    if (typeof val === "number") {
        return val;
    } else {
        val = val.replace("px", "");
        const parsed = Number.parseInt(val);
        if (Number.isNaN(parsed)) {
            return fallback;
        } else {
            return parsed;
        }
    }
}
