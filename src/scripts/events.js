/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

import * as utility from "./utility";
import debounce from "lodash/debounce";

/**
 * Run a function when the Dom is loaded. Replaces $(document).ready(callback)
 *
 * @param {EventListener} callback - The Callback to run.
 */
export function onReady(callback) {
    if (document.readyState !== "loading") {
        callback(undefined);
    } else {
        document.addEventListener("DOMContentLoaded", callback);
    }
}

const resizeKeys = [];

/**
 * Runs when the window is resized. Debounce by default.
 *
 * @param {EventListener} callback - The Callback to run.
 * @param {string|number=} key - A key to prevent adding an event twice. If the passed key has already been used, a new event listener will not be registered.
 * @param {number=} waitTime - The debounce time in between callback calls. Defaults to 200ms.
 */
export function onResize(callback, key = undefined, waitTime = 200) {
    if (!key || resizeKeys.includes(key)) {
        resizeKeys.push(key);
        window.addEventListener("resize", debounce(callback, waitTime));
    }
}

/**
 * Class for addons to register events with.
 *
 * Events can take a callback or return a Promise.
 * Events will be exectuted in the order they are registered.
 */
export class Events {
    constructor() {
        this.vanillaReadyEvents = [];

        // Function binding
        this.onVanillaReady = this.onVanillaReady.bind(this);
        this.execute = this.execute.bind(this);
    }

    /**
     * Register a callback for DOMContentLoaded.
     *
     * @param {PromiseOrNormalCallback} callback - The function to call. This can return a Promise but doesn't have to.
     */
    onVanillaReady(callback) {
        this.vanillaReadyEvents.push(callback);
    }

    /**
     * Execute all of the registered events in order.
     *
     * @returns {Promise<any[]>} - A Promise when the events have all fired.
     */
    execute() {
        return utility.resolvePromisesSequentially(this.vanillaReadyEvents).then();
    }
}

export default new Events();
