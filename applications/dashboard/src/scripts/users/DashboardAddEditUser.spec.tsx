/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { act, fireEvent, render, screen } from "@testing-library/react";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { LoadStatus } from "@library/@types/api/core";
import DashboardAddEditUser from "@dashboard/users/DashboardAddEditUser";
import { EditProfileFieldsFixture } from "@library/editProfileFields/__fixtures__/EditProfileFields.fixtures";
import { ProfileFieldsFixture } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { mockAPI } from "@library/__tests__/utility";

// set up a mock API to intercept calls (from the autocomplete lookup) to /roles.
// Otherwise, in the test environment, the API request is rejected, by which time the test runner will fail and blame whichever test it is currently running.
const mockAdapter = mockAPI();
mockAdapter.onGet(/roles/).reply(200, []);

afterAll(() => {
    mockAdapter.restore();
});

const renderInProvider = async (props: Partial<React.ComponentProps<typeof DashboardAddEditUser>> = {}) => {
    await act(async () => {
        render(
            EditProfileFieldsFixture.createMockProfileFieldsProvider(
                <DashboardAddEditUser forceModalVisibility {...props} />,
                {
                    users: {
                        usersByID: {
                            2: {
                                status: LoadStatus.SUCCESS,
                                data: UserFixture.createMockUser({ userID: 2, name: "test-user" }),
                            },
                        },
                        patchStatusByPatchID: {},
                    },
                    userProfiles: {
                        ...EditProfileFieldsFixture.mockFields,
                    },
                },
            ),
        );
    });
};

describe("AddEditUserModal", () => {
    it("Adding user, empty fields", async () => {
        await renderInProvider({
            profileFields: ProfileFieldsFixture.mockProfileFields() as ProfileField[],
        });

        //empty user default fields
        expect(screen.getByLabelText("Username")).toBeTruthy();
        expect(screen.getByLabelText("Username")).toHaveValue("");
        expect(screen.getByLabelText("Email")).toBeTruthy();
        expect(screen.getByLabelText("Email")).toHaveValue("");
        expect(screen.getByLabelText("Password")).toBeTruthy();
        expect(screen.getByLabelText("Password")).toHaveValue("");

        //and some profile fields
        expect(screen.getByLabelText(ProfileFieldsFixture.mockProfileFields()[0]["label"])).toBeTruthy();
        expect(screen.getByLabelText(ProfileFieldsFixture.mockProfileFields()[0]["label"])).toHaveValue("");
        expect(screen.getByLabelText(ProfileFieldsFixture.mockProfileFields()[1]["label"])).toBeTruthy();
        expect(screen.getByLabelText(ProfileFieldsFixture.mockProfileFields()[3]["label"])).toBeTruthy();
    });

    it("Editing user, some fields should be pre-filled and generate button should show up on radio click", async () => {
        await renderInProvider({
            userData: { userID: 2, name: "test-user", email: "test@example.com" },
            profileFields: ProfileFieldsFixture.mockProfileFields() as ProfileField[],
        });

        //corresponding inputs should be pre-filled, username, email, profile-fields etc
        expect(screen.getByDisplayValue("test-user")).toBeTruthy();
        expect(screen.getByDisplayValue("test@example.com")).toBeTruthy();
        expect(
            screen.getByDisplayValue(EditProfileFieldsFixture.mockFields.profileFieldsByUserID[2].data!.text),
        ).toBeTruthy();

        //click on the radio, generate button should appear
        expect(screen.getByRole("radiogroup")).toBeTruthy;

        await act(async () => {
            const setManuallyRadioOption = screen.getByRole("radiogroup").lastElementChild;
            setManuallyRadioOption && fireEvent.click(setManuallyRadioOption);
        });

        expect(screen.getByText("Generate Password")).toBeTruthy;

        //new password field
        const newPasswordInput = screen.getByLabelText("New Password");
        expect(newPasswordInput).toBeTruthy;
    });
});
