/**
 * A reducer registry so that we can have dynamically loading reducers.
 *
 * @see http://nicolasgallagher.com/redux-modules-and-code-splitting/
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IThemeState, themeReducer } from "@library/theming/themeReducer";
import { IUsersStoreState, usersReducer } from "@library/features/users/userModel";
import { Reducer, ReducersMapObject, combineReducers } from "redux";
import getStore from "@library/redux/getStore";

const dynamicReducers = {};

export function registerReducer(name: string, reducer: Reducer) {
    dynamicReducers[name] = reducer;
    getStore().replaceReducer(combineReducers(getReducers()));
}

export interface ICoreStoreState extends IUsersStoreState {
    theme: IThemeState;
}

export function getReducers(): ReducersMapObject<any, any> {
    return {
        // We have a few static reducers.
        users: usersReducer,
        theme: themeReducer,
        ...dynamicReducers,
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
