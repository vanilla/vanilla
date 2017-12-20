import _get from "lodash/get";

let config = {};

/**
 * Fetch the configuration data inserted into the page.
 *
 * This is currently a stub method, and needs to be replaced once the PHP side
 * of this has been completed.
 */
export function initializeConfig() {
    config = {
        debug: true,
    };
}

initializeConfig();

/**
 * Get a value from the configuration.
 *
 * @param {string} key - A key of the config in dot notation.
 * @param {any=} defaultValue - The default value to be used if the key is not found
 *
 * @returns {any} - The configuration value
 */
export function getConfig(key, defaultValue = false) {
    return _get(config, key, defaultValue);
}
