/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ICategoryItem } from "@library/categoriesWidget/CategoriesWidget";

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
                item.children = groupCategoryItems(item.children as ICategoryItem[]);
            }
            groupedData.push(item);
        }
    }

    if (currentGroup) {
        groupedData.push(currentGroup);
    }

    return groupedData;
}
