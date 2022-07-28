/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useState } from "react";
import { t } from "@vanilla/i18n";
import NavigationLinks from "./NavigationLinks";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { ItemID, ITreeData, ITreeItem } from "@library/tree/types";
import { uuidv4 } from "@vanilla/utils";
import { navigationLinksModalClasses } from "@dashboard/components/navigation/NavigationLinksModal.styles";

const sanitizeData = (data: INavigationVariableItem) => ({
    ...data,
    name: data.name.length ? data.name : t("(untitled)"),
    url: data.url.length ? data.url : "/",
});

interface IProps {
    isVisible: boolean;
    onCancel(): void;
    navigationItems: INavigationVariableItem[];
    onSave(items: INavigationVariableItem[]): void;
    isNestingEnabled?: boolean;
    title: string;
    description?: React.ReactNode;
}

function ensureIDs(items: INavigationVariableItem[]): INavigationVariableItem[] {
    return items.map((item) => {
        return {
            ...item,
            id: item.id || uuidv4(),
            children: item.children ? ensureIDs(item.children) : [],
        };
    });
}

const makeTreeItems = (items: INavigationVariableItem[]): Array<ITreeItem<INavigationVariableItem>> =>
    items.reduce((acc, item) => {
        const { id } = item;
        if (!item.children) {
            item.children = [];
        }
        const treeItem: ITreeItem<INavigationVariableItem> = {
            id,
            children: item.children.map((c) => c.id),
            hasChildren: item.children.length > 0,
            isExpanded: true,
            data: item,
        };
        return [...acc, treeItem, ...makeTreeItems(item.children)];
    }, []);

const makeTreeData = (items: INavigationVariableItem[]): ITreeData<INavigationVariableItem> => {
    items = ensureIDs(items);
    const treeItems: Record<ItemID, ITreeItem<INavigationVariableItem>> = makeTreeItems(items).reduce((acc, item) => {
        acc[item.id] = item;
        return acc;
    }, {});
    return {
        rootId: "tree",
        items: {
            ...treeItems,
            tree: {
                id: "tree",
                children: items.map((i) => i.id),
                data: undefined as any,
            },
        },
    };
};

const makeVariableItems = (
    treeItems: Record<ItemID, ITreeItem<INavigationVariableItem>>,
    rootId: ItemID,
): INavigationVariableItem[] =>
    treeItems[rootId].children.reduce((acc, itemID) => {
        const treeItem = treeItems[itemID];
        const newItem: INavigationVariableItem = {
            ...sanitizeData(treeItem.data),
            children: makeVariableItems(treeItems, itemID),
        };
        return [...acc, newItem];
    }, []);

export function NavigationLinksModal(props: IProps) {
    const { onCancel, isVisible, navigationItems, onSave, isNestingEnabled: isExpandable, title, description } = props;
    const classes = navigationLinksModalClasses();

    const [isEditing, setIsEditing] = useState(false);
    const [treeData, setTreeData] = useState(makeTreeData(navigationItems));

    function saveData() {
        const { items, rootId } = treeData;
        const flatData = makeVariableItems(items, rootId);
        onSave(flatData);
    }

    function cancel() {
        if (isEditing) {
            return;
        }
        setTreeData(makeTreeData(navigationItems));
        onCancel();
    }

    useEffect(() => {
        setTreeData(makeTreeData(navigationItems));
    }, [navigationItems]);

    return (
        <Modal isVisible={isVisible} size={ModalSizes.LARGE} exitHandler={cancel}>
            <Frame
                header={<FrameHeader closeFrame={cancel} title={t(title)} />}
                body={
                    <FrameBody>
                        {description && <p className={classes.modalDescription}>{description}</p>}
                        <NavigationLinks
                            isNestingEnabled={isExpandable}
                            treeData={treeData}
                            onChangeTreeData={setTreeData}
                            onStartEditing={() => setIsEditing(true)}
                            onStopEditing={() => setIsEditing(false)}
                        />
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight={true}>
                        <Button
                            className={classes.modalButton}
                            buttonType={ButtonTypes.TEXT}
                            onClick={cancel}
                            disabled={isEditing}
                        >
                            {t("Cancel")}
                        </Button>
                        <Button
                            className={classes.modalButton}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            onClick={saveData}
                            disabled={isEditing}
                        >
                            {t("Apply")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}
