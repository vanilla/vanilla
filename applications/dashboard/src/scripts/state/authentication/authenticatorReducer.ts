/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import {Action} from "redux";
import {
    IAuthenticationState,
    ISigninAuthenticatorState,
    LoadStatus
} from "./IAuthenticationState";
import * as actions from "./authenticatorActions";

export function signinReducer(state?: ISigninAuthenticatorState, action?: Action) {
    if (state === undefined) {
        return {
            status: LoadStatus.PENDING,
            data: [],
        };
    }

    switch (action.type) {
        case actions.GET_SIGNIN_AUTHENTICATORS_REQUEST:
            return {
                ...state,
                status: LoadStatus.LOADING,
            };
        case actions.GET_SIGNIN_AUTHENTICATORS_SUCCESS:
            return {
                ...state,
                status: LoadStatus.SUCCESS,
                data: action.payload.data,
                error: undefined,
            };
        case actions.GET_SIGNIN_AUTHENTICATORS_ERROR:
            return {
                ...state,
                status: LoadStatus.ERROR,
                error: action.payload.error,
            };
        default:
            return state;
    }
}
