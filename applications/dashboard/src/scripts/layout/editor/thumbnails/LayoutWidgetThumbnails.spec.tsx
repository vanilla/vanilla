/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";

import { cleanup, fireEvent, render, waitFor } from "@testing-library/react";
import { LayoutEditorFixture } from "@dashboard/layout/editor/__fixtures__/LayoutEditor.fixtures";
import LayoutWidgetsThumbnails from "@dashboard/layout/editor/thumbnails/LayoutWidgetsThumbnails";
import { LoadStatus } from "@library/@types/api/core";
import { stableObjectHash } from "@vanilla/utils";

const MOCK_WIDGETS = LayoutEditorFixture.widgetData([
    "react.article.articles",
    "react.banner.content",
    "react.banner.full",
]);

const renderInProvider = (element) => {
    return render(LayoutEditorFixture.createMockLayoutsProvider(element));
};

describe("LayoutWidgetThumbnails", () => {
    afterEach(cleanup);
    it("Selection fires onChange event", async () => {
        const mockChange = jest.fn();
        const { findByText } = renderInProvider(
            <LayoutWidgetsThumbnails
                labelID={"testLabel"}
                widgets={MOCK_WIDGETS}
                onChange={mockChange}
                value={"react.article.articles"}
            />,
        );
        const bannerWidget = await findByText("banner content");
        fireEvent.click(bannerWidget);
        expect(mockChange.mock.calls.length).toBe(1);
    });
    it("Clear button empties input value", () => {
        const { findByText, container } = renderInProvider(
            <LayoutWidgetsThumbnails
                labelID={"testLabel"}
                widgets={MOCK_WIDGETS}
                onChange={() => null}
                value={"react.article.articles"}
            />,
        );

        const searchInput = container.querySelector("input");
        searchInput && fireEvent.change(searchInput, { target: { value: "banner" } });
        expect(searchInput).toHaveValue("banner");
        const clearButton = container.querySelector(`button[title*="Clear"]`);
        clearButton && fireEvent.click(clearButton);
        expect(searchInput).toHaveValue("");
    });
    it("Search input filters available widgets", () => {
        const { findByText, container } = renderInProvider(
            <LayoutWidgetsThumbnails
                labelID={"testLabel"}
                widgets={MOCK_WIDGETS}
                onChange={() => null}
                value={"react.article.articles"}
            />,
        );

        const searchInput = container.querySelector("input");
        searchInput && fireEvent.change(searchInput, { target: { value: "banner" } });
        waitFor(async () => {
            const articleWidget = await findByText("article articles");
            expect(articleWidget).not.toBeInTheDocument();
        });
    });
});
