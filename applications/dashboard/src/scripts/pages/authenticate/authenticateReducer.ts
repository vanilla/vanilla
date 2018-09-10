/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { combineReducers } from "redux";
import authenticatorsReducer from "@dashboard/pages/authenticate/authenticatorsReducer";
import passwordReducer from "@dashboard/pages/recoverPassword/recoverPasswordReducer";

const authenticateReducer = combineReducers({
    authenticators: authenticatorsReducer,
    password: passwordReducer,
});

export default authenticateReducer;
