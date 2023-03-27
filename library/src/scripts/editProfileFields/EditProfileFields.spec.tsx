/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { RenderResult, act, render, screen } from "@testing-library/react";
import { EditProfileFields } from "@library/editProfileFields/EditProfileFields";
import {
    ProfileFieldsFixtures,
    mockProfileFieldsByUserID,
} from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { ProfileFieldFormType } from "@dashboard/userProfiles/types/UserProfiles.types";

const mockProfileFields = ProfileFieldsFixtures.mockProfileFields();

describe("UserProfileSettings", () => {
    let result: RenderResult;

    const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider({
        profileFields: mockProfileFields,
    });

    beforeEach(async () => {
        await act(async () => {
            result = render(
                <MockProfileFieldsProvider>
                    <EditProfileFields userID={2} />
                </MockProfileFieldsProvider>,
            );
        });
    });

    it("It renders inputs for all enabled profile fields.", async () => {
        expect.hasAssertions();
        mockProfileFields.forEach(({ label, formType }) => {
            const input = (
                formType === ProfileFieldFormType.CHECKBOX ? result.queryByLabelText(label) : result.queryByText(label)
            ) as HTMLInputElement;
            expect(input).toBeInTheDocument();
        });
    });

    it("Renders the descriptions for each profile field (except checkboxes).", async () => {
        expect.hasAssertions();
        mockProfileFields.forEach(({ formType, description }) => {
            if (!!description && formType !== ProfileFieldFormType.CHECKBOX) {
                const profileFieldDescription = result.queryByText(description);
                expect(profileFieldDescription!).toBeInTheDocument();
            }
        });
    });

    it("Populates initial values in form controls", async () => {
        expect.assertions(7);
        expect(await result.findByDisplayValue("Sample textarea string")).toBeInTheDocument();
        expect(await result.findByDisplayValue("Sample input text")).toBeInTheDocument();
        expect(await result.findByDisplayValue(1)).toBeInTheDocument();
        expect(await result.findByText("Option 2")).toBeInTheDocument();
        expect(await result.findByText("Token 1")).toBeInTheDocument();
        expect(await result.findByText("Token 4")).toBeInTheDocument();
        const checkbox = result.getByRole("checkbox");
        expect(checkbox).toHaveAttribute("checked");
    });
});

describe("Mutability set to none", () => {
    it("All inputs are disabled", async () => {
        const mockDisabledFields = ProfileFieldsFixtures.mockDisabledFields();
        const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider({
            profileFields: mockDisabledFields,
        });

        const { container } = render(
            <MockProfileFieldsProvider>
                <EditProfileFields userID={2} />
            </MockProfileFieldsProvider>,
        );

        expect.assertions(mockDisabledFields.length);

        const inputs = container.querySelectorAll(`input`);
        inputs.forEach((input) => {
            expect(input).toBeDisabled();
        });
    });
});
