/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IHomeWidgetItemOptions } from "@library/homeWidget/WidgetItemOptions";
import {
    WidgetContainerDisplayType,
    IHomeWidgetContainerOptions,
    homeWidgetContainerVariables,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import { DeepPartial } from "redux";
import { QuickLinks } from "@library/navigation/QuickLinks";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import CategoriesWidgetList from "@library/widgets/CategoriesWidget.List";
import CategoriesWidgetGrid from "@library/widgets/CategoriesWidget.Grid";
import type CategoriesWidgetItem from "@library/widgets/CategoriesWidget.Item";

export interface ICategoriesWidgetProps {
    title?: string;
    subtitle?: string;
    description?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    itemOptions?: DeepPartial<IHomeWidgetItemOptions>;
    itemData: CategoriesWidgetItem.Item[];
    isAsset?: boolean;
    isPreview?: boolean; // preview in layout editor
    categoryOptions?: CategoriesWidgetItem.Options;
}

export function CategoriesWidget(props: ICategoriesWidgetProps) {
    const isList = props.containerOptions?.displayType === WidgetContainerDisplayType.LIST;
    const isLink = props.containerOptions?.displayType === WidgetContainerDisplayType.LINK;

    const quickLinks = props.itemData.map((item, index) => {
        return {
            id: `${index}`,
            name: item.name ?? "",
            url: (item.to as string) ?? "",
        };
    });

    if (isLink) {
        return <QuickLinks title={props.title} links={quickLinks} containerOptions={props.containerOptions} />;
    }

    return (
        <LayoutWidget>
            {isList || (!props.containerOptions?.displayType && props.isAsset) ? (
                <CategoriesWidgetList {...props} isPreview={props.isPreview} />
            ) : (
                // grid is the default display type for categories widget
                <CategoriesWidgetGrid
                    {...props}
                    containerOptions={{
                        ...props.containerOptions,
                        maxColumnCount:
                            props.containerOptions?.maxColumnCount ??
                            homeWidgetContainerVariables().options.maxColumnCount,
                    }}
                />
            )}
        </LayoutWidget>
    );
}

export default CategoriesWidget;
