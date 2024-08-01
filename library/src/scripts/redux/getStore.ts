/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { createStore, compose, applyMiddleware, combineReducers, Store, AnyAction, DeepPartial, Action } from "redux";
import { getReducers, ICoreStoreState } from "@library/redux/reducerRegistry";
import thunk from "redux-thunk";

// There may be an initial state to import.
const initialActions = window.__ACTIONS__ || [];

const middleware = [thunk];

// https://github.com/zalmoxisus/redux-devtools-extension/blob/master/docs/Troubleshooting.md#excessive-use-of-memory-and-cpu
const actionSanitizer = (action: AnyAction) =>
    (action.type as string).includes("[editorInstance]") && action.payload && action.payload.quill
        ? {
              ...action,
              payload: { ...action.payload, quill: "<<Quill Instance>>" },
          }
        : action;
// Browser may have redux dev tools installed, if so integrate with it.
const composeEnhancers =
    "__REDUX_DEVTOOLS_EXTENSION_COMPOSE__" in window
        ? window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__({ actionSanitizer, trace: true, serialize: false })
        : compose;
const enhancer = composeEnhancers(applyMiddleware(...middleware));

// Build the store, add devtools extension support if it's available.
let store;

export function resetStore() {
    store = undefined;
}

export const resetActionAC = (newState: ICoreStoreState) => {
    return {
        type: "@@store/RESET",
        payload: newState,
    };
};

export function createRootReducer() {
    const appReducer = combineReducers(getReducers());

    return (state, action) => {
        if (action.type === "@@store/RESET") {
            return action.payload;
        } else {
            return appReducer(state, action);
        }
    };
}

export function hasStore(): boolean {
    return store != null;
}

export default function getStore<S = ICoreStoreState>(initialState?: DeepPartial<S>, force?: boolean): Store<S, any> {
    if (store === undefined || force) {
        // Get our reducers.
        const reducer = createRootReducer();
        store = createStore(reducer, initialState || ({} as any), enhancer);

        // Dispatch initial actions returned from the server.
        initialActions.forEach(store.dispatch);
    }

    return store;
}
