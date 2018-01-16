/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import ajax from "@core/ajax";

export default class ApiCaller {
    constructor() {
        this.apiBase = "/api/v2";
        this.endpoint = "";
    }

    get uri() {
        return this.apiBase + this.endpoint;
    }

    /**
     * Transform an Object into a QueryString.
     *
     * @param {Object} parameters - An Object to generate a query string from.
     *
     * @returns {string};
     */
    static encodeQueryStringObject(parameters) {
        return Object.keys(parameters)
            .map(param => encodeURIComponent(param) + "=" + encodeURIComponent(parameters[param]))
            .join("&");
    }

    /**
     * Create a GET request. Can return all or 1 of an item.
     *
     * @param {number=} id - The ID to lookup. If this is not passed, everything will be passed.
     * @param {Object=} parameters - An Object to generate a query string from.
     *
     * @returns {Promise<any>} - A Promise with an Axios response https://github.com/axios/axios#response-schema.
     */
    get = (id = null, parameters = {}) => {
        const uri = id ? this.uri + "/" + id : this.uri;

        return ajax.get(uri, ApiCaller.encodeQueryStringObject(parameters));
    };

    /**
     * Create a GET request. Returns only the data needed for an edit.
     *
     * @param {number} id - The ID to get the editable information..
     *
     * @returns {Promise<any>} - A Promise with an Axios response https://github.com/axios/axios#response-schema.
     * @throws {Error} - If an ID is not passed.
     */
    getEdit = (id) => {
        if (!id) {
            throw new Error(`Unable to DELETE on ${this.uri} without an ID.`);
        }
        const uri = this.uri + "/" + id + "/edit";

        return ajax.get(uri);
    };

    /**
     * Create a POST request.
     *
     * @param {Object} body - The body of the request.
     *
     * @returns {Promise<any>} - A Promise with an Axios response https://github.com/axios/axios#response-schema.
     */
    post = (body) => ajax.post(this.uri, body);

    /**
     * Create a PATCH request.
     *
     * @param {number} id - The ID to patch.
     * @param {Object} body - The body of the request.
     *
     * @returns {Promise<any>} - A Promise with an Axios response https://github.com/axios/axios#response-schema.
     */
    patch = (id, body) => {
        if (!id) {
            throw new Error(`Unable to DELETE on ${this.uri} without an ID.`);
        }

        return ajax.patch(this.uri + "/" + id, body);
    };

    /**
     * Create a PATCH request.
     *
     * @param {number} id - The ID to patch.
     *
     * @returns {Promise<any>} - A Promise with an Axios response https://github.com/axios/axios#response-schema.
     * @throws {Error} - If an ID is not passed.
     */
    delete = (id) => {
        if (!id) {
            throw new Error(`Unable to DELETE on ${this.uri} without an ID.`);
        }

        return ajax.delete(this.uri + "/" + id);
    };
}
