/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { formTreeClasses } from "@library/tree/FormTree.classes";
import { formTreeVariables } from "@library/tree/FormTree.variables";
import { FormTreeContext, IFormTreeItemParams } from "@library/tree/FormTreeContext";
import { FormTreeLabels } from "@library/tree/FormTreeLabels";
import { FormTreeRow } from "@library/tree/FormTreeRow";
import Tree from "@library/tree/Tree";
import {
    IRenderItemParams,
    ItemID,
    ITreeData,
    ITreeDestinationPosition,
    ITreeItem,
    ITreeSourcePosition,
} from "@library/tree/types";
import { getFirstItemID, moveItemOnTree, mutateTreeItem, removeItemFromTreeByID } from "@library/tree/utils";
import { useUniqueID } from "@library/utility/idUtils";
import { mountPortal, useMeasure } from "@vanilla/react-utils";
import { uuidv4 } from "@vanilla/utils";
import React, { useRef, useState } from "react";

interface IProps<T> extends IFormTreeItemParams<T> {
    value: ITreeData<T>;
    onChange(treeData: ITreeData<T>): void;
    displayLabels?: boolean;
    onEditStateChange?(isEditing: boolean): void;
    id?: string;

    ["aria-label"]?: string;
    ["aria-labelledby"]?: string;
    ["aria-describedby"]?: string;

    RowContentsComponent?: React.ComponentType<T>;
}

export const DRAGGING_ITEM_PORTAL_ID = "dragging-item-portal";

/**
 * Form based drag and drop.
 *
 * Current Features
 * - Single level drag and drop (could be expanded to nested easily).
 * - No collapsing/uncollapsing.
 */
export default function FormTree<ItemDataType extends {}>(props: IProps<ItemDataType>) {
    const { value, onChange, itemSchema, displayLabels = true } = props;

    const compactBreakpoint = formTreeVariables().row.compactBreakpoint;

    const rootID = useUniqueID("tree");
    const containerRef = useRef<HTMLDivElement>(null);
    const containerMeasure = useMeasure(containerRef);
    const classes = formTreeClasses();
    const isCompact = containerMeasure.width > 0 && containerMeasure.width <= compactBreakpoint;

    const [currentEditID, _setCurrentEditID] = useState<ItemID | null>(null);
    const [selectedItemID, setSelectedItemID] = useState<ItemID | null>(null);
    function setCurrentEditID(itemID: ItemID | null) {
        _setCurrentEditID(itemID);
        props.onEditStateChange?.(itemID !== null);
    }

    function deleteItem(itemID: ItemID) {
        onChange(removeItemFromTreeByID(value, itemID).tree);
    }

    function onItemChange(itemID: ItemID, data: ItemDataType) {
        onChange(mutateTreeItem(value, itemID, { data }));
    }

    function markItemHidden(itemID: ItemID, data: ItemDataType, isHidden: boolean) {
        if (!props.markItemHidden) {
            return data;
        }
        onChange(
            mutateTreeItem(value, itemID, {
                data: props.markItemHidden(itemID, data, isHidden),
            }),
        );
        return data;
    }

    return (
        <div
            className={classes.tree}
            ref={containerRef}
            id={props.id}
            aria-label={props["aria-label"]}
            aria-labelledby={props["aria-labelledby"]}
            aria-describedby={props["aria-describedby"]}
        >
            <FormTreeContext.Provider
                value={{
                    currentEditID,
                    setCurrentEditID,
                    selectedItemID,
                    setSelectedItemID,
                    saveItem: onItemChange,
                    deleteItem: deleteItem,
                    isCompact,
                    rootID,
                    // Item data
                    itemSchema,
                    getRowIcon: props.getRowIcon,
                    markItemHidden: markItemHidden,
                    isItemEditable: props.isItemEditable,
                    isItemDeletable: props.isItemDeletable,
                    isItemHideable: props.isItemHideable,
                    isItemHidden: props.isItemHidden,
                }}
            >
                {displayLabels && <FormTreeLabels />}
                <FormTreeContent<ItemDataType> {...props} />
            </FormTreeContext.Provider>
        </div>
    );
}

function FormTreeContent<ItemDataType extends {}>(props: IProps<ItemDataType>) {
    const { value, onChange, RowContentsComponent } = props;
    const firstItemID = getFirstItemID(value);
    const renderItem = (params: IRenderItemParams<ItemDataType>) => {
        const rendered = (
            <FormTreeRow<ItemDataType>
                {...params}
                RowContentsComponent={RowContentsComponent}
                isFirstItem={firstItemID === params.item.id}
            />
        );
        // Because of positioning issues in modals, we render the dragging item into a portal.
        if (params.snapshot.isDragging) {
            return mountPortal(rendered, DRAGGING_ITEM_PORTAL_ID, true) as any;
        } else {
            return rendered;
        }
    };
    function calculatePath(destination: ITreeDestinationPosition): ItemID[] {
        const path: ItemID[] = [];
        let parent: ITreeItem<ItemDataType> | undefined = value.items[destination.parentId];
        const items = Object.values(value.items);
        do {
            if (parent) {
                path.unshift(parent.id);
            }
            parent = items.find((item) => item.children.includes(parent!.id));
        } while (parent && parent.id !== value.rootId);
        return path;
    }

    function onDragEnd(source: ITreeSourcePosition, destination?: ITreeDestinationPosition) {
        if (!destination) {
            return;
        }
        // Prevent nesting deeper than two levels.
        const path = calculatePath(destination);
        if (path.length > 2) {
            destination.parentId = path[1];
        }
        // Move the item to it's destination.
        let newTree = moveItemOnTree(value, source, destination);
        // Make sure the destination parent is expanded.
        if (destination.parentId) {
            newTree = mutateTreeItem(newTree, destination.parentId, { isExpanded: true });
        }

        onChange(newTree);
    }

    return (
        <Tree
            tree={props.value}
            renderItem={renderItem}
            onDragEnd={onDragEnd}
            onExpand={() => {}}
            onCollapse={() => {}}
            offsetPerLevel={1}
            isDragEnabled
            isNestingEnabled={false}
        />
    );
}

function ensureIDs<T extends { id?: ItemID }>(items: T[]): Array<T & { id: ItemID }> {
    return items.map((item) => {
        return {
            ...item,
            id: uuidv4(),
        };
    });
}
