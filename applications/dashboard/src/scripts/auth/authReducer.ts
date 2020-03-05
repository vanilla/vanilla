/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { AuthActions } from "@dashboard/auth/AuthActions";
import { LoadStatus, ILoadable } from "@library/@types/api/core";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IUserAuthenticator } from "@dashboard/@types/api/authenticate";
import { IUserFragment } from "@library/@types/api/users";
import { useSelector } from "react-redux";

export interface IAuthState {
    authenticators: ILoadable<IUserAuthenticator[]>;
    signin: ILoadable<IUserFragment>;
    passwordReset: ILoadable<{}>;
}

export type IRequestPasswordState = ILoadable<{}>;

export interface IAuthStoreState {
    auth: IAuthState;
}

export function useAuthStoreState() {
    return useSelector((state: IAuthStoreState) => state.auth);
}

const AUTH_INITIAL_STATE: IAuthState = {
    authenticators: { status: LoadStatus.PENDING },
    signin: { status: LoadStatus.PENDING },
    passwordReset: { status: LoadStatus.PENDING },
};

export const authReducer = produce(
    reducerWithInitialState(AUTH_INITIAL_STATE)
        .case(AuthActions.getAuthenticatorsACs.started, (nextState, payload) => {
            nextState.authenticators.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(AuthActions.getAuthenticatorsACs.done, (nextState, payload) => {
            nextState.authenticators = {
                status: LoadStatus.SUCCESS,
                data: payload.result,
            };
            return nextState;
        })
        .case(AuthActions.getAuthenticatorsACs.failed, (nextState, payload) => {
            nextState.authenticators = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return nextState;
        })
        .case(AuthActions.resetPasswordACs.started, (nextState, payload) => {
            nextState.passwordReset.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(AuthActions.resetPasswordACs.done, (nextState, payload) => {
            nextState.passwordReset = {
                status: LoadStatus.SUCCESS,
                data: payload.result,
            };
            return nextState;
        })
        .case(AuthActions.resetPasswordACs.failed, (nextState, payload) => {
            nextState.passwordReset = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return nextState;
        })
        .case(AuthActions.passwordLoginACs.started, (nextState, payload) => {
            nextState.signin.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(AuthActions.passwordLoginACs.done, (nextState, payload) => {
            nextState.signin = {
                status: LoadStatus.SUCCESS,
                data: payload.result,
            };
            return nextState;
        })
        .case(AuthActions.passwordLoginACs.failed, (nextState, payload) => {
            nextState.signin = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return nextState;
        }),
);
