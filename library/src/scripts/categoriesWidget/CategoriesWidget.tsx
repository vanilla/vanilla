/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { homeWidgetItemVariables, IHomeWidgetItemOptions } from "@library/homeWidget/HomeWidgetItem.styles";
import {
    WidgetContainerDisplayType,
    IHomeWidgetContainerOptions,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import { DeepPartial } from "redux";
import { HomeWidget } from "@library/homeWidget/HomeWidget";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { QuickLinks } from "@library/navigation/QuickLinks";
import { Widget } from "@library/layout/Widget";
import CategoryList from "@library/categoriesWidget/CategoryList";
import { getMeta } from "@library/utility/appUtils";
import CategoryGrid from "@library/categoriesWidget/CategoryGrid";
import { ICategoryItemOptions } from "@library/categoriesWidget/CategoryList.variables";
import { ICategoryItem } from "@library/categoriesWidget/CategoryItem";

export interface ICategoriesWidgetProps {
    title?: string;
    subtitle?: string;
    description?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    itemOptions?: DeepPartial<IHomeWidgetItemOptions>;
    itemData: ICategoryItem[];
    isAsset?: boolean;
    isPreview?: boolean; // preview in layout editor
    categoryOptions?: ICategoryItemOptions;
}

export function CategoriesWidget(props: ICategoriesWidgetProps) {
    const isList =
        !props.containerOptions?.displayType || props.containerOptions?.displayType === WidgetContainerDisplayType.LIST;
    const isLink = props.containerOptions?.displayType === WidgetContainerDisplayType.LINK;

    const categoryListLayoutsEnabled = getMeta("featureFlags.layoutEditor.categoryList.Enabled", false);

    const globalVariables = homeWidgetItemVariables().options;
    const itemVars = homeWidgetItemVariables(props.itemOptions).options;
    const isListWithNoBorder =
        props.containerOptions?.displayType === WidgetContainerDisplayType.LIST &&
        itemVars.box.borderType === BorderType.NONE;

    const containerAsItemBorderType: BorderType | undefined =
        props.containerOptions?.borderType === "navLinks" ? BorderType.SEPARATOR : props.containerOptions?.borderType;

    const defaultItemOptions: DeepPartial<IHomeWidgetItemOptions> = {
        ...props.itemOptions,
        imagePlacement:
            props.containerOptions?.displayType === WidgetContainerDisplayType.LIST
                ? "left"
                : props.itemOptions?.imagePlacement,
        box: {
            ...props.itemOptions?.box,
            borderType: isListWithNoBorder ? BorderType.SEPARATOR : containerAsItemBorderType,
        },
        display: {
            counts: globalVariables.display.counts,
        },
        alignment: props.containerOptions?.headerAlignment ?? globalVariables.alignment,
    };

    const quickLinks = props.itemData.map((item, index) => {
        return {
            id: `${index}`,
            name: item.name ?? "",
            url: (item.to as string) ?? "",
        };
    });

    // under a feature flag for now
    if (categoryListLayoutsEnabled && !isLink) {
        return (
            <Widget>
                {isList ? (
                    <CategoryList {...props} categoryOptions={props.categoryOptions} isPreview={props.isPreview} />
                ) : (
                    <CategoryGrid {...props} itemOptions={defaultItemOptions} />
                )}
            </Widget>
        );
    }

    if (isLink) {
        return <QuickLinks title={props.title} links={quickLinks} containerOptions={props.containerOptions} />;
    } else {
        return (
            <Widget>
                <HomeWidget {...props} itemOptions={defaultItemOptions} />
            </Widget>
        );
    }
}

export default CategoriesWidget;
