/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import LineBlot from "@rich-editor/quill/blots/abstract/LineBlot";
import withWrapper from "@rich-editor/quill/blots/abstract/withWrapper";
import WrapperBlot from "@rich-editor/quill/blots/abstract/WrapperBlot";
import Parchment from "parchment";
import Container from "quill/blots/container";
import { Parent } from "parchment/dist/src/blot/abstract/blot";
import Quill from "quill/core";

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

/**
 * Utility function to sync a get a list item value from a domNode.
 *
 * @param domNode The domNode to set properties on.
 */
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

/**
 * Utility function to sync a set a list item value in a domNode.
 *
 * @param domNode The domNode to set properties on.
 * @param value The value to sync.
 */
function syncValueToElement(element: HTMLElement, value: IListItem) {
    if (value) {
        element.setAttribute("data-depth", value.depth);
        element.setAttribute("data-type", value.type);
    }
}

/**
 * The list wrapper. Either an <ol> or <ul>.
 *
 * - Never create this directly. This should only be created by a ListItemWrapper.
 * - ListGroup itself should never be used. Use either the ordered or unordered sub classes.
 */
export abstract class ListGroup extends WrapperBlot {
    /**
     * Create the dom node for th item and
     *
     * @param value
     */
    public static create(value: IListItem) {
        const element = super.create(value);
        syncValueToElement(element, value);
        return element;
    }

    /**
     * Join the children elements together where possible.
     */
    public optimize(context) {
        this.optimizeAdjacentGroups();
        super.optimize(context);
    }

    /**
     * Optimize together groups that are next to each other.
     *
     * - Groups with the same type and depth will be merged together.
     * - If the next group has a greater depth it will be nested into the last item of the current group.
     */
    private optimizeAdjacentGroups() {
        const next = this.next;
        if (next instanceof ListGroup && next.prev === this) {
            const ownValue = this.getValue();
            const nextValue = next.getValue();

            if (!ownValue || !nextValue) {
                return;
            }

            if (nextValue.depth === ownValue.depth && nextValue.type === ownValue.type) {
                // Simple same level, same type join.
                next.moveChildren(this);
                next.remove();
            } else if (nextValue.depth > ownValue.depth) {
                // We have another list that is of a level deeper than our own.
                // Let's try and join it if possible.
                const targetListItem = this.children.tail;
                if (!(targetListItem instanceof ListItemWrapper)) {
                    return;
                }

                const hasNestedList = targetListItem.children.tail && targetListItem.children.tail instanceof ListGroup;
                if (hasNestedList) {
                    // Try to merge the lists if possible.
                    const existingListGroup = targetListItem.children.tail as ListGroup;
                    const existingListGroupValue = existingListGroup.getValue();
                    if (
                        existingListGroupValue.type === nextValue.type &&
                        existingListGroupValue.depth === nextValue.depth
                    ) {
                        // We can only merge them if they are the same type. Otherwise we put the different lists next to each other an do another optimization pass afterwards.
                        next.moveChildren(existingListGroup);
                        next.remove();
                    } else {
                        // They are different types so they need to get different list groups.
                        next.insertInto(targetListItem);
                    }
                } else {
                    // We don't have an existing nested list, so we can move it entirely.
                    next.insertInto(targetListItem);
                }
            }
        }
    }

    /**
     * Utility for getting the value from the blot's domNode.
     */
    public getValue(): IListItem {
        return getValueFromElement(this.domNode);
    }
}

/**
 * ListGroup for <ul> tags.
 */
export class OrderedListGroup extends ListGroup {
    public static blotName = "orderedListGroup";
    public static className = "orderedListGroup";
    public static tagName = ListTag.OL;
}

/**
 * ListGroup for <ol> tags.
 */
export class UnorderedListGroup extends ListGroup {
    public static blotName = "unorderedListGroup";
    public static className = "unorderedListGroup";
    public static tagName = ListTag.UL;
}

/**
 * The li in <ul><li><span /></li></ul>
 *
 * Although this blot represents the item itself, the actual format, value, & delta
 * all come from the list content.
 *
 * This item is purely a wrapper so that nested list content can be included separately form the
 */
export class ListItemWrapper extends withWrapper(Container as any) {
    public static scope = Parchment.Scope.BLOCK_BLOT;
    public static blotName = "listItemWrapper";
    public static className = "listItemWrapper";
    public static tagName = ListTag.LI;
    public static parentName = [UnorderedListGroup.blotName, OrderedListGroup.blotName];

    /**
     * @override
     * To sync the element values into the items domNode.
     */
    public static create(value: IListItem) {
        const element = super.create(value) as HTMLElement;
        syncValueToElement(element, value);
        return element;
    }

