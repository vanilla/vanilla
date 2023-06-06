/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { RenderResult, act, render } from "@testing-library/react";
import { EditProfileFields } from "@library/editProfileFields/EditProfileFields";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import {
    ProfileField,
    ProfileFieldDataType,
    ProfileFieldFormType,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { transformUserProfileFieldsData } from "@library/editProfileFields/utils";

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
                    <EditProfileFields userID={2} isOwnProfile={true} />
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
                <EditProfileFields userID={2} isOwnProfile={true} />
            </MockProfileFieldsProvider>,
        );

        expect.assertions(mockDisabledFields.length);

        const inputs = container.querySelectorAll(`input`);
        inputs.forEach((input) => {
            expect(input).toBeDisabled();
        });
    });
});

describe("Utils", () => {
    const mockProfileFieldsWithNumberTokens = [...mockProfileFields];
    mockProfileFieldsWithNumberTokens.push({
        ...(mockProfileFields.find((field) => field.apiName === "tokens") as ProfileField),
        apiName: "number_tokens",
        label: "Number tokens field",
        description: "Mock number tokens for testing purposes",
        dataType: ProfileFieldDataType.NUMBER_MUL,
        dropdownOptions: [99, 999, 9999],
    });

    const mockInitialUserProfileFields = {
        text: "Sample input text",
        date: "2023-12-12T00:00:00+00:00", //date format from BE
        number: 1,
        number_tokens: [99, 999],
    };

    it("Initial user profile fields are transformed correctly.", () => {
        const transformedData = transformUserProfileFieldsData(
            mockInitialUserProfileFields,
            mockProfileFieldsWithNumberTokens,
        );
        //numbers are transformed into string
        transformedData.number_tokens.forEach((token) => {
            expect(typeof token).toBe("string");
        });
        //date to ISO standard string without Z in the end so we ingnore time zones
        expect(transformedData.date).toBe("2023-12-12T00:00:00.000");
    });
});
