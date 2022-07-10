/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { DraggableProvided, DraggableStateSnapshot, DraggableProvidedDraggableProps } from "react-beautiful-dnd";
import { IRenderItemParams, ItemID, ITreeItem } from "./types";

interface Props<D> {
    item: ITreeItem<D>;
    path: number[];
    onExpand: (itemId: ItemID, path: number[]) => void;
    onCollapse: (itemId: ItemID, path: number[]) => void;
    provided: DraggableProvided;
    snapshot: DraggableStateSnapshot;
    offsetPerLevel: number;
    renderItem(params: IRenderItemParams<D>);
    itemRef: (itemId: ItemID, element: HTMLElement | null) => void;
}

export default function TreeItem<D = {}>(props: Props<D>) {
    const { item, itemRef, renderItem, path, onExpand, onCollapse, provided, snapshot, offsetPerLevel } = props;

    function patchDraggableProps(
        draggableProps: DraggableProvidedDraggableProps,
        snapshot: DraggableStateSnapshot,
    ): DraggableProvidedDraggableProps {
        const transitions =
            draggableProps.style && draggableProps.style.transition ? [draggableProps.style.transition] : [];
        if (snapshot.dropAnimation) {
            transitions.push(
                // @ts-ignore
                `padding-left ${snapshot.dropAnimation.duration}s ${snapshot.dropAnimation.curve}`,
            );
        }
        const transition = transitions.join(", ");

        return {
            ...draggableProps,
            style: {
                ...draggableProps.style,
                paddingLeft: (path.length - 1) * offsetPerLevel,
                // @ts-ignore
                transition,
            },
        };
    }

    const innerRef = (el: HTMLElement | null) => {
        itemRef(item.id, el);
        provided.innerRef(el);
    };

    const finalProvided: DraggableProvided = {
        draggableProps: patchDraggableProps(provided.draggableProps, snapshot),
        dragHandleProps: provided.dragHandleProps,
        innerRef,
    };

    return renderItem({
        item,
        onExpand: (itemId) => onExpand(itemId, path),
        onCollapse: (itemId) => onCollapse(itemId, path),
        provided: finalProvided,
        snapshot,
        depth: path.length - 1,
    });
}
