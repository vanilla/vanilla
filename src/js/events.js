/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

import utility from "./utility";

/**
 * Run a function when the Dom is loaded. Replaces $(document).ready(callback)
 *
 * @param {function} callback - The Callback to run.
 */
function onDomContentLoaded(callback) {
    if (document.readyState != "loading") {
        callback();
    } else {
        document.addEventListener("DOMContentLoaded", callback);
    }
}

/**
 * Class for addons to register events with.
 *
 * Events can take a callback or return a Promise.
 * Events will be exectuted in the order they are registered.
 */
export class Events {

    readyEvents = [];

    /**
     * Register a callback for DOMContentLoaded.
     *
     * @param {PromiseCallback} callback - The function to call. This can return a Promise but doesn't have to.
     */
    onReady = (callback) => {
        this.readyEvents.push(callback);
    }

    /**
     * Execute all of the registered events in order.
     *
     * @return {Promise<void>}
     */
    execute = () => {
        return utility.resolvePromisesSequentially(this.readyEvents);
    }
}

export default new Events();
