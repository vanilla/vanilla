/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor } from "@testing-library/react";
import { VanillaLabsPage } from "@dashboard/pages/VanillaLabsPage";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";
import { stableObjectHash } from "@vanilla/utils";

describe("VanillaLabsPage", () => {
    it("Toggles are disabled while loading", async () => {
        const { container } = render(
            <TestReduxProvider
                state={{
                    config: {
                        configsByLookupKey: {
                            [stableObjectHash(["labs.*"])]: {
                                status: LoadStatus.LOADING,
                            },
                        },
                    },
                }}
            >
                <VanillaLabsPage />
            </TestReduxProvider>,
        );
        waitFor(() => {
            const toggles = container.querySelectorAll(`input[id*="formToggle"]`);
            expect(toggles.length).toBeGreaterThan(0);
            toggles.forEach((toggle: HTMLInputElement) => {
                expect(toggle).toHaveAttribute("disabled");
            });
        });
    });
    it("Toggles reflect lab state", async () => {
        const { container } = render(
            <TestReduxProvider
                state={{
                    config: {
                        configsByLookupKey: {
                            [stableObjectHash(["labs.*"])]: {
                                status: LoadStatus.SUCCESS,
                                data: {
                                    "labs.layoutEditor": true,
                                    "labs.newAnalytics": false,
                                },
                            },
                        },
                    },
                }}
            >
                <VanillaLabsPage />
            </TestReduxProvider>,
        );

        waitFor(() => {
            const toggles = container.querySelectorAll(`input[id*="formToggle"]`);
            toggles.forEach((toggle: HTMLInputElement) => {
                const isChecked = !!toggle.getAttribute("checked");
                const labName =
                    toggle.parentElement?.parentElement?.parentElement?.parentElement?.querySelector("h3")?.innerText;
                if (labName === "Layout Editor") {
                    expect(isChecked).toBeTruthy();
                }
                if (labName === "Analytics BETA") {
                    expect(isChecked).toBeFalsy();
                }
            });
        });
    });
    it("Extra lab components are rendered", async () => {
        VanillaLabsPage.registerBeforeLabItems(function TestComponent() {
            return <h1>I am a test component</h1>;
        });

        const { findByText } = render(
            <TestReduxProvider
                state={{
                    config: {
                        configsByLookupKey: {
                            [stableObjectHash(["labs.*"])]: {
                                status: LoadStatus.SUCCESS,
                                data: {
                                    "labs.layoutEditor": true,
                                    "labs.newAnalytics": false,
                                },
                            },
                        },
                    },
                }}
            >
                <VanillaLabsPage />
            </TestReduxProvider>,
        );

        expect(await findByText(/I am a test component/)).toBeInTheDocument();
    });
});
