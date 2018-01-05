import axios from "axios";

const instance = axios.create();

const middlewares = [];

/**
 * Register a middleware to run before after every request.
 */
export const registerMiddleware = (callback) => middlewares.push(callback);

export default instance;
