/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { CategoriesWidget } from "@library/categoriesWidget/CategoriesWidget";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";

interface IProps extends Omit<React.ComponentProps<typeof CategoriesWidget>, "itemData"> {}

export function CategoriesWidgetPreview(props: IProps) {
    return <CategoriesWidget {...props} itemData={LayoutEditorPreviewData.categories()} />;
}
