/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, screen, waitFor, within } from "@testing-library/react";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { LoadStatus } from "@library/@types/api/core";
import DashboardAddEditUser, { IDashboardAddEditProps } from "@dashboard/users/DashboardAddEditUser";
import { EditProfileFieldsFixture } from "@library/editProfileFields/__fixtures__/EditProfileFields.fixtures";
import { ProfileFieldsFixture } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";

const renderInProvider = (props?: Partial<IDashboardAddEditProps>) => {
    const { container } = render(
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
    return container;
};
describe("AddEditUserModal", () => {
    it("Adding user, empty fields", () => {
        renderInProvider({
            profileFields: ProfileFieldsFixture.mockProfileFields() as ProfileField[],
        });

        waitFor(() => {
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
    });

    it("Editing user, some fields should be pre-filled and generate button should show up on radio click", () => {
        renderInProvider({
            userData: { userID: 2, name: "test-user", email: "test@example.com" },
            profileFields: ProfileFieldsFixture.mockProfileFields() as ProfileField[],
        });
        waitFor(() => {
            //corresponding inputs should be pre-filled, username, email, profile-fields etc
            expect(screen.getByDisplayValue("test-user")).toBeTruthy();
            expect(screen.getByDisplayValue("test@example.com")).toBeTruthy();
            expect(
                screen.getByDisplayValue(EditProfileFieldsFixture.mockFields.profileFieldsByUserID[2].data!.text),
            ).toBeTruthy();

            //click on the radio, generate button should appear
            expect(screen.getByRole("radiogroup")).toBeTruthy;
            const setManuallyRadioOption = screen.getByRole("radiogroup").lastElementChild;
            setManuallyRadioOption && fireEvent.click(setManuallyRadioOption);
            expect(screen.getByText("Generate Password")).toBeTruthy;

            //new password field
            const newPasswordInput = screen.getByLabelText("New Password");
            expect(newPasswordInput).toBeTruthy;
        });
    });
});
