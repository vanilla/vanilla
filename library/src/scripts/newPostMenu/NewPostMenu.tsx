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
    forceDesktopOnly?: boolean; //for storybook purposes
}

export default function NewPostMenu(props: INewPostMenuProps) {
    const { items, forceDesktopOnly } = props;
    const isCompact = !useSection().isFullWidth && !forceDesktopOnly;
    const content = isCompact ? <NewPostMenuFAB items={items} /> : <NewPostMenuDropDown {...props} />;

    return <Widget>{content}</Widget>;
}
