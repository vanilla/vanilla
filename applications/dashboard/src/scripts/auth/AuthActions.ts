/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserAuthenticator } from "@dashboard/@types/api/authenticate";
import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import actionCreatorFactory from "typescript-fsa";
import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { IUserFragment } from "@library/@types/api/users";
import { formatUrl } from "@library/utility/appUtils";
import { useDispatch } from "react-redux";
import { useMemo } from "react";

const createAction = actionCreatorFactory("@@auth");

export interface IAuthenticatePasswordRequest {
    username: string;
    password: string;
    persist?: boolean;
}

export interface IAuthenticatePasswordResponse extends IUserFragment {}

export interface IResetPasswordRequest {
    email: string;
}

export interface IResetPasswordResponse {}

/**
 * Actions for authentication in Vanilla.
 */
export class AuthActions extends ReduxActions {
    ///
    /// Password reset
    ///

    /**
     * Static action creators.
     */
    public static resetPasswordACs = createAction.async<IResetPasswordRequest, IResetPasswordResponse, IApiError>(
        "RESET_PASSWORD",
    );

    /**
     * Thunk for requesting a password reset.
     */
    public resetPassword = (options: IResetPasswordRequest) => {
        const thunk = bindThunkAction(AuthActions.resetPasswordACs, async () => {
            const response = await apiv2.post("/users/request-password", options);
            return response.data;
        })(options);

        return this.dispatch(thunk);
    };

    ///
    /// Password Signin
    ///

    /**
     * Static action creators.
     */
    public static passwordLoginACs = createAction.async<
        IAuthenticatePasswordRequest,
        IAuthenticatePasswordResponse,
        IApiError
    >("PASSWORD_LOGIN");

    /**
     * Thunk for doing a password login.
     */
    public loginWithPassword = async (params: IAuthenticatePasswordRequest) => {
        const thunk = bindThunkAction(AuthActions.passwordLoginACs, async () => {
            const response = await apiv2.post("/authenticate/password", params);
            return response.data;
        })(params);

        const result: IAuthenticatePasswordResponse | null = await this.dispatch(thunk);
        if (!result) {
            return;
        }

        const urlParms = new URLSearchParams();
        window.location.href = formatUrl(urlParms.get("target") || "/", true);
    };

    ///
    /// Authenticators List.
    ///

    /**
     * Static actions.
     */
    public static getAuthenticatorsACs = createAction.async<{}, IUserAuthenticator[], IApiError>(
        "GET_ALL_AUTHENTICATORS",
    );

    /**
     * Thunk for requesting authenticators.
     */
    public getAuthenticators = () => {
        const thunk = bindThunkAction(AuthActions.getAuthenticatorsACs, async () => {
            const response = await apiv2.get("/authenticate/authenticators");
            return response.data;
        })({});

        return this.dispatch(thunk);
    };
}

export function useAuthActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => {
        return new AuthActions(dispatch, apiv2);
    }, [dispatch]);
    return actions;
}
