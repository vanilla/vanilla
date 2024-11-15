/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { render, waitFor, screen } from "@testing-library/react";
import ConnectionsIndexPage from "@oauth2/pages/AuthenticatorIndexPage";
import { OauthFixture } from "@oauth2/__fixtures__/OauthFixture";
import { LoadStatus } from "@library/@types/api/core";
import { IAuthenticator } from "@oauth2/AuthenticatorTypes";

function emptyStore() {
    return OauthFixture.createOathTestStore({
        authenticatorIDsByHash: {
            [-7521020409]: {
                status: LoadStatus.SUCCESS,
                data: {
                    items: [],
                    pagination: {
                        total: 1,
                        limit: 10,
                        offset: 0,
                    },
                },
            },
        },
    });
}

function storeWithAuthenticators() {
    const auths = [1, 2, 3].map((i) =>
        OauthFixture.createMockAuthenticator({
            authenticatorID: i,
            name: `Mock Authenticator ${i}`,
            clientID: `mock-authenticator-${i}`,
        }),
    );

    const authenticatorsByID = auths.reduce((acc, auth) => {
        return {
            ...acc,
            [`${auth.authenticatorID}`]: auth,
        };
    }, {});

    return OauthFixture.createOathTestStore({
        authenticatorsByID,
        authenticatorIDsByHash: {
            [-7521020409]: {
                status: LoadStatus.SUCCESS,
                data: {
                    items: auths.map((auth) => auth.authenticatorID!),
                    pagination: {
                        total: 1,
                        limit: 10,
                        offset: 0,
                    },
                },
            },
        },
    });
}

describe("AuthenticatorIndexPage", () => {
    it("Renders empty state", async () => {
        render(OauthFixture.providerWrap(<ConnectionsIndexPage />, emptyStore()));
        await waitFor(() => {
            expect(screen.getByText("Add a connection to get started.")).toBeInTheDocument();
        });
    });

    it("Renders list of authenticators without error", async () => {
        render(OauthFixture.providerWrap(<ConnectionsIndexPage />, storeWithAuthenticators()));
        await waitFor(() => {
            expect(screen.getByText("Mock Authenticator 1")).toBeInTheDocument();
            expect(screen.getByText("Mock Authenticator 2")).toBeInTheDocument();
            expect(screen.getByText("Mock Authenticator 3")).toBeInTheDocument();
        });
    });
});
