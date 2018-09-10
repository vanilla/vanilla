/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import * as actions from "@dashboard/pages/authenticate/authenticatorsActions";
import { IAuthenticatorState } from "@dashboard/@types/state";
import { LoadStatus } from "@library/@types/api";

const initialState: IAuthenticatorState = {
    status: LoadStatus.PENDING,
};

export default function authenticatorsReducer(
    state: IAuthenticatorState = initialState,
    action: actions.ActionTypes,
): IAuthenticatorState {
    switch (action.type) {
        case actions.GET_USER_AUTHENTICATORS_REQUEST:
            return {
                ...state,
                status: LoadStatus.LOADING,
            };
        case actions.GET_USER_AUTHENTICATORS_SUCCESS:
            return {
                status: LoadStatus.SUCCESS,
                data: action.payload.data,
                error: undefined,
            };
        case actions.GET_USER_AUTHENTICATORS_ERROR:
            return {
                ...state,
                status: LoadStatus.ERROR,
                error: action.payload,
            };
        default:
            return state;
    }
}
