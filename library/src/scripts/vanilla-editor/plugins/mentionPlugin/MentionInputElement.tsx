import React, { HTMLAttributes } from "react";
import { getHandler, PlateRenderElementProps, TMentionElement, Value } from "@udecode/plate-headless";

type ElementProps<V extends Value = Value> = PlateRenderElementProps<V, TMentionElement> & HTMLAttributes<HTMLElement>;

export interface MentionInputElementProps<V extends Value> extends ElementProps<V> {
    /**
     * Prefix rendered before mention
     */
    prefix?: string;
    onClick?: (mentionNode: any) => void;
    renderLabel?: (mentionable: TMentionElement) => string;
}

export const MentionInputElement = <V extends Value>(props: MentionInputElementProps<V>) => {
    const { attributes, children, nodeProps, element, onClick } = props;

    return (
        <span {...attributes} onClick={getHandler(onClick, element)} {...nodeProps}>
            {/* display the "@" trigger inline*/}
            {element.trigger}
            {children}
        </span>
    );
};
