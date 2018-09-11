/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { ILoadable } from "@library/@types/api";
import { IUserFragment } from "@dashboard/@types/api";
import { IUserAuthenticator } from "@dashboard/@types/api/authenticate";

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
