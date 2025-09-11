/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Vanilla
 * @license gpl-2.0-only
 */

import type CategoriesWidgetItem from "@library/widgets/CategoriesWidget.Item";
import { createContext, useContext } from "react";

export interface ICategoryItemFragmentContext {
    categoryItem: CategoriesWidgetItem.Item;
    onCategoryFollowChange?: (categoryWithNewPreferences: {
        categoryID: CategoriesWidgetItem.Item["categoryID"];
        preferences: CategoriesWidgetItem.Item["preferences"];
    }) => void;
    options: CategoriesWidgetItem.Options;
    isPreview?: boolean;
}

export const CategoryItemFragmentContext = createContext<ICategoryItemFragmentContext>(
    // Context required or things blow up.
    {} as any,
);

export function useCategoryItemFragmentContext() {
    const value = useContext(CategoryItemFragmentContext);
    if (Object.keys(value).length === 0) {
        throw new Error("useCategoryItemFragmentContext must be used within a CategoryItemFragmentContext provider");
    }
    return value;
}
