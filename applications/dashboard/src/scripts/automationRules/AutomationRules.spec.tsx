/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { mockAPI } from "@library/__tests__/utility";
import {
    mockAutomationRulesCatalog,
    mockCategoriesData,
    mockProfileField,
    mockRecipesList,
} from "@dashboard/automationRules/AutomationRules.fixtures";
import { AutomationRulesList } from "@dashboard/automationRules/AutomationRulesList";
import { fireEvent, render, screen } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import React from "react";
import { AutomationRulesContext, IAutomationRulesContext } from "@dashboard/automationRules/AutomationRules.context";
import { AutomationRulesAddEdit } from "@dashboard/automationRules/pages/AutomationRulesAddEditPage";
import { mockRolesState } from "@dashboard/components/panels/MembersSearchFilterPanel.spec";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { createReducer } from "@reduxjs/toolkit";
import AutomationRulesSummary from "@dashboard/automationRules/AutomationRulesSummary";
import { mapApiValuesToFormValues } from "@dashboard/automationRules/AutomationRules.utils";
import { AutomationRuleActionType } from "@dashboard/automationRules/AutomationRules.types";
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

describe("AutomationRules", () => {
    let mockAdapter: MockAdapter;

    beforeAll(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet("/automation-rules/recipes?expand=all").reply((config) => {
            return config.params.escalations ? [200, [mockRecipesList[5]]] : [200, mockRecipesList];
        });
        mockAdapter.onGet(/automation-rules\/2\/recipe/).reply(200, mockRecipesList[1]);
        mockAdapter.onGet(/automation-rules\/3\/recipe/).reply(200, mockRecipesList[2]);
        mockAdapter.onGet(/automation-rules\/4\/recipe/).reply(200, mockRecipesList[3]);
        mockAdapter.onGet("/categories/search?query=&limit=30").reply(200, mockCategoriesData);
        mockAdapter.onGet("/categories").reply(200, mockCategoriesData);
        mockAdapter.onGet(/\/categories\/\d+/).reply((config) => {
            const id = config.url?.split("/").pop();
            const category = mockCategoriesData.find((category) => category.categoryID === parseInt(id!))!;
            return [200, category];
        });
    });

    it("Automation Rules List Page - Add Rule Button, Table, Search and Filter are on place", async () => {
        renderInProvider(<AutomationRulesList />);

        const addRuleButton = await screen.findByRole("button", { name: "Add Rule" });
        const table = await screen.findByRole("table");
        const searchBar = await screen.findByRole("search");
        const filterButton = await screen.findByRole("button", { name: "Filter" });

        [addRuleButton, table, searchBar, filterButton].forEach((element) => {
            expect(element).toBeInTheDocument();
        });
    });
    it("Automation Rules List Page - Recipes are translated into table values", async () => {
        renderInProvider(<AutomationRulesList />);

        const table = await screen.findByRole("table");
        const tableRows = table.querySelectorAll("tbody tr");
        expect(tableRows).toHaveLength(mockRecipesList.length);
        mockRecipesList.forEach((recipe, index) => {
            const row = tableRows[index];
            expect(row).toBeInTheDocument();
            expect(row).toHaveTextContent(recipe.name);
            expect(row.innerHTML.includes(recipe.dateUpdated)).toBe(true);
            expect(row.innerHTML.includes(recipe.dateLastRun)).toBe(true);
        });

        // first is enabled, second not
        expect(tableRows[0].querySelector("input[type=checkbox]")).toHaveAttribute("checked");
        expect(tableRows[1].querySelector("input[type=checkbox]")).not.toHaveAttribute("checked");

        // delete button is disabled, if dispatch status is pending (rule is still running)
        expect(tableRows[1].querySelectorAll("button")[1]).toBeDisabled();
    });

    it("Escalation Rules List Page - Only rules having escalation actions", async () => {
        renderInProvider(<AutomationRulesList isEscalationRulesList />);

        const table = await screen.findByRole("table");
        const tableRows = table.querySelectorAll("tbody tr");
        expect(tableRows).toHaveLength(1);
        expect(tableRows[0]).toHaveTextContent(mockRecipesList[5].name);
    });

    it("Automation Rules Add/Edit - Adding a rule, check some trigger action dependencies and default values", async () => {
        renderInProvider(<AutomationRulesAddEdit />, {
            automationRulesCatalog: mockAutomationRulesCatalog,
            rolesByID: mockRolesState.rolesByID?.data,
            profileFields: [mockProfileField],
        });

        const triggerField = await screen.findByText("Rule Trigger");
        const actionField = await screen.findByText("Rule Action");
        const dropDowns = await screen.findAllByRole("combobox");

        expect(triggerField).toBeInTheDocument();
        expect(actionField).toBeInTheDocument();
        expect(dropDowns).toHaveLength(2);

        const triggerDropDown = dropDowns[0];
        triggerDropDown && triggerDropDown.click();
        const triggerDropDownOptions = await screen.findAllByRole("option");

        expect(triggerDropDownOptions).toHaveLength(Object.keys(mockAutomationRulesCatalog.triggers).length);

        const staleDiscussionTriggerOption = await screen.findByText(
            mockAutomationRulesCatalog.triggers.staleDiscussionTrigger.name,
        );
        expect(staleDiscussionTriggerOption).toBeInTheDocument();

        staleDiscussionTriggerOption && staleDiscussionTriggerOption.click();

        // corresponding fields are displayed
        const triggerTimeDelayField = await screen.findByText("Trigger Delay");
        const postTypeField = await screen.findByText("Post Type");

        expect(triggerTimeDelayField).toBeInTheDocument();
        expect(postTypeField).toBeInTheDocument();

        // default values for postType field are populated
        const defaultValue1 = await screen.findAllByText("Discussion");
        const defaultValue2 = await screen.findAllByText("Question");

        expect(defaultValue1).toBeDefined();
        expect(defaultValue2).toBeDefined();

        // action dropdown options are only the ones supported by the selected trigger
        const actionDropDown = dropDowns[1];
        actionDropDown && actionDropDown.click();
        const actionDropDownOptions = await screen.findAllByRole("option");

        expect(actionDropDownOptions).toHaveLength(
            mockAutomationRulesCatalog.triggers.staleDiscussionTrigger.triggerActions.length,
        );
        actionDropDownOptions.forEach((option) => {
            const optionText = option.querySelector("span")?.textContent;
            expect(
                mockAutomationRulesCatalog.triggers.staleDiscussionTrigger.triggerActions
                    .map((actionType) => mockAutomationRulesCatalog.actions[actionType].name)
                    .includes((optionText ?? "") as AutomationRuleActionType),
            ).toBeTruthy();
        });
    });

    it("Automation Rules Add/Edit - Adding a rule, the case when values having same name with different parent type (e.g. followCategoryAction and moveToCategoryAction, both have categoryID as a value)", async () => {
        renderInProvider(<AutomationRulesAddEdit />, {
            automationRulesCatalog: mockAutomationRulesCatalog,
            rolesByID: mockRolesState.rolesByID?.data,
            profileFields: [mockProfileField],
        });

        const dropDowns = await screen.findAllByRole("combobox");
        expect(dropDowns).toHaveLength(2);

        const actionDropDown = dropDowns[1];
        fireEvent.click(actionDropDown);

        const categoryFollowActionOption = await screen.findByText(
            mockAutomationRulesCatalog.actions.categoryFollowAction.name,
        );
        expect(categoryFollowActionOption).toBeInTheDocument();

        fireEvent.click(categoryFollowActionOption);

        const categoryIDField = await screen.findByText("Category to Follow");
        expect(categoryIDField).toBeInTheDocument();

        const newDropDowns = await screen.findAllByRole("combobox");

        // our category field is the last one
        const categoryDropDown = newDropDowns[2];
        fireEvent.click(categoryDropDown);

        // only as a dropdown option
        const category1 = await screen.findByText(mockCategoriesData[0].name);
        expect(category1).toBeInTheDocument();

        // select the first category
        fireEvent.click(category1);

        const allCategory1 = await screen.findAllByText(mockCategoriesData[0].name);

        // dropdown option and the actual selected token/option
        expect(allCategory1).toHaveLength(2);

        // now let's change the action to moveToCategoryAction and check categoryID field
        fireEvent.click(actionDropDown);

        const categoryMoveActionOption = await screen.findByText(/Move/);

        expect(categoryMoveActionOption).toBeInTheDocument();

        fireEvent.click(categoryMoveActionOption);

        const newCategoryFieldLabel = await screen.findByText("Category to move to");
        expect(newCategoryFieldLabel).toBeInTheDocument();

        // only as dropdown option, selected token/option is gone
        const previouslySelectedCategory = await screen.findAllByText(mockCategoriesData[0].name);
        expect(previouslySelectedCategory).toHaveLength(1);
    });

    it("Automation Rules Add/Edit - Recipe data (profileFieldTrigger-addRemoveRoleAction) is correctly populated in form values", async () => {
        renderInProvider(<AutomationRulesAddEdit automationRuleID="3" />, {
            automationRulesCatalog: mockAutomationRulesCatalog,
            rolesByID: mockRolesState.rolesByID?.data,
            profileFields: [mockProfileField],
        });

        const dropDowns = await screen.findAllByRole("combobox");
        const dropDownValues = dropDowns.map((element) => element["value"]);

        expect(dropDownValues.includes(mockAutomationRulesCatalog.triggers.profileFieldTrigger.name)).toBe(true);
        expect(dropDownValues.includes(mockAutomationRulesCatalog.actions.addRemoveRoleAction.name)).toBe(true);
        expect(
            dropDownValues.includes(Object.keys(mockRecipesList[2].trigger?.triggerValue?.profileField ?? {})[0]),
        ).toBe(true);
        expect(dropDownValues.includes(mockRecipesList[2].action.actionValue.addRoleID?.toString())).toBe(true);
        expect(dropDownValues.includes(mockRecipesList[2].action.actionValue.removeRoleID?.toString())).toBe(true);

        const textFields = await screen.findAllByRole("textbox");
        const textFieldValues = textFields.map((element) => element["value"]);
        expect(
            textFieldValues.includes(mockRecipesList[2].trigger?.triggerValue?.profileField?.test_text_profileField),
        ).toBe(true);
    });

    it("Automation Rules Add/Edit - Recipe data (emailDomainTrigger-categoryFollowAction) is correctly populated in form values", async () => {
        renderInProvider(<AutomationRulesAddEdit automationRuleID="4" />, {
            automationRulesCatalog: mockAutomationRulesCatalog,
            rolesByID: mockRolesState.rolesByID?.data,
            categories: mockCategoriesData,
        });

        const dropDowns = await screen.findAllByRole("combobox");
        const dropDownValues = dropDowns.map((element) => element["value"]);

        expect(dropDownValues.includes(mockAutomationRulesCatalog.triggers.emailDomainTrigger.name)).toBe(true);
        expect(dropDownValues.includes(mockAutomationRulesCatalog.actions.categoryFollowAction.name)).toBe(true);

        // 2 categories are populated in the dropdown as tokens (they are also in rich options that's why there are 2 of them for each category)
        const category1 = await screen.findAllByText(mockCategoriesData[0].name);
        const category2 = await screen.findAllByText(mockCategoriesData[1].name);
        expect(category1.length).toBeGreaterThan(0);
        expect(category2.length).toBeGreaterThan(0);

        const textFields = await screen.findAllByRole("textbox");
        const textFieldValues = textFields.map((element) => element["value"]);
        expect(textFieldValues.includes(mockRecipesList[3].trigger?.triggerValue?.emailDomain)).toBe(true);
    });
    it("Automation Rules Add/Edit - Editing a rule when its running - Disabled view", async () => {
        renderInProvider(<AutomationRulesAddEdit automationRuleID="2" />, {
            automationRulesCatalog: mockAutomationRulesCatalog,
            rolesByID: mockRolesState.rolesByID?.data,
        });

        // all inputs are  disabled
        const dropDowns = await screen.findAllByRole("combobox");
        const inputs = await screen.findAllByRole("textbox");
        inputs.forEach((input) => {
            expect(input).toBeDisabled();
        });
        dropDowns.forEach((input) => {
            expect(input).toBeDisabled();
        });

        // save button is disabled
        const saveButton = await screen.findByRole("button", { name: "Save" });
        expect(saveButton).toBeDisabled();

        // running status is displayed
        const running = await screen.findByText("Running");
        expect(running).toBeInTheDocument();
    });

    it("Escalation Rules Add/Edit - Adding an escalation rule, triggers dropdown should have only triggers containing at least one escalation actions, actions dropdown should contain anly escalation actions", async () => {
        const escalationActions = ["createEscalationAction", "escalateToZendeskAction", "escalateGithubIssueAction"];
        const mockEscalationRulesCatalog = { ...mockAutomationRulesCatalog };
        Object.keys(mockEscalationRulesCatalog.triggers).forEach((trigger) => {
            if (
                !mockEscalationRulesCatalog.triggers[trigger].triggerActions.some((action) =>
                    escalationActions.includes(action),
                )
            ) {
                delete mockEscalationRulesCatalog.triggers[trigger];
            }
        });
        Object.keys(mockEscalationRulesCatalog.actions).forEach((action) => {
            if (!escalationActions.includes(action)) {
                delete mockEscalationRulesCatalog.actions[action];
            }
        });
        renderInProvider(<AutomationRulesAddEdit />, {
            automationRulesCatalog: mockEscalationRulesCatalog,
            rolesByID: mockRolesState.rolesByID?.data,
            profileFields: [mockProfileField],
        });

        const dropDowns = await screen.findAllByRole("combobox");

        expect(dropDowns).toHaveLength(2);

        const triggerDropDown = dropDowns[0];
        triggerDropDown && triggerDropDown.click();
        const triggerDropDownOptions = await screen.findAllByRole("option");

        expect(triggerDropDownOptions).toHaveLength(Object.keys(mockEscalationRulesCatalog.triggers).length);

        const staleDiscussionTriggerOption = await screen.findByText(
            mockAutomationRulesCatalog.triggers.staleDiscussionTrigger.name,
        );
        expect(staleDiscussionTriggerOption).toBeInTheDocument();

        staleDiscussionTriggerOption && staleDiscussionTriggerOption.click();

        // action dropdown options are only escalation actions supported by the selected trigger
        const actionDropDown = dropDowns[1];
        actionDropDown && actionDropDown.click();
        const actionDropDownOptions = await screen.findAllByRole("option");

        const expectedActions = mockEscalationRulesCatalog.triggers.staleDiscussionTrigger.triggerActions.filter(
            (actionType) => escalationActions.includes(actionType),
        );

        expect(actionDropDownOptions).toHaveLength(expectedActions.length);

        actionDropDownOptions.forEach((option) => {
            const optionText = option.querySelector("span")?.textContent;
            expect(
                expectedActions
                    .map((actionType) => mockEscalationRulesCatalog.actions[actionType].name)
                    .includes((optionText ?? "") as AutomationRuleActionType),
            ).toBeTruthy();
        });
    });
    it("Automation Rules Summary - Form values (profileFieldTrigger-addRemoveRoleAction) are present in summary section", async () => {
        renderInProvider(<AutomationRulesSummary formValues={mapApiValuesToFormValues(mockRecipesList[2])} />, {
            automationRulesCatalog: mockAutomationRulesCatalog,
            rolesByID: mockRolesState.rolesByID?.data,
            profileFields: [mockProfileField],
        });

        const profileFieldName = await screen.findByText(mockProfileField.label);
        const profileFieldValue = await screen.findByText(
            mockRecipesList[2].trigger?.triggerValue?.profileField?.test_text_profileField as string,
        );
        const addRoleName = await screen.findByText(
            mockRolesState.rolesByID?.data?.[mockRecipesList[2].action.actionValue.addRoleID].name ?? "",
        );
        const removeRoleName = await screen.findByText(
            mockRolesState.rolesByID?.data?.[mockRecipesList[2].action.actionValue.removeRoleID].name ?? "",
        );

        [profileFieldName, profileFieldValue, addRoleName, removeRoleName].forEach((name) => {
            expect(name).toBeInTheDocument();
        });
    });

    it("Automation Rules Summary - Form values (emailDomainTrigger-categoryFollowAction) are present in summary section", async () => {
        renderInProvider(<AutomationRulesSummary formValues={mapApiValuesToFormValues(mockRecipesList[3])} />, {
            automationRulesCatalog: mockAutomationRulesCatalog,
            rolesByID: mockRolesState.rolesByID?.data,
            categories: mockCategoriesData,
        });

        const emailDomains = mockRecipesList[3].trigger?.triggerValue?.emailDomain
            .split(",")
            .map((item) => item.trim());

        const emailDomain1 = await screen.findByText(emailDomains[0]);
        const emailDomain2 = await screen.findByText(emailDomains[1]);

        const category1 = await screen.findByText(
            mockCategoriesData.find(
                (category) => category.categoryID === mockRecipesList[3].action.actionValue.categoryID[0],
            )?.name ?? "",
        );
        const category2 = await screen.findByText(
            mockCategoriesData.find(
                (category) => category.categoryID === mockRecipesList[3].action.actionValue.categoryID[1],
            )?.name ?? "",
        );

        [emailDomain1, emailDomain2, category1, category2].forEach((name) => {
            expect(name).toBeInTheDocument();
        });
    });
});
