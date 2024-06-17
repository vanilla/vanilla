/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { mockAPI } from "@library/__tests__/utility";
import { mockRecipesList } from "@dashboard/automationRules/AutomationRules.fixtures";
import { AutomationRulesListImpl } from "@dashboard/automationRules/AutomationRulesList";
import { fireEvent, render, screen, waitFor, within } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import React from "react";
import { IAutomationRulesContext, AutomationRulesContext } from "../AutomationRules.context";
import { mockRolesState } from "@dashboard/components/panels/MembersSearchFilterPanel.spec";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { createReducer } from "@reduxjs/toolkit";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { AutomationRulesPreview } from "@dashboard/automationRules/preview/AutomationRulesPreview";
import {
    EMPTY_AUTOMATION_RULE_FORM_VALUES,
    convertTimeIntervalToApiValues,
    mapApiValuesToFormValues,
} from "@dashboard/automationRules/AutomationRules.utils";
import MockAdapter from "axios-mock-adapter";
import { dateRangeToString } from "@library/search/SearchUtils";
import { AutomationRulesRunOnce } from "@dashboard/automationRules/AutomationRulesRunOnce";
import { AutomationRulesPreviewCollectionRecordsContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewCollectionRecordsContent";
import { CollectionRecordTypes, ICollectionResource } from "@library/featuredCollections/Collections.variables";
import { DiscussionFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Discussion.Fixture";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

let mockAdapter: MockAdapter;
const mockUser = UserFixture.createMockUser({ name: "Test User" });

const mockCollectionResource: ICollectionResource = {
    collectionID: 1,
    recordID: 10,
    recordType: CollectionRecordTypes.DISCUSSION,
    dateAddedToCollection: "2020-10-06T15:30:44+00:00",
    collection: {
        collectionID: 1,
        name: "test_collection",
    },
    record: DiscussionFixture.fakeDiscussions[0],
};

beforeAll(() => {
    mockAdapter = mockAPI();

    mockAdapter.onGet(/automation-rules\/recipes/).reply(200, mockRecipesList);
    mockAdapter.onGet("/users?expand=profileFields").reply(200, [mockUser], { "x-app-page-result-count": 1 });
    mockAdapter
        .onGet("/collections/contents/en?expand=collection")
        .reply(200, [mockCollectionResource], { "x-app-page-result-count": 1 });
});

const renderInProvider = (children: React.ReactNode, values?: IAutomationRulesContext) => {
    const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider({
        extraReducers: {
            roles: createReducer(mockRolesState, () => {}),
            users: createReducer(
                {
                    current: {
                        ...UserFixture.adminAsCurrent,
                    },
                    usersByID: {
                        2: {
                            ...UserFixture.adminAsCurrent,
                        },
                        3: mockUser,
                    },
                },
                () => {},
            ),
        },
    });
    render(
        <QueryClientProvider client={queryClient}>
            <MockProfileFieldsProvider>
                <AutomationRulesContext.Provider
                    value={{
                        profileFields: undefined,
                        automationRulesCatalog: undefined,
                        ...values,
                    }}
                >
                    {children}
                </AutomationRulesContext.Provider>
            </MockProfileFieldsProvider>
        </QueryClientProvider>,
    );
};

describe("AutomationRules Preview", () => {
    it("Toggling the enable button will render the preview modal with relevant content in it", async () => {
        renderInProvider(<AutomationRulesListImpl />);

        const ruleWithEmailDomainTrigger = await screen.findByText(mockRecipesList[3].name);
        expect(ruleWithEmailDomainTrigger).toBeInTheDocument();

        const table = await screen.findByRole("table");
        const tableRows = await table.querySelectorAll("tbody tr");
        expect(tableRows).toHaveLength(mockRecipesList.length);

        const rowWithEmailDomainTrigger = tableRows[3];
        const toggle = rowWithEmailDomainTrigger.querySelector("input[type=checkbox]");
        expect(toggle).toBeInTheDocument();

        toggle && fireEvent.click(toggle);
        const modal = await screen.findByRole("dialog");
        expect(modal).toBeInTheDocument();
        expect(within(modal).queryByText("Confirm")).toBeInTheDocument();
        expect(within(modal).queryByText("Cancel")).toBeInTheDocument();

        const message = await within(modal).findByText(/Users Matching Criteria Now: 1/);
        expect(message).toBeInTheDocument();

        const expectedContent = await within(modal).findAllByText(mockUser.name);
        expect(expectedContent).toBeDefined();
    });
    it("Preview - no trigger values, should show the relevant message", async () => {
        renderInProvider(<AutomationRulesPreview formValues={EMPTY_AUTOMATION_RULE_FORM_VALUES} isVisible={true} />);
        const modal = await screen.findByRole("dialog");
        expect(modal).toBeInTheDocument();

        const message = await within(modal).findByText(/Please set required trigger values to see the preview./);
        expect(message).toBeInTheDocument();

        expect(within(modal).queryByText("Confirm")).not.toBeInTheDocument();
        expect(within(modal).queryByText("Cancel")).not.toBeInTheDocument();
        expect(within(modal).queryAllByText("Close")).toBeDefined();
    });

    it("Preview - no content, should show the relevant message", async () => {
        mockAdapter.onGet("/users?expand=profileFields").reply(200, [], {});
        renderInProvider(
            <AutomationRulesPreview formValues={mapApiValuesToFormValues(mockRecipesList[2])} isVisible={true} />,
        );
        const modal = await screen.findByRole("dialog");
        expect(modal).toBeInTheDocument();

        const message = await within(modal).findByText(/This will not affect anyone right now/);
        expect(message).toBeInTheDocument();
    });

    it("Preview - convertTimeIntervalToApiValues() function, getting the right conversion for time based triggers", async () => {
        const hourInteval = convertTimeIntervalToApiValues(16, "hour", new Date("11/12/2023, 12:34:56 PM"));
        const dayInteval = convertTimeIntervalToApiValues(2, "day", new Date("11/12/2023, 12:34:56 PM"));
        const weekInteval = convertTimeIntervalToApiValues(2, "week", new Date("11/12/2023, 12:34:56 PM"));
        const yearInteval = convertTimeIntervalToApiValues(2, "year", new Date("11/12/2023, 12:34:56 PM"));

        const maxRangeDate = convertTimeIntervalToApiValues(3, "year", new Date("11/12/2023, 12:34:56 PM"));

        [hourInteval, dayInteval, weekInteval, yearInteval].forEach((interval, index) => {
            const assertSmallerThan = (value?: string, needle?: string) => {
                expect(value?.includes("<=")).toBe(true);
                expect(value?.includes("[")).toBe(false);
                expect(value?.includes("]")).toBe(false);
                expect(value?.includes(needle ?? "")).toBe(true);

                // same month unless we are doing 2 weeks variant
                needle !== "29" && expect(value?.includes("11")).toBe(true);

                // same year unless we are doing 2 years variant
                needle !== "2021" && expect(value?.includes("2023")).toBe(true);
            };

            const assertRange = (value?: string) => {
                expect(value?.includes(intervalApiValueAsString ?? ""));
                expect(value?.includes("[")).toBe(true);
                expect(value?.includes("]")).toBe(true);

                // start
                expect(value?.includes("2020")).toBe(true);
                expect(value?.includes("12:34:56")).toBe(true);
                expect(value?.includes("12")).toBe(true);

                // only one comma
                expect([...(value?.matchAll(/,/g) ?? [])]).toHaveLength(1);
            };

            expect(interval instanceof Date).toBe(true);

            const intervalApiValueAsString = dateRangeToString({
                start: undefined,
                end: interval.toLocaleString(),
            });

            const intervalWithMaxRangeApiValueAsString = dateRangeToString({
                start: maxRangeDate.toLocaleString(),
                end: interval.toLocaleString(),
            });

            // depending on locale, date/time format might be different so let's just check if the desired values are present
            if (index === 0) {
                // date should change to 1 day back, e.g format "<=11/11/2023, 8:34:56 PM"
                expect(intervalApiValueAsString?.includes("12")).toBe(false);
                expect(
                    intervalApiValueAsString?.includes("8:34:56") || intervalApiValueAsString?.includes("20:34:56"),
                ).toBe(true);
                assertSmallerThan(intervalApiValueAsString);

                // should be a range, e.g. "[11/12/2020 12:34:56 PM,11/11/2023 8:34:56 PM]"
                assertRange(intervalWithMaxRangeApiValueAsString);
            }
            if (index === 1) {
                // same hour, 2 days back, e.g. "<=11/10/2023, 12:34:56 PM"
                assertSmallerThan(intervalApiValueAsString, "10");

                // should be a range, e.g. "[11/12/2020 12:34:56 PM,11/10/2023 12:34:56 PM]"
                assertRange(intervalWithMaxRangeApiValueAsString);
            }
            if (index === 2) {
                // same hour, 2 weeks back, e.g. "<=10/29/2023, 12:34:56 PM"
                assertSmallerThan(intervalApiValueAsString, "29");

                // should be a range, e.g. "[11/12/2020 12:34:56 PM,10/29/2023 12:34:56 PM]"
                assertRange(intervalWithMaxRangeApiValueAsString);
            }
            if (index === 3) {
                // same hour, 2 years back, e.g. "<=11/12/2021, 12:34:56 PM"
                assertSmallerThan(intervalApiValueAsString, "2021");

                // should be a range, e.g. "[11/12/2020 12:34:56 PM,11/12/2021 12:34:56 PM]"
                assertRange(intervalWithMaxRangeApiValueAsString);
            }
        });
    });

    it("Preview from Run Once - preview modal with relevant content is successfully rendered when clicking on Run Once", async () => {
        renderInProvider(
            <AutomationRulesRunOnce
                formValues={mapApiValuesToFormValues(mockRecipesList[3])}
                automationRuleID={mockRecipesList[3].automationRuleID}
                onConfirmSaveChanges={vitest.fn()}
                onError={vitest.fn()}
            />,
            {},
        );

        const runOnceButton = await screen.findByRole("button", { name: "Run Once" });
        expect(runOnceButton).toBeInTheDocument();

        fireEvent.click(runOnceButton);

        const modal = await screen.findByRole("dialog");
        expect(modal).toBeInTheDocument();

        // its preview modal
        expect(within(modal).queryByText("Automation Rule Preview")).toBeInTheDocument();
    });

    it("Preview from Run Once - save changes confirm modal is rendered instead of preview if we changed form values", async () => {
        renderInProvider(
            <AutomationRulesRunOnce
                formValues={mapApiValuesToFormValues(mockRecipesList[3])}
                formFieldsChanged={true}
                automationRuleID={mockRecipesList[3].automationRuleID}
                onConfirmSaveChanges={vitest.fn()}
                onError={vitest.fn()}
            />,
            {},
        );

        const runOnceButton = await screen.findByRole("button", { name: "Run Once" });
        expect(runOnceButton).toBeInTheDocument();

        fireEvent.click(runOnceButton);

        const modal = await screen.findByRole("dialog");
        expect(modal).toBeInTheDocument();

        // its save changes modal
        expect(within(modal).queryByText("Unsaved Changes")).toBeInTheDocument();
        expect(screen.queryByText("Automation Rule Preview")).not.toBeInTheDocument();

        const saveAndContinueButton = await within(modal).findByRole("button", { name: "Save & Continue" });
        expect(saveAndContinueButton).toBeInTheDocument();

        fireEvent.click(saveAndContinueButton);

        // its preview modal now
        await waitFor(async () => {
            const previewModal = await screen.queryByText("Automation Rule Preview");
            expect(previewModal).toBeInTheDocument();
        });
    });

    it("Preview from Run Once - button is disabled if the rule is running", async () => {
        renderInProvider(
            <AutomationRulesRunOnce
                formValues={mapApiValuesToFormValues(mockRecipesList[3])}
                isRunning={true}
                automationRuleID={mockRecipesList[3].automationRuleID}
                onConfirmSaveChanges={vitest.fn()}
                onError={vitest.fn()}
            />,
            {},
        );

        const runOnceButton = await screen.findByRole("button", { name: "Run Once" });
        expect(runOnceButton).toBeInTheDocument();
        expect(runOnceButton).toBeDisabled();
    });

    it("Preview content - records from collections", async () => {
        renderInProvider(
            <AutomationRulesPreviewCollectionRecordsContent query={{ limit: 30, collectionID: [1] }} />,
            {},
        );

        const previewTitle = await screen.findByText(/Posts Matching Criteria Now: 1/);
        const discussionName = await screen.findByText(mockCollectionResource.record?.name ?? "");
        const collectionName = await screen.findByText(mockCollectionResource.collection?.name ?? "");
        expect(previewTitle).toBeInTheDocument();
        expect(discussionName).toBeInTheDocument();
        expect(collectionName).toBeInTheDocument();
    });
});
