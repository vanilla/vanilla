/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useRef, useState } from "react";
import Button from "@library/forms/Button";
import useNavigationManagerStyles from "./NavigationLinks.styles";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { useSection } from "@library/layout/LayoutContext";
import { t } from "@vanilla/i18n";
import { PlusCircleIcon } from "@library/icons/common";
import Tree from "@library/tree/Tree";
import {
    IRenderItemParams,
    ItemID,
    ITreeData,
    ITreeDestinationPosition,
    ITreeItem,
    ITreeSourcePosition,
} from "@library/tree/types";
import { moveItemOnTree, mutateTreeItem, mutateTreeEvery, removeItemFromTree } from "@library/tree/utils";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { uuidv4 } from "@vanilla/utils";
import ModalConfirm from "@library/modal/ModalConfirm";
import Translate from "@library/content/Translate";
import { INavigationLinkItemHandle, NavigationLinkItem } from "@dashboard/components/navigation/NavigationLinkItem";
import { Icon } from "@vanilla/icons";

function makeNewLink() {
    const newLinkId = uuidv4();
    return {
        id: newLinkId,
        children: [],
        data: {
            id: newLinkId,
            children: [],
            name: "",
            url: "",
            isCustom: true,
        },
    };
}

interface IDeleteModalProps {
    isVisible?: boolean;
    item?: ITreeItem<INavigationVariableItem>;
    onConfirm(item: ITreeItem<INavigationVariableItem>): void;
    onCancel(item: ITreeItem<INavigationVariableItem>): void;
}

function DeleteConfirmModal({ item, isVisible, onCancel, onConfirm }: IDeleteModalProps) {
    const label = item?.data.name;
    const url = item?.data.url;
    return (
        <ModalConfirm
            isVisible={Boolean(isVisible)}
            title={(<Translate source={'Delete "<0/>"'} c0={label} />) as unknown as string}
            onCancel={() => onCancel(item!)}
            onConfirm={() => onConfirm(item!)}
        >
            <Translate
                source={'Are you sure you want to delete <0/> "<1/>" ?'}
                c0={label}
                c1={
                    <strong>
                        <em>{url}</em>
                    </strong>
                }
            />
        </ModalConfirm>
    );
}

interface IProps {
    treeData: ITreeData<INavigationVariableItem>;
    onStartEditing(): void;
    onStopEditing(): void;
    onChangeTreeData(treeData: ITreeData<INavigationVariableItem>): void;
    isNestingEnabled?: boolean;
}

