/**
 * Application functions for interop between Components in different packages.
 *
 * @module application
 */
import gdn from "@core/gdn";
import { PromiseOrNormalCallback } from "@core/utility";
import React, { ComponentClass } from "react";
import { RouteProps } from "react-router-dom";
import isUrl from "validator/lib/isUrl";

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
 * Determine if a string is an allowed URL. Some domains may be whitelisted or blacklisted.
 *
 * @param input - The string to check.
 */
export function isAllowedUrl(input: string): boolean {
    // TODO: Check for allowed/whitelisted/blacklisted urls here.
    const options = {
        protocols: ["http", "https", "ftp"],
        require_tld: true,
        require_protocol: true,
        require_host: true,
        require_valid_protocol: true,
        allow_underscores: false,
        host_whitelist: false,
        host_blacklist: false,
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
export function formatUrl(path: string): string {
    if (path.indexOf("//") >= 0) {
        return path;
    } // this is an absolute path.

    const urlFormat = getMeta("UrlFormat", "/{Path}");

    if (path.substr(0, 1) === "/") {
        path = path.substr(1);
    }

    if (urlFormat.indexOf("?") >= 0) {
        path = path.replace("?", "&");
    }

    return urlFormat.replace("{Path}", path);
}

/**
 * @type {Object} The currently registered Components.
 * @private
 */
const _components = {};

/**
 * Register a component in the Components registry.
 *
 * @param name The name of the component.
 * @param component The component to register.
 */
export function addComponent(name: string, component: ComponentClass) {
    _components[name.toLowerCase()] = component;
}

/**
 * Test to see if a component has been registered.
 *
 * @param name The name of the component to test.
 * @returns Returns **true** if the component has been registered or **false** otherwise.
 */
export function componentExists(name: string): boolean {
    return _components[name.toLowerCase()] !== undefined;
}

/**
 * Get a component from the component registry.
 *
 * @param name The name of the component.
 * @returns Returns the component or **undefined** if there is no registered component.
 */
export function getComponent(name: string): ComponentClass | undefined {
    return _components[name.toLowerCase()];
}

/**
 * @type {Array} The currently registered routes.
 * @private
 */
const _routes: any[] = [];

/**
 * Register one or more routes to the app component.
 *
 * @param routes An array of routes to add.
 */
export function addRoutes(routes: Array<React.ReactElement<RouteProps>>) {
    if (!Array.isArray(routes)) {
        _routes.push(routes);
    } else {
        _routes.push(...routes);
    }
}

/**
 * Get all of the currently registered routes.
 *
 * @returns Returns an array of routes.
 */
export function getRoutes(): Array<React.ReactElement<RouteProps>> {
    return _routes;
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
 * @param {function} callback - The callback to execute.
 */
export function onContent(callback) {
    document.addEventListener("X-DOMContentReady", callback);
}
