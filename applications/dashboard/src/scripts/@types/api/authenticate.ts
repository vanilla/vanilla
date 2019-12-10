/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";

export interface IUserAuthenticator {
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
    sso?: any;
}

export enum AuthenticationStep {
    AUTHENTICATED = "authenticated",
    LINK_USER = "linkUser",
}

export interface IAuthenticateResponse {
    authenticationStep: AuthenticationStep;
    user?: IUserFragment;
    authSessionID?: string;
    targetUrl?: string;
}
