import React from "react";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { act, fireEvent, render, waitFor, screen, within } from "@testing-library/react";
import { ProfileField, ProfileFieldFormType } from "@dashboard/userProfiles/types/UserProfiles.types";
import { FilteredProfileFields, ProfileFieldValue } from "@dashboard/components/panels/FilteredProfileFields";

const renderInProvider = async (
    mockFields: ProfileField[],
    mockValues: ProfileFieldValue,
    onChange: (delta: ProfileFieldValue) => void,
) => {
    await act(async () => {
        const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider({
            profileFields: mockFields,
        });
        render(
            <MockProfileFieldsProvider>
                <FilteredProfileFields values={mockValues} onChange={onChange} />
            </MockProfileFieldsProvider>,
        );
    });
};

describe("FilteredProfileFields", () => {
    it("Displays a filter label and values", async () => {
        const label = "Mock Field Label";
        const values = ["Mock value one", "Mock value two"];
        const mockProfileFieldValue: ProfileFieldValue = {
            mockField: values,
        };
        const mockField = ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.TOKENS, {
            apiName: "mockField",
            label,
        });
        await renderInProvider([mockField], mockProfileFieldValue, () => null);
        expect(screen.getByText(label)).toBeInTheDocument();
        values.forEach((value) => {
            expect(screen.getByText(value)).toBeInTheDocument();
        });
    });
    it("Displays a date filter with both start and end date", async () => {
        const label = "Mock Date Label";
        const values = { start: "2023-08-20", end: "2023-08-21" };
        const mockProfileFieldValue: ProfileFieldValue = {
            mockField: values,
        };
        const mockField = ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.DATE, {
            apiName: "mockField",
            label,
        });
        await renderInProvider([mockField], mockProfileFieldValue, () => null);
        expect(screen.getByText(`Between ${values.start} - ${values.end}`)).toBeInTheDocument();
    });
    it("Displays a date filter with only start date", async () => {
        const label = "Mock Date Label";
        const values = { start: "2023-08-20" };
        const mockProfileFieldValue: ProfileFieldValue = {
            mockField: values,
        };
        const mockField = ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.DATE, {
            apiName: "mockField",
            label,
        });
        await renderInProvider([mockField], mockProfileFieldValue, () => null);
        expect(screen.getByText(`From ${values.start}`)).toBeInTheDocument();
    });
    it("Displays a date filter with only end date", async () => {
        const label = "Mock Date Label";
        const values = { end: "2023-08-21" };
        const mockProfileFieldValue: ProfileFieldValue = {
            mockField: values,
        };
        const mockField = ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.DATE, {
            apiName: "mockField",
            label,
        });
        await renderInProvider([mockField], mockProfileFieldValue, () => null);
        expect(screen.getByText(`To ${values.end}`)).toBeInTheDocument();
    });
    it("Returns a new value object when a filter is removed", async () => {
        const label = "Mock Field Label";
        const values = ["Mock value one", "Mock value two"];
        const mockProfileFieldValue: ProfileFieldValue = {
            mockField: values,
        };
        const mockField = ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.TOKENS, {
            apiName: "mockField",
            label,
        });

        const mockChange = jest.fn();

        await renderInProvider([mockField], mockProfileFieldValue, mockChange);
        const valueTwoToken = screen.getByText(values[1]).parentElement;
        const removeButton = valueTwoToken && within(valueTwoToken).getByRole("button");

        await act(async () => {
            fireEvent.click(removeButton!);
        });

        waitFor(() => {
            expect(valueTwoToken).not.toBeInTheDocument();
            expect(mockChange).toBeCalled();
            expect(mockChange.mock.calls[1][0]["mockField"]).toBe([values[0]]);
        });
    });
    it("Removes the entire date", async () => {
        const label = "Mock Date Label";
        const values = { end: "2023-08-21" };
        const mockProfileFieldValue: ProfileFieldValue = {
            mockField: values,
        };
        const mockField = ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.DATE, {
            apiName: "mockField",
            label,
        });

        const mockChange = jest.fn();

        await renderInProvider([mockField], mockProfileFieldValue, mockChange);
        const valueToken = screen.getByText(`To ${values.end}`).parentElement;
        const removeButton = valueToken && within(valueToken).getByRole("button");

        await act(async () => {
            fireEvent.click(removeButton!);
        });

        waitFor(() => {
            expect(valueToken).not.toBeInTheDocument();
            expect(mockChange).toBeCalled();
            expect(mockChange.mock.calls[1][0]).toBe({});
        });
    });
});
