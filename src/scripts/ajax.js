/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import axios from "axios";
import { formatUrl, getMeta } from "@core/utility";


/**
 * Add the transient key to every request.
 *
 * @see {AxiosTransformer}
 *
 * @param {FormData} data - The data from the request.
 *
 * @returns {string|Buffer|ArrayBuffer|FormData|Stream} - Must
 */
function addTransientKey(data) {
    data.append("transientKey", getMeta("TransientKey"));
    return data;
}

const dataTransformers = [
    addTransientKey,
];

const instance = axios.create({
    baseURL: formatUrl("/api/v2/"),
    transformRequest: dataTransformers,
});

export default instance;
