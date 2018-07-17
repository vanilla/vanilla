import { IAuthenticateState } from "@dashboard/state/authenticate/IAuthenticateState";

/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

export default interface IState {
    authenticate: IAuthenticateState;
}

export const enum LoadStatus {
    PENDING = "PENDING",
    LOADING = "LOADING",
    ERROR = "ERROR",
    SUCCESS = "SUCCESS",
}

export interface ILoadable<T> {
    status: LoadStatus;
    error?: IError;
    data: T;
}

// TODO: Make this have more stuff.
export type IError = string;
