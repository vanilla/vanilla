/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Parchment from "parchment";
import Block from "quill/blots/block";
import Container from "quill/blots/container";
import { Blot } from "quill/core";

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

export class ListItem extends Block {
    public static blotName = "list-item";
    public static tagName = ListTag.LI;

    public static create(value: IListItem) {
        const element = super.create(value) as HTMLElement;
        element.setAttribute("data-depth", value.depth);
        element.setAttribute("data-type", value.type);
        return element;
    }

    public static getValueFromElement(domNode: HTMLElement): IListItem {
        const depthAttr = domNode.getAttribute("data-depth");
        const typeAtrr = domNode.getAttribute("data-type");

        let depth = 0;
        if (depthAttr !== null) {
            depth = parseInt(depthAttr, 10);
        }

        let type = ListType.BULLETED;
        if (typeAtrr !== null) {
            type = type;
        }

        return {
            depth,
            type,
        };
    }

    public static formats(domNode: HTMLElement) {
        // We want the List item to never match.
        // It should always take it's format from the parent ListGroup.
        // See bubbleFormats.
        return undefined;
    }

    private value: IListItem;

    public constructor(domNode: HTMLElement, value: IListItem) {
        super(domNode);
        this.value = this.statics.getValueFromElement(domNode);
    }

    public getValue(): IListItem {
        return this.value;
    }

    public format(name, value) {
        if (name === ListItem.blotName && !value) {
            this.replaceWith(Parchment.create(this.statics.scope), true);
        } else {
            super.format(name, value);
        }
    }

    public clone() {
        return super.clone();
    }

    public remove() {
        if (this.prev == null && this.next == null) {
            this.parent.remove();
        } else {
            super.remove();
        }
    }

    public replaceWith(name, value) {
        this.parent.isolate(this.offset(this.parent), this.length());
        if (name === (this.parent as any).statics.blotName) {
            this.parent.replaceWith(name, value);
            return this;
        } else {
            this.parent.unwrap();
            return super.replaceWith(name, value);
        }
    }
}

/**
 * WARNING
 * There are some simple rules to follow with this blot.
 *
 * 1. Don't mutate this.domNode outside of `syncDomNode`.
 * 2. Mutate `this.value` instead.
 */
export class ListGroup extends Container {
    public static blotName = "list";
    public static scope = Parchment.Scope.BLOCK_BLOT;
    public static tagName = [ListTag.OL, ListTag.UL] as any;
    public static allowedChildren = [ListItem];

    public static create(value: ListValue) {
        value = this.mapListValue(value);

        let tagName;
        switch (value.type) {
            case ListType.BULLETED:
            case ListType.CHECKBOX:
                tagName = ListTag.UL;
                break;
            case ListType.NUMBERED:
                tagName = ListTag.OL;
                break;
            default:
                throw new Error(`Error rendering ListItem. Invalid type: ${value.type}`);
        }
        const element = document.createElement(tagName) as HTMLElement;
        element.setAttribute("depth", value.depth);
        if (value.isChecked) {
            element.setAttribute("isChecked", value.isChecked);
        }
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

    /**
     * Get the active format value for the blot.
     *
     * @param domNode
     */
    private static getValueFromDomNode(domNode: HTMLElement): IListItem | undefined {
        const depthAttr = domNode.getAttribute("depth");
        let depth = 0;
        if (depthAttr !== null) {
            depth = parseInt(depthAttr, 10);
        }
        if (domNode.tagName === ListTag.OL) {
            return {
                type: ListType.NUMBERED,
                depth,
            };
        }
        if (domNode.tagName === "UL") {
            const isChecked = domNode.getAttribute("data-isChecked");
            if (isChecked !== null) {
                return {
                    type: ListType.CHECKBOX,
                    isChecked: isChecked === "true" ? true : false,
                    depth,
                };
            } else {
                return {
                    type: ListType.BULLETED,
                    depth,
                };
            }
        }
    }

    public static formats = ListGroup.getValueFromDomNode;

    public constructor(domNode: HTMLElement, private value: IListItem) {
        super(domNode);
    }

    public get statics() {
        return this.constructor as typeof ListGroup;
    }

    public formats() {
        // We don't inherit from FormatBlot
        return { [this.statics.blotName]: this.statics.getValueFromDomNode(this.domNode) };
    }

    public format(name, value) {
        if (this.children.length > 0) {
            ((this.children.tail as any) as ListItem).format(name, value);
        }
    }

    public insertBefore(blot, ref) {
        if (blot instanceof ListItem) {
            super.insertBefore(blot as any, ref);
        } else {
            const index = ref == null ? this.length() : ref.offset(this);
            const after = this.split(index);
            after.parent.insertBefore(blot, after);
        }
    }

    public optimize(context) {
        super.optimize(context);
        const next = this.next;
        if (
            next instanceof ListGroup &&
            next.prev === this &&
            next.statics.blotName === this.statics.blotName &&
            next.domNode instanceof HTMLElement &&
            next.domNode.tagName === this.domNode.tagName
        ) {
            // Join similar groupings together.
            next.moveChildren(this);
            next.remove();

            const value = this.statics.getValueFromDomNode(this.domNode);
            if (!value) {
                return;
            }

            console.log("optimize list children");
            // Take children of depths that need to be nested and place them further inside.
            this.children.forEach((listItem: Blot) => {
                if (listItem instanceof ListItem) {
                    const prevItem = (listItem.prev as any) as ListItem | null;
                    // Wrap the blot and insert it deeper.
                    const itemValue = listItem.getValue();
                    if (!itemValue) {
                        return;
                    }

                    // Bail out for item values that less than or the same as us.
                    if (itemValue.depth <= value.depth) {
                        return;
                    }

                    let cleanupRequired = false;
                    let targetListGroup: ListGroup;
                    if (prevItem && prevItem.children.length === 1 && prevItem.children.head instanceof ListGroup) {
                        targetListGroup = prevItem.children.head;
                    } else {
                        cleanupRequired = true;
                        const newWrapperListItem = Parchment.create(ListItem.blotName, value) as Container;
                        targetListGroup = Parchment.create(this.statics.blotName, itemValue) as ListGroup;
                        this.insertBefore(newWrapperListItem, listItem);
                        newWrapperListItem.appendChild(targetListGroup);
                    }

                    const newListItem = Parchment.create(ListItem.blotName, itemValue) as Container;
                    targetListGroup.appendChild(newListItem);
                    listItem.moveChildren(newListItem);
                    listItem.remove();
                }
            });
        }
    }

    public replace(target) {
        const item = Parchment.create(ListItem.blotName, this.statics.getValueFromDomNode(this.domNode));
        this.appendChild(item);
        target.moveChildren(item);
        super.replace(target);
    }
}

ListItem.allowedChildren = [...Block.allowedChildren, ListGroup];
