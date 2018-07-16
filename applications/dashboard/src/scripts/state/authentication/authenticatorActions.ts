import { Action, Dispatch } from "redux";
import { actionCreator, ActionsUnion, createAction } from "@dashboard/state/utility";
import { ISigninAuthenticatorState, IState, LoadStatus } from "./IAuthenticationState";
import api from "@dashboard/apiv2";

// export const GET_SIGNIN_AUTHENTICATORS = 'GET_SIGNIN_AUTHENTICATORS';
export const GET_SIGNIN_AUTHENTICATORS_REQUEST = 'GET_SIGNIN_AUTHENTICATORS_REQUEST';
export const GET_SIGNIN_AUTHENTICATORS_ERROR = 'GET_SIGNIN_AUTHENTICATORS_ERROR';
export const GET_SIGNIN_AUTHENTICATORS_SUCCESS = 'GET_SIGNIN_AUTHENTICATORS_SUCCESS';

export interface IGetSigninAuthenticatorsError {
    error: string;
}
export type AuthenticatorAction = IGetSigninAuthenticatorsRequest | IGetSigninAuthenticatorsSuccess | IGetSigninAuthenticatorsError;

export const actions = {
    getSigninAuthenticatorsRequest: actionCreator<string>(GET_SIGNIN_AUTHENTICATORS_REQUEST),
    getSigninAuthenticatorsSuccess: actionCreator<string, ISigninAuthenticatorState>(GET_SIGNIN_AUTHENTICATORS_SUCCESS),
    getSigninAuthenticatorsError: actionCreator<string, IGetSigninAuthenticatorsError>(GET_SIGNIN_AUTHENTICATORS_ERROR),
};

export function getSigninAuthenticators() {
    return (dispatch: Dispatch<any>, getState: () => IState) => {
        const {authentication} = getState();
        if (authentication.signin.status === LoadStatus.LOADING) {
            return;
        }

        dispatch(actions.getSigninAuthenticatorsRequest());

        return api
            .get("/authenticate/authenticators")
            .then(response => {
                dispatch(actions.getSigninAuthenticatorsSuccess(response));
            })
            .catch(error => {
                dispatch(actions.getSigninAuthenticatorsError({error: error.response.data.message}))
            });
    }
}

export type ActionTypes = ActionsUnion<typeof actions>;
