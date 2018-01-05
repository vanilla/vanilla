/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import axios from "axios";

const instance = axios.create();

const middlewares = [];

/**
 * Register a middleware to run before after every request.
 */
export const registerMiddleware = (callback) => middlewares.push(callback);

export default instance;
