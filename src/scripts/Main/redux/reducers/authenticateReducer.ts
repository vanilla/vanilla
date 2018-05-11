/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { AUTHENTICATE_AUTHENTICATORS_SET } from "../actions/authenticateActions";

const initialState = {
    authenticators: [],
};

const authenticateReducer = (state = initialState, action) => {
    switch (action.type) {
        case AUTHENTICATE_AUTHENTICATORS_SET:
            return {
                ...state,
                authenticators: action.payload,
            };

        default:
            return state;
    }
};

export default authenticateReducer;
