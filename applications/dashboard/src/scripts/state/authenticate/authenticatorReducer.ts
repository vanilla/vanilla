/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { Action } from "redux";
import { ISigninAuthenticatorState } from "./IAuthenticateState";
import * as actions from "./authenticatorActions";
import { SigninAuthenticatorAction } from "./authenticatorActions";
import { LoadStatus } from "@dashboard/state/IState";

export function signinReducer(state?: ISigninAuthenticatorState, action?: SigninAuthenticatorAction) {
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
