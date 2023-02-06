/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, waitFor, screen } from "@testing-library/react";
import { EditProfileFields } from "@library/editProfileFields/EditProfileFields";
import { EditProfileFieldsFixture } from "@library/editProfileFields/__fixtures__/EditProfileFields.fixtures";

describe("UserProfileSettings", () => {
    it("UserProfileSettings displays all configured profile fields", async () => {
        const { getByText } = render(
            EditProfileFieldsFixture.createMockProfileFieldsProvider(<EditProfileFields userID={2} />, {
                userProfiles: {
                    ...EditProfileFieldsFixture.mockFields,
                },
            }),
        );

        await waitFor(() => {
            expect(getByText("Text Field")).toBeInTheDocument();
            expect(getByText("Text Multiline Field")).toBeInTheDocument();
            expect(getByText("Dropdown Field")).toBeInTheDocument();
            expect(getByText("Checkbox Field")).toBeInTheDocument();
            expect(getByText("Date Field")).toBeInTheDocument();
            expect(getByText("Number Field")).toBeInTheDocument();
        });
    });

    it("Populates initial values in form controls", async () => {
        const { getByText, getByDisplayValue, container } = render(
            EditProfileFieldsFixture.createMockProfileFieldsProvider(<EditProfileFields userID={2} />, {
                userProfiles: {
                    ...EditProfileFieldsFixture.mockFields,
                },
            }),
        );

        await waitFor(async () => {
            expect(getByDisplayValue("Sample textarea string")).toBeInTheDocument();
            expect(getByDisplayValue("Sample input text")).toBeInTheDocument();
            expect(getByDisplayValue(1)).toBeInTheDocument();
            expect(getByText("Option 2")).toBeInTheDocument();
            expect(getByText("Token 1")).toBeInTheDocument();
            expect(getByText("Token 4")).toBeInTheDocument();
            const checkbox = screen.getByRole("checkbox");
            expect(checkbox).toHaveAttribute("checked");
        });
    });

    it("All inputs are disabled when mutablity is set to none", async () => {
        const { container } = render(
            EditProfileFieldsFixture.createMockProfileFieldsProvider(<EditProfileFields userID={2} />, {
                userProfiles: {
                    ...EditProfileFieldsFixture.mockDisabledFields,
                },
            }),
        );

        const inputs = container.querySelectorAll(`input`);
        inputs.forEach((input) => {
            expect(input).toBeDisabled();
        });
    });
});
