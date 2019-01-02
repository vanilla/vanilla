/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { combineReducers } from "redux";
import authenticatorsReducer from "@dashboard/pages/authenticate/authenticatorsReducer";
import passwordReducer from "@dashboard/pages/recoverPassword/recoverPasswordReducer";

const authenticateReducer = combineReducers({
    authenticators: authenticatorsReducer,
    password: passwordReducer,
});

export default authenticateReducer;
