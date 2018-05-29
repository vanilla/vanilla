/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { createStore, applyMiddleware, compose } from "redux";
import { createEpicMiddleware } from "redux-observable";
import { getRootReducer } from "./rootReducer";
import { getRootEpic } from "./rootEpic";

// there may be an initial state to import
const initialState = window.__INITIAL_STATE__ || {};

// prepare the epics
const epicMiddleware = createEpicMiddleware(getRootEpic());

// browser may have redux dev tools installed, if so integrate with it
const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;

// build the store, add devtools extension support if it's available
const store = createStore(getRootReducer(), initialState, composeEnhancers(applyMiddleware(epicMiddleware)));

export function getStore() {
    return store;
}
