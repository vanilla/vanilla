/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import WrapperBlot from "@rich-editor/quill/blots/abstract/WrapperBlot";
import { ListItemWrapperBlot } from "@rich-editor/quill/blots/lists/ListItemWrapperBlot";
import Container from "quill/blots/container";
import { IListObjectValue, syncValueToElement, getValueFromElement, ListTag, ListBlotType } from "./ListUtils";

/**
 * The list wrapper. Either an <ol> or <ul>.
 *
 * - Never create this directly. This should only be created by a ListItemWrapper.
 * - ListGroup itself should never be used. Use either the ordered or unordered sub classes.
 */
export abstract class ListGroupBlot extends WrapperBlot {
    public static allowedChildren = [Container];

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
        this.reverseOptimizeUnwrapping();
        this.optimizeAdjacentGroups();
        super.optimize(context);
        if (this.children?.length === 0) {
            this.remove();
        }
    }

    /**
     * Optimize list wrappers here instead of in the wrapper,
     * because we need to optimize from the outside inwards.
     * By default quill optimizes children before parents.
     *
     * @param forceOptimize Pass this to force an optimize, even if we aren't a top level group.
     */
    public reverseOptimizeUnwrapping(forceOptimize: boolean = false) {
        if (!forceOptimize && this.parent !== this.scroll) {
            // Only the outermost list group will trigger this.
            // Keep in mind the inner list groups will have optimize() called first.
            return;
        }

        this.children.forEach((child) => {
            if (child instanceof ListItemWrapperBlot) {
                child.reverseOptimizeUnwrapping();
            }
        });
    }

    /**
     * Optimize together groups that are next to each other.
     *
     * - Groups with the same type and depth will be merged together.
     * - If the next group has a greater depth it will be nested into the last item of the current group.
     */
    private optimizeAdjacentGroups() {
        const next = this.next;
        if (next instanceof ListGroupBlot && next.prev === this) {
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
                if (!(targetListWrapper instanceof ListItemWrapperBlot)) {
                    return;
                }

                next.children.forEach((child: ListItemWrapperBlot) => {
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
export class OrderedListGroupBlot extends ListGroupBlot {
    public static blotName = ListBlotType.ORDERED_LIST_GROUP;
    public static className = ListBlotType.ORDERED_LIST_GROUP;
    public static tagName = ListTag.OL;
}

/**
 * ListGroup for <ol> tags.
 */
export class UnorderedListGroupBlot extends ListGroupBlot {
    public static blotName = ListBlotType.UNORDERED_LIST_GROUP;
    public static className = ListBlotType.UNORDERED_LIST_GROUP;
    public static tagName = ListTag.UL;
}
