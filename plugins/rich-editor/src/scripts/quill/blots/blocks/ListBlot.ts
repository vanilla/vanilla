/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Parchment from "parchment";
import Block from "quill/blots/block";
import Container from "quill/blots/container";

/* tslint:disable:max-classes-per-file */

enum ListTag {
    OL = "OL",
    UL = "UL",
    LI = "LI",
}

enum ListType {
    NUMBERED = "numbered",
    BULLETED = "bulleted",
    CHECKBOX = "checkbox",
}

interface IListItem {
    type: ListType;
    index?: number;
    isChecked?: boolean;
}

export class ListItem extends Block {
    public static blotName = "list-item";
    public static tagName = ListTag.LI;

    static formats(domNode) {
        return domNode.tagName === this.tagName ? undefined : super.formats(domNode);
    }

    public constructor(domNode: HTMLElement, value: IListItem) {
        super(domNode);

        console.log("List item with", value);
        // domNode.setAttribute("data-type", value.type);
        // domNode.setAttribute("data-index", value.type);
        // domNode.setAttribute("isChecked", value.type);
    }

    public format(name, value) {
        if (name === ListItem.blotName && !value) {
            this.replaceWith(Parchment.create(this.statics.scope), true);
        } else {
            super.format(name, value);
        }
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

export class ListGroup extends Container {
    public static blotName = "list";
    public static scope = Parchment.Scope.BLOCK_BLOT;
    public static tagName = [ListTag.OL, ListTag.UL] as any;
    public static defaultChild = "list-item";
    public static allowedChildren = [ListItem, ListGroup];

    public static create(value: IListItem | string) {
        if (typeof value === "string") {
            return ListGroup.legacyCreate(value);
        }

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
        return document.createElement(tagName);
    }

    private static legacyCreate(value: string) {
        const tagName = value === "ordered" ? ListTag.OL : ListTag.UL;
        const node = super.create(tagName);
        return node;
    }

    public static formats(domNode) {
        if (domNode.tagName === "OL") {
            return "ordered";
        }
        if (domNode.tagName === "UL") {
            if (domNode.hasAttribute("data-checked")) {
                return domNode.getAttribute("data-checked") === "true" ? "checked" : "unchecked";
            } else {
                return "bullet";
            }
        }
        return undefined;
    }

    public formats() {
        // We don't inherit from FormatBlot
        return { [this.statics.blotName]: this.statics.formats(this.domNode) };
    }

    public format(name, value) {
        if (this.children.length > 0) {
            this.children.tail.format(name, value);
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
            next instanceof Container &&
            next.prev === this &&
            next.statics.blotName === this.statics.blotName &&
            next.domNode instanceof HTMLElement &&
            next.domNode.tagName === this.domNode.tagName &&
            next.domNode.getAttribute("data-checked") === this.domNode.getAttribute("data-checked")
        ) {
            next.moveChildren(this);
            next.remove();
        }
    }

    public replace(target) {
        if (target.statics.blotName !== this.statics.blotName) {
            const item = Parchment.create(this.statics.defaultChild);
            target.moveChildren(item);
            this.appendChild(item);
        }
        super.replace(target);
    }
}
