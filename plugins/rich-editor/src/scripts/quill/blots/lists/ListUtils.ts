/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

export enum ListTag {
    OL = "OL",
    UL = "UL",
    LI = "LI",
}

export enum ListType {
    ORDERED = "ordered",
    BULLETED = "bullet",
    CHECKBOX = "checkbox",
}
export interface IListObjectValue {
    type: ListType;
    index?: number;
    isChecked?: boolean;
    depth: number;
}
type ListStringValue = "ordered" | "bullet";

export type ListValue = IListObjectValue | ListStringValue;
/**
 * Utility function to sync a get a list item value from a domNode.
 *
 * @param domNode The domNode to set properties on.
 */
export function getValueFromElement(domNode: HTMLElement): IListObjectValue {
    const depthAttr = domNode.getAttribute("data-depth");
    const typeAtrr = domNode.getAttribute("data-type");

    let depth = 0;
    if (depthAttr !== null) {
        depth = parseInt(depthAttr, 10);
    }

    let type = ListType.BULLETED;
    if (typeAtrr !== null) {
        type = typeAtrr as ListType;
    }

    return {
        depth,
        type,
    };
}
/**
 * Utility function to sync a set a list item value in a domNode.
 *
 * @param domNode The domNode to set properties on.
 * @param value The value to sync.
 */
export function syncValueToElement(element: HTMLElement, value: IListObjectValue) {
    if (value) {
        element.setAttribute("data-depth", value.depth);
        element.setAttribute("data-type", value.type);
    }
}

export enum ListBlotType {
    UNORDERED_LIST_GROUP = "unorderedListGroup",
    ORDERED_LIST_GROUP = "orderedListGroup",
    LIST_ITEM_WRAPPER = "listItemWrapper",
    LIST_LINE = "list",
}
