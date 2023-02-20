import React from "react";
import { MyMentionElement, MyValue } from "@library/vanilla-editor/typescript";
import { getHandler } from "@udecode/plate-core";
import { PlateRenderElementProps } from "@udecode/plate-headless";

export interface MentionElementProps extends PlateRenderElementProps<MyValue, MyMentionElement> {
    prefix?: string;
    onClick?: (mentionNode: any) => void;
}

export const MentionElement = (props: MentionElementProps) => {
    const { attributes, nodeProps, element, prefix, onClick, children } = props;

    const href = props.element.url;
    const userName = props.element.name;

    return (
        <a {...attributes} {...nodeProps} href={href} className="atMention">
            <span contentEditable={false} onClick={getHandler(onClick, element)}>
                {prefix}
                {userName}
            </span>
            {children}
        </a>
    );
};
