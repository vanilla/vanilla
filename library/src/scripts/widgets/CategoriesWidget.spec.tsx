/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { mockCategoriesDataWithHeadings } from "@library/widgets/CategoriesWidget.fixtures";
import { groupCategoryItems, updateCategoryFollowCount } from "@library/widgets/CategoriesWidget.utils";
import cloneDeep from "lodash-es/cloneDeep";
describe("CategoriesWidget", () => {
    it("Ensure we get separate groups of items for grid display with groupCategoryItems() function", () => {
        const categoryItems = cloneDeep(mockCategoriesDataWithHeadings);
        const groupedCategories = groupCategoryItems(categoryItems);

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

    it("Ensure we successfully update followers count for a given category in the tree and return updated data (updateCategoryFollowCount() function).", () => {
        const mockCategories = mockCategoriesDataWithHeadings;
        mockCategories[3].children![1].counts = [
            { count: 99000, countAll: 99000, labelCode: "comments" },
            { count: 99, countAll: 99, labelCode: "discussions" },
            { count: 99099, countAll: 99099, labelCode: "posts" },
            { count: 9, countAll: 9, labelCode: "followers" },
        ];
        const originalMockCategories = cloneDeep(mockCategories);

        // some deeply nested category with id 20
        const result = updateCategoryFollowCount(mockCategories, 20, true);

        // same data with same structure
        expect(result?.length).toBe(mockCategories.length);
        expect(result?.[1].children![0].categoryID).toBe(mockCategories?.[1].children![0].categoryID);

        // this one did not have followers count, now it should, with count 1
        const matchingCategory = result && result[0].children![0].children![0].children![0];
        const followersCount = matchingCategory?.counts.filter((countType) => countType.labelCode === "followers");
        expect(followersCount).toBeTruthy();
        expect(followersCount?.[0].count).toBe(1);

        // this one had followers count, we are going to substract one
        const result1 = updateCategoryFollowCount(mockCategories, mockCategories[3].children![1].categoryID, false);
        const matchingCategory1 = result1 && result1[3].children![1];

        // other counts are preserved
        expect(matchingCategory1?.counts.filter((countType) => countType.labelCode === "discussions")[0].count).toBe(
            mockCategories[3].children![1].counts.filter((countType) => countType.labelCode === "discussions")[0].count,
        );
        // and followers count is updated
        const followersCount1 = matchingCategory1?.counts.filter((countType) => countType.labelCode === "followers");
        expect(followersCount1).toBeTruthy();
        const previousCount = originalMockCategories[3].children![1].counts.filter(
            (countType) => countType.labelCode === "followers",
        )[0].count;
        expect(followersCount1?.[0].count).toBe(previousCount - 1);
    });
});
