/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { Dispatch } from "redux";
import { generateApiActionCreators, ActionsUnion, createAction } from "@dashboard/state/utility";
import api, { LoadStatus } from "@dashboard/apiv2";
import IState from "@dashboard/state/IState";
import { IAuthenticator } from "@dashboard/state/authenticate/IAuthenticateState";
import { AxiosResponse } from "axios";

// export const GET_SIGNIN_AUTHENTICATORS = 'GET_SIGNIN_AUTHENTICATORS';
export const GET_SIGNIN_AUTHENTICATORS_REQUEST = "GET_SIGNIN_AUTHENTICATORS_REQUEST";
export const GET_SIGNIN_AUTHENTICATORS_ERROR = "GET_SIGNIN_AUTHENTICATORS_ERROR";
export const GET_SIGNIN_AUTHENTICATORS_SUCCESS = "GET_SIGNIN_AUTHENTICATORS_SUCCESS";

const getAuthenticatorsActions = generateApiActionCreators(
    GET_SIGNIN_AUTHENTICATORS_REQUEST,
    GET_SIGNIN_AUTHENTICATORS_SUCCESS,
    GET_SIGNIN_AUTHENTICATORS_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    [] as IAuthenticator[],
);

export function getAuthenticators() {
    return (dispatch: Dispatch<any>, getState: () => IState) => {
        const { authenticate } = getState();
        if (authenticate.signin.status === LoadStatus.LOADING) {
            return;
        }

        dispatch(getAuthenticatorsActions.request());

        return api
            .get("/authenticate/authenticators")
            .then((response: AxiosResponse<IAuthenticator[]>) => {
                dispatch(getAuthenticatorsActions.success(response));
            })
            .catch(error => {
                dispatch(getAuthenticatorsActions.error(error));
            });
    };
}

export type ActionTypes = ActionsUnion<typeof getAuthenticatorsActions>;
