/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { generateApiActionCreators, ActionsUnion, apiThunk, createAction } from "@library/redux/utility";
import { IRequestPasswordOptions } from "@dashboard/@types/api/authenticate";

// Authenticating user /authenticate/password
export const POST_REQUEST_PASSWORD_REQUEST = "POST_REQUEST_PASSWORD_REQUEST";
export const POST_REQUEST_PASSWORD_ERROR = "POST_REQUEST_PASSWORD_ERROR";
export const POST_REQUEST_PASSWORD_SUCCESS = "POST_REQUEST_PASSWORD_SUCCESS";
export const AFTER_REQUEST_PASSWORD_SUCCESS_NAVIGATE = "AFTER_REQUEST_PASSWORD_SUCCESS_NAVIGATE";

export const afterRequestPasswordSuccessNavigate = () => createAction(AFTER_REQUEST_PASSWORD_SUCCESS_NAVIGATE);

const otherActions = {
    afterRequestPasswordSuccessNavigate,
};

const requestPasswordActions = generateApiActionCreators(
    POST_REQUEST_PASSWORD_REQUEST,
    POST_REQUEST_PASSWORD_SUCCESS,
    POST_REQUEST_PASSWORD_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {},
    {} as IRequestPasswordOptions,
);

export type ActionTypes = ActionsUnion<typeof requestPasswordActions> | ActionsUnion<typeof otherActions>;

export const postRequestPassword = (params: IRequestPasswordOptions) =>
    apiThunk("post", "users/request-password", requestPasswordActions, params);
