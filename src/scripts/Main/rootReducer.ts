import { combineReducers } from "redux";

// reducers from each module
import authenticate from "@dashboard/Authenticate/state/reducer";

/**
 * Merge all reducers into one.
 *
 * @see http://redux.js.org/docs/api/combineReducers.html
 */
const rootReducer = combineReducers({
    authenticate,
});

export function getRootReducer() {
    return rootReducer;
}
