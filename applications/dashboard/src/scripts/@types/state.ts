/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import { IUserAuthenticator } from "@dashboard/@types/api/authenticate";
import { ILoadable } from "@library/@types/api/core";

export type IAuthenticatorState = ILoadable<IUserAuthenticator[]>;
export type IPasswordState = ILoadable<IUserFragment>;

export interface IAuthenticateState {
    authenticators: IAuthenticatorState;
    password: IPasswordState;
}

export type IRequestPasswordState = ILoadable<{}>;

export interface IUsersState {
    requestPassword: IRequestPasswordState;
}

export interface IStoreState {
    authenticate: IAuthenticateState;
    users: IUsersState;
}
