/**
 * Application functions for interop between components in different packages.
 *
 * @module app
 */
import React from 'react';
import gdn from "@core/gdn";

/**
 * Get a piece of metadata passed from the server.
 *
 * @param {string} key - The key to lookup.
 * @param {any=} defaultValue - A fallback value in case the key cannot be found.
 *
 * @returns {any}
 */
export function getMeta(key, defaultValue = undefined) {
    if (gdn.meta && gdn.meta[key]) {
        return gdn.meta[key];
    }

    return defaultValue;
}

/**
 * Set a piece of metadata. This will override what was passed from the server.
 *
 * @param {string} key - The key to store under.
 * @param {any} value - The value to set.
 */
export function setMeta(key, value) {
    gdn.meta[key] = value;
}

/**
 * Translate a string into the current locale.
 *
 * @param {string} str The string to translate.
 * @param {string=} defaultTranslation The default translation to use.
 */
export function translate(str, defaultTranslation) {
    // Codes that begin with @ are considered literals.
    if (str.substr(0, 1) === '@') {
        return str.substr(1);
    }

    if (gdn.translations[str] !== undefined) {
        return gdn.translations[str];
    }

    return defaultTranslation !== undefined ? defaultTranslation : str;
}

/**
 * The t function is an alias for translate.
 *
 * @type {translate}
 */
export const t = translate;

/**
 * Format a URL in the format passed from the controller.
 *
 * @param {string} path - The path to format.
 *
 * @returns {string}
 */
export function formatUrl(path) {
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
 * @type {Object} The currently registered components.
 * @private
 */
const allComponents = {};

/**
 * Register a component in the components registry.
 *
 * @param {string} name The name of the component.
 * @param {React.Component} component The component to register.
 */
export function addComponent(name, component) {
    allComponents[name.toLowerCase()] = component;
}

/**
 * Test to see if a component has been registered.
 *
 * @param {string} name The name of the component to test.
 * @returns {boolean} Returns **true** if the component has been registered or **false** otherwise.
 */
export function componentExists(name) {
    return allComponents[name.toLowerCase()] !== undefined;
}

/**
 * Get a component from the component registry.
 *
 * @param {string} name The name of the component.
 */
export function getComponent(name) {
    return allComponents[name.toLowerCase()];
}

/**
 * @type {Array} The currently registered routes.
 * @private
 */
const allRoutes = [];

/**
 * Register one or more routes to the app component.
 *
 * @param {Array} routes An array of routes to add.
 */
export function addRoutes(routes) {
    if (!Array.isArray(routes)) {
        allRoutes.push(routes);
    } else {
        allRoutes.push(...routes);
    }
}

/**
 * Get all of the currently registered routes.
 *
 * @returns {Array} Returns an array of routes.
 */
export function getRoutes() {
    return allRoutes;
}
