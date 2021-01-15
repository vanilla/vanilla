/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import LineBlot from "@rich-editor/quill/blots/abstract/LineBlot";
import WrapperBlot from "@rich-editor/quill/blots/abstract/WrapperBlot";
import Parchment from "parchment";
import Container from "quill/blots/container";
import { Blot, BlotInterface } from "quill/core";
import {
    IListObjectValue,
    syncValueToElement,
    getValueFromElement,
    ListTag,
    ListType,
    ListValue,
    ListBlotType,
} from "./ListUtils";
import { ListGroupBlot, UnorderedListGroupBlot, OrderedListGroupBlot } from "./ListGroupBlot";
import { ListItemWrapperBlot } from "./ListItemWrapperBlot";

const MAX_NESTING_DEPTH = 4;

/**
 * The content of a list.
 * Eg. The span of <ul><li><span/></li></ul>.
 *
 * This blot maintains the actual value for the list item.
 * It is also responsible for syncing that value up to the ListWrapper.
 */

export class ListLineBlot extends LineBlot implements Blot {
    public static blotName = ListBlotType.LIST_LINE;
    public static className = "listItem";
    public static parentName = ListBlotType.LIST_ITEM_WRAPPER;

    // This is not the actual tag name we build with (the parent has that)
    // It needs to be set so that pasting works properly though.
    // We actually create a <span> and wrap in an <li>.
    public static tagName = ListTag.LI;

    protected useWrapperReplacement = false;

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

    /**
     * @override
     * Overridden to dynamically create the parent list wrapper with the item's value.
     */
    protected createWrapper() {
        const value = this.getValue();
        return Parchment.create(ListItemWrapperBlot.blotName, value) as WrapperBlot;
    }

    /**
     * Get the nested list item group in our wrapper if it exists.
     */
    protected getWrapperGroup(): ListGroupBlot | null {
        const wrapper = this.getWrapper();
        if (wrapper instanceof ListItemWrapperBlot) {
            return wrapper.getListGroup();
        }
        return null;
    }

    /**
     * Determine if we have an other list inside of us.
     */
    public hasNestedList(): boolean {
        return !!this.getWrapperGroup();
    }

    /**
     * Like a softer `format()`. The difference is we don't want to replace the item. We want to update it in place.
     *
     * @param newDepth
     */
    public updateIndentValue(newDepth: number, flattenChildren: boolean = false) {
        newDepth = Math.min(MAX_NESTING_DEPTH, newDepth);
        this.domNode.setAttribute("data-depth", newDepth);
        this.parent.domNode.setAttribute("data-depth", newDepth);
        this.cache = {};

        // Handle all children.
        const wrapperGroup = this.getWrapperGroup();
        const newChildDepth = flattenChildren ? newDepth : newDepth + 1;
        if (wrapperGroup) {
            wrapperGroup.domNode.setAttribute("data-depth", newChildDepth);
            wrapperGroup.children.forEach((child) => {
                if (child instanceof ListItemWrapperBlot) {
                    const content = child.getListContent();
                    if (content instanceof ListLineBlot && content !== this) {
                        content.updateIndentValue(newChildDepth);
                    }
                }
            });
        }
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
        const hasPreviousItem =
            this.parent instanceof ListItemWrapperBlot && this.parent.prev instanceof ListItemWrapperBlot;
        const hasPreviousGroup =
            this.parent instanceof ListItemWrapperBlot &&
            this.parent.parent instanceof ListGroupBlot &&
            this.parent.parent.prev instanceof ListGroupBlot;
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
    public outdent(flattenChildren: boolean = false) {
        if (this.canOutdent()) {
            // Bring all siblings into us as a child.
            // Example
            // Before
            // - 1
            //   - 1.1
            //   - 1.2
            // After Outdent 1.1
            // - 1
            // - 1.1
            //   - 1.2
            const wrapper = this.getWrapper() as ListItemWrapperBlot;
            let next = wrapper.next;
            while (next) {
                const nextInOldStructure = next.next;
                if (next instanceof ListItemWrapperBlot) {
                    wrapper.addNestedChild(next, "end");
                }
                next = nextInOldStructure;
            }

            // Perform the outdent.
            this.updateIndentValue(this.getValue().depth - 1, flattenChildren);
        }
    }

    /**
     * @override
     * Overridden to safely handle list values changing.
     */
    public replaceWith(formatName: string | BlotInterface, value?: any): any {
        const replacingWithList = formatName === ListLineBlot.blotName;

        if (replacingWithList && value) {
            let { depth, type } = value as IListObjectValue;
            this.updateIndentValue(depth);
            this.updateListType(type);
            return this;
        } else {
            // Flatten the item, then replace it.
            // Walk up to the scroll and get the top level list group.
            this.updateIndentValue(0, true);
            let parent: any = this.parent;
            while (parent.parent !== this.scroll) {
                parent = parent.parent;
            }
            if (!(parent instanceof ListGroupBlot)) {
                return null;
            }
            const split = parent.split(this.offset(parent));
            parent.reverseOptimizeUnwrapping(true);
            const replacement = (typeof formatName === "string"
                ? Parchment.create(formatName, value)
                : formatName) as Container;
            this.moveChildren(replacement);
            this.scroll.insertBefore(replacement, split);
            this.remove();
            if (split.length() <= 1) {
                split.remove();
            }
            if (parent.length() <= 1) {
                parent.remove();
            }

            return replacement;
        }
    }

    public updateListType(listType: string) {
        if (this.getValue().type === listType) {
            // Nothing to do here.
            return;
        }

        // Get all siblings (including ourself).
        const currentListGroup = this.parent.parent;
        if (!(currentListGroup instanceof ListGroupBlot)) {
            // Just in case.
            return;
        }

        // Swap out the parent.
        let newListGroup: ListGroupBlot | null = null;
        const newValue: IListObjectValue = {
            ...this.getValue(),
            type: listType as ListType,
        };
        switch (listType) {
            case ListType.BULLETED:
                newListGroup = new UnorderedListGroupBlot(UnorderedListGroupBlot.create(newValue));
                break;
            case ListType.ORDERED:
                newListGroup = new OrderedListGroupBlot(OrderedListGroupBlot.create(newValue));
                break;
            default:
                return;
        }

        // Set our own values and the parent.
        // We will change our own, and all sibling list types.
        currentListGroup.children.forEach((child) => {
            if (child instanceof ListItemWrapperBlot) {
                child.domNode.setAttribute("data-type", listType);
                child.getListContent()?.domNode.setAttribute("data-type", listType);
            }
        });

        newListGroup.insertInto(currentListGroup.parent, currentListGroup);
        currentListGroup.moveChildren(newListGroup);
        currentListGroup.remove();
    }

    /**
     * Utility for getting the value from the blot's domNode.
     */
    public getValue(): IListObjectValue {
        return getValueFromElement(this.domNode);
    }
}
