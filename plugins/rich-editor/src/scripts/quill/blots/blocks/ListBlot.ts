/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Parchment from "parchment";
import LineBlot from "@rich-editor/quill/blots/abstract/LineBlot";
import WrapperBlot from "@rich-editor/quill/blots/abstract/WrapperBlot";
import Quill, { Blot } from "quill/core";
import Container from "quill/blots/container";
import Block from "quill/blots/block";
import Inline from "quill/blots/inline";
import Scroll from "quill/blots/scroll";

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

function syncValueToElement(element: HTMLElement, value: IListItem) {
    element.setAttribute("data-depth", value.depth);
    element.setAttribute("data-type", value.type);
}

export class ListGroup extends WrapperBlot {
    public static scope = Parchment.Scope.BLOCK_BLOT;

    public static create(value: IListItem) {
        const element = super.create(value);
        syncValueToElement(element, value);
        return element;
    }

    public formatAt(index: number, length: number, name: string, value: any): void {
        const [child] = this.children.find(index);
        if (child instanceof ListItem) {
            const nestedList = child.getNestedList();
            if (nestedList) {
                const offset = nestedList.offset(this);
                if (offset < index) {
                    nestedList.formatAt(index, length, name, value);
                    return;
                }
            }
        }
        super.formatAt(index, length, name, value);
    }

    public insertAt(index: number, value: string, def: any) {
        // Pass things along to our nested list
        const [child] = this.children.find(index);
        if (child instanceof ListItem) {
            const nestedList = child.getNestedList();
            if (nestedList) {
                const offset = nestedList.offset(this);
                if (offset < index) {
                    nestedList.insertAt(index - offset, value, def);
                    return;
                }
            }
        }

        super.insertAt(index, value, def);
    }

    public deleteAt(index: number, length: number) {
        const [child] = this.children.find(index);
        if (child instanceof ListItem) {
            const nestedList = child.getNestedList();
            if (nestedList) {
                const offset = nestedList.offset(this);
                if (offset < index) {
                    nestedList.deleteAt(index, length);
                    return;
                }
            }
        }
        super.deleteAt(index, length);
    }

    public descendant(criteria, index: number) {
        return super.descendant(blot => {
            const isCorrectListItem = this.isBlotCorrectListItem(blot, index);
            if (!isCorrectListItem) {
                return false;
            } else {
                // Default check
                return (
                    (criteria.blotName == null && criteria(blot)) ||
                    (criteria.blotName != null && blot instanceof criteria)
                );
            }
        }, index);
    }

    private isBlotCorrectListItem = (blot: Blot, index: number) => {
        if (blot instanceof ListItem) {
            const nestedList = blot.getNestedList();
            if (nestedList) {
                const offset = nestedList.offset(this);
                if (offset < index) {
                    return false;
                }
            }
        }
        return true;
    };

    /**
     * @override
     * So in this scenario
     *
     *- List 1.0
     *  - List 1.1
     *
     * List 1.0 contains 1.2, so a naive search for a range only targetting 1.1 will
     * include 1.0.
     *
     * This method filters out 1.0 if the range only contains text characters from 1.1.
     */
    public descendants(criteria, index: number, length: number) {
        const descendants = super.descendants(criteria, index, length);
        const filtered = descendants.filter((blot: Blot) => this.isBlotCorrectListItem(blot, index));
        return filtered;
    }

    public path(index: number, inclusive: boolean = false): Array<[Blot, number]> {
        const childResult = this.children.find(index, inclusive);
        const [child, offset] = childResult;
        const position: Array<[any, number]> = [[this, index]];
        if (child instanceof Container || child instanceof ListItem) {
            // if (child instanceof ListGroup) {
            //     // We need to offset our index by the list's offset.
            //     index = index - child.offset(this);
            // }
            const childOffset = child.path(offset, true);
            return position.concat(childOffset);
        } else if (child != null) {
            position.push([child, offset]);
        }
        return position;
    }

