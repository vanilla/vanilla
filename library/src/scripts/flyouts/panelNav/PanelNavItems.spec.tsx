/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { render, screen } from "@testing-library/react";
import { PanelNavItems } from "@library/flyouts/panelNav/PanelNavItems";

describe("PanelNavItems", () => {
    it("URL transformations with absolute url and relative urls starting with: slash, without slash and tilde", async () => {
        const navItems = [
            {
                name: "Valid_Relative_Url_1",
                url: "/English/categories/category-1",
                parentID: "root",
                recordID: "someID",
                recordType: "customLink",
                children: [],
            },
            {
                name: "Valid_Relative_Url_2",
                url: "~/English/categories/category-1",
                parentID: "root",
                recordID: "someID",
                recordType: "customLink",
                children: [],
            },
            {
                name: "Invalid_Relative_Url",
                url: "English/categories/category-1",
                parentID: "root",
                recordID: "someID",
                recordType: "customLink",
                children: [],
            },
            {
                name: "Valid_Absolute_Url",
                url: "https://vanillaforums.com/",
                parentID: "root",
                recordID: "someID",
                recordType: "customLink",
                children: [],
            },
        ];

        render(
            <PanelNavItems navItems={navItems} pushParentItem={() => {}} popParentItem={() => {}} isNestable={false} />,
        );
        navItems.forEach((navItem) => {
            const item = screen.getByText(navItem.name);

            if (navItem.name === "Valid_Absolute_Url") {
                expect(item).toHaveAttribute("href", navItems[3].url);
            } else {
                expect(item).toHaveAttribute("href", "http://localhost/English/categories/category-1");
            }
        });
    });
});
