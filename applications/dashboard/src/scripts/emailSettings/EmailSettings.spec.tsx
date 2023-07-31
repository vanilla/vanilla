/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, waitFor, screen } from "@testing-library/react";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { EmailSettings } from "@dashboard/emailSettings/EmailSettings";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";

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
    it("Loads the dummy configs", async () => {
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
        waitFor(() => {
            expect(screen.findByText(/Joe Support/)).toBeInTheDocument();
            expect(screen.findByText(/support@email.com/)).toBeInTheDocument();
        });
    });
});
