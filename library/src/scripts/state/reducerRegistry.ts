/**
 * A reducer registry so that we can have dynamically loading reducers.
 *
 * @see http://nicolasgallagher.com/redux-modules-and-code-splitting/
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onReady } from "@library/application";
import { logError } from "@library/utility";
import { Reducer, ReducersMapObject } from "redux";
import UsersModel from "@library/users/UsersModel";

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
        users: new UsersModel().reducer,
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
