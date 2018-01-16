/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import flow from "lodash/flow";

/**
 * Lower case the first letter of an item.
 *
 * @param {string} input - The string to lowercase.
 *
 * @returns {string} - The result.
 */
function lowerCaseFirstLetter(input) {
    return input.charAt(0).toLowerCase() + input.slice(1);

}

/**
 * Transform the keys of legacy form data.
 *
 * @param {Object} input
 *
 * @returns {Object}
 */
function transformLegacyKeys(input) {
    const output = {};

    for (const [key, value] of Object.entries(input)) {
        const newKey = lowerCaseFirstLetter(key);
        output[newKey] = value;
    }

    return output;
}

/**
 * Transform Announce into pinned and pinLocation
 *
 * @param {Object} input
 *
 * @returns {Object}
 */
function transformAnnounce(input) {
    if (!("Announce" in input)) {
        return input;
    }

    const { Announce } = input;
    const pinned = Announce > 0;

    if (pinned) {
        const pinLocation = Announce === 1 ? "recent" : "category";

        const result = {
            ...input,
            pinned,
            pinLocation,
        };

        delete result["Announce"];
        return result;
    } else {
        const result = {
            ...input,
            pinned,
        };

        delete result["Announce"];
        return result;
    }
}

/**
 * Transform the data from the old form type into what needs to be sent to the API.
 *
 * @param {Object} formData - The data to transform.
 *
 * @returns {Object} The transformed data.
 */
export function transformLegacyFormData(formData) {
    const transform = flow(
        transformAnnounce,
        transformLegacyKeys,
    );
    return transform(formData);
}