    /**
     * @override
     * Ensure line breaks are properly inserted and can separate the list wrapper properly.
     */
    public insertAt(index, value, def) {
        if (value === "\n" && index < this.getListContent()!.length()) {
            const originalListItem = this.getListContent()!;
            const newWrapper = Parchment.create(ListItemWrapper.blotName, this.getValue()) as ListItemWrapper;
            const secondHalfListItem = originalListItem.split(index);
            secondHalfListItem.insertInto(newWrapper);
            newWrapper.insertInto(this.parent, this.next);

            const nestedGroup = this.getListGroup();
            if (nestedGroup) {
                nestedGroup.insertInto(newWrapper);
            }
        } else {
            super.insertAt(index, value, def);
        }
    }

    /**
     * @override
     * Ensure everything except ListItem and ListGroup are inserted into the ListItem.
     */
    public insertBefore(blot, ref) {
        if (blot instanceof ListItem || blot instanceof ListGroup) {
            super.insertBefore(blot, ref);
        } else {
            this.ensureListContent();
            this.getListContent()!.insertBefore(blot, ref);
        }
    }

    /**
     * @override
     * Overridden to implement list nesting.
     */
    public optimize(context) {
        this.optimizeNesting();
        this.optimizeUnwrapping();
        this.ensureListContent();
        super.optimize(context);
    }

    private ensureListContent() {
        if (!this.getListContent()) {
            const listContent = (Parchment.create(ListItem.blotName, this.getValue()) as any) as ListItem;

            this.children.forEach(child => {
                if (!(child instanceof ListGroup)) {
                    child.insertInto(listContent);
                }
            });
            this.insertBefore(listContent, this.children.head);
        }
    }

    /**
     * Merge the next item into this item's list group if it has a greater depth.
     */
    private optimizeNesting() {
        const next = this.next;
        if (next instanceof ListItemWrapper && next.prev === this) {
            const ownValue = this.getValue();
            const nextValue = next.getValue();

            if (!ownValue || !nextValue) {
                return;
            }

            if (nextValue.depth > ownValue.depth) {
                const nestedGroup = this.getListGroup();
                if (nestedGroup) {
                    next.insertInto(nestedGroup);

                    // Adjust our list type to the target value.
                    const newNextValue: IListItem = {
                        ...nextValue,
                        type: nestedGroup.getValue().type,
                    };
                    next.getListContent()!.format("list", newNextValue);
                } else {
                    // Just insert it directly into the end. It will create its own group.
                    next.insertInto(this);
                }
            }
        }
    }

    /**
     * Move this item up into it's parent if it's nested too far.
     */
    private optimizeUnwrapping() {
        const parentGroup = this.parent;
        const parentWrapper = parentGroup.parent;
        const grandParentGroup = parentWrapper.parent;
        if (
            parentGroup instanceof ListGroup &&
            parentWrapper instanceof ListItemWrapper &&
            grandParentGroup instanceof ListGroup
        ) {
            const parentGroupValue = parentGroup.getValue();
            const grandParentGroupValue = grandParentGroup.getValue();
            const ownValue = this.getValue();

            if (ownValue.depth < parentGroupValue.depth) {
                // Insert into the next list group. First we match it's list type.
                const newValue = {
                    ...ownValue,
                    type: grandParentGroupValue.type,
                };

                this.insertInto(grandParentGroup, parentWrapper.next);
                const listItem = this.getListContent();
                if (listItem) {
                    listItem.format(ListItem.blotName, newValue);
                }
            }
        }
    }

    /**
     * @override
     * Overriding the createWrapper to dynamically create the parent list group and pass it a value.
     */
    protected createWrapper() {
        const value = this.getValue();
        switch (value.type) {
            case ListType.NUMBERED:
                return Parchment.create(OrderedListGroup.blotName, value) as OrderedListGroup;
            default:
                return Parchment.create(UnorderedListGroup.blotName, value) as UnorderedListGroup;
        }
    }

    /**
     * Utility for getting the value from the blot's domNode.
     */
    public getValue(): IListItem {
        return getValueFromElement(this.domNode);
    }

    /**
     * Utility for getting a nested list blot from this blot's children.
     *
     * This _should_ be in the last position if it exists.
     */
    public getListGroup(): ListGroup | null {
        const tail = this.children.tail;
        if (tail instanceof ListGroup) {
            return tail;
        } else {
            return null;
        }
    }

    /**
     * Utility for getting the content blot from this blot's children.
     *
     * This _should_ be in the first position.
     */
    public getListContent(): ListItem | null {
        const item = this.children.head;
        if (item instanceof ListItem) {
            return item;
        } else {
            return null;
        }
    }
}

