/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { combineReducers } from "redux";

// reducers from each module
import authenticate from "./reducers/authenticateReducer";

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
