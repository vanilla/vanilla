/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import withWrapper from "@rich-editor/quill/blots/abstract/withWrapper";
import ExternalEmbedBlot from "@rich-editor/quill/blots/embeds/ExternalEmbedBlot";
import { ListLineBlot } from "@rich-editor/quill/blots/lists/ListLineBlot";
import {
    ListBlotType,
    ListTag,
    IListObjectValue,
    syncValueToElement,
    ListType,
    getValueFromElement,
} from "@rich-editor/quill/blots/lists/ListUtils";
import Parchment from "parchment";
import BlockBlot from "quill/blots/block";
import Container from "quill/blots/container";
import { ListGroupBlot, UnorderedListGroupBlot, OrderedListGroupBlot } from "./ListGroupBlot";

/**
 * The li in <ul><li><span /></li></ul>
 *
 * Although this blot represents the item itself, the actual format, value, & delta
 * all come from the list content.
 *
 * This item is purely a wrapper so that nested list content can be included separately form the
 */

export class ListItemWrapperBlot extends withWrapper(Container as any) {
    public static scope = Parchment.Scope.BLOCK_BLOT;
    public static blotName = ListBlotType.LIST_ITEM_WRAPPER;
    public static className = ListBlotType.LIST_ITEM_WRAPPER;
    public static tagName = ListTag.LI;
    public static allowedChildren = [Container, BlockBlot];
    public static parentName = [ListBlotType.ORDERED_LIST_GROUP, ListBlotType.UNORDERED_LIST_GROUP];

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
        if (!force) {
            if (index === 0) {
                return this;
            }

            const ownLength = this.length();
            if (index === ownLength - 1) {
                return this.next;
            }
        }
        const ownItem = this.getListContent();
        const ownGroup = this.getListGroup();
        if (ownItem && index < ownItem.length()) {
            const after = ownItem.split(index, force) as ListLineBlot;
            if (after instanceof ListLineBlot) {
                const wrapper = Parchment.create(ListItemWrapperBlot.blotName, this.getValue()) as ListItemWrapperBlot;
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

        // handling insertion of images into lists
        if (def && value === ExternalEmbedBlot.blotName) {
            const embed = Parchment.create(value, def);
            const group = this.getWrapper();
            const secondGroup = group?.split(index);
            secondGroup?.parent.insertBefore(embed, secondGroup);
            return;
        } else if (value.includes("\n") && isInListContent) {
            // Break the insert up on it's newlines.
            const startsWithNewline = value.startsWith("\n");
            const inserts = value.split("\n").filter((val) => !!val);

            // If the first part of the insert is a newline, insert it into the content and pop it off.
            const targetNext = this.split(index, startsWithNewline) as ListItemWrapperBlot;
            if (!startsWithNewline) {
                const firstInsert = inserts.shift();
                if (firstInsert) {
                    this.getListContent()!.insertAt(index, firstInsert);
                }
            }

            // Each of the rest of the inserts will get inserted on their own list
            const listItems = inserts.map((insert, inc) => {
                const item = Parchment.create(ListLineBlot.blotName, this.getValue()) as ListLineBlot;
                if (insert !== "") {
                    item.insertAt(0, insert, undefined);
                }
                return item;
            });

            // includes conditions for the new line inserts blot
            // if the last element of the <ul> and the last charectrer in the string
            listItems.forEach((item) => {
                const clone = this.clone() as ListItemWrapperBlot; // Clone the <li/> tag.
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
        if (blot instanceof ListLineBlot || blot instanceof ListGroupBlot) {
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
    public addNestedChild(blot: ListItemWrapperBlot, target: "start" | "end") {
        const group = this.getListGroup();
        if (group) {
            if (group.getValue().depth < blot.getValue().depth && group.children.tail instanceof ListItemWrapperBlot) {
                group.children.tail.addNestedChild(blot, target);
            } else if (target === "start") {
                blot.insertInto(group, group.children.head);
            } else if (target === "end") {
                blot.insertInto(group);
            }
        } else {
            let newGroup: ListGroupBlot | null = null;
            const value = blot.getValue();
            switch (blot.getValue().type) {
                case ListType.BULLETED: {
                    newGroup = new UnorderedListGroupBlot(UnorderedListGroupBlot.create(value));
                    break;
                }
                case ListType.ORDERED: {
                    newGroup = new OrderedListGroupBlot(OrderedListGroupBlot.create(value));
                }
            }
            if (newGroup) {
                blot.insertInto(newGroup);
                newGroup.insertInto(this);
            }
        }
    }

    /**
     * @override
     * Overridden to implement list nesting.
     */
    public optimize(context) {
        this.optimizeNesting();
        this.ensureListContent();
        super.optimize(context);
    }

    /**
     * Ensure that all text content is wrapped in a `ListBlot`.
     */
    private ensureListContent() {
        if (!this.getListContent()) {
            const listContent = Parchment.create(ListLineBlot.blotName, this.getValue()) as any;

            this.children.forEach((child) => {
                if (!(child instanceof ListGroupBlot)) {
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
        const prev = this.prev;
        if (prev instanceof ListItemWrapperBlot && prev.next === this) {
            const ownValue = this.getValue();
            const prevValue = prev.getValue();
            if (prevValue.depth < ownValue.depth) {
                prev.addNestedChild(this, "end");
            }
        }
    }

    /**
     * Move this item up into it's parent if it's nested too far.
     */
    public reverseOptimizeUnwrapping() {
        const parentGroup = this.parent;
        const parentWrapper = parentGroup.parent;
        const grandParentGroup = parentWrapper.parent;
        if (
            parentGroup instanceof ListGroupBlot &&
            parentWrapper instanceof ListItemWrapperBlot &&
            grandParentGroup instanceof ListGroupBlot
        ) {
            // We have higher level list group we can check against.
            const parentGroupValue = grandParentGroup.getValue();
            const ownValue = this.getValue();

            if (ownValue.depth <= parentGroupValue.depth) {
                this.insertInto(grandParentGroup, parentWrapper.next);
            }
        }

        // // Now do the children
        this.getListGroup()?.reverseOptimizeUnwrapping(true);
    }

    /**
     * @override
     * Overriding the createWrapper to dynamically create the parent list group and pass it a value.
     */
    protected createWrapper() {
        const value = this.getValue();
        switch (value.type) {
            case ListType.ORDERED:
                return Parchment.create(OrderedListGroupBlot.blotName, value) as OrderedListGroupBlot;
            default:
                return Parchment.create(UnorderedListGroupBlot.blotName, value) as UnorderedListGroupBlot;
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
    public getListGroup(): ListGroupBlot | null {
        const tail = this.children.tail;
        if (tail instanceof ListGroupBlot) {
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
    public getListContent(): ListLineBlot | null {
        const item = this.children.head;
        if (item instanceof ListLineBlot) {
            return item;
        } else {
            return null;
        }
    }
}
