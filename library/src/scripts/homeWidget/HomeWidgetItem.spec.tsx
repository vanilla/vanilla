/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor } from "@testing-library/react";
import { HomeWidgetItem } from "@library/homeWidget/HomeWidgetItem";
import { HomeWidgetItemContentType } from "@library/homeWidget/HomeWidgetItem.styles";

const testSrcSet = {
    100: "100.file-ext",
    500: "500.file-ext",
    1500: "1500.file-ext",
};

describe("HomeWidgetItem", () => {
    it("Render icons without src-set", async () => {
        const { container } = render(<HomeWidgetItem to="/" name={"test"} iconUrl="/icon-url.file-ext" />);
        const imageNodes = container.querySelectorAll("img");
        expect(imageNodes.length).toBeGreaterThan(0);
        expect(imageNodes[0]).not.toHaveAttribute("srcset");
        expect(imageNodes[0]).toHaveAttribute("src");
    });
    it("Render icons with src-set", async () => {
        const { container } = render(
            <HomeWidgetItem to="/" name={"test"} iconUrl="/icon-url.file-ext" iconUrlSrcSet={testSrcSet} />,
        );
        const imageNodes = container.querySelectorAll("img");
        expect(imageNodes.length).toBeGreaterThan(0);
        expect(imageNodes[0]).toHaveAttribute("srcset");
    });
    it("Render passed in icon component", async () => {
        const { container } = render(
            <HomeWidgetItem
                to="/"
                name={"test"}
                iconComponent={
                    <svg data-test={"test"}>
                        <text>Icon Component</text>
                    </svg>
                }
            />,
        );
        const customNodes = container.querySelectorAll("svg");
        expect(customNodes.length).toBeGreaterThan(0);
        expect(customNodes[0]).toHaveAttribute("data-test");
    });
    it("Render image without src-set", async () => {
        const { container } = render(
            <HomeWidgetItem
                to="/"
                name={"test"}
                imageUrl="/icon-url.file-ext"
                options={{ contentType: HomeWidgetItemContentType.TITLE_BACKGROUND_DESCRIPTION }}
            />,
        );
        const imageNodes = container.querySelectorAll("img");
        expect(imageNodes.length).toBeGreaterThan(0);
        expect(imageNodes[0]).not.toHaveAttribute("srcset");
        expect(imageNodes[0]).toHaveAttribute("src");
    });
    it("Render icons with src-set", async () => {
        const { container } = render(
            <HomeWidgetItem
                to="/"
                name={"test"}
                imageUrl="/icon-url.file-ext"
                imageUrlSrcSet={testSrcSet}
                options={{ contentType: HomeWidgetItemContentType.TITLE_BACKGROUND_DESCRIPTION }}
            />,
        );
        const imageNodes = container.querySelectorAll("img");
        expect(imageNodes.length).toBeGreaterThan(0);
        expect(imageNodes[0]).toHaveAttribute("srcset");
    });
});
