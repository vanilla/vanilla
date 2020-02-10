/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import { style } from "typestyle";
import getStore from "@library/redux/getStore";
import { getMeta } from "@library/utility/appUtils";
import memoize from "lodash/memoize";
import merge from "lodash/merge";
import { color, rgba, rgb, hsla, hsl, ColorHelper } from "csx";
import { logDebug, logWarning, hashString, logError } from "@vanilla/utils";
import { getThemeVariables } from "@library/theming/getThemeVariables";
import { isArray } from "util";

export const DEBUG_STYLES = Symbol.for("Debug");

/**
 * A better helper to generate human readable classes generated from TypeStyle.
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
    function styleCreator(subcomponentName: string, ...objects: NestedCSSProperties[]): string;
    function styleCreator(debug: symbol, subcomponentName: string, ...objects: NestedCSSProperties[]): string;
    function styleCreator(...objects: NestedCSSProperties[]): string;
    function styleCreator(...objects: Array<NestedCSSProperties | string | symbol>): string {
        if (objects.length === 0) {
            return style();
        }

        let debugName = componentName;
        let shouldLogDebug = false;
        let styleObjs: Array<NestedCSSProperties | undefined> = objects as any;
        if (objects[0] === DEBUG_STYLES) {
            styleObjs.shift();
            shouldLogDebug = true;
        }
        if (typeof objects[0] === "string") {
            const [subcomponentName, ...restObjects] = styleObjs;
            debugName += `-${subcomponentName}`;
            styleObjs = restObjects;
        }

        if (shouldLogDebug) {
            logWarning(`Debugging component ${debugName}`);
            logDebug(styleObjs);
        }

        const hasNestedStyles = !!objects.find(obj => typeof obj === "object" && "$nest" in obj);

        // Applying $unique generally gives better consistency, but it can cause issues with nested styles.
        // As a result we don't apply it if the class has any nested styles.
        return style({ $debugName: debugName, $unique: !hasNestedStyles }, ...styleObjs);
    }

    return styleCreator;
}

let themeUniqueness = hashString(Math.random().toString());

export function clearThemeCache() {
    themeUniqueness = hashString(Math.random().toString());
    return themeUniqueness;
}

/**
 * Wrap a callback so that it will only run once with a particular set of global theme variables.
 *
 * @param callback The function to wrap.
 */
export function useThemeCache<Cb>(callback: Cb): Cb {
    const makeCacheKey = (...args) => {
        const storeState = getStore().getState();
        const themeKey = getMeta("ui.themeKey", "default");
        const status = storeState.theme.assets.status;
        const cacheKey = themeKey + status + themeUniqueness;
        const result = cacheKey + JSON.stringify(args);
        return result;
    };
    return memoize(callback as any, makeCacheKey);
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
export function variableFactory(componentNames: string | string[]) {
    const themeVars = getThemeVariables();
    componentNames = typeof componentNames === "string" ? [componentNames] : componentNames;

    const componentThemeVars = componentNames
        .map(name => themeVars?.[name] ?? {})
        .reduce((prev, curr) => {
            return merge(prev, curr);
        }, {});

    return function makeThemeVars<T extends object>(subElementName: string, declaredVars: T): T {
        const customVars = componentThemeVars?.[subElementName] ?? null;
        if (customVars === null) {
            return declaredVars;
        }

        const normalized = normalizeVariables(customVars, declaredVars);
        return normalized;
    };
}

const rgbRegex = /rgba?\((\d+),\s?(\d+),\s?(\d+)[,\s]?(.+)\)/;
const hslRegex = /hsla?\((\d+),\s?(\d+),\s?(\d+)[,\s](.+)?\)/;

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
        } else if (defaultVariable instanceof ColorHelper) {
            if (customVariable instanceof ColorHelper) {
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
 * Convert a color string into an instance.
 * @param colorString
 */
export function colorStringToInstance(colorString: string, throwOnFailure: boolean = false) {
    if (colorString.startsWith("#")) {
        // It's a colour.
        return color(colorString);
    } else if (colorString.match(rgbRegex)) {
        const result = rgbRegex.exec(colorString)!;

        const r = parseInt(result[1], 10);
        const g = parseInt(result[2], 10);
        const b = parseInt(result[3], 10);
        const a = parseFloat(result[4]);

        if (a !== null) {
            return rgba(r, g, b, a);
        } else {
            return rgb(r, g, b);
        }
    } else if (colorString.match(hslRegex)) {
        const result = hslRegex.exec(colorString)!;

        const h = parseInt(result[1], 10);
        const s = parseInt(result[2], 10);
        const l = parseInt(result[3], 10);
        const a = parseFloat(result[4]);

        if (a !== null) {
            return hsla(h, s, l, a);
        } else {
            return hsl(h, s, l);
        }
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
