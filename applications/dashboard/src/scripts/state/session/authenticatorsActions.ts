/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { Dispatch } from "redux";
import { generateApiActionCreators, ActionsUnion, createAction, apiThunk } from "@dashboard/state/utility";
import api from "@dashboard/apiv2";
import { AxiosResponse } from "axios";
import {
    IUserAuthenticator,
    IAuthenticatePasswordParams,
    IAuthenticatePasswordResponseData,
} from "@dashboard/@types/api";
import { IStoreState } from "@dashboard/@types/state";
import apiv2 from "@dashboard/apiv2";
import { formatUrl } from "@dashboard/application";

export const GET_USER_AUTHENTICATORS_REQUEST = "GET_USER_AUTHENTICATORS_REQUEST";
export const GET_USER_AUTHENTICATORS_ERROR = "GET_USER_AUTHENTICATORS_ERROR";
export const GET_USER_AUTHENTICATORS_SUCCESS = "GET_USER_AUTHENTICATORS_SUCCESS";

const getAuthenticatorsActions = generateApiActionCreators(
    GET_USER_AUTHENTICATORS_REQUEST,
    GET_USER_AUTHENTICATORS_SUCCESS,
    GET_USER_AUTHENTICATORS_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IUserAuthenticator[],
);

export const getUserAuthenticators = () =>
    apiThunk("get", "authenticate/authenticators", getAuthenticatorsActions, undefined);

// Authenticating user /authenticate/password
export const POST_AUTHENTICATE_PASSWORD_REQUEST = "POST_AUTHENTICATE_PASSWORD_REQUEST";
export const POST_AUTHENTICATE_PASSWORD_ERROR = "POST_AUTHENTICATE_PASSWORD_ERROR";
export const POST_AUTHENTICATE_PASSWORD_SUCCESS = "POST_AUTHENTICATE_PASSWORD_SUCCESS";

const authenticatePasswordActions = generateApiActionCreators(
    POST_AUTHENTICATE_PASSWORD_REQUEST,
    POST_AUTHENTICATE_PASSWORD_SUCCESS,
    POST_AUTHENTICATE_PASSWORD_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IAuthenticatePasswordResponseData,
    {} as IAuthenticatePasswordParams,
);

export const postAuthenticatePassword = (params: IAuthenticatePasswordParams) =>
    apiThunk("post", "/authenticate/password", authenticatePasswordActions, params);

export type ActionTypes = ActionsUnion<typeof getAuthenticatorsActions>;
