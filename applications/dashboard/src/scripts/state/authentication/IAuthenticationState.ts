import IBaseState from "../IState";

export enum LoadStatus {
    PENDING = "PENDING",
    LOADING = "LOADING",
    ERROR = "ERROR",
    SUCCESS = "SUCCESS",
};

export interface ILoadable<T> {
    status: LoadStatus;
    error?: string; // TODO: Change
    data: T;
}

export type ISigninAuthenticatorState = ILoadable<[IAuthenticator]>;
export type IProfileAuthenticatorState = ILoadable<[IAuthenticator]>;

export interface IAuthenticationState {
    signin: ISigninAuthenticatorState;
    profile: IProfileAuthenticatorState;
}

export interface IAuthenticator {
    authenticatorID: string;
    type: string;
    isUnique: boolean;
    name: string;
    ui: {
        url: string,
        buttonName: string,
        photoUrl: string | null,
        backgroundColor: string | null,
        foregroundColor: string | null,
    },
    isUserLinked?: boolean,
}

export interface IState extends IBaseState {
    authentication: IAuthenticationState;
}
