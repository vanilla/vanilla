/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { generateApiActionCreators, ActionsUnion, apiThunk } from "@library/state/utility";
import { IAuthenticatePasswordResponseData, IAuthenticatePasswordParams } from "@dashboard/@types/api";
import apiv2 from "@library/apiv2";
import { AxiosResponse, AxiosError } from "axios";
import { formatUrl } from "@library/application";

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

export const postAuthenticatePassword = (params: IAuthenticatePasswordParams) => dispatch => {
    dispatch(authenticatePasswordActions.request(params));
    apiv2
        .post("/authenticate/password", params)
        .then((response: AxiosResponse) => {
            dispatch(authenticatePasswordActions.success(response, params));
            const urlParms = new URLSearchParams();
            window.location.href = formatUrl(urlParms.get("target") || "/");
        })
        .catch((axiosError: AxiosError) => {
            const error = axiosError.response ? axiosError.response.data : (axiosError as any);
            dispatch(authenticatePasswordActions.error(error));
        });
};

export type ActionTypes = ActionsUnion<typeof authenticatePasswordActions>;
