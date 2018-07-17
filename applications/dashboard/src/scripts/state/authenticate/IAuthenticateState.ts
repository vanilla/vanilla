/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { ILoadable } from "@dashboard/state/IState";

export interface IAuthenticator {
    authenticatorID: string;
    type: string;
    isUnique: boolean;
    name: string;
    ui: {
        url: string;
        buttonName: string;
        photoUrl: string | null;
        backgroundColor: string | null;
        foregroundColor: string | null;
    };
    isUserLinked?: boolean;
}

export type ISigninAuthenticatorState = ILoadable<[IAuthenticator]>;
export type IProfileAuthenticatorState = ILoadable<[IAuthenticator]>;

export interface IAuthenticateState {
    signin: ISigninAuthenticatorState;
    profile: IProfileAuthenticatorState;
}
