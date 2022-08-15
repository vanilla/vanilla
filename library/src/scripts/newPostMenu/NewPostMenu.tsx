/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { useSection } from "@library/layout/LayoutContext";
import { Widget } from "@library/layout/Widget";
import NewPostMenuFAB from "@library/newPostMenu/NewPostMenuFAB";
import NewPostMenuDropDown from "@library/newPostMenu/NewPostMenuDropdown";
import { DeepPartial } from "redux";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";

export enum PostTypes {
    LINK = "link",
    BUTTON = "button",
}
export interface IAddPost {
    id: string;
    action: (() => void) | string;
    type: PostTypes;
    className?: string;
    label: string;
    icon: string;
    asOwnButton?: boolean;
}
export interface INewPostMenuProps {
    title?: string;
    items: IAddPost[];
    borderRadius?: number;
    containerOptions?: DeepPartial<IHomeWidgetContainerOptions>;
    asOwnButtons?: string[];
    excludedButtons?: string[];
    customLabels?: string[];
    forceDesktopOnly?: boolean; //for storybook purposes
    disableDropdownItemsClick?: boolean; //cases when we want dropdown to open but links there don't redirect when clicked
}

export default function NewPostMenu(props: INewPostMenuProps) {
    const { items, forceDesktopOnly, disableDropdownItemsClick } = props;
    const isCompact = !useSection().isFullWidth && !forceDesktopOnly;

    if (!items || !items.length) {
        return <></>;
    }

    const content = isCompact ? (
        <NewPostMenuFAB items={items} />
    ) : (
        <NewPostMenuDropDown {...props} disableDropdownItemsClick={disableDropdownItemsClick} />
    );

    return <Widget>{content}</Widget>;
}
