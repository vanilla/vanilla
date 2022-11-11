/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor, screen, within } from "@testing-library/react";
import { ProfileFieldsList } from "@dashboard/userProfiles/components/ProfileFieldsList";
import { ProfileFieldsFixture } from "@dashboard/userProfiles/components/ProfileFields.fixtures";

const onEdit = jest.fn();
const onDelete = jest.fn();

const renderInProvider = () => {
    return render(
        ProfileFieldsFixture.createMockProfileFieldsProvider(<ProfileFieldsList onEdit={onEdit} onDelete={onDelete} />),
    );
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
    it("Custom Profile Fields header renders", () => {
        renderInProvider();
        expect(screen.getByText("Custom Profile Fields")).toBeInTheDocument();
    });

    it("Render custom profile fields table headers and actions header is only visible to screen reader", () => {
        renderInProvider();
        columns.forEach(({ label }) => {
            expect(screen.getByRole("columnheader", { name: label })).toBeInTheDocument();
        });

        const actionsHeader = screen.getByRole("columnheader", { name: "actions" });
        expect(within(actionsHeader).getByText("actions")).toHaveClass("sr-only");
    });

    it("Render profile fields in rows with toggle and action buttons", () => {
        renderInProvider();
        const mockFields = ProfileFieldsFixture.mockProfileFields();
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

    it("Disable a profile field", () => {
        renderInProvider();
        const checkbox = within(screen.getAllByRole("row")[1]).getByRole("checkbox");
        expect(checkbox).toBeChecked();
        fireEvent.click(checkbox);
        waitFor(() => {
            expect(checkbox).not.toBeChecked();
        });
    });
});
