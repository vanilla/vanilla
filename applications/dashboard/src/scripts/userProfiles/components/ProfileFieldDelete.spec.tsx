/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, fireEvent, act } from "@testing-library/react";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import ProfileFieldDelete from "@dashboard/userProfiles/components/ProfileFieldDelete";
import { LiveAnnouncer } from "react-aria-live";

const mockField = ProfileFieldsFixtures.mockProfileFields()[0];
const mockClose = jest.fn();

const renderInProvider = () => {
    const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider();
    return render(
        <MockProfileFieldsProvider>
            <LiveAnnouncer>
                <ProfileFieldDelete field={mockField} close={mockClose} />,
            </LiveAnnouncer>
        </MockProfileFieldsProvider>,
    );
};

describe("ProfileFieldDelete", () => {
    afterEach(() => {
        mockClose.mockReset();
    });
    it("Render profile field label in the title", async () => {
        const { findByText } = renderInProvider();
        const title = await findByText(`Delete Profile Field: "${mockField.label}"`);
        expect(title).toBeInTheDocument();
    });

    it("Render profile field label in the modal content", async () => {
        const { findByText } = renderInProvider();
        const question = await findByText(`Are you sure you want to delete the "${mockField.label}" profile field?`);
        expect(question).toBeInTheDocument();
    });

    it("Call mock close method by clicking the close icon button", async () => {
        const { findByRole } = renderInProvider();
        const closeButton = await findByRole("button", { name: "Close" });
        expect(closeButton).toBeInTheDocument();
        await act(async () => {
            fireEvent.click(closeButton);
        });
        expect(mockClose).toHaveBeenCalled();
    });

    it("Call mock close method by clicking the cancel button", async () => {
        const { findByRole } = renderInProvider();
        const cancelButton = await findByRole("button", { name: "Cancel" });
        expect(cancelButton).toBeInTheDocument();
        await act(async () => {
            fireEvent.click(cancelButton);
        });
        expect(mockClose).toHaveBeenCalled();
    });
});
