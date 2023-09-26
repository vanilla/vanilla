/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { mockCategoriesDataWithHeadings } from "@library/categoriesWidget/CategoriesWidget.fixtures";
import { groupCategoryItems } from "@library/categoriesWidget/CategoriesWidget.utils";

describe("CategoriesWidget", () => {
    it("Ensure we get separate groups of items for grid display", () => {
        const groupedCategories = groupCategoryItems(mockCategoriesDataWithHeadings);

        // we should have 4 top level groups, 2 headings and 2 gridItemsGroup
        expect(groupedCategories.length).toBe(4);
        expect(groupedCategories.filter((group) => group.displayAs === "heading").length).toBe(2);
        expect(groupedCategories.filter((group) => group.displayAs === "gridItemsGroup").length).toBe(2);

        // no heading type items in gridItemsGroup
        groupedCategories
            .filter((group) => group.displayAs === "gridItemsGroup")
            .forEach((group) => {
                expect(group.children?.some((child) => child.displayAs === "heading")).toBe(false);
            });

        // same logic for heading children
        const headingGroups = groupedCategories.filter((group) => group.displayAs === "heading");
        expect(headingGroups[0].children?.length).toBe(2);
        expect(headingGroups[1].children?.length).toBe(3);
        headingGroups.forEach((group) => {
            group.children
                ?.filter((group) => group.displayAs === "gridItemsGroup")
                .forEach((group) => {
                    expect(group.children?.some((child) => child.displayAs === "heading")).toBe(false);
                });
        });
    });
});
