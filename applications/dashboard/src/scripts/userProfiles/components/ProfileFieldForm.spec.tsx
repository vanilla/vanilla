/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render } from "@testing-library/react";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import ProfileFieldForm from "@dashboard/userProfiles/components/ProfileFieldForm";

jest.setTimeout(20000);

const mockSubmit = jest.fn();
const mockClose = jest.fn();

describe("ProfileFieldForm", () => {
    it("Make display options checkboxes are rendered and have desired default values", async () => {
        const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider();

        const { findByRole } = render(
            <MockProfileFieldsProvider>
                <ProfileFieldForm isVisible onSubmit={mockSubmit} onExit={mockClose} title={"Test Title"} />
            </MockProfileFieldsProvider>,
        );

        //show on posts is not checked
        expect(await findByRole("checkbox", { name: "Show on posts" })).not.toBeChecked();

        //show on search is checked
        expect(await findByRole("checkbox", { name: "Show in Member Directory" })).toBeChecked();
    });
});
