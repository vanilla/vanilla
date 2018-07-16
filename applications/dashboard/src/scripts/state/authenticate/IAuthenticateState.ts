import { ILoadable } from "@dashboard/state/IState";

export type ISigninAuthenticatorState = ILoadable<[IAuthenticator]>;
export type IProfileAuthenticatorState = ILoadable<[IAuthenticator]>;

export interface IAuthenticateState {
    signin: ISigninAuthenticatorState;
    profile: IProfileAuthenticatorState;
}

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
