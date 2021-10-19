import React from "react";
import { useLayout } from "@library/layout/LayoutContext";
import { WidgetLayoutWidget } from "@library/layout/WidgetLayoutWidget";
import NewPostMenuFAB from "@library/flyouts/NewPostMenuFAB";
import NewPostMenuDropDown from "@library/flyouts/NewPostMenuDropdown";

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
}
interface NewPostMenuProps {
    items: IAddPost[];
}

export default function NewPostMenu(props: NewPostMenuProps) {
    const { items } = props;
    const isCompact = !useLayout().isFullWidth;
    const content = isCompact ? <NewPostMenuFAB items={items} /> : <NewPostMenuDropDown items={items} />;

    return <WidgetLayoutWidget>{content}</WidgetLayoutWidget>;
}
