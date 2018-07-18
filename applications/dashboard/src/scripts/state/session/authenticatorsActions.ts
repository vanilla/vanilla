/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { Dispatch } from "redux";
import { generateApiActionCreators, ActionsUnion, createAction } from "@dashboard/state/utility";
import api from "@dashboard/apiv2";
import { AxiosResponse } from "axios";
import { LoadStatus, IUserAuthenticator } from "@dashboard/@types/api";
import { IStoreState } from "@dashboard/@types/state";

export const GET_SIGNIN_AUTHENTICATORS_REQUEST = "GET_SIGNIN_AUTHENTICATORS_REQUEST";
export const GET_SIGNIN_AUTHENTICATORS_ERROR = "GET_SIGNIN_AUTHENTICATORS_ERROR";
export const GET_SIGNIN_AUTHENTICATORS_SUCCESS = "GET_SIGNIN_AUTHENTICATORS_SUCCESS";

const getAuthenticatorsActions = generateApiActionCreators(
    GET_SIGNIN_AUTHENTICATORS_REQUEST,
    GET_SIGNIN_AUTHENTICATORS_SUCCESS,
    GET_SIGNIN_AUTHENTICATORS_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    [] as IUserAuthenticator[],
);

export function getUserAuthenticators() {
    return (dispatch: Dispatch<any>, getState: () => IStoreState) => {
        dispatch(getAuthenticatorsActions.request());

        return api
            .get("/authenticate/authenticators")
            .then((response: AxiosResponse<IUserAuthenticator[]>) => {
                dispatch(getAuthenticatorsActions.success(response));
            })
            .catch(error => {
                dispatch(getAuthenticatorsActions.error(error));
            });
    };
}

export type ActionTypes = ActionsUnion<typeof getAuthenticatorsActions>;
