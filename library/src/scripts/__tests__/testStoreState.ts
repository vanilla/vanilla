/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DeepPartial } from "redux";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import merge from "lodash/merge";
import { INITIAL_USERS_STATE, INITIAL_THEMES_STATE, INITIAL_LOCALE_STATE } from "@library/features/users/userModel";
import getStore, { resetActionAC } from "@library/redux/getStore";

const DEFAULT_STATE: ICoreStoreState = {
    users: INITIAL_USERS_STATE,
    theme: INITIAL_THEMES_STATE,
    locales: INITIAL_LOCALE_STATE,
};

export function testStoreState(state: DeepPartial<ICoreStoreState>) {
    return merge({}, DEFAULT_STATE, state);
}

export function resetStoreState(state: DeepPartial<ICoreStoreState>) {
    const store = getStore();
    console.log("resetting redux state", state);
    store.dispatch(resetActionAC(testStoreState(state)));
}
