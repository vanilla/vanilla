/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor, screen, waitForElementToBeRemoved, within } from "@testing-library/react";
import { LayoutEditorFixture } from "@dashboard/layout/editor/__fixtures__/LayoutEditor.fixtures";
import ApplyLayoutOnCategory from "@dashboard/appearance/components/ApplyLayoutOnCategory";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";

describe("ApplyLayoutOnCategory", () => {
    it("We are on categoryList layoutViewType, but we don't have subcommunities enabled, we still apply layout through modal.", async () => {
        render(
            <Provider store={getStore()}>
                <ApplyLayoutOnCategory
                    layout={LayoutEditorFixture.mockLayoutDetails({ layoutViewType: "categoryList" })}
                    forceModalOpen
                />
            </Provider>,
        );

        await waitFor(async () => {
            // we have our modal with categories dropdown
            const modal = await screen.getByRole("dialog");
            expect(modal).toBeInTheDocument();
            expect(within(modal).queryByLabelText("Categories")).toBeInTheDocument();
            expect(screen.getAllByRole("combobox").length).toBe(1);
        });
    });
});
