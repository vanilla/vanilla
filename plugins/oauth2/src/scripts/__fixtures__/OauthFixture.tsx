/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { INITIAL_AUTHENTICATORS_STATE } from "@oauth2/AuthenticatorReducer";
import { IAuthenticationRequestPrompt, IAuthenticator } from "@oauth2/AuthenticatorTypes";
import { configureStore, createReducer } from "@reduxjs/toolkit";
import { ReactNode } from "react";
import { Provider } from "react-redux";

export class OauthFixture {
    public static createOathTestStore(state = {}) {
        const testReducer = createReducer(
            {
                authenticators: {
                    ...INITIAL_AUTHENTICATORS_STATE,
                    ...state,
                },
            },
            () => {},
        );

        return configureStore({ reducer: testReducer });
    }

    public static providerWrap = (children: ReactNode, store = this.createOathTestStore()) => {
        return <Provider store={store}>{children}</Provider>;
    };

    public static createMockAuthenticator(override?: Partial<IAuthenticator>): IAuthenticator {
        return {
            authenticatorID: 0,
            name: "Mock Authenticator",
            clientID: "mock-authenticator",
            secret: "mock-secret",
            type: "oauth2",
            urls: {
                signInUrl: "https://vanilla.tld/signin",
                signOutUrl: "https://vanilla.tld/signout",
                registerUrl: "https://vanilla.tld/register",
                profileUrl: "https://vanilla.tld/profile",
                authorizeUrl: "https://vanilla.tld/authorize",
                tokenUrl: "https://vanilla.tld/token",
            },
            userMappings: {
                uniqueID: "id",
                email: "email",
                name: "displayname",
                fullName: "name",
                photoUrl: "picture",
                roles: "roles",
            },
            authenticationRequest: {
                scope: "openid",
                prompt: IAuthenticationRequestPrompt.LOGIN,
            },
            useBearerToken: true,
            useBasicAuthToken: false,
            postProfileRequest: false,
            allowAccessTokens: false,
            isOidc: false,
            markVerified: true,
            active: false,
            default: false,
            visible: true,
            ...override,
        };
    }
}
