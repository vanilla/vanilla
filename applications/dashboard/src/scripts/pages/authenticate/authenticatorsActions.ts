/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { generateApiActionCreators, ActionsUnion, apiThunk } from "@library/redux/utility";
import { IUserAuthenticator } from "@dashboard/@types/api/authenticate";

export const GET_USER_AUTHENTICATORS_REQUEST = "GET_USER_AUTHENTICATORS_REQUEST";
export const GET_USER_AUTHENTICATORS_SUCCESS = "GET_USER_AUTHENTICATORS_SUCCESS";
export const GET_USER_AUTHENTICATORS_ERROR = "GET_USER_AUTHENTICATORS_ERROR";

const getAuthenticatorsActions = generateApiActionCreators(
    GET_USER_AUTHENTICATORS_REQUEST,
    GET_USER_AUTHENTICATORS_SUCCESS,
    GET_USER_AUTHENTICATORS_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IUserAuthenticator[],
);

export const getUserAuthenticators = () =>
    apiThunk("get", "authenticate/authenticators", getAuthenticatorsActions, undefined);

export type ActionTypes = ActionsUnion<typeof getAuthenticatorsActions>;
