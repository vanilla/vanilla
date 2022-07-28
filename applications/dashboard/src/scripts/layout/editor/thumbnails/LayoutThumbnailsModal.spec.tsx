/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { LayoutThumbnailsModal } from "@dashboard/layout/editor/thumbnails/LayoutThumbnailsModal";
import { fireEvent, render, waitFor } from "@testing-library/react";
import { LayoutEditorFixture } from "@dashboard/layout/editor/__fixtures__/LayoutEditor.fixtures";

const MOCK_WIDGETS = LayoutEditorFixture.widgetData([
    "react.article.articles",
    "react.banner.content",
    "react.banner.full",
]);

describe("LayoutThumbnailsModal", () => {
    it("onAddSection fires on form submit", () => {
        const mockAddSection = jest.fn();
        const { container } = render(
            <LayoutThumbnailsModal
                sections={MOCK_WIDGETS}
                title="Choose your widget"
                isVisible={true}
                exitHandler={() => null}
                onAddSection={mockAddSection}
                itemType="widgets"
                selectedSection="react.banner.content"
            />,
        );
        waitFor(async () => {
            const addButton = container.querySelector(`button[type="submit"]`);
            addButton && fireEvent.click(addButton);
            expect(mockAddSection.mock.calls.length).toBe(1);
        });
    });
});
