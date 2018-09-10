/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { combineReducers } from "redux";
import requestPasswordReducer from "@dashboard/pages/recoverPassword/recoverPasswordReducer";

const usersReducer = combineReducers({
    requestPassword: requestPasswordReducer,
});

export default usersReducer;
