/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ICategoryItem } from "@library/categoriesWidget/CategoryItem";

export type CategoryGridItemsGroup = Pick<ICategoryItem, "children" | "displayAs" | "depth">;

/**
 * Groups all not heading type and same level sibling items, so we render a grid from it.
 *
 * @param itemData - Original array.
 * @returns  Grouped array.
 */
export function groupCategoryItems(itemData: ICategoryItem[]) {
    const groupedData: CategoryGridItemsGroup[] = [];
    let currentGroup: CategoryGridItemsGroup | null = null;

    for (const item of itemData) {
        if (item.displayAs !== "heading") {
            if (!currentGroup) {
                currentGroup = {
                    displayAs: "gridItemsGroup",
                    children: [],
                    depth: item.depth,
                };
            }
            currentGroup.children?.push(item);
        } else {
            if (currentGroup) {
                groupedData.push(currentGroup);
                currentGroup = null;
            }

            if (item.children && item.children.length > 0) {
                item.children = groupCategoryItems(item.children) as ICategoryItem[];
            }
            groupedData.push(item);
        }
    }

    if (currentGroup) {
        groupedData.push(currentGroup);
    }

    return groupedData;
}

/**
 * Finds the category in the tree by its id and updates followers count.
 *
 * @param itemData - Original data.
 * @param categoryID - Item ID.
 * @param addFollower - Add follower count or substract.
 * @returns  New data.
 */
export function updateCategoryFollowCount(
    itemData: ICategoryItem[],
    categoryID: ICategoryItem["categoryID"],
    addFollower: boolean,
) {
    for (let i = 0; i < itemData.length; i++) {
        const currentItem = itemData[i];

        if (currentItem.categoryID === categoryID) {
            const followersCountIndex = currentItem.counts.findIndex((count) => count.labelCode === "followers");
            if (followersCountIndex > -1) {
                const existingCount = currentItem.counts[followersCountIndex].count;
                currentItem.counts[followersCountIndex].count = addFollower
                    ? existingCount + 1
                    : // some extra caution here to prevent negative values, we started counting followers count recently,
                    //so some old followers might not be in the existing count here
                    existingCount > 0
                    ? existingCount - 1
                    : existingCount;
            } else {
                //if no followers count lets add one
                currentItem.counts = [...currentItem.counts, ...[{ count: 1, labelCode: "followers" }]];
            }
            return itemData;
        }

        if (currentItem.children && currentItem.children.length > 0) {
            const updatedChildren = updateCategoryFollowCount(currentItem.children, categoryID, addFollower);

            if (updatedChildren !== null) {
                currentItem.children = updatedChildren;
                return itemData;
            }
        }
    }

    return null;
}
