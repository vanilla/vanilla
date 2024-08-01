/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, screen } from "@testing-library/react";
import { ProfileRedirectForm } from "@dashboard/userProfiles/components/ProfileRedirectForm";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import { act } from "react-dom/test-utils";
import { mockAPI } from "@library/__tests__/utility";

describe("ProfileRedirectForm", () => {
    it("Skeleton renders while config is loading", async () => {
        await act(async () => {
            render(
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
        });
        const loadingRectangleNodes = document.querySelectorAll(`*[class*="loading"]`);
        expect(loadingRectangleNodes.length).toBeGreaterThan(0);
    });

    it("Form displays fetched config values", async () => {
        const expected = {
            "redirectURL.profile": "profile-url-test",
            "redirectURL.message": "profile-message-test",
        };
        await act(async () => {
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
        });

        //we could have used await screen.findByLabelText("Profile" ...`) here, but looks like jest is complaining about some incorrect attributes with a message -
        // - the element associated with this label (<input />) is non-labellable [https://html.spec.whatwg.org/multipage/forms.html#category-label]. If you really need to label a <input />, you can use aria-label or aria-labelledby instead.
        //also, looks like thats a known issue and should be going away with dependency version upgrades https://github.com/testing-library/dom-testing-library/issues/877
        const redirectForm = await screen.findByTestId("profile-redirect-form");
        const inputs = redirectForm.querySelectorAll("input");
        expect(inputs.length).toBe(2);
        expect(inputs[0]).toHaveAttribute("value", "profile-url-test");
        expect(inputs[1]).toHaveAttribute("value", "profile-message-test");
    });

    it("Saves changed values only", async () => {
        const mockAdapter = mockAPI();
        mockAdapter.onPatch(/config/).replyOnce(200, {});
        const expected = {
            "redirectURL.profile": "profile-url-test",
            "redirectURL.message": "profile-message-test",
        };
        await act(async () => {
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
        });

        await act(async () => {
            const redirectForm = await screen.findByTestId("profile-redirect-form");
            const messageInput = redirectForm.querySelectorAll("input")[1];
            fireEvent.change(messageInput, { target: { value: "changed-value" } });
        });

        await act(async () => {
            const saveButton = await screen.findByText("Save");
            fireEvent.click(saveButton);
        });

        expect(mockAdapter.history.patch.length).toBeGreaterThan(0);
    });
});
