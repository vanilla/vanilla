import React from "react";
import { useSection } from "@library/layout/LayoutContext";
import { Widget } from "@library/layout/Widget";
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
    const isCompact = !useSection().isFullWidth;
    const content = isCompact ? <NewPostMenuFAB items={items} /> : <NewPostMenuDropDown items={items} />;

    return <Widget>{content}</Widget>;
}
