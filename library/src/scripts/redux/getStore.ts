/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { createStore, compose, applyMiddleware, combineReducers, Store, AnyAction, DeepPartial } from "redux";
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
const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__
    ? window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__({ actionSanitizer })
    : compose;
const enhancer = composeEnhancers(applyMiddleware(...middleware));

// Build the store, add devtools extension support if it's available.
let store;

export default function getStore<S = ICoreStoreState>(initialState?: DeepPartial<S>): Store<S, any> {
    if (store === undefined) {
        // Get our reducers.
        const reducer = combineReducers(getReducers());
        store = createStore(reducer, initialState || {}, enhancer);

        // Dispatch initial actions returned from the server.
        initialActions.forEach(store.dispatch);
    }

    return store;
}
