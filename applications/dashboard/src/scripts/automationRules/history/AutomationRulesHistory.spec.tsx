/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { mockAPI } from "@library/__tests__/utility";
import { mockDispatches } from "@dashboard/automationRules/AutomationRules.fixtures";
import { render, screen } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import React from "react";
import { IAutomationRulesContext, AutomationRulesContext } from "@dashboard/automationRules/AutomationRules.context";
import { mockRolesState } from "@dashboard/components/panels/MembersSearchFilterPanel.spec";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { createReducer } from "@reduxjs/toolkit";
import { AutomationRulesHistoryImpl } from "@dashboard/automationRules/history/AutomationRulesHistory";
import { MemoryRouter } from "react-router";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import MockAdapter from "axios-mock-adapter";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
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
                        3: UserFixture.createMockUser({ name: "Test User" }),
                    },
                },
                () => {},
            ),
        },
    });
    render(
        <QueryClientProvider client={queryClient}>
            <MemoryRouter>
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
            </MemoryRouter>
        </QueryClientProvider>,
    );
};

describe("AutomationRulesHistory", () => {
    let mockAdapter: MockAdapter;

    beforeAll(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet(/automation-rules\/dispatches/).reply(200, mockDispatches, { "x-app-page-result-count": 5 });
    });

    it("Automation Rules History Page - Filters, Table, Pager", async () => {
        renderInProvider(<AutomationRulesHistoryImpl />);

        const actionFilter = await screen.findByText(/Action Type:/);
        const updatedFilter = await screen.findByText(/Updated:/);
        const lastRunFilter = await screen.findByText(/Last Run:/);
        const statusFilter = await screen.findByText(/Status:/);
        const table = await screen.findByRole("table");
        const pager = await screen.getByLabelText(/Jump to a specific page/);

        [actionFilter, updatedFilter, lastRunFilter, statusFilter, pager, table].forEach((element) => {
            expect(element).toBeInTheDocument();
        });
    });

    it("Automation Rules History Page - Dispatch values are injected in table and and clicking on rule id filter applies it", async () => {
        renderInProvider(<AutomationRulesHistoryImpl />);

        // let's check for 2 dispatches, if there are present, the rest should be present as well
        const rule1 = await screen.findByText(mockDispatches[0].automationRule.name);
        const rule2 = await screen.findByText(mockDispatches[1].automationRule.name);

        [rule1, rule2].forEach((element) => {
            expect(element).toBeInTheDocument();
        });

        const rows = await screen.findAllByRole("row");
        // including header
        expect(rows.length).toBe(mockDispatches.length + 1);

        const table = await screen.findByRole("table");
        const timeElements = table.querySelectorAll("time");
        expect(timeElements.length).toBe(mockDispatches.length * 2);

        timeElements.forEach((time: HTMLTimeElement, index) => {
            const expected = index % 2 === 0 ? "May 5, 2019, 3:51 PM" : "May 5, 2019, 4:51 PM";
            expect(time).toHaveTextContent(expected);
        });

        const ruleIdFilter = await screen.queryByText(/Rule ID/);
        expect(ruleIdFilter).not.toBeInTheDocument();

        const copyToClipBoardButtons = await screen.getAllByRole("button", { name: /Copy Link/ });
        const historyByRuleButtons = await screen.getAllByRole("button", { name: /History By Rule/ });
        expect(copyToClipBoardButtons.length).toBe(mockDispatches.length);
        expect(historyByRuleButtons.length).toBe(mockDispatches.length);

        // Click on the first rule id filter
        historyByRuleButtons[0].click();

        const newRuleIDFilter = await screen.queryByText(
            `Rule ID: ${mockDispatches[0].automationRule.automationRuleID}`,
        );
        expect(newRuleIDFilter).toBeInTheDocument();
    });
});
