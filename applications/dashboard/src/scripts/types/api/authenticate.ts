import { IUserFragment } from "@dashboard/types/api";

/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

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

export const enum AuthenticationStep {
    AUTHENTICATED = "authenticated",
    LINK_USER = "linkUser",
}

export interface IAuthenticateResponse {
    authenticationStep: AuthenticationStep;
    user?: IUserFragment;
    authSessionID?: string;
    targetUrl?: string;
}

export interface IAuthenticatePasswordResponse extends IUserFragment {}
