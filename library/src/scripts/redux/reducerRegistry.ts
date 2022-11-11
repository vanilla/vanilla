/**
 * A reducer registry so that we can have dynamically loading reducers.
 *
 * @see http://nicolasgallagher.com/redux-modules-and-code-splitting/
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IThemeState, themeReducer } from "@library/theming/themeReducer";
import { usersReducer } from "@library/features/users/userModel";
import { IUsersStoreState } from "@library/features/users/userTypes";
import { Reducer, ReducersMapObject, combineReducers } from "redux";
import { ILocaleState, localeReducer } from "@library/locales/localeReducer";
import getStore, { hasStore } from "@library/redux/getStore";
import NotificationsModel from "@library/features/notifications/NotificationsModel";
import ConversationsModel from "@library/features/conversations/ConversationsModel";
import { tagsReducer } from "@library/features/tags/TagsReducer";
import { discussionsReducer } from "@library/features/discussions/discussionsReducer";
import { configSlice, IConfigState } from "@library/config/configReducer";
import { collectionsReducer } from "@library/featuredCollections/collectionsReducer";

let dynamicReducers = {};

export function registerReducer(name: string, reducer: Reducer) {
    dynamicReducers[name] = reducer;
    if (hasStore()) {
        const store = getStore();
        store.replaceReducer(combineReducers(getReducers()));

        const initialActions = window.__ACTIONS__ || [];

        // Re-apply initial actions.
        initialActions.forEach(store.dispatch);
    }
}

export interface ICoreStoreState extends IUsersStoreState {
    theme: IThemeState;
    locales: ILocaleState;
    config: IConfigState;
}

export function getReducers(): ReducersMapObject<any, any> {
    return {
        // We have a few static reducers.
        users: usersReducer,
        discussions: discussionsReducer,
        locales: localeReducer,
        tags: tagsReducer,
        notifications: new NotificationsModel().reducer,
        conversations: new ConversationsModel().reducer,
        theme: themeReducer,
        collections: collectionsReducer,
        [configSlice.name]: configSlice.reducer,
        ...dynamicReducers,
    };
}

export function resetReducers() {
    dynamicReducers = {};
}

/**
 * @deprecated
 */
const reducerRegistry = {
    register: registerReducer,
    getReducers,
};

export default reducerRegistry;
