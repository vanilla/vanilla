/**
 * Application functions for interop between Components in different packages.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ComponentClass } from "react";
import ReactDOM from "react-dom";
import gdn from "@library/gdn";
import { RouteProps } from "react-router";
import { logError, PromiseOrNormalCallback, logWarning } from "@vanilla/utils";
import isUrl from "validator/lib/isURL";
import { mountReact } from "@vanilla/react-utils";
import { AppContext } from "@library/AppContext";

/**
 * Get a piece of metadata passed from the server.
 *
 * @param key - The key to lookup.
 * @param defaultValue - A fallback value in case the key cannot be found.
 *
 * @returns Returns a meta value or the default value.
 */
export function getMeta(key: string, defaultValue?: any) {
    if (!gdn.meta) {
        return defaultValue;
    }

    const parts = key.split(".");
    let haystack = gdn.meta;

    for (const part of parts) {
        if (!haystack.hasOwnProperty(part)) {
            return defaultValue;
        }
        haystack = haystack[part];
    }
    return haystack;
}

/**
 * Set a piece of metadata. This will override what was passed from the server.
 *
 * @param key - The key to store under.
 * @param value - The value to set.
 */
export function setMeta(key: string, value: any) {
    const parts = key.split(".");
    const last = parts.pop();

    if (!last) {
        throw new Error(`Unable to set meta value ${key}. ${last} is not a valid object key.`);
    }

    let haystack = gdn.meta;

    for (const part of parts) {
        if (haystack[part] === null || typeof haystack[part] !== "object") {
            haystack[part] = {};
        }
        haystack = haystack[part];
    }
    haystack[last] = value;
}

/**
 * Translate a string into the current locale.
 *
 * @param str - The string to translate.
 * @param defaultTranslation - The default translation to use.
 *
 * @returns Returns the translation or the default.
 */
export function translate(str: string, defaultTranslation?: string): string {
    // Codes that begin with @ are considered literals.
    if (str.substr(0, 1) === "@") {
        return str.substr(1);
    }

    if (gdn.translations[str] !== undefined) {
        return gdn.translations[str];
    }

    return defaultTranslation !== undefined ? defaultTranslation : str;
}

/**
 * The t function is an alias for translate.
 */
export const t = translate;

/**
 * Determine if a string is an allowed URL.
 *
 * In the future this may be extended to check if we want to whitelist/blacklist various URLs.
 *
 * @param input - The string to check.
 */
export function isAllowedUrl(input: string): boolean {
    // Options https://github.com/chriso/validator.js#validators
    const options = {
        protocols: ["http", "https"],
        require_tld: true,
        require_protocol: true,
        require_host: true,
        require_valid_protocol: true,
        allow_trailing_dot: false,
        allow_protocol_relative_urls: false,
    };
    return isUrl(input, options);
}

/**
 * Format a URL in the format passed from the controller.
 *
 * @param path - The path to format.
 *
 * @returns Returns a URL that can be used in the APP.
 */
export function formatUrl(path: string, withDomain: boolean = false): string {
    if (path.indexOf("//") >= 0) {
        return path;
    } // this is an absolute path.

    // The context paths that come down are expect to have no / at the end of them.
    // Normally a domain like so: https://someforum.com
    // When we don't have that we want to fallback to "" so that our path with a / can get passed.
    const urlBase = withDomain
        ? window.location.origin + getMeta("context.basePath", "")
        : getMeta("context.basePath", "");
    return urlBase + path;
}

/**
 * Extract relative URL part from absolute full URL.
 *
 * @param fullUrl - The absolute url to transform.
 *
 * @returns Returns a URL that can be used in the APP.
 */
export function getRelativeUrl(fullUrl: string): string {
    const urlBase = window.location.origin + getMeta("context.basePath", "");
    return fullUrl.replace(urlBase, "");
}

/**
 * Create the URL of an asset of the site.
 *
 * @param path - The path to format.
 *
 * @returns Returns a URL that can be used for a static asset.
 */
export function assetUrl(path: string): string {
    if (path.indexOf("//") >= 0) {
        return path;
    } // this is an absolute path.

    // The context paths that come down are expect to have no / at the end of them.
    // Normally a domain like so: https://someforum.com
    // When we don't have that we want to fallback to "" so that our path with a / can get passed.
    const urlFormat = getMeta("context.assetPath", "");
    return urlFormat + path;
}

/**
 * Create the URL to the theme's asset folder
 *
 * @param path - The path to format.
 *
 * @returns Returns a URL that can be used for a static asset.
 */
export function themeAsset(path: string): string {
    const themeKey = getMeta("ui.themeKey");
    return assetUrl(`/themes/${themeKey}/${path}`);
}

/**
 * @type {Array}
 * @private
 */
const _readyHandlers: PromiseOrNormalCallback[] = [];

/**
 * Register a callback that executes when the document and the core libraries are ready to use.
 *
 * @param callback - The function to call. This can return a Promise but doesn't have to.
 */
export function onReady(callback: PromiseOrNormalCallback) {
    _readyHandlers.push(callback);
}

/**
 * Execute all of the registered events in order.
 *
 * @returns A Promise when the events have all fired.
 */
export function _executeReady(): Promise<any[]> {
    return new Promise(resolve => {
        const handlerPromises = _readyHandlers.map(handler => handler());
        const exec = () => {
            return Promise.all(handlerPromises).then(resolve);
        };

        if (document.readyState !== "loading") {
            return exec();
        } else {
            document.addEventListener("DOMContentLoaded", exec);
        }
    });
}

/**
 * Execute a callback when a piece of DOM content is ready to be operated on.
 *
 * This is similar to onReady() but also includes content that is added dynamically (ex. AJAX).
 * Note that this function is meant to bridge the non-react parts of the application with react.
 *
 * @param callback - The callback to execute.
 */
export function onContent(callback: (event: CustomEvent) => void) {
    document.addEventListener("X-DOMContentReady", callback);
}

/**
 * Remove a listener registered with `onContent`.
 */
export function removeOnContent(callback: (event: CustomEvent) => void) {
    document.removeEventListener("X-DOMContentReady", callback);
}

/**
 * Make a URL to a user's profile.
 */
export function makeProfileUrl(username: string) {
    const userPath = `/profile/${encodeURIComponent(username)}`;
    return formatUrl(userPath);
}
