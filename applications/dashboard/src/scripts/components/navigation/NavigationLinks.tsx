/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useRef, useState } from "react";
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
import { NavigationLinkItem } from "@dashboard/components/navigation/NavigationLinkItem";
import { Icon } from "@vanilla/icons";
import { Row } from "@library/layout/Row";
import { FramedModal } from "@library/modal/FramedModal";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { SchemaFormBuilder } from "@library/json-schema-forms";
import { roleLookUp } from "@dashboard/moderation/communityManagmentUtils";
import { buildUrl } from "@library/utility/appUtils";

interface IProps {
    treeData: ITreeData<INavigationVariableItem>;
    onChangeTreeData(treeData: ITreeData<INavigationVariableItem>): void;
    isNestingEnabled?: boolean;
}

type EditingState =
    | false
    | {
          type: "add";
          parentID: ItemID;
      }
    | {
          type: "edit";
          itemID: ItemID;
      };

export default function NavigationLinks(props: IProps) {
    const { treeData, onChangeTreeData } = props;
    const containerRef = useRef<HTMLDivElement>(null);
    const layout = useSection();
    const classes = useNavigationManagerStyles();

    const [editState, setEditState] = useState<EditingState>(false);
    const isEditing = editState !== false;

    function deleteItem(itemID: ItemID) {
        const item = treeData.items[itemID];
        if (item.data.isCustom) {
            permanentlyDeleteItem(itemID);
        } else {
            onChangeTreeData(
                mutateTreeItem(treeData, itemID, {
                    isExpanded: false,
                    data: { ...item.data, isHidden: true },
                }),
            );
        }
    }

    function setItemVisibility(itemID: ItemID, isHidden: boolean) {
        const item = treeData.items[itemID];
        onChangeTreeData(
            mutateTreeItem(treeData, itemID, {
                isExpanded: true,
                data: { ...item.data, isHidden },
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

    function getSelectedItemID() {
        const selectedItem = containerRef.current?.querySelector<HTMLElement>("*[data-rbd-draggable-id]:focus-within");
        return selectedItem?.dataset.rbdDraggableId;
    }

    function getSelectParentID() {
        const selectedID = getSelectedItemID();
        if (!selectedID) {
            return undefined;
        }
        const selectedItemParent = Object.values(treeData.items).find((item) => item.children.includes(selectedID));
        return selectedItemParent?.id;
    }

    function addNewLink(newLink: ITreeItem<INavigationVariableItem>, parentID: ItemID) {
        const parent = treeData.items[parentID]!;
        let newTreeData = {
            rootId: treeData.rootId,
            items: {
                ...treeData.items,
                [parentID]: {
                    ...parent,
                    children: [...parent.children, newLink.id],
                },
                [newLink.id]: newLink,
            },
        };
        onChangeTreeData(newTreeData);
    }

    function onSave(itemID: ItemID, data: INavigationVariableItem) {
        onChangeTreeData(mutateTreeItem(treeData, itemID, { data }));
    }

    function renderItem(params: IRenderItemParams<INavigationVariableItem>) {
        const { depth, provided, item, snapshot } = params;
        const { id } = item;
        return (
            <NavigationLinkItem
                item={item}
                depth={depth}
                provided={provided}
                snapshot={snapshot}
                onDelete={() => deleteItem(id)}
                onShow={() => setItemVisibility(id, false)}
                onHide={() => setItemVisibility(id, true)}
                onExpand={() => expandItem(id)}
                onCollapse={() => collapseItem(id)}
                onEdit={() =>
                    setEditState({
                        type: "edit",
                        itemID: id,
                    })
                }
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

    return (
        <>
            {editState !== false &&
                (editState.type === "add" ? (
                    <AddEditForm
                        initialValue={null}
                        onCancel={() => {
                            setEditState(false);
                        }}
                        onSave={(value) => {
                            addNewLink({ id: value.id, children: [], data: value }, editState.parentID);
                            setEditState(false);
                        }}
                    />
                ) : (
                    <AddEditForm
                        initialValue={treeData.items[editState.itemID].data}
                        onCancel={() => {
                            setEditState(false);
                        }}
                        onSave={(value) => {
                            onSave(editState.itemID, value);
                            setEditState(false);
                        }}
                    />
                ))}

            <div className={classes.toolbar}>
                {props.isNestingEnabled ? (
                    <Row gap={24}>
                        <Button
                            buttonType={ButtonTypes.TEXT}
                            className={classes.expandColapseButton}
                            onClick={expandAll}
                            ariaLabel={t("Expand All")}
                        >
                            <Icon icon="expand-all" />
                            {!layout.isCompact && <span>{t("Expand All")}</span>}
                        </Button>
                        <Button
                            buttonType={ButtonTypes.TEXT}
                            className={classes.expandColapseButton}
                            onClick={collapseAll}
                            ariaLabel={t("Collapse All")}
                        >
                            <Icon icon="collapse-all" />
                            {!layout.isCompact && <span>{t("Collapse All")}</span>}
                        </Button>
                    </Row>
                ) : (
                    ""
                )}
                <div className={classes.spacer} />

                <Button
                    buttonType={ButtonTypes.TEXT}
                    className={classes.newLinkButton}
                    ariaLabel={t("New Link")}
                    disabled={isEditing}
                    onClick={() => {
                        setEditState({
                            type: "add",
                            parentID: getSelectParentID() ?? treeData.rootId,
                        });
                    }}
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

function AddEditForm(props: {
    initialValue: INavigationVariableItem | null;
    onSave: (value: INavigationVariableItem) => void;
    onCancel: () => void;
}) {
    const [value, setValue] = useState<INavigationVariableItem>(
        props.initialValue ?? {
            name: "",
            url: "",
            id: uuidv4(),
            isCustom: true,
        },
    );

    return (
        <FramedModal
            onClose={props.onCancel}
            title={props.initialValue ? t("Add Link") : t("Edit Link")}
            onFormSubmit={(e) => {
                e.preventDefault();
                e.stopPropagation();
                props.onSave({ ...value, url: buildUrl(value.url) });
            }}
            footer={
                <Button buttonType={"textPrimary"} type="submit">
                    {props.initialValue ? t("Add Link") : t("Update Link")}
                </Button>
            }
        >
            <DashboardSchemaForm
                forceVerticalLabels={true}
                instance={value}
                onChange={setValue}
                schema={SchemaFormBuilder.create()
                    .textBox("name", t("Label"), t("Enter the text to be displayed."))
                    .required()
                    .textBox(
                        "url",
                        t("URL"),
                        t("Enter a full URL for external pages or a path for pages on this site."),
                    )
                    .required()
                    .selectLookup("roleIDs", t("Permissions"), t("Choose who can see this link."), roleLookUp, true)
                    .withControlParams({ placeholder: t("All Roles") })
                    .getSchema()}
            />
        </FramedModal>
    );
}
