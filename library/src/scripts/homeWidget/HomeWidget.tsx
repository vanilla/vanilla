/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import {
    IHomeWidgetItemOptions,
    HomeWidgetItemContentType,
    homeWidgetItemVariables,
} from "@library/homeWidget/HomeWidgetItem.styles";
import {
    IHomeWidgetContainerOptions,
    homeWidgetContainerVariables,
    homeWidgetContainerClasses,
    WidgetContainerDisplayType,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import { IHomeWidgetItemProps, HomeWidgetItem } from "@library/homeWidget/HomeWidgetItem";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { DeepPartial } from "redux";
import { BorderType } from "@library/styles/styleHelpersBorders";

export interface IWidgetCommonProps {
    /** The title of the widget */
    title?: string;
    /** The subtitle of the widget */
    subtitle?: string;
    /** Text describing the widget */
    description?: string;
}
interface IProps extends IWidgetCommonProps {
    // Options
    containerOptions?: IHomeWidgetContainerOptions;
    itemOptions?: DeepPartial<IHomeWidgetItemOptions>;
    maxItemCount?: number;

    // Content
    itemData: IHomeWidgetItemProps[];
}

export function HomeWidget(props: IProps) {
    const itemOptions = homeWidgetItemVariables(props.itemOptions).options;
    const containerOptionsWithDefaults = {
        ...props.containerOptions,
        isGrid:
            !props.containerOptions?.isCarousel &&
            (!props.containerOptions?.displayType ||
                props.containerOptions?.displayType === WidgetContainerDisplayType.GRID),
        maxColumnCount:
            props.containerOptions?.displayType === WidgetContainerDisplayType.LIST
                ? 1
                : props.containerOptions?.maxColumnCount,
    };
    const containerOptions = homeWidgetContainerVariables(containerOptionsWithDefaults).options;
    const containerClasses = homeWidgetContainerClasses(props.containerOptions);
    const contentIsListWithSeparators =
        containerOptions.displayType === WidgetContainerDisplayType.LIST &&
        itemOptions.box.borderType === BorderType.SEPARATOR;

    let items = props.itemData;

    if (props.maxItemCount && items.length > props.maxItemCount) {
        items = items.slice(0, props.maxItemCount);
    }

    let extraSpacerItemCount = 0;
    if (
        [
            HomeWidgetItemContentType.TITLE_BACKGROUND,
            HomeWidgetItemContentType.TITLE_BACKGROUND_DESCRIPTION,
            HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON,
            HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
        ].includes(itemOptions.contentType) &&
        props.itemData.length < containerOptions.maxColumnCount! &&
        !containerOptions.isCarousel &&
        containerOptions.displayType !== WidgetContainerDisplayType.CAROUSEL
    ) {
        extraSpacerItemCount = containerOptions.maxColumnCount! - props.itemData.length;
    }

    return (
        <HomeWidgetContainer
            subtitle={props.subtitle}
            description={props.description}
            options={containerOptionsWithDefaults}
            title={props.title}
            contentIsListWithSeparators={contentIsListWithSeparators}
        >
            {items.map((item, i) => {
                return <HomeWidgetItem key={i} {...item} options={props.itemOptions} />;
            })}
            {[...new Array(extraSpacerItemCount)].map((_, i) => {
                return <div key={"spacer-" + i} className={containerClasses.gridItemSpacer}></div>;
            })}
        </HomeWidgetContainer>
    );
}
