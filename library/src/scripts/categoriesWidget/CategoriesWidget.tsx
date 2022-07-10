/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { homeWidgetItemVariables, IHomeWidgetItemOptions } from "@library/homeWidget/HomeWidgetItem.styles";
import {
    WidgetContainerDisplayType,
    IHomeWidgetContainerOptions,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import { DeepPartial } from "redux";
import { IHomeWidgetItemProps } from "@library/homeWidget/HomeWidgetItem";
import { HomeWidget } from "@library/homeWidget/HomeWidget";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { QuickLinks } from "@library/navigation/QuickLinks";
import { Widget } from "@library/layout/Widget";
interface IProps {
    title?: string;
    subtitle?: string;
    description?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    itemOptions?: DeepPartial<IHomeWidgetItemOptions>;
    itemData: IHomeWidgetItemProps[];
    maxItemCount?: number; //this will probably go away with categories API "limit" full support
}

export function CategoriesWidget(props: IProps) {
    const itemVars = homeWidgetItemVariables(props.itemOptions).options;
    const isListWithNoBorder =
        props.containerOptions?.displayType === WidgetContainerDisplayType.LIST &&
        itemVars.box.borderType === BorderType.NONE;
    const defaultItemOptions = {
        ...props.itemOptions,
        imagePlacement:
            props.containerOptions?.displayType === WidgetContainerDisplayType.LIST
                ? "left"
                : props.itemOptions?.imagePlacement,
        box: {
            ...props.itemOptions?.box,
            borderType: isListWithNoBorder ? BorderType.SEPARATOR : props.itemOptions?.box?.borderType,
        },
    };

    const quickLinks = props.itemData.map((item, index) => {
        return {
            id: `${index}`,
            name: item.name ?? "",
            url: (item.to as string) ?? "",
        };
    });

    if (props.containerOptions?.displayType === WidgetContainerDisplayType.LINK) {
        return <QuickLinks title={props.title} links={quickLinks} containerOptions={props.containerOptions} />;
    } else {
        return (
            <Widget>
                <HomeWidget {...props} itemOptions={defaultItemOptions} />
            </Widget>
        );
    }
}
