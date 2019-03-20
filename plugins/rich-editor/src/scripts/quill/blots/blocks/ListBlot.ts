/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Parchment from "parchment";
import LineBlot from "@rich-editor/quill/blots/abstract/LineBlot";
import WrapperBlot from "@rich-editor/quill/blots/abstract/WrapperBlot";

/* tslint:disable:max-classes-per-file */

export enum ListTag {
    OL = "OL",
    UL = "UL",
    LI = "LI",
}

export enum ListType {
    NUMBERED = "numbered",
    BULLETED = "bulleted",
    CHECKBOX = "checkbox",
}

interface IListItem {
    type: ListType;
    index?: number;
    isChecked?: boolean;
    depth: number;
}

type ListStringValue = "ordered" | "bullet";

export type ListValue = IListItem | ListStringValue;

function getValueFromElement(domNode: HTMLElement): IListItem {
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

export class ListGroup extends WrapperBlot {
    public static create(value: { depth: number }) {
        const element = super.create(value);
        element.setAttribute("data-depth", value.depth);
        return element;
    }

    public attach() {
        super.attach();
    }

    /**
     * Join the children elements together where possible.
     *
     * @param {any} context -
     */
    public optimize(context) {
        const next = this.next;

        console.log("optimizing blot group");

        if (next instanceof ListGroup && next.prev === this) {
            const ownValue = getValueFromElement(this.domNode);
            const nextValue = getValueFromElement(next.domNode);

            if (!ownValue || !nextValue) {
                return;
            }

            // Simple same level, same type join.
            if (nextValue.depth === ownValue.depth && nextValue.type === ownValue.type) {
                console.log("Found next item of same depth and same type");
                next.moveChildren(this);
                next.remove();
            } else if (nextValue.depth === ownValue.depth + 1) {
                const hasNestedList = this.children.tail && this.children.tail instanceof ListGroup;
                console.log("Found next item that is 1 deeper");
                if (hasNestedList) {
                    console.log("We have an existing list");
                    // Try to Merge the lists.
                    const existingList = this.children.tail as ListGroup;
                    const existingListValue = getValueFromElement(existingList.domNode);
                    if (existingListValue.type === nextValue.type) {
                        // We can only merge them if they are the same type.
                        next.moveChildren(existingList);
                        next.remove();
                    }
                } else {
                    console.log("We don't have an existing list");
                    // Let's make the new list.
                    next.insertInto(this);
                }
            }
        }
    }
}

export class OrderedListGroup extends ListGroup {
    public static blotName = "orderedListGroup";
    public static className = "orderedListGroup";
    public static tagName = ListTag.OL;
}

export class UnorderedListGroup extends ListGroup {
    public static blotName = "unorderedListGroup";
    public static className = "unorderedListGroup";
    public static tagName = ListTag.UL;
}

export class ListItem extends LineBlot {
    public static blotName = "list";
    public static className = "listItem";
    public static tagName = ListTag.LI;
    public static parentName = [UnorderedListGroup.blotName, OrderedListGroup.blotName];
    public static allowedChildren = [...LineBlot.allowedChildren, ListGroup];

    public static create(value: ListValue) {
        value = this.mapListValue(value);
        const element = super.create(value) as HTMLElement;
        element.setAttribute("data-depth", value.depth);
        element.setAttribute("data-type", value.type);
        return element;
    }

    private static mapListValue(value: ListValue): IListItem {
        if (typeof value === "object") {
            return value;
        } else {
            switch (value) {
                case "bullet":
                    return {
                        type: ListType.BULLETED,
                        depth: 0,
                    };
                case "ordered":
                    return {
                        type: ListType.NUMBERED,
                        depth: 0,
                    };
                default:
                    return {
                        type: ListType.BULLETED,
                        depth: 0,
                    };
            }
        }
    }

    public static formats = getValueFromElement;

    private value: IListItem;

    public constructor(domNode: HTMLElement) {
        super(domNode);
        this.value = getValueFromElement(domNode);
    }

    protected createWrapper() {
        const value = this.getValue();
        switch (value.type) {
            case ListType.NUMBERED:
                return Parchment.create(OrderedListGroup.blotName, value) as OrderedListGroup;
            default:
                return Parchment.create(UnorderedListGroup.blotName, value) as UnorderedListGroup;
        }
    }

    public getValue(): IListItem {
        return this.value;
    }
}

OrderedListGroup.allowedChildren = [WrapperBlot, ListItem];
UnorderedListGroup.allowedChildren = [WrapperBlot, ListItem];