export default function NavigationLinks(props: IProps) {
    const { treeData, onChangeTreeData, onStartEditing, onStopEditing } = props;
    const treeItemsRef = useRef<Record<ItemID, INavigationLinkItemHandle>>({});
    const containerRef = useRef<HTMLDivElement>(null);
    const layout = useSection();
    const classes = useNavigationManagerStyles();

    const [editingID, setEditingID] = useState<ItemID | undefined>();
    const [editOnceID, setEditOnceID] = useState<ItemID | undefined>();
    const [deleteID, setDeleteID] = useState<ItemID | undefined>();

    const isEditing = editingID !== undefined;

    useEffect(() => {
        if (editOnceID !== undefined) {
            treeItemsRef.current[editOnceID]?.edit();
        }
    }, [editOnceID]);

    function deleteItem(itemID: ItemID) {
        const item = treeData.items[itemID];
        if (item.data.isCustom) {
            setDeleteID(itemID);
        } else {
            onChangeTreeData(
                mutateTreeItem(treeData, itemID, {
                    isExpanded: false,
                    data: { ...item.data, isHidden: true },
                }),
            );
        }
    }

    function showItem(itemID: ItemID) {
        const item = treeData.items[itemID];
        onChangeTreeData(
            mutateTreeItem(treeData, itemID, {
                isExpanded: true,
                data: { ...item.data, isHidden: false },
            }),
        );
    }

    function collapseItem(itemID: ItemID) {
        onChangeTreeData(mutateTreeItem(treeData, itemID, { isExpanded: false }));
    }

    function expandItem(itemID: ItemID) {
        onChangeTreeData(mutateTreeItem(treeData, itemID, { isExpanded: true }));
    }

    function collapseAll() {
        onChangeTreeData(mutateTreeEvery(treeData, { isExpanded: false }));
    }

    function expandAll() {
        onChangeTreeData(mutateTreeEvery(treeData, { isExpanded: true }));
    }

    function startEditing(itemID: ItemID) {
        setEditingID(itemID);
        onStartEditing();
    }

    function stopEditing(itemID: ItemID) {
        const item = treeData.items[itemID];
        if (item.data.name === "" && item.data.url === "") {
            permanentlyDeleteItem(itemID);
        }
        setEditingID(undefined);
        onStopEditing();
    }

    function getSelectedItemID() {
        const selectedItem = containerRef.current?.querySelector<HTMLElement>("*[data-rbd-draggable-id]:focus-within");
        return selectedItem?.dataset.rbdDraggableId;
    }

    function getDropParentID() {
        const selectedID = getSelectedItemID();
        if (!selectedID) {
            return undefined;
        }
        const selectedItemParent = Object.values(treeData.items).find((item) => item.children.includes(selectedID));
        return selectedItemParent?.id;
    }

    function newLink() {
        const dropParentID = getDropParentID() || treeData.rootId;
        const dropParent = treeData.items[dropParentID];
        const newLink = makeNewLink();
        let newTreeData = {
            rootId: treeData.rootId,
            items: {
                ...treeData.items,
                [dropParentID]: {
                    ...dropParent,
                    children: [...dropParent.children, newLink.id],
                },
                [newLink.id]: newLink,
            },
        };
        onChangeTreeData(newTreeData);
        setEditOnceID(newLink.id);
    }

    function onSave(itemID: ItemID, data: INavigationVariableItem) {
        onChangeTreeData(mutateTreeItem(treeData, itemID, { data }));
    }

    function renderItem(params: IRenderItemParams<INavigationVariableItem>) {
        const { depth, provided, item, snapshot } = params;
        const { id } = item;
        return (
            <NavigationLinkItem
                ref={(ref) => (treeItemsRef.current[id] = ref!)}
                item={item}
                depth={depth}
                provided={provided}
                snapshot={snapshot}
                disabled={isEditing && editingID !== id}
                onDelete={() => deleteItem(id)}
                onShow={() => showItem(id)}
                onSave={(data) => onSave(id, data)}
                onExpand={() => expandItem(id)}
                onCollapse={() => collapseItem(id)}
                onStartEditing={() => startEditing(id)}
                onStopEditing={() => stopEditing(id)}
            />
        );
    }

    function calculatePath(destination: ITreeDestinationPosition): ItemID[] {
        const path: ItemID[] = [];
        let parent: ITreeItem<INavigationVariableItem> | undefined = treeData.items[destination.parentId];
        const items = Object.values(treeData.items);
        do {
            if (parent) {
                path.unshift(parent.id);
            }
            parent = items.find((item) => item.children.includes(parent!.id));
        } while (parent && parent.id !== treeData.rootId);
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
        let newTree = moveItemOnTree(treeData, source, destination);
        // Make sure the destination parent is expanded.
        if (destination.parentId) {
            newTree = mutateTreeItem(newTree, destination.parentId, { isExpanded: true });
        }

        onChangeTreeData(newTree);
    }

    function permanentlyDeleteItem(itemID: ItemID) {
        const parent = Object.values(treeData.items).find((item) => item.children.includes(itemID));
        const position: ITreeSourcePosition = {
            parentId: parent!.id,
            index: parent!.children.indexOf(itemID),
        };
        const result = removeItemFromTree(treeData, position);
        onChangeTreeData(result.tree);
    }

    function onConfirmDelete() {
        permanentlyDeleteItem(deleteID!);
        setDeleteID(undefined);
    }

    function onCancelDelete() {
        setDeleteID(undefined);
    }

    return (
        <>
            <DeleteConfirmModal
                isVisible={deleteID !== undefined}
                item={deleteID ? treeData.items[deleteID] : undefined}
                onConfirm={onConfirmDelete}
                onCancel={onCancelDelete}
            />

            <div className={classes.toolbar}>
                {props.isNestingEnabled ? (
                    <>
                        <Button
                            buttonType={ButtonTypes.CUSTOM}
                            className={classes.expandColapseButton}
                            onClick={expandAll}
                            ariaLabel={t("Expand All")}
                        >
                            <Icon icon="navigation-expandAll" />
                            {!layout.isCompact && <span>{t("Expand All")}</span>}
                        </Button>
                        <Button
                            buttonType={ButtonTypes.CUSTOM}
                            className={classes.expandColapseButton}
                            onClick={collapseAll}
                            ariaLabel={t("Collapse All")}
                        >
                            <Icon icon="navigation-collapseAll" />
                            {!layout.isCompact && <span>{t("Collapse All")}</span>}
                        </Button>
                    </>
                ) : (
                    ""
                )}
                <div className={classes.spacer} />

                <Button
                    buttonType={ButtonTypes.CUSTOM}
                    className={classes.newLinkButton}
                    ariaLabel={t("New Link")}
                    disabled={isEditing}
                    onClick={newLink}
                >
                    <PlusCircleIcon />
                    {!layout.isCompact && <span>{t("New Link")}</span>}
                </Button>
            </div>
            <div className={classes.treeContainer} ref={containerRef}>
                <Tree
                    tree={treeData}
                    renderItem={renderItem}
                    onDragEnd={onDragEnd}
                    onExpand={(itemID) => expandItem(itemID)}
                    onCollapse={(itemID) => collapseItem(itemID)}
                    offsetPerLevel={16}
                    isDragEnabled
                    isNestingEnabled={props.isNestingEnabled ?? true}
                />
            </div>
        </>
    );
}
