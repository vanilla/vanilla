import { ILinkPages } from "@library/navigation/SimplePagerModel";

type AuthenticatorType = "oauth2";

export interface IGetAllAuthenticatorsParams {
    page: number;
    limit?: number;
    type?: AuthenticatorType;
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
    useBasicAuthToken: boolean;
    postProfileRequest: boolean;
    allowAccessTokens: boolean;
    isOidc?: boolean;
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
