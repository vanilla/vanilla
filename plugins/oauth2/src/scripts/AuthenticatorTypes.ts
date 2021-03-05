import { ILoadable, LoadStatus, IApiError, IFieldError } from "@library/@types/api/core";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import { ActionCreator } from "typescript-fsa";

type AuthenticatorType = "oauth2";

export interface IAuthenticatorStore {
    authenticators: IAuthenticatorState;
}

export interface IAuthenticatorDeleteState {
    authenticatorID?: number;
    error?: IApiError;
    status?: LoadStatus;
}

export interface IAuthenticatorState {
    authenticatorsByID: {
        [id: number]: IAuthenticator;
    };
    authenticatorIDsByHash: {
        [hash: string]: ILoadable<IAuthenticatorIDList>;
    };
    form: IAuthenticatorFormState;
    deleteState: IAuthenticatorDeleteState;
}

export interface IGetAllAuthenticatorsParams {
    page: number;
    limit?: number;
    type?: AuthenticatorType;
}

export interface IAuthenticatorFormState {
    data: IAuthenticator;
    status?: LoadStatus;
    error?: IApiError;
}

export interface IAuthenticatorIDList {
    items: number[];
    pagination: ILinkPages;
}

export interface IAuthenticatorList {
    items: IAuthenticator[];
    pagination: ILinkPages;
}

export interface IAuthenticator {
    authenticatorID?: number;
    name: string;
    clientID: string;
    secret: string;
    type: AuthenticatorType;
    urls: IAuthenticatorUrls;
    userMappings: IAuthenticatorUserMappings;
    authenticationRequest: IAuthenticationRequest;
    useBearerToken: boolean;
    allowAccessTokens: boolean;
    active: boolean;
    default: boolean;
    visible: boolean;
}

export interface IAuthenticationRequest {
    scope?: string;
    prompt?: IAuthenticationRequestPrompt;
}

export enum IAuthenticationRequestPrompt {
    CONSENT = "consent",
    CONSENT_AND_LOGIN = "consent and login",
    LOGIN = "login",
    NONE = "none",
}

export interface IAuthenticatorUserMappings {
    uniqueID?: string;
    email?: string;
    name?: string;
    fullName?: string;
    photoUrl?: string;
    roles?: string;
}

export interface IAuthenticatorUrls {
    signInUrl?: string;
    signOutUrl?: string;
    registerUrl?: string;
    profileUrl?: string;
    authorizeUrl?: string;
    tokenUrl?: string;
}

export interface IAuthenticatorFormHook {
    form: IAuthenticatorFormState;
    update(authenticator: Partial<IAuthenticator>): void;
    save(): Promise<IAuthenticator>;
    fieldsError?: {
        [key: string]: IFieldError[];
    };
}

export const INITIAL_AUTHENTICATOR_USER_MAPPINGS: IAuthenticatorUserMappings = {
    uniqueID: "user_id",
    email: "email",
    name: "displayname",
    fullName: "name",
    photoUrl: "picture",
    roles: "roles",
};

export const INITIAL_AUTHENTICATION_REQUEST: IAuthenticationRequest = {
    scope: "",
    prompt: IAuthenticationRequestPrompt.LOGIN,
};

export const INITIAL_AUTHENTICATOR_FORM_STATE: IAuthenticatorFormState = {
    data: {
        authenticatorID: undefined,
        name: "",
        clientID: "",
        secret: "",
        type: "oauth2",
        urls: {},
        userMappings: INITIAL_AUTHENTICATOR_USER_MAPPINGS,
        authenticationRequest: INITIAL_AUTHENTICATION_REQUEST,
        useBearerToken: false,
        allowAccessTokens: false,
        active: true,
        default: false,
        visible: true,
    },
    status: undefined,
    error: undefined,
};

export const INITIAL_AUTHENTICATOR_STATE: IAuthenticatorState = {
    authenticatorsByID: {},
    authenticatorIDsByHash: {},
    form: INITIAL_AUTHENTICATOR_FORM_STATE,
    deleteState: {},
};
