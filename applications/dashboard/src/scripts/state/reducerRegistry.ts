/**
 * A reducer registry so that we can have dynamically loading reducers.
 *
 * @see http://nicolasgallagher.com/redux-modules-and-code-splitting/
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { logError } from "@dashboard/utility";
import { Reducer, ReducersMapObject } from "redux";
import { onReady } from "@dashboard/application";

let haveGot = false;
let wasReadyCalled = false;
const reducers = {};

onReady(() => {
    wasReadyCalled = true;
});

export function registerReducer(name: string, reducer: Reducer) {
    if (haveGot) {
        logError("Cannot register reducer %s after reducers applied to the store.", name);
    } else {
        reducers[name] = reducer;
    }
}

export function getReducers(): ReducersMapObject<any, any> {
    haveGot = true;

    if (!wasReadyCalled) {
        logError("getReducers() was called before onReady");
    }

    return {
        ...reducers,
    };
}

/**
 * @deprecated
 */
const reducerRegistry = {
    register: registerReducer,
    getReducers,
};

export default reducerRegistry;
