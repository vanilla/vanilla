/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IHomeWidgetItemOptions } from "@library/homeWidget/HomeWidgetItem.styles";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { IHomeWidgetItemProps, HomeWidgetItem } from "@library/homeWidget/HomeWidgetItem";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";

interface IProps {
    // Options
    containerOptions?: IHomeWidgetContainerOptions;
    itemOptions?: IHomeWidgetItemOptions;
    maxItemCount?: number;

    // Content
    title?: string;
    itemData: IHomeWidgetItemProps[];
}

export function HomeWidget(props: IProps) {
    return (
        <HomeWidgetContainer options={props.containerOptions} title={props.title}>
            {props.itemData.slice(0, props.maxItemCount ?? props.itemData.length - 1).map((item, i) => {
                return <HomeWidgetItem key={i} {...item} options={props.itemOptions} />;
            })}
        </HomeWidgetContainer>
    );
}
