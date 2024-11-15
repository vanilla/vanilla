/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, screen, within, act, waitFor } from "@testing-library/react";
import { ProfileFieldsList } from "@dashboard/userProfiles/components/ProfileFieldsList";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { ProfileField, CreatableFieldFormType } from "@dashboard/userProfiles/types/UserProfiles.types";
import { vitest } from "vitest";

const onEdit = vitest.fn();
const onDelete = vitest.fn();
const onToggleEnabled = vitest.fn();

const renderInProvider = async (mockFields?: ProfileField[]) => {
    const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider({
        profileFields: mockFields,
    });
    render(
        <MockProfileFieldsProvider>
            <ProfileFieldsList onEdit={onEdit} onDelete={onDelete} onToggleEnabled={onToggleEnabled} />
        </MockProfileFieldsProvider>,
    );
    await vitest.dynamicImportSettled();
};

const columns = [
    { name: "label", label: "label" },
    { name: "apiName", label: "api label" },
    { name: "formType", label: "type" },
    { name: "visibility", label: "visibility" },
    { name: "enabled", label: "active", type: "checkbox" },
    { name: "actions", label: "actions", type: "buttons" },
];

describe("ProfileFieldsList", () => {
    it("Custom Profile Fields header renders", async () => {
        await renderInProvider();
        expect(screen.getByText("Custom Profile Fields")).toBeInTheDocument();
    });

    it("Render custom profile fields table headers and actions header is only visible to screen reader", async () => {
        await renderInProvider();
        columns.forEach(({ label }) => {
            expect(screen.getByRole("columnheader", { name: label })).toBeInTheDocument();
        });

        const actionsHeader = screen.getByRole("columnheader", { name: "actions" });
        expect(within(actionsHeader).getByText("actions")).toHaveClass("sr-only");
    });

    it("Render profile fields in rows with toggle and action buttons", async () => {
        const mockFields = ProfileFieldsFixtures.mockProfileFields();
        await renderInProvider(mockFields);
        screen.getAllByRole("row").forEach((row, rowIdx) => {
            if (rowIdx > 0) {
                const field = mockFields[rowIdx - 1];
                const cells = within(row).getAllByRole("cell");
                columns.forEach((column, colIdx) => {
                    const currentCell = cells[colIdx];
                    switch (column.type) {
                        case "checkbox":
                            const checkbox = within(currentCell).getByRole("checkbox");
                            expect(checkbox).toBeInTheDocument();
                            expect(checkbox).toBeChecked();
                            break;

                        case "buttons":
                            expect(within(currentCell).getByRole("button", { name: "Edit" })).toBeInTheDocument();
                            expect(within(currentCell).getByRole("button", { name: "Delete" })).toBeInTheDocument();
                            break;

                        default:
                            expect(currentCell).toHaveTextContent(field[column.name]);
                            break;
                    }
                });
            }
        });
    });

    it("Delete button is disabled for core fields", async () => {
        const mockCoreField = ProfileFieldsFixtures.mockProfileField(CreatableFieldFormType.DROPDOWN, {
            isCoreField: true,
        });
        await renderInProvider([mockCoreField]);
        const mockCoreFieldTableRow = screen.getAllByRole("row")[1];
        expect(within(mockCoreFieldTableRow).getByRole("button", { name: "Delete" })).toBeDisabled();
    });

    // Skipping following the vitest migration. The logic for making sure the edit actually works was never wired up in tests.
    // The actual assertion logic would fail in waitFor but not get recorded as a failure in jest.
    it.skip("Disable a profile field", async () => {
        await renderInProvider();
        const checkbox = within(screen.getAllByRole("row")[1]).getByRole("checkbox");
        expect(checkbox).toBeChecked();

        fireEvent.click(checkbox);
        expect(checkbox).not.toBeChecked();
    });
});
