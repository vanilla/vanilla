/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { combineReducers } from "redux";
import { signinReducer } from "@dashboard/state/authenticate/authenticatorReducer";
import { LoadStatus } from "@dashboard/state/IState";

const authenticateReducer = combineReducers({
    signin: signinReducer,
    profile: (s = []) => ({ status: LoadStatus.PENDING, data: [] }),
});

export default authenticateReducer;
