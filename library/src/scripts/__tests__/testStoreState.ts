import { DeepPartial } from "redux";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import merge from "lodash/merge";
import { INITIAL_USERS_STATE, INITIAL_THEMES_STATE, INITIAL_LOCALE_STATE } from "@library/features/users/userModel";

const DEFAULT_STATE: ICoreStoreState = {
    users: INITIAL_USERS_STATE,
    theme: INITIAL_THEMES_STATE,
    locales: INITIAL_LOCALE_STATE,
};

export function testStoreState(state: DeepPartial<ICoreStoreState>) {
    return merge(DEFAULT_STATE, state);
}
