import { Dispatch } from "redux";
import { actionCreator, ActionsUnion, createAction } from "@dashboard/state/utility";
import { ISigninAuthenticatorState } from "./IAuthenticateState";
import api from "@dashboard/apiv2";
import IState, { LoadStatus } from "@dashboard/state/IState";
import { IAuthenticator } from "@dashboard/state/authenticate/IAuthenticateState";

// export const GET_SIGNIN_AUTHENTICATORS = 'GET_SIGNIN_AUTHENTICATORS';
export const GET_SIGNIN_AUTHENTICATORS_REQUEST = "GET_SIGNIN_AUTHENTICATORS_REQUEST";
export const GET_SIGNIN_AUTHENTICATORS_ERROR = "GET_SIGNIN_AUTHENTICATORS_ERROR";
export const GET_SIGNIN_AUTHENTICATORS_SUCCESS = "GET_SIGNIN_AUTHENTICATORS_SUCCESS";

export interface IGetSigninAuthenticatorsSuccess {
    data: [IAuthenticator];
}

export interface IGetSigninAuthenticatorsError {
    error: string;
}

export const actions = {
    getSigninAuthenticatorsRequest: actionCreator<typeof GET_SIGNIN_AUTHENTICATORS_REQUEST>(
        GET_SIGNIN_AUTHENTICATORS_REQUEST,
    ),
    getSigninAuthenticatorsSuccess: actionCreator<
        typeof GET_SIGNIN_AUTHENTICATORS_SUCCESS,
        IGetSigninAuthenticatorsSuccess
    >(GET_SIGNIN_AUTHENTICATORS_SUCCESS),
    getSigninAuthenticatorsError: actionCreator<typeof GET_SIGNIN_AUTHENTICATORS_ERROR, IGetSigninAuthenticatorsError>(
        GET_SIGNIN_AUTHENTICATORS_ERROR,
    ),
};

export function getSigninAuthenticators() {
    return (dispatch: Dispatch<any>, getState: () => IState) => {
        const { authenticate } = getState();
        if (authenticate.signin.status === LoadStatus.LOADING) {
            return;
        }

        dispatch(actions.getSigninAuthenticatorsRequest());

        return api
            .get("/authenticate/authenticators")
            .then(response => {
                dispatch(actions.getSigninAuthenticatorsSuccess(response));
            })
            .catch(error => {
                dispatch(actions.getSigninAuthenticatorsError({ error: error.response.data.message }));
            });
    };
}

export type ActionTypes = ActionsUnion<typeof actions>;
