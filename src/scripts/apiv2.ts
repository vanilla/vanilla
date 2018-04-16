/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import { formatUrl, getMeta, setMeta } from "@core/application";
import axios, { AxiosRequestConfig, AxiosResponse } from "axios";

/**
 * Add the transient key to every request.
 *
 * @param {AxiosRequestConfig} request The request being submitted.
 * @returns {AxiosRequestConfig} Returns the transformed request.
 */
function handleRequest(request: AxiosRequestConfig): AxiosRequestConfig {
    request.headers["X-Transient-Key"] = getMeta("TransientKey");
    return request;
}

/**
 * Intercept all responses and do some data processing.
 *
 * - Refresh the CSRF token.
 *
 * @param {AxiosResponse} response The response to handle.
 * @returns {AxiosResponse} Returns the response.
 */
function handleResponse(response: AxiosResponse) {
    if ("x-csrf-token" in response.headers) {
        setMeta("TransientKey", response.headers["x-csrf-token"]);
    }
    return response;
}

const api = axios.create({
    baseURL: formatUrl("/api/v2/"),
});
api.interceptors.request.use(handleRequest);
api.interceptors.response.use(handleResponse);

export default api;
