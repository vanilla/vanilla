/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, waitFor, screen, fireEvent } from "@testing-library/react";
import { ProfileFieldsFixture } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import ProfileFieldDelete from "@dashboard/userProfiles/components/ProfileFieldDelete";
import { LiveAnnouncer } from "react-aria-live";

const mockField = ProfileFieldsFixture.mockProfileFields()[0];
const mockClose = jest.fn();

const renderInProvider = () => {
    return render(
        ProfileFieldsFixture.createMockProfileFieldsProvider(
            <LiveAnnouncer>
                <ProfileFieldDelete field={mockField} close={mockClose} />,
            </LiveAnnouncer>,
        ),
    );
};

describe("ProfileFieldDelete", () => {
    it("Render profile field label in the title", () => {
        renderInProvider();
        waitFor(() => {
            const title = screen.getByText(`Delete Profile Field: "${mockField.label}"`);
            expect(title).toBeInTheDocument();
        });
    });

    it("Render profile field label in the modal content", () => {
        renderInProvider();
        waitFor(() => {
            const question = screen.getByText(
                `Are you sure you want to delete the "${mockField.label}" profile field?`,
            );
            expect(question).toBeInTheDocument();
        });
    });

    it("Call mock close method by clicking the close icon button", () => {
        renderInProvider();
        const closeButton = screen.getByRole("button", { name: "Close" });
        expect(closeButton).toBeInTheDocument();
        fireEvent.click(closeButton);
        waitFor(() => {
            expect(mockClose).toBeCalled();
        });
    });

    it("Call mock close method by clicking the cancel button", () => {
        renderInProvider();
        const cancelButton = screen.getByRole("button", { name: "Cancel" });
        expect(cancelButton).toBeInTheDocument();
        fireEvent.click(cancelButton);
        waitFor(() => {
            expect(mockClose).toBeCalled();
        });
    });
});
