/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import ApiCaller from "@core/ApiCaller";
import ajax from "@core/ajax";

/**
 * Class for calling the discussions API.
 */
class DiscussionsApi extends ApiCaller {
    constructor() {
        super();

        this.endpoint = "/discussions";
    }

    /**
     * Get a list of the current user's bookmarked discussions.
     *
     * @returns {Promise<any>} - A Promise with an Axios response https://github.com/axios/axios#response-schema.
     */
    getBookmarked = () => ajax.get(this.uri + "/bookmarked");

    /**
     * Create a PUT request to bookmark an item.
     *
     * @param {number} id - The ID to patch.
     *
     * @returns {Promise<any>} - A Promise with an Axios response https://github.com/axios/axios#response-schema.
     * @throws {Error} - If an ID is not passed.
     */
    putBookmark = (id) => {
        if (!id) {
            throw new Error(`Unable to PUT on ${this.uri} without an ID.`);
        }

        return ajax.delete(this.uri + "/" + id + "/bookmark");
    };
}

const instance = new DiscussionsApi();

export default instance;
