/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, act, fireEvent, within, RenderResult } from "@testing-library/react";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { EmailSettings } from "@dashboard/emailSettings/notificationSettings/EmailSettings";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { setMeta } from "@library/utility/appUtils";

const queryClient = new QueryClient();

beforeAll(() => {
    setMeta("featureFlags.Digest.Enabled", true);
});

const dummyData = {
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

describe("EmailSettings", () => {
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
                        <EmailSettings />
                    </TestReduxProvider>
                </QueryClientProvider>,
            );
        });
    });

    it("Loads the dummy configs", async () => {
        expect(await result.findByDisplayValue(/Joe Support/)).toBeInTheDocument();
        expect(await result.findByDisplayValue(/support@email.com/)).toBeInTheDocument();
    });
});
