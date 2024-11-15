/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { RenderResult, act, cleanup, fireEvent, render, within } from "@testing-library/react";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { LoadStatus } from "@library/@types/api/core";
import DashboardAddEditUser from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser";
import {
    ProfileFieldsFixtures,
    mockProfileFieldsByUserID,
} from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { CreatableFieldFormType, CreatableFieldMutability } from "@dashboard/userProfiles/types/UserProfiles.types";
import { mockAPI } from "@library/__tests__/utility";
import { IUsersState } from "@library/features/users/userTypes";
import { createReducer } from "@reduxjs/toolkit";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { LiveAnnouncer } from "react-aria-live";
import MockAdapter from "axios-mock-adapter/types";

// set up a mock API to intercept calls (from the autocomplete lookup) to /roles.
// Otherwise, in the test environment, the API request is rejected, by which time the test runner will fail and blame whichever test it is currently running.
let mockAdapter: MockAdapter;

beforeEach(() => {
    mockAdapter = mockAPI();
    mockAdapter.onGet(/roles/).reply(200, []);
});

const mockUsersState: Partial<IUsersState> = {
    current: {
        ...UserFixture.adminAsCurrent,
    },
    usersByID: {
        2: {
            status: LoadStatus.SUCCESS,
            data: UserFixture.createMockUser({ userID: 2, name: "test-user" }),
        },
    },
    patchStatusByPatchID: {},
};

const mockProfileFields = [
    ...ProfileFieldsFixtures.mockProfileFields(),
    ProfileFieldsFixtures.mockProfileField(CreatableFieldFormType.TEXT, {
        apiName: "mutableTextField",
        mutability: CreatableFieldMutability.ALL,
    }),
    ProfileFieldsFixtures.mockProfileField(CreatableFieldFormType.TEXT, {
        apiName: "immutableTextField",
        mutability: CreatableFieldMutability.NONE,
    }),
];

async function renderInProvider(props: Partial<React.ComponentProps<typeof DashboardAddEditUser>> = {}) {
    const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider({
        profileFields: mockProfileFields,
        extraReducers: {
            users: createReducer(mockUsersState, () => {}),
        },
    });

    return render(
        <QueryClientProvider
            client={
                new QueryClient({
                    defaultOptions: {
                        queries: {
                            retry: false,
                        },
                    },
                })
            }
        >
            <MockProfileFieldsProvider>
                <LiveAnnouncer>
                    <DashboardAddEditUser {...props} profileFields={mockProfileFields} />
                </LiveAnnouncer>
            </MockProfileFieldsProvider>
            ,
        </QueryClientProvider>,
    );
}

const assertProfileFieldsCurrentValues = (result: RenderResult) => {
    // the profile fields' initial values are pre-filled, and correspond to the user data
    mockProfileFields.forEach(({ apiName, label, formType }) => {
        if (formType === CreatableFieldFormType.TOKENS) {
            //Combobox current values are in <label/> tags, so lets try to find them
            mockProfileFieldsByUserID[2]!.data![apiName].forEach((tokenValue) => {
                const elementsWithTokenValue = result.queryAllByText(tokenValue);
                expect(
                    elementsWithTokenValue.find((element) => element.tagName.toLowerCase() === "label"),
                ).toBeInTheDocument();
            });
        } else {
            const input = result.queryByLabelText(label)! as HTMLInputElement;
            expect(formType === CreatableFieldFormType.CHECKBOX ? `${input.checked}` : `${input.value}`).toEqual(
                `${mockProfileFieldsByUserID[2]!.data![apiName] ?? ""}`,
            );
        }
    });
};

