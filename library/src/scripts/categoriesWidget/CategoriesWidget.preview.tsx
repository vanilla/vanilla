/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { CategoriesWidget } from "@library/categoriesWidget/CategoriesWidget";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { IHomeWidgetItemOptions } from "@library/homeWidget/HomeWidgetItem.styles";
import { DeepPartial } from "redux";

interface IProps extends Omit<React.ComponentProps<typeof CategoriesWidget>, "itemData"> {
    itemOptions?: DeepPartial<IHomeWidgetItemOptions> & {
        fallbackIcon?: string;
        fallbackImage?: string;
    };
}

export function CategoriesWidgetPreview(props: IProps) {
    return (
        <CategoriesWidget
            {...props}
            itemData={LayoutEditorPreviewData.categories(6, {
                fallbackIcon: props.itemOptions?.fallbackIcon,
                fallbackImage: props.itemOptions?.fallbackImage,
            })}
        />
    );
}
