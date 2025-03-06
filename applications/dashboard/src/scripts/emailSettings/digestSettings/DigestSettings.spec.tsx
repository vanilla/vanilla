/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { render, screen, act, fireEvent, within, RenderResult } from "@testing-library/react";
import { DigestSettings } from "@dashboard/emailSettings/digestSettings/DigestSettings";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { TestDigestModalImpl } from "@dashboard/emailSettings/components/TestDigestModal";
import { DigestScheduleImpl } from "@dashboard/emailSettings/components/DigestSchedule";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import { mockAPI } from "@library/__tests__/utility";
import { LiveAnnouncer } from "react-aria-live";
import MockAdapter from "axios-mock-adapter";
import { IEmailDigestAdditionalSettingPosition } from "@dashboard/emailSettings/EmailSettings.types";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

let mockApi: MockAdapter;
beforeEach(() => {
    mockApi = mockAPI();
});

afterEach(() => {
    mockApi.reset();
    vitest.clearAllMocks();
});

const dummyData = {
    "emailDigest.enabled": true,
    "emailDigest.autosubscribe.enabled": true,
    "emailDigest.imageEnabled": true,
    "emailDigest.postCount": 3,
    "emailDigest.dayOfWeek": 1,
};

const defaultConfigKeysForLookup = [
    "emailDigest.enabled",
    "emailDigest.autosubscribe.enabled",
    "emailDigest.optInTimeFrame",
    "emailDigest.logo",
    "emailDigest.dayOfWeek",
    "emailDigest.postCount",
    "emailDigest.metaOptions",
    "emailDigest.imageEnabled",
    "emailDigest.authorEnabled",
    "emailDigest.viewCountEnabled",
    "emailDigest.commentCountEnabled",
    "emailDigest.scoreCountEnabled",
    "emailDigest.title",
    "emailDigest.includeCommunityName",
    "emailDigest.introduction",
    "emailDigest.footer",
];

async function renderInProvider(configKeysToLookup?: string[], configResponse?: any) {
    mockApi.onGet(/users\/by-name/).reply(200, []);
    mockApi.onGet(/digest\/delivery-dates.*/).reply(200, {});
    mockApi.onGet("/config").reply(200, configResponse ?? dummyData);

    return render(
        <QueryClientProvider client={queryClient}>
            <LiveAnnouncer>
                <TestReduxProvider
                    state={{
                        config: {
                            configsByLookupKey: {
                                [stableObjectHash(configKeysToLookup ?? defaultConfigKeysForLookup)]: {
                                    status: LoadStatus.SUCCESS,
                                    data: configResponse ?? dummyData,
                                },
                            },
                        },
                    }}
                >
                    <DigestSettings />
                </TestReduxProvider>
            </LiveAnnouncer>
        </QueryClientProvider>,
    );
}

describe("DigestSettings", () => {
    let result: RenderResult;

    beforeEach(async () => {
        result = await renderInProvider();
    });

    describe("Email digest section", () => {
        it("We have all expected fields in the form", async () => {
            await vi.dynamicImportSettled();
            const deliveryDate = await result.findByText("Weekly Delivery Day");
            const numberOfPosts = await result.findByText("Number of posts");
            const subjectLineAndTitle = await result.findByText("Subject Line and Title");
            const introduction = await result.findByText("Introduction");
            const footer = await result.findByText("Footer");

            [deliveryDate, numberOfPosts, subjectLineAndTitle, introduction, footer].forEach((field) => {
                expect(field).toBeInTheDocument();
            });
        });

        it("There is a dropdown button that opens Test Digest modal", async () => {
            await vi.dynamicImportSettled();
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
    beforeEach(async () => {
        await act(async () => {
            render(
                <DigestScheduleImpl
                    isFetched={true}
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
    beforeEach(async () => {
        mockApi.onGet(/users\/by-name/).reply(200, []);
        await act(async () => {
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

describe("DigestSettings with extra setting field", () => {
    it("We have all expected fields in the form", async () => {
        DigestSettings.addAdditionalSetting({
            [IEmailDigestAdditionalSettingPosition.AFTER_POST_COUNT]: {
                "emailDigest.myTestField": {
                    type: "text",
                    "x-control": {
                        label: "Test Field Label",
                        inputType: "textBox",
                    },
                },
            },
        });
        const configKeysToLookup = [...defaultConfigKeysForLookup];
        configKeysToLookup.splice(
            configKeysToLookup.indexOf("emailDigest.postCount") + 1,
            0,
            "emailDigest.myTestField",
        );

        const result = await renderInProvider(configKeysToLookup, {
            ...dummyData,
            "emailDigest.myTestField": 5,
        });

        await vi.dynamicImportSettled();
        const newSettingField = await result.findByText("Test Field Label");
        expect(newSettingField).toBeInTheDocument();
    });
});
