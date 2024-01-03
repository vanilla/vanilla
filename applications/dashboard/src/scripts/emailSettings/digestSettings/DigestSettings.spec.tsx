/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, act, fireEvent, within, RenderResult } from "@testing-library/react";
import { DigestSettings } from "@dashboard/emailSettings/digestSettings/DigestSettings";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { TestDigestModalImpl } from "@dashboard/emailSettings/components/TestDigestModal";
import { DigestScheduleImpl } from "@dashboard/emailSettings/components/DigestSchedule";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { setMeta } from "@library/utility/appUtils";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";

jest.setTimeout(100000);

const queryClient = new QueryClient();

beforeAll(() => {
    setMeta("featureFlags.Digest.Enabled", true);
});

const dummyData = {
    "emailDigest.enabled": true,
    "emailDigest.imageEnabled": true,
    "emailDigest.postCount": 3,
    "emailDigest.dayOfWeek": 1,
    "emailStyles.format": "html",
    "emailStyles.image": "",
    "emailStyles.textColor": "#663399",
    "emailStyles.backgroundColor": "#8A2BE2",
    "emailStyles.containerBackgroundColor": "#9400D3",
    "emailStyles.buttonTextColor": "#9932CC",
    "emailStyles.buttonBackgroundColor": "#8B008B",
    "outgoingEmails.supportName": "Joe Support",
    "outgoingEmails.supportAddress": "support@email.com",
    "emailNotifications.disabled": false,
    "emailNotifications.fullPost": true,
    "outgoingEmails.footer": '[{"type":"p","children":[{"text":"footer content here"}]}]',
};

describe("DigestSettings", () => {
    let result: RenderResult;

    beforeEach(async () => {
        await act(async () => {
            result = render(
                <QueryClientProvider client={queryClient}>
                    <TestReduxProvider
                        state={{
                            config: {
                                configsByLookupKey: {
                                    [stableObjectHash([
                                        "emailDigest.enabled",
                                        "emailDigest.imageEnabled",
                                        "emailDigest.postCount",
                                        "emailDigest.dayOfWeek",
                                        "emailStyles.format",
                                        "emailStyles.image",
                                        "emailStyles.textColor",
                                        "emailStyles.backgroundColor",
                                        "emailStyles.containerBackgroundColor",
                                        "emailStyles.buttonTextColor",
                                        "emailStyles.buttonBackgroundColor",
                                        "outgoingEmails.supportName",
                                        "outgoingEmails.supportAddress",
                                        "emailNotifications.disabled",
                                        "emailNotifications.fullPost",
                                        "outgoingEmails.footer",
                                    ])]: {
                                        status: LoadStatus.SUCCESS,
                                        data: dummyData,
                                    },
                                },
                            },
                        }}
                    >
                        <DigestSettings />
                    </TestReduxProvider>
                </QueryClientProvider>,
            );
        });
    });

    describe("Email digest section", () => {
        it("There is a dropdown button that opens Test Digest modal", async () => {
            const digestOptionsDropdownButton = await result.findByRole("button", { name: "Email Digest Options" });
            expect(digestOptionsDropdownButton).toBeInTheDocument();

            await act(async () => {
                fireEvent.click(digestOptionsDropdownButton);
            });

            const sendTestDigestButton = await result.findByRole("button", { name: "Send Test Digest" });
            await act(async () => {
                fireEvent.click(sendTestDigestButton);
            });

            const modal = await result.findByRole("dialog");
            expect(modal).toBeInTheDocument();
        });
    });
});

describe("DigestSchedule", () => {
    beforeEach(() => {
        render(
            <DigestScheduleImpl
                upcomingDigestDates="Sat Sep 16th, 2023; Sat Sep 23rd, 2023; Sat Sep 30th, 2023; "
                sentDigestDates={[
                    {
                        dateScheduled: "2023-08-15T17:29:28+00:00",
                        totalSubscribers: 34,
                    },
                    {
                        dateScheduled: "2023-08-08T17:29:28+00:00",
                        totalSubscribers: 65,
                    },
                    {
                        dateScheduled: "2023-08-01T17:29:28+00:00",
                        totalSubscribers: 4,
                    },
                ]}
            />,
        );
    });

    it("Loads the digest schedule", async () => {
        expect(
            await screen.findByText(
                /The next three email digest delivery dates: Sat Sep 16th, 2023; Sat Sep 23rd, 2023; Sat Sep 30th, 2023;/,
            ),
        ).toBeInTheDocument();
    });
});

describe("TestDigestModal", () => {
    beforeEach(() => {
        render(
            <QueryClientProvider client={queryClient}>
                <TestReduxProvider>
                    <TestDigestModalImpl
                        onSubmit={async () => {}}
                        onCancel={() => null}
                        isLoading={false}
                        topLevelErrors={null}
                    />
                </TestReduxProvider>
            </QueryClientProvider>,
        );
    });

    it("Successfully validates email", async () => {
        const form = await screen.findByRole("form");
        expect(form).toBeInTheDocument();

        const recipientField = await within(form).findByLabelText("*Recipient");
        expect(recipientField).toBeInTheDocument();

        const submitButton = await within(form).findByText<HTMLButtonElement>("Send", { exact: true });
        expect(submitButton).toBeInTheDocument();

        await act(async () => {
            fireEvent.change(recipientField, { target: { value: "tsdfdsfsdcom" } });
        });

        await act(async () => {
            fireEvent.click(submitButton);
        });

        expect(within(form).queryByText(/Not a valid email/)).toBeInTheDocument();

        await act(async () => {
            fireEvent.change(recipientField, { target: { value: "test@email.com" } });
        });

        await act(async () => {
            fireEvent.click(submitButton);
        });

        expect(within(form).queryByText(/Not a valid email/)).not.toBeInTheDocument();
    });
});
