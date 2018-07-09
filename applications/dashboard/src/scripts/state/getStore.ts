/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { createStore, compose, applyMiddleware, combineReducers, Store } from "redux";
import reducerRegistry from "./reducerRegistry";
import thunk from "redux-thunk";
import IState from "@dashboard/state/IState";

// there may be an initial state to import
const initialState = window.__INITIAL_STATE__ || {};

const middleware = [thunk];

// Preserve initial state for not-yet-loaded reducers
const combine = reducers => {
    const reducerNames = Object.keys(reducers);
    Object.keys(initialState).forEach(stateItem => {
        if (reducerNames.indexOf(stateItem) === -1) {
            reducers[stateItem] = (state = null) => state;
        }
    });
    return combineReducers(reducers);
};

// browser may have redux dev tools installed, if so integrate with it
const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;
const enhancer = composeEnhancers(applyMiddleware(...middleware));

// Get our reducers.
const reducer = combineReducers(reducerRegistry.getReducers());

// build the store, add devtools extension support if it's available
const store = createStore(reducer, initialState, enhancer);

// Replace the store's reducer whenever a new reducer is registered.
reducerRegistry.setChangeListener(reducers => {
    store.replaceReducer(combineReducers(reducers));
    store.dispatch({ type: "RESET" });
});

export default function getStore<S extends IState = IState>() {
    return store as Store<S>;
}
