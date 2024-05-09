/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor, screen } from "@testing-library/react";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import { UserProfileSettings } from "@dashboard/userProfiles/UserProfileSettings";

describe("UserProfileSettings", () => {
    it("Renders the profile redirection form", () => {
        render(
            <TestReduxProvider
                state={{
                    config: {
                        configsByLookupKey: {
                            [stableObjectHash(["redirectURL.profile", "redirectURL.message"])]: {
                                status: LoadStatus.SUCCESS,
                                data: {
                                    "redirectURL.profile": "profile-url-test",
                                    "redirectURL.message": "profile-message-test",
                                },
                            },
                        },
                    },
                }}
            >
                <UserProfileSettings />
            </TestReduxProvider>,
        );
        expect(screen.getByText(/User Profile/)).toBeInTheDocument();
        expect(screen.getByText(/"Profile" redirection URL/)).toBeInTheDocument();
        expect(screen.getByText(/"New Message" redirection URL/)).toBeInTheDocument();
    });
});
