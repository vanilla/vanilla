/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor, screen } from "@testing-library/react";
import { ProfileRedirectForm } from "@dashboard/userProfiles/components/ProfileRedirectForm";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import { act } from "react-dom/test-utils";
import { mockAPI } from "@library/__tests__/utility";

describe("ProfileRedirectForm", () => {
    it("Skeleton renders while config is loading", () => {
        const { container, debug } = render(
            <TestReduxProvider
                state={{
                    config: {
                        configsByLookupKey: {
                            [stableObjectHash(["redirectURL.profile", "redirectURL.message"])]: {
                                status: LoadStatus.LOADING,
                            },
                        },
                    },
                }}
            >
                <ProfileRedirectForm />
            </TestReduxProvider>,
        );
        const loadingRectangleNodes = container.querySelectorAll(`*[class*="loading"]`);
        expect(loadingRectangleNodes.length).toBeGreaterThan(0);
    });

    it("Form displays fetched config values", () => {
        const expected = {
            "redirectURL.profile": "profile-url-test",
            "redirectURL.message": "profile-message-test",
        };
        render(
            <TestReduxProvider
                state={{
                    config: {
                        configsByLookupKey: {
                            [stableObjectHash(["redirectURL.profile", "redirectURL.message"])]: {
                                status: LoadStatus.SUCCESS,
                                data: {
                                    ...expected,
                                },
                            },
                        },
                    },
                }}
            >
                <ProfileRedirectForm />
            </TestReduxProvider>,
        );
        waitFor(async () => {
            const profile = await screen.findByLabelText(`"Profile" redirection URL`);
            const message = await screen.findByLabelText(`"New Message" redirection URL`);
            expect(profile).toHaveAttribute("value", "profile-url-test");
            expect(message).toHaveAttribute("value", "profile-message-test");
        });
    });

    it("Saves changed values only", () => {
        const mockAdapter = mockAPI();
        mockAdapter.onPatch(/config/).replyOnce(200, {});
        const expected = {
            "redirectURL.profile": "profile-url-test",
            "redirectURL.message": "profile-message-test",
        };
        render(
            <TestReduxProvider
                state={{
                    config: {
                        configsByLookupKey: {
                            [stableObjectHash(["redirectURL.profile", "redirectURL.message"])]: {
                                status: LoadStatus.SUCCESS,
                                data: {
                                    ...expected,
                                },
                            },
                        },
                    },
                }}
            >
                <ProfileRedirectForm />
            </TestReduxProvider>,
        );
        waitFor(async () => {
            const message = await screen.findByLabelText(`"New Message" redirection URL`);
            const saveButton = await screen.findByText("Save");
            act(() => {
                fireEvent.change(message, { target: { value: "changed-value" } });
            });
            act(() => {
                fireEvent.click(saveButton);
            });
            expect(mockAdapter.history.patch.length).toBeGreaterThan(0);
        });
    });
});
