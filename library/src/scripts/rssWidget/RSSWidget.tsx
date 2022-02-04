/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { HomeWidgetItemContentType, IHomeWidgetItemOptions } from "@library/homeWidget/HomeWidgetItem.styles";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { DeepPartial } from "redux";
import { IHomeWidgetItemProps } from "@library/homeWidget/HomeWidgetItem";
import { HomeWidget } from "@library/homeWidget/HomeWidget";

interface IProps {
    title?: string;
    subtitle?: string;
    description?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    itemOptions?: DeepPartial<IHomeWidgetItemOptions>;
    itemData: IHomeWidgetItemProps[];
    maxItemCount?: number;
}

export function RSSWidget(props: IProps) {
    const defaultItemOptions = {
        ...props.itemOptions,
        contentType: props.itemOptions?.contentType
            ? props.itemOptions?.contentType
            : HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
    };
    return <HomeWidget {...props} itemOptions={defaultItemOptions} />;
}
