/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, waitFor, screen, act, fireEvent } from "@testing-library/react";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { EmailSettings } from "@dashboard/emailSettings/EmailSettings";
import TestDigestModal from "@dashboard/emailSettings/components/TestDigestModal";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

const queryClient = new QueryClient();

const dummmyData = {
    "emailStyles.format": "html",
    "emailStyles.image": "",
    "emailStyles.textColor": "#663399",
    "emailStyles.backgroundColor": "#8A2BE2",
    "emailStyles.buttonBackgroundColor": "#8B008B",
    "emailStyles.buttonTextColor": "#9932CC",
    "emailStyles.containerBackgroundColor": "#9400D3",
    "outgoingEmails.footer": '[{"type":"p","children":[{"text":"footer content here"}]}]',
    "outgoingEmails.supportAddress": "support@email.com",
    "outgoingEmails.supportName": "Joe Support",
    "emailNotifications.disabled": false,
    "emailNotifications.fullPost": true,
    "emailDigest.enabled": true,
    "emailDigest.imageEnabled": false,
    "emailDigest.dayOfWeek": 1,
};

describe("EmailSettings", () => {
    beforeEach(() => {
        render(
            <TestReduxProvider
                state={{
                    config: {
                        configsByLookupKey: {
                            [stableObjectHash([
                                "emailStyles.format",
                                "emailStyles.image",
                                "emailStyles.textColor",
                                "emailStyles.backgroundColor",
                                "emailStyles.containerBackgroundColor",
                                "emailStyles.buttonTextColor",
                                "emailStyles.buttonBackgroundColor",
                                "outgoingEmails.supportName",
                                "outgoingEmails.supportAddress",
                                "outgoingEmails.footer",
                                "emailNotifications.disabled",
                                "emailNotifications.fullPost",
                                "emailDigest.enabled",
                                "emailDigest.imageEnabled",
                                "emailDigest.dayOfWeek",
                            ])]: {
                                status: LoadStatus.SUCCESS,
                                data: dummmyData,
                            },
                        },
                    },
                }}
            >
                <EmailSettings />
            </TestReduxProvider>,
        );
    });

    it("Loads the dummy configs", async () => {
        waitFor(() => {
            expect(screen.findByText(/Joe Support/)).toBeInTheDocument();
            expect(screen.findByText(/support@email.com/)).toBeInTheDocument();
        });
    });

    it("Opens Test Digest modal", async () => {
        waitFor(async () => {
            const digestOptionsButton = await screen.findByRole("button", { name: "Email Digest Options" });
            expect(digestOptionsButton).toBeInTheDocument();

            await act(async () => {
                fireEvent.click(digestOptionsButton);
            });

            const modal = await screen.getByRole("dialog");
            expect(modal).toBeInTheDocument();
        });
    });
});

describe("TestDigestModal", () => {
    beforeEach(() => {
        render(
            <QueryClientProvider client={queryClient}>
                <TestReduxProvider>
                    <TestDigestModal settings={dummmyData} onCancel={() => null} />,
                </TestReduxProvider>
            </QueryClientProvider>,
        );
    });
    it("Throws error with no recipient", async () => {
        waitFor(async () => {
            const form = await screen.getByRole("dialog").querySelector("form");
            expect(form).toBeInTheDocument();

            await act(async () => {
                fireEvent.submit(screen.getByRole("dialog").querySelector("form")!);
            });

            expect(screen.findByText(/DestinationAddress is not a valid email./)).toBeInTheDocument();
        });
    });

    it("Successfully enters recipient", async () => {
        waitFor(async () => {
            const form = await screen.getByRole("dialog").querySelector("form");
            expect(form).toBeInTheDocument();

            const recipientField = await screen.findByLabelText("SearRecipientchMembers");
            expect(recipientField).toBeInTheDocument();

            await act(async () => {
                fireEvent.change(recipientField, { target: { value: "test@email.com" } });
            });

            await act(async () => {
                fireEvent.submit(screen.getByRole("dialog").querySelector("form")!);
            });

            expect(screen.findByText(/DestinationAddress is not a valid email./)).not.toBeInTheDocument();
        });
    });
});
