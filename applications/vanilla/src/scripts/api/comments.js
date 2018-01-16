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
class CommentsApi extends ApiCaller {
    constructor() {
        super();

        this.endpoint = "/comments";
    }
}

const instance = new CommentsApi();

export default instance;