    /**
     * Join the children elements together where possible.
     */
    public optimize(context) {
        this.optimizeAdjacentGroups();
        super.optimize(context);
    }

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
                if (!(targetListItem instanceof ListItem)) {
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

    public insertInto(parentBlot: Container, ref?: Blot) {
        if (!(parentBlot instanceof Scroll) && !(parentBlot instanceof ListItem)) {
            // Move it into the scroll anyways;
            super.insertInto(this.scroll, parentBlot.next);
        } else {
            super.insertInto(parentBlot, ref);
        }
    }

    public getValue(): IListItem {
        return getValueFromElement(this.domNode);
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

export class ListContent extends Inline {
    public static blotName = "listContent";
    public static className = "listContent";
    public static tagName = "span";
}

export class ListItem extends LineBlot {
    public static blotName = "list";
    public static className = "listItem";
    public static tagName = ListTag.LI;
    public static parentName = [UnorderedListGroup.blotName, OrderedListGroup.blotName];
    public static allowedChildren = [...LineBlot.allowedChildren, ListGroup, ListContent];

    public static create(value: ListValue) {
        value = this.mapListValue(value);
        const element = super.create(value) as HTMLElement;
        element.setAttribute("data-depth", value.depth);
        element.setAttribute("data-type", value.type);
        return element;
    }

    public constructor(domNode) {
        super(domNode);
        this.insertBefore(Parchment.create("text", ""));
    }

    /**
     *
     */
    public optimize(context) {
        this.optimizeNesting();
        this.optimizeUnwraps();
        super.optimize(context);
    }

    private optimizeNesting() {
        const next = this.next;
        if (next instanceof ListItem && next.prev === this) {
            const ownValue = this.getValue();
            const nextValue = next.getValue();

            if (!ownValue || !nextValue) {
                return;
            }

            if (nextValue.depth > ownValue.depth) {
                if (this.children.tail instanceof ListGroup) {
                    const targetGroup = this.children.tail;
                    next.insertInto(targetGroup);

                    // Adjust our list type to the target value.
                    const newNextValue: IListItem = {
                        ...nextValue,
                        type: targetGroup.getValue().type,
                    };
                    next.format("list", newNextValue);
                } else {
                    // Just insert it directly into the end. It will create its own group.
                    next.insertInto(this);
                }
            }
        }
    }

    private optimizeUnwraps() {
        if (this.children.length === 1 && this.children.head instanceof ListGroup) {
            // If our only child is a list group (no text)
            // Try to unwrap into the list group.
            const onlyChildGroup = this.children.head;

            if (this.parent instanceof ListGroup) {
                onlyChildGroup.moveChildren(this.parent);
            }
        }

        const parentGroup = this.parent;
        const parentItem = parentGroup.parent;
        const grandParentGroup = parentItem.parent;
        if (
            parentGroup instanceof ListGroup &&
            parentItem instanceof ListItem &&
            grandParentGroup instanceof ListGroup
        ) {
            const parentGroupValue = parentGroup.getValue();
            const grandparentGroupValue = grandParentGroup.getValue();
            const ownValue = this.getValue();

            // We definitely don't belong here.
            if (ownValue.depth < parentGroupValue.depth) {
                this.insertInto(grandParentGroup, parentItem.next);

                // Adjust our list type to the target value.
                const newValue: IListItem = {
                    ...ownValue,
                    type: grandParentGroup.getValue().type,
                };
                this.format("list", newValue);
            } else if (ownValue.depth === grandparentGroupValue.depth) {
                // Try to merge into the the grandparent list group.
                this.insertInto(grandParentGroup, parentItem.next);
            }
        }
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

    public getNestedList(): ListGroup | null {
        let listGroup: ListGroup | null = null;
        this.children.forEach(item => {
            if (item instanceof ListGroup) {
                listGroup = item;
            }
        });
        return listGroup;
    }

    public length() {
        if (this.getNestedList()) {
            return super.length() - 1;
        } else {
            return super.length();
        }
    }

    public path(index: number, inclusive: boolean = false): Array<[Blot, number]> {
        const childResult = this.children.find(index, inclusive);
        const [child, offset] = childResult;
        const position: Array<[any, number]> = [[this, index]];
        if (child instanceof Container || child instanceof ListItem) {
            // if (child instanceof ListGroup) {
            //     // We need to offset our index by the list's offset.
            //     index = index - child.offset(this);
            // }
            const childOffset = child.path(offset, inclusive);
            return position.concat(childOffset);
        } else if (child != null) {
            position.push([child, offset]);
        }
        return position;
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
        if (!(this.prev instanceof ListItem)) {
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

        if (ownValue.depth <= 0) {
            return;
        }

        if (this.domNode.textContent === "") {
            return;
        }

        const newValue = {
            ...ownValue,
            depth: ownValue.depth - 1,
        };
        this.format(ListItem.blotName, newValue);
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
        return getValueFromElement(this.domNode);
    }
}

OrderedListGroup.allowedChildren = [WrapperBlot, ListItem];
UnorderedListGroup.allowedChildren = [WrapperBlot, ListItem];
