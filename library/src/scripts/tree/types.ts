/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DraggableProvided, DraggableStateSnapshot, DraggableLocation, Combine } from "react-beautiful-dnd";

export type ItemID = string | number;

export interface ITreeItem<D> {
    id: ItemID;
    children: ItemID[];
    hasChildren?: boolean;
    isExpanded?: boolean;
    isChildrenLoading?: boolean;
    data: D;
}

export interface ITreeData<D = {}> {
    rootId: ItemID;
    items: Record<ItemID, ITreeItem<D>>;
}

export interface IDragState {
    // Source location
    source: DraggableLocation;
    // Dragging mode
    mode: string;
    // Pending destination location
    destination?: DraggableLocation;
    // Last level, while the user moved an item horizontally
    horizontalLevel?: number;
    // Combine for nesting operation
    combine?: Combine;
}

export interface IRenderItemParams<D> {
    item: ITreeItem<D>;
    depth: number;
    onExpand: (itemId: ItemID) => void;
    onCollapse: (itemId: ItemID) => void;
    provided: DraggableProvided;
    snapshot: DraggableStateSnapshot;
}

export type Path = number[];

export type FlattenedTree<D> = Array<IFlattenedItem<D>>;

export interface IFlattenedItem<D> {
    item: ITreeItem<D>;
    path: Path;
}

export interface ITreeSourcePosition {
    parentId: ItemID;
    index: number;
}

export interface ITreeDestinationPosition {
    parentId: ItemID;
    index?: number;
}

export interface ITreeItemMutation<D> {
    id?: ItemID;
    children?: ItemID[];
    hasChildren?: boolean;
    isExpanded?: boolean;
    isChildrenLoading?: boolean;
    data?: D;
}
