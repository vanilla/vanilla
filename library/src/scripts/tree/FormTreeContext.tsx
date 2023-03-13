/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ItemID, ITreeItem } from "@library/tree/types";
import { IconType } from "@vanilla/icons";
import { IFormControl, JsonSchema } from "@vanilla/json-schema-forms";
import { slugify } from "@vanilla/utils";
import React, { useContext, useMemo } from "react";

export interface IFormTreeItemParams<ItemDataType = any> {
    itemSchema: JsonSchema;
    getRowIcon?(treeItem: ITreeItem<ItemDataType>): IconType | null;
    isItemEditable?(item: ItemDataType): boolean;
    isItemDeletable?(item: ItemDataType): boolean;
    isItemHideable?(item: ItemDataType): boolean;
    isItemHidden?(item: ItemDataType): boolean;
    markItemHidden?(itemID: ItemID, item: ItemDataType, isHidden?: boolean): ItemDataType;
}

export interface IFormTreeContext<ItemDataType = any> extends IFormTreeItemParams<ItemDataType> {
    // Internal state
    currentEditID: ItemID | null;
    setCurrentEditID(itemID: ItemID | null): void;
    selectedItemID: ItemID | null;
    setSelectedItemID(itemID: ItemID | null): void;
    saveItem(itemID: ItemID, data: ItemDataType): void;
    deleteItem(itemID: ItemID): void;
    rootID: string;
    isCompact: boolean;
}

const noop = () => {};

export const FormTreeContext = React.createContext<IFormTreeContext>({
    currentEditID: null,
    setCurrentEditID: noop,
    selectedItemID: null,
    setSelectedItemID: noop,
    saveItem: noop,
    deleteItem: noop,
    isCompact: false,
    itemSchema: {},
    rootID: "",
});

export function useFormTreeContext<ItemDataType>(): IFormTreeContext<ItemDataType> {
    return useContext(FormTreeContext);
}

export function useFormTreeControls() {
    const treeContext = useFormTreeContext();
    const controls = useMemo(() => {
        const controls: Record<string, IFormControl> = {};
        Object.entries(treeContext.itemSchema.properties!).forEach(([propertyName, property]) => {
            const control: IFormControl | undefined = (property as any)["x-control"];
            if (control) {
                controls[propertyName] = control;
            }
        });
        return controls;
    }, [treeContext.itemSchema]);

    return controls;
}

export function useFormTreeLabels() {
    const controls = useFormTreeControls();
    const labels = useMemo(() => {
        return Object.fromEntries(
            Object.entries(controls).map(([propertyName, control]) => {
                return [propertyName, control.label];
            }),
        );
    }, [controls]);

    return labels;
}

export function makeFormTreeLabelID(context: IFormTreeContext, propertyName: string): string {
    return context.rootID + "-" + slugify(propertyName);
}
