/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import FormTree from "@library/tree/FormTree";
import { formTreeClasses } from "@library/tree/FormTree.classes";
import { ItemID, ITreeItem } from "@library/tree/types";
import { itemsToTree, PartialTreeItem, treeToItems } from "@library/tree/utils";
import { useUniqueID } from "@library/utility/idUtils";
import { IconType } from "@vanilla/icons";
import { ICommonControl, IControlProps, JsonSchema } from "@vanilla/json-schema-forms";
import { FormGroupLabel } from "@vanilla/ui";
import React, { useEffect, useMemo, useState } from "react";

interface IFormTreeControl extends ICommonControl {
    itemSchema: JsonSchema;
}

interface IProps<ItemDataType = any> extends Omit<IControlProps, "control"> {
    control: IFormTreeControl;
    getRowIcon?(treeItem: ITreeItem<ItemDataType>): IconType;
}

interface IHideableItem {
    isHidden?: boolean;
    id?: ItemID;
    children?: IHideableItem[];
}

export function FormTreeControl(props: IProps) {
    const [treeValue, setTreeValue] = useState(itemsToTree<any>(props.instance));
    const hasInstance = props.instance != null;

    const descriptionID = useUniqueID("treeDescription");

    useEffect(() => {
        // When the count of items changes try to reinitialize?
        // I'm unsure of this, and a better solution will need to be found in the future.
        // I'm quite afraid of paying the conversion cost back and forth between the items and the tree though.
        setTreeValue(itemsToTree<any>(props.instance));
    }, [hasInstance]);

    const classes = formTreeClasses();

    return (
        <>
            {!!props.control.description && (
                <FormGroupLabel
                    className={classes.treeDescription}
                    id={descriptionID}
                    description={props.control.description}
                />
            )}
            <FormTree<IHideableItem>
                aria-describedby={props.control.description ? descriptionID : undefined}
                aria-label={props.control.label}
                value={treeValue}
                onChange={(newTreeValue) => {
                    setTreeValue(newTreeValue);
                    props.onChange(treeToItems(newTreeValue));
                }}
                itemSchema={props.control.itemSchema}
                isItemDeletable={() => false}
                isItemHidden={(item) => item.isHidden ?? false}
                isItemHideable={() => true}
                markItemHidden={(itemID, item, isHidden) => {
                    return {
                        ...item,
                        isHidden,
                    };
                }}
                getRowIcon={props.getRowIcon}
            />
        </>
    );
}