describe("Adding a user", () => {
    let result: RenderResult;

    beforeEach(async () => {
        await act(async () => {
            result = await renderInProvider({ forceModalVisibility: true });
        });
    });

    it("The fields are empty.", async () => {
        //There is a modal
        const modal = await result.findByRole("dialog");
        expect(modal).toBeInTheDocument();

        //empty user default fields
        const userNameInput = await within(modal).findByLabelText("*Username");
        expect(userNameInput).toHaveValue("");
        expect(await within(modal).findByLabelText("*Username")).toBeInTheDocument;
        expect(await within(modal).findByLabelText("*Email")).toBeInTheDocument;
        expect(await within(modal).findByLabelText("*Email")).toHaveValue("");
        expect(await within(modal).findByLabelText("Password")).toBeInTheDocument;

        // the profile fields' initial values are empty
        mockProfileFields.forEach(({ label, formType }) => {
            const input = within(modal).queryByLabelText(label)! as HTMLInputElement;
            expect(input).toBeInTheDocument();
            expect(formType === CreatableFieldFormType.CHECKBOX ? input.checked : input.value).toBeFalsy();
        });
    });

    it("Renders the descriptions for each profile field (except checkboxes).", async () => {
        expect.hasAssertions();

        mockProfileFields.forEach(({ formType, description }) => {
            if (!!description && formType !== CreatableFieldFormType.CHECKBOX) {
                const profileFieldDescription = result.queryByText(description);
                expect(profileFieldDescription!).toBeInTheDocument();
            }
        });
    });

    it("The password field has a `Generate Password` button", async () => {
        const modal = await result.findByRole("dialog");
        const passwordInput = await within(modal).findByLabelText("Password");
        const passwordGroup: HTMLElement = passwordInput.closest("[role=group]")!;
        const generatePasswordButton = await within(passwordGroup).findByRole("button", { name: "Generate Password" });
        expect(generatePasswordButton).toBeInTheDocument();
    });
});

describe("Editing self", () => {
    let result: RenderResult;

    const mockUserData = { userID: 2, name: "test-user", email: "test@example.com" };

    beforeEach(async () => {
        await act(async () => {
            result = await renderInProvider({
                userData: mockUserData,
                forceModalVisibility: true,
            });
        });
    });

    it("The user data fields are pre-filled.", async () => {
        //Username and email fields are pre-filled
        expect(await result.findByLabelText("*Username")).toHaveValue(mockUserData.name);
        expect(await result.findByLabelText("*Email")).toHaveValue(mockUserData.email);

        assertProfileFieldsCurrentValues(result);
    });
});

describe("Editing another user", () => {
    let result: RenderResult;

    const mockUserData = {
        userID: 3,
        name: "test-user",
        email: "test@example.com",
        profileFields: {
            mutableTextField: "test",
            immutableTextField: "test",
        },
    };

    afterEach(() => {
        cleanup();
    });

    describe("Password field", () => {
        beforeEach(async () => {
            await act(async () => {
                result = await renderInProvider({
                    userData: mockUserData,
                    forceModalVisibility: true,
                });
            });
        });

        it("The password options radio group is rendered. Selecting the manual option makes a 'New Password' field appear.", async () => {
            const radioGroup = await result.findByLabelText("Password Options");
            expect(radioGroup).toBeInTheDocument();

            await act(async () => {
                const manualOption = await within(radioGroup).findByLabelText("Manually", { exact: false });
                fireEvent.click(manualOption);
            });

            expect(await result.findByText("Generate Password")).toBeInTheDocument;

            const newPasswordInput = await result.findByLabelText("New Password");
            expect(newPasswordInput).toBeInTheDocument();
        });
    });

    describe("Disabled profile fields in the form", () => {
        beforeEach(async () => {
            mockAdapter.onPatch(`/users/${mockUserData.userID}`).replyOnce(200, []);
            await act(async () => {
                result = await renderInProvider({
                    userData: mockUserData,
                    forceModalVisibility: true,
                });
            });
        });

        it("Mutable profile fields are included in the outgoing PATCH request. Disabled profile fields are omitted.", async () => {
            const modal = await result.findByRole("dialog");
            const form = await within(modal).findByRole("form");
            await act(async () => {
                fireEvent.submit(form);
            });

            expect(mockAdapter.history.patch.length).toBe(1);
            const patchPayload = JSON.parse(mockAdapter.history.patch[0].data);
            expect(!!("mutableTextField" in patchPayload.profileFields)).toBe(true);
            expect(!!("immutableTextField" in patchPayload.profileFields)).toBe(false);
        });
    });
});

describe("Accessing the page directly via url", () => {
    let result: RenderResult;
    const mockUserData = { userID: 2, name: "test-user-no-modal", email: "test-user-no-modal@example.com" };

    beforeEach(async () => {
        await act(async () => {
            result = await renderInProvider({
                userData: mockUserData,
                isAddEditUserPage: true,
            });
        });
    });

    it("The form is rendered, but is not in a modal.", async () => {
        const modal = result.queryByRole("dialog");

        expect(modal).not.toBeInTheDocument();

        //fields still need to be present and pre-populated
        const userNameInput = await result.findByDisplayValue(mockUserData.name);
        expect(userNameInput).toBeInTheDocument();
        const emailInput = await result.findByDisplayValue(mockUserData.email);
        expect(emailInput).toBeInTheDocument();

        assertProfileFieldsCurrentValues(result);
    });
});
