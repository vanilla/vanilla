import getStore from "@library/redux/getStore";
import { getMeta } from "@library/utility/appUtils";
import memoize from "lodash-es/memoize";
import { hashString } from "@vanilla/utils";
import { useEffect, useState } from "react";
import { useWithThemeContext } from "@library/theming/ThemeOverrideContext";

// A unique identifier that represents the current state of the theme.
let _themeCacheID = hashString(Math.random().toString());
// Event name for resetting the theme cacheID.

export const THEME_CACHE_EVENT = "V-Clear-Theme-Cache";

export function resetThemeCache() {
    _themeCacheID = hashString(Math.random().toString());
    document.dispatchEvent(new CustomEvent(THEME_CACHE_EVENT, { detail: _themeCacheID }));
    return _themeCacheID;
}

export function useThemeCacheID() {
    const [cacheID, setCacheID] = useState(_themeCacheID);
    useEffect(() => {
        const listener = (e: CustomEvent) => {
            setCacheID(e.detail);
        };
        document.addEventListener(THEME_CACHE_EVENT, listener);

        return () => {
            document.removeEventListener(THEME_CACHE_EVENT, listener);
        };
    });

    return { cacheID, resetThemeCache };
}

/**
 * Wrap a callback so that it will only run once with a particular set of global theme variables.
 *
 * @param callback The function to wrap.
 */
// eslint-disable-next-line @typescript-eslint/ban-types
export function useThemeCache<Cb extends Function>(callback: Cb): Cb & { useAsHook: Cb } {
    const makeCacheKey = (...args) => {
        const storeState = getStore().getState();
        const themeKey = getMeta("ui.themeKey", "default");
        const status = storeState.theme.assets.status;
        const cacheKey = themeKey + status + _themeCacheID + window.__THEME_OVERRIDE_CONTEXT__?.themeID;
        const result = cacheKey + JSON.stringify(args);
        return result;
    };
    const memoized = memoize(callback as any, makeCacheKey) as Cb;

    const result = {
        useAsHook: (...args: any) => useWithThemeContext(() => memoized(...args)),
    };

    return Object.assign(memoized, result) as any;
}
