/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { combineReducers } from "redux";
import { signinReducer } from "@dashboard/state/authenticate/authenticatorReducer";
import { LoadStatus } from "@dashboard/state/IState";

const authenticateReducer = combineReducers({
    signin: signinReducer,
    profile: (s = []) => ({ status: LoadStatus.PENDING, data: [] }),
});

export default authenticateReducer;
