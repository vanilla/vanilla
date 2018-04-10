/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import {formatUrl, getMeta, setMeta} from "@core/application";
import axios, {AxiosResponse} from "axios";

/**
 * Add the transient key to every request.
 *
 * @see {AxiosTransformer}
 *
 * @param {FormData} data The data from the request.
 * @param {any} headers The request header config.
 *
 * @returns {string|Buffer|ArrayBuffer|FormData|Stream} - Must
 */
function addTransientKey(data, headers: any) {
    headers.post['X-Transient-Key'] = getMeta('TransientKey');
    return data;
}

const requestTransformers = [
    addTransientKey,
];

const api = axios.create({
    baseURL: formatUrl("/api/v2/"),
    // transformRequest: requestTransformers,
});
// api.defaults.headers.post['Content-Type'] = 'application/json';
api.defaults.headers.post['X-Transient-Key'] = getMeta('TransientKey');
api.interceptors.response.use(handleResponse);

/**
 * Intercept all responses and do some data processing.
 *
 * - Refresh the CSRF token.
 *
 * @param {AxiosResponse} response The response to handle.
 * @returns {AxiosResponse} Returns the response.
 */
function handleResponse(response: AxiosResponse) {
    if ('x-csrf-token' in response.headers) {
        setMeta('TransientKey', response.headers['x-csrf-token']);
        api.defaults.headers.post['X-Transient-Key'] = getMeta('TransientKey');
    }
    return response;
}

export default api;
