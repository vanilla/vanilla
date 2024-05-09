/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";

import { act, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { LayoutEditorFixture } from "@dashboard/layout/editor/__fixtures__/LayoutEditor.fixtures";
import LayoutWidgetsThumbnails from "@dashboard/layout/editor/thumbnails/LayoutWidgetsThumbnails";

const MOCK_WIDGETS = LayoutEditorFixture.widgetData([
    "react.article.articles",
    "react.banner.content",
    "react.banner.full",
]);

const renderInProvider = async (element: React.ReactNode) => {
    await act(async () => {
        render(LayoutEditorFixture.createMockLayoutsProvider(element));
    });
};

describe("LayoutWidgetThumbnails", () => {
    it("Selection fires onChange event", async () => {
        const mockChange = jest.fn();
        await renderInProvider(
            <LayoutWidgetsThumbnails
                labelID={"testLabel"}
                widgets={MOCK_WIDGETS}
                onChange={mockChange}
                value={"react.article.articles"}
            />,
        );
        const bannerWidget = await screen.findByText("banner content");
        await act(async () => {
            fireEvent.click(bannerWidget);
        });
        expect(mockChange.mock.calls.length).toBe(1);
    });
    it("Clear button empties input value", async () => {
        await renderInProvider(
            <LayoutWidgetsThumbnails
                labelID={"testLabel"}
                widgets={MOCK_WIDGETS}
                onChange={() => null}
                value={"react.article.articles"}
            />,
        );

        const searchInput = document.querySelector("input");
        await act(async () => {
            searchInput && fireEvent.change(searchInput, { target: { value: "banner" } });
        });
        expect(searchInput).toHaveValue("banner");

        const clearButton = document.querySelector(`button[title*="Clear"]`);
        await act(async () => {
            clearButton && fireEvent.click(clearButton);
        });
        expect(searchInput).toHaveValue("");
    });
    it("Search input filters available widgets", async () => {
        await renderInProvider(
            <LayoutWidgetsThumbnails
                labelID={"testLabel"}
                widgets={MOCK_WIDGETS}
                onChange={() => null}
                value={"react.article.articles"}
            />,
        );

        const searchInput = document.querySelector("input");

        await act(async () => {
            searchInput && fireEvent.change(searchInput, { target: { value: "banner" } });
        });

        waitFor(async () => {
            const articleWidget = screen.queryByText("article articles");
            expect(articleWidget).not.toBeInTheDocument();
        });
    });
});
