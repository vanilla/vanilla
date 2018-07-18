/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { ILoadable } from "@dashboard/@types/api";
import { IUserAuthenticator } from "@dashboard/@types/api/authenticate";

export type IAuthenticatorState = ILoadable<IUserAuthenticator[]>;

export interface ISessionState {
    authenticators: IAuthenticatorState;
}

export interface IStoreState {
    session: ISessionState;
}
