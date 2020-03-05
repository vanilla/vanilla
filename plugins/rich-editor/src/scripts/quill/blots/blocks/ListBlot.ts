/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import LineBlot from "@rich-editor/quill/blots/abstract/LineBlot";
import withWrapper from "@rich-editor/quill/blots/abstract/withWrapper";
import WrapperBlot from "@rich-editor/quill/blots/abstract/WrapperBlot";
import Parchment from "parchment";
import Container from "quill/blots/container";
import Quill from "quill/core";

/* tslint:disable:max-classes-per-file */

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

interface IListObjectValue {
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
function getValueFromElement(domNode: HTMLElement): IListObjectValue {
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
function syncValueToElement(element: HTMLElement, value: IListObjectValue) {
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
    public static create(value: IListObjectValue) {
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

            if (nextValue.depth === ownValue.depth && (nextValue.type === ownValue.type || ownValue.depth > 0)) {
                // Simple same level, same type join.
                next.moveChildren(this);
                next.remove();
            } else if (nextValue.depth > ownValue.depth) {
                // We have another list that is of a level deeper than our own.
                // Let's try and join it if possible.
                const targetListWrapper = this.children.tail;
                if (!(targetListWrapper instanceof ListItemWrapper)) {
                    return;
                }

                next.children.forEach((child: ListItemWrapper) => {
                    targetListWrapper.addNestedChild(child, "end");
                });
            }
        }
    }

    /**
     * Utility for getting the value from the blot's domNode.
     */
    public getValue(): IListObjectValue {
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
     * Create the dom node for th item and
     *
     * @param value
     */
    public static create(value: IListObjectValue) {
        const element = super.create(value) as HTMLElement;
        syncValueToElement(element, value);
        return element;
    }

    /**
     * @override
     */
    public split(index: number, force?: boolean) {
        if (!force && (index === this.length() - 1 || index === 0)) {
            return this;
        }
        const ownItem = this.getListContent();
        const ownGroup = this.getListGroup();
        if (ownItem && index < ownItem.length()) {
            const after = ownItem.split(index, force) as ListItem;
            if (after instanceof ListItem) {
                const wrapper = Parchment.create(ListItemWrapper.blotName, this.getValue()) as ListItemWrapper;
                after.insertInto(wrapper);
                wrapper.insertInto(this.parent, this.next);
                if (ownGroup) {
                    ownGroup.insertInto(wrapper);
                }
                return wrapper;
            }
            return this;
        } else {
            return super.split(index, force);
        }
    }

    /**
     * @override
     * Ensure line breaks are properly inserted and can separate the list wrapper properly.
     */
    public insertAt(index, value: string, def) {
        const isInListContent = this.getListContent() && index < this.getListContent()!.length();

        // validate new line insertion position
        if (value.includes("\n") && isInListContent) {
            const after = this.split(index, true);
            const targetNext = after === this ? this.next : after;
            const isEndOfLine = index === this.length() - 1;

            // Break the insert up on it's newlines.
            const inserts = value === "\n" ? [""] : value.split("\n");

            // condition to filter for the position of the cursor in the string.
            // eg end of line
            // offset

            // If we split the blot, we need to remove the first newline.
            if (this.next && targetNext === this.next && inserts[0] === "") {
                inserts.shift();
            }

            // If the first part of the insert is not a newline, insert it into the content and pop it off.
            if (inserts[0] && inserts[0] !== "") {
                this.getListContent()!.insertAt(index, inserts.shift()!);
            }

            // Each of the rest of the inserts will get inserted on their own list
            const listItems = inserts.map((insert, inc) => {
                const item = Parchment.create(ListItem.blotName, this.getValue()) as ListItem;
                if (insert !== "") {
                    item.insertAt(0, insert, undefined);
                }
                return item;
            });

            // includes conditions for the new line inserts blot
            // if the last element of the <ul> and the last charectrer in the string
            listItems.forEach(item => {
                const clone = this.clone() as ListItemWrapper; // Clone the <li/> tag.
                clone.appendChild(item); // Insert the <p> tag in inside of the <li/>
                this.parent.insertBefore(clone, targetNext); // Insert the <li> inside of the <ul> or <ol>
            });
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
     * Nest a ListItemWrapper inside of this one.
     *
     * @param blot The item to wrap.
     */
    public addNestedChild(blot: ListItemWrapper, target: "start" | "end") {
        const group = this.getListGroup();
        if (group) {
            if (group.getValue().depth < blot.getValue().depth && group.children.tail instanceof ListItemWrapper) {
                group.children.tail.addNestedChild(blot, target);
            } else if (target === "start") {
                blot.insertInto(group, group.children.head);
            } else if (target === "end") {
                blot.insertInto(group);
            }
        } else {
            blot.insertInto(this);
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

    /**
     * Ensure that all text content is wrapped in a `ListBlot`.
     */
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
                const ownNestedGroup = this.getListGroup();
                const nextNestedGroup = next.getListGroup();
                if (ownNestedGroup) {
                    next.insertInto(ownNestedGroup);

                    // Adjust our list type to the target value.
                    const newNextValue: IListObjectValue = {
                        ...nextValue,
                        type: ownNestedGroup.getValue().type,
                    };
                    next.getListContent()!.format("list", newNextValue);
                } else {
                    // Just insert it directly into the end. It will create its own group.
                    next.insertInto(this);
                }

                if (nextNestedGroup) {
                    nextNestedGroup.moveChildren(this.parent, this.next);
                    nextNestedGroup.remove();
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
            case ListType.ORDERED:
                return Parchment.create(OrderedListGroup.blotName, value) as OrderedListGroup;
            default:
                return Parchment.create(UnorderedListGroup.blotName, value) as UnorderedListGroup;
        }
    }

    /**
     * Utility for getting the value from the blot's domNode.
     */
    public getValue(): IListObjectValue {
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
     * Lower the indentation level of the blot and all of it's children.
     */
    public flattenSelfAndSiblings(targetGroup = this.parent, ref?: any) {
        const content = this.getListContent();
        const sibling = this.next;
        if (content && content.getValue().depth > 0) {
            content.updateIndentValue(0);
            this.insertInto(targetGroup, ref);
        }
        const group = this.getListGroup();
        if (group) {
            group.children.forEach(child => {
                if (child instanceof ListItemWrapper) {
                    child.flattenSelfAndSiblings(targetGroup, ref);
                }
            });
            group.remove();
        }

        if (sibling instanceof ListItemWrapper) {
            sibling.flattenSelfAndSiblings(targetGroup, ref);
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

const MAX_NESTING_DEPTH = 4;

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
    public static parentName = ListItemWrapper.blotName;

    // This is not the actual tag name we build with (the parent has that)
    // It needs to be set so that pasting works properly though.
    // We actually create a <span> and wrap in an <li>.
    public static tagName = ListTag.LI;

    /**
     * @override
     * - To map the old type of list value into the new one.
     * - To sync the element values into the items domNode.
     */
    public static create(value: ListValue) {
        value = this.mapListValue(value);
        // Ignore the super create.
        const element = document.createElement("p");
        element.classList.add(this.className);
        syncValueToElement(element, value);
        return element;
    }

    /**
     * Get the depth a list item based on the it's parent HTML elements.
     *
     * @param listElement The HTML element to check.
     */

    private static getListDepth(listElement: HTMLElement): number {
        let depth = 0;
        let parent = listElement.parentElement;
        while (parent && Object.values(ListTag).includes(parent.tagName as ListTag)) {
            if (parent.tagName === ListTag.LI) {
                depth++;
            }
            parent = parent.parentElement;
        }
        return depth;
    }

    /**
     * Get the format value for a list item if it matches.
     *
     * @param domNode The element to check.
     */
    public static formats(domNode: HTMLElement): IListObjectValue | undefined {
        if (domNode.tagName === ListTag.LI) {
            const parentTag = (domNode.parentElement && domNode.parentElement.tagName) || ListTag.UL;
            return {
                depth: this.getListDepth(domNode),
                type: parentTag === ListTag.OL ? ListType.ORDERED : ListType.BULLETED,
            };
        } else if (domNode.classList.contains(this.className)) {
            // Also handle our in built in lists.
            return getValueFromElement(domNode);
        }
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
    private static mapListValue(value: ListValue): IListObjectValue {
        if (typeof value === "object") {
            return {
                ...value,
                depth: Math.min(MAX_NESTING_DEPTH, value.depth),
            };
        } else {
            switch (value) {
                case "bullet":
                    return {
                        type: ListType.BULLETED,
                        depth: 0,
                    };
                case "ordered":
                    return {
                        type: ListType.ORDERED,
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
     * Like a softer `format()`. The difference is we don't want to replace the item. We want to update it in place.
     *
     * @param newDepth
     */
    public updateIndentValue(newDepth: number) {
        newDepth = Math.min(MAX_NESTING_DEPTH, newDepth);
        this.domNode.setAttribute("data-depth", newDepth);
        this.parent.domNode.setAttribute("data-depth", newDepth);
        this.cache = {};
    }

    /**
     * We can indent in the following scenarios
     *
     * <ul>
     *   <li>item 1</li>
     *   <li>[CURSOR]item 2</li>
     * </ul>
     *
     * or
     *
     * <ol><li>Item 1</li></ol>
     * <ul><li>[CURSOR]Item 2</li></ul>
     */
    public canIndent(): boolean {
        const hasPreviousItem = this.parent instanceof ListItemWrapper && this.parent.prev instanceof ListItemWrapper;
        const hasPreviousGroup =
            this.parent instanceof ListItemWrapper &&
            this.parent.parent instanceof ListGroup &&
            this.parent.parent.prev instanceof ListGroup;
        const lessThanMaxDepth = this.getValue().depth < MAX_NESTING_DEPTH;
        return (hasPreviousItem || hasPreviousGroup) && lessThanMaxDepth;
    }

    /**
     * Determine when we can outdent.
     */
    public canOutdent(): boolean {
        return this.getValue().depth > 0 || this.domNode.textContent === "";
    }

    /**
     * Increase the nesting level of this list item.
     *
     * @returns The recreated, newly indent list item.
     */
    public indent() {
        if (this.canIndent()) {
            this.updateIndentValue(this.getValue().depth + 1);
        }
    }

    /**
     * Decrease the nesting level of this list item.
     */
    public outdent() {
        if (!this.canOutdent()) {
            return;
        }

        const ownValue = this.getValue();
        if (ownValue.depth === 0) {
            const textContent = this.domNode.textContent || "";
            if (textContent.length === 0) {
                this.breakUpGroupAndMoveToScroll();
            } else {
                return;
            }
        }
        this.updateIndentValue(ownValue.depth - 1);
    }

    /**
     * @override
     * Overridden to safely handle list values changing.
     */
    public replaceWith(formatName, value?: any) {
        if (formatName !== ListItem.blotName) {
            this.breakUpGroupAndMoveToScroll(formatName, value);
        } else {
            if (!value) {
                return this.breakUpGroupAndMoveToScroll();
            }

            if (typeof value === "object" && (value as IListObjectValue).type !== this.getValue().type) {
                return this.breakUpGroupAndMoveToScroll(formatName, value);
            }

            return super.replaceWith(formatName, value);
        }
    }

    /**
     * Flatten the item and all of it's sibling list items into the top level scroll.
     *
     * @example
     * Before
     * - Item 1
     *   - Item 1.1
     *   - Item 1.2
     *     - Item 1.2.1
     *     - Item 1.2.2
     *   - Item 1.3
     * - Item 2
     *
     * Call this method on Item 1.1
     *
     * After
     * - Item 1
     *   - Item 1.1
     * - Item 1.2
     * - Item 1.2.1
     * - Item 1.2.2
     * - Item 1.3
     * - Item 2
     *
     */
    private flattenSelfAndSiblings = () => {
        if (this.parent instanceof ListItemWrapper) {
            let parent: any = this.parent;
            let topListWrapper: ListItemWrapper | undefined;
            let topListGroup: ListGroup | undefined;
            while (parent !== this.scroll) {
                if (parent instanceof ListGroup) {
                    topListGroup = parent;
                }

                if (parent instanceof ListItemWrapper) {
                    topListWrapper = parent;
                }
                parent = parent.parent;
            }
            const ref = topListWrapper ? topListWrapper.next : undefined;
            this.parent.flattenSelfAndSiblings(topListGroup, ref);
        }
    };

    /**
     * Break up the blot group and move it up into the scroll scroll container.
     *
     * @param formatName The new block format to use.
     * @param value The value for the new block format.
     */
    public breakUpGroupAndMoveToScroll(formatName = "block", value: any = "") {
        const newBlock =
            typeof formatName === "string"
                ? (Parchment.create(formatName, value) as Container)
                : (formatName as Container);
        // Clone the children.
        this.children.forEach(blot => {
            newBlock.appendChild(blot.clone());
        });
        let parentWrapper = this.parent as ListItemWrapper;

        this.flattenSelfAndSiblings();

        parentWrapper = this.parent as ListItemWrapper;
        const parentGroup = parentWrapper.parent as ListGroup;

        if (parentWrapper.prev === null) {
            this.scroll.insertBefore(newBlock, parentGroup);
            const listGroup = parentWrapper.getListGroup();
            if (listGroup) {
                this.scroll.insertBefore(listGroup, parentGroup);
            }
            parentWrapper.remove();
        } else {
            const after = parentGroup.split(parentWrapper.offset(parentGroup)) as ListGroup;
            this.scroll.insertBefore(newBlock, after);
            parentWrapper.getListContent()!.remove();
        }

        return newBlock;
    }

    /**
     * Utility for getting the value from the blot's domNode.
     */
    public getValue(): IListObjectValue {
        return getValueFromElement(this.domNode);
    }
}

OrderedListGroup.allowedChildren = [ListItemWrapper];
UnorderedListGroup.allowedChildren = [ListItemWrapper];
ListItemWrapper.allowedChildren = [ListGroup, ListItem];
