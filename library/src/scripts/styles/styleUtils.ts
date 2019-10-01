/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import { style } from "typestyle";
import { getDeferredStoreState } from "@library/redux/getStore";
import { getMeta } from "@library/utility/appUtils";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import memoize from "lodash/memoize";
import merge from "lodash/merge";
import { color } from "csx";
import { logDebug, logWarning } from "@vanilla/utils";
import { getThemeVariables } from "@library/theming/getThemeVariables";

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

        return style({ $debugName: debugName }, ...styleObjs);
    }

    return styleCreator;
}

/**
 * Wrap a callback so that it will only run once with a particular set of global theme variables.
 *
 * @param callback The function to wrap.
 */
export function useThemeCache<Cb>(callback: Cb): Cb {
    const makeCacheKey = (...args) => {
        const storeState = getDeferredStoreState<ICoreStoreState, null>(null);
        const themeKey = getMeta("ui.themeKey", "default");
        const status = storeState ? storeState.theme.assets.status : "not loaded yet";
        const cacheKey = themeKey + status;
        return cacheKey + JSON.stringify(args);
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
export function variableFactory(componentName: string) {
    const themeVars = getThemeVariables();
    const componentVars = (themeVars && themeVars[componentName]) || {};

    return function makeThemeVars<T extends object>(subElementName: string, declaredVars: T): T {
        const subcomponentVars = (componentVars && componentVars[subElementName]) || {};
        return merge(declaredVars, normalizeVariables(subcomponentVars));
    };
}

/**
 * Take some Object/Value from the variable factory and wrap it in it's proper wrapper.
 *
 * Iterates through all children and does the following:
 *
 * - Strings starting with `#` get wrapped in `color()`;
 */
function normalizeVariables(variables: any) {
    if (typeof variables === "object") {
        const newObj: any = {};
        for (const [key, value] of Object.entries(variables)) {
            newObj[key] = normalizeVariables(value);
        }
        return newObj;
    }

    if (typeof variables === "string") {
        if (variables.startsWith("#")) {
            // It's a colour.
            return color(variables);
        }
    }

    return variables;
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