/**
 * The content of a list.
 * Eg. The span of <ul><li><span/></li></ul>.
 *
 * This blot maintains the actual value for the list item.
 * It is also responsible for syncing that value up to the ListWrapper.
 */
export class ListItem extends LineBlot {
    public static blotName = "list";
    public static className = "listItem";
    public static tagName = "span";
    public static parentName = ListItemWrapper.blotName;

    /**
     * @override
     * - To map the old type of list value into the new one.
     * - To sync the element values into the items domNode.
     */
    public static create(value: ListValue) {
        value = this.mapListValue(value);
        const element = super.create(value) as HTMLElement;
        syncValueToElement(element, value);
        return element;
    }

    /**
     * Map the old style list value to the new old.
     *
     * @param value Potentially an old or new style value.
     *
     * @example
     * list: "bullet"
     * list: { type: "bulleted", depth: 0 }
     */
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

    /**
     * The value of the blot's line.
     */
    public static formats = getValueFromElement;

    protected useWrapperReplacement = false;

    /**
     * @override
     * Overridden to dynamically create the parent list wrapper with the item's value.
     */
    protected createWrapper() {
        const value = this.getValue();
        return Parchment.create(ListItemWrapper.blotName, value) as WrapperBlot;
    }

    /**
     * @override
     * Overridden to ensure changing the depth is immediately synced to the item's domNode.
     * Also syncs to to the parent item's domNode.
     */
    public format(name, value) {
        // Keep the parents value in sync.
        if (name === ListItem.blotName) {
            syncValueToElement(this.domNode, value);
            syncValueToElement(this.parent.domNode, value);
            this.cache = {};
        } else {
            super.format(name, value);
        }
    }

    /**
     * Increase the nesting level of this list item.
     *
     * @returns The recreated, newly indent list item.
     */
    public indent() {
        const ownValue = this.getValue();

        // The previous item needs to be a list item to indent
        // Otherwise we have nothing to nest into.
        if (!(this.parent.prev instanceof ListItemWrapper)) {
            return;
        }

        const newValue = {
            ...ownValue,
            depth: ownValue.depth + 1,
        };
        this.format(ListItem.blotName, newValue);
    }

    /**
     * Decrease the nesting level of this list item.
     */
    public outdent() {
        const ownValue = this.getValue();

        if (ownValue.depth === 0) {
            const textContent = this.domNode.textContent || "";
            if (textContent.length === 0) {
                this.breakUpGroupAndMoveToScroll();
            } else {
                return;
            }
        }

        const newValue = {
            ...ownValue,
            depth: ownValue.depth - 1,
        };
        this.format(ListItem.blotName, newValue);
    }

    public replaceWith(formatName, value?: any) {
        if (formatName !== ListItem.blotName) {
            const ownValue = this.getValue();
            if (ownValue.depth > 0) {
                // Force an update to our nesting.
                const newValue = {
                    ...this.getValue,
                    depth: 0,
                };
                this.format(ListItem.blotName, newValue);
                const quill = this.quill;
                if (quill) {
                    quill.update(Quill.sources.SILENT);
                }
            }
            this.breakUpGroupAndMoveToScroll(formatName, value);
        } else {
            return super.replaceWith(formatName, value);
        }
    }

    public breakUpGroupAndMoveToScroll(formatName = "block", value: any = "") {
        const parentWrapper = this.parent as ListItemWrapper;
        const parentGroup = parentWrapper.parent as ListGroup;
        const newBlock =
            typeof formatName === "string"
                ? (Parchment.create(formatName, value) as Container)
                : (formatName as Container);
        this.moveChildren(newBlock);
        if (parentWrapper.prev === null) {
            parentWrapper.remove();
            this.scroll.insertBefore(newBlock, parentGroup);
        } else if (parentGroup.children.length > 1) {
            const after = parentGroup.split(this.offset(parentGroup)) as ListGroup;
            this.scroll.insertBefore(newBlock, after);
        }

        this.remove();
        return newBlock;
    }

    /**
     * Utility for getting the value from the blot's domNode.
     */
    public getValue(): IListItem {
        return getValueFromElement(this.domNode);
    }

    /**
     * Get the attached quill instance.
     *
     * This will _NOT_ work before attach() is called.
     */
    protected get quill(): Quill | null {
        if (!this.scroll || !this.scroll.domNode.parentNode) {
            return null;
        }

        return Quill.find(this.scroll.domNode.parentNode!);
    }
}

OrderedListGroup.allowedChildren = [ListItemWrapper];
UnorderedListGroup.allowedChildren = [ListItemWrapper];
ListItemWrapper.allowedChildren = [ListGroup, ListItem];
