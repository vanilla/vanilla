import Block from "quill/blots/block";
import Container from "quill/blots/container";
import Parchment from "parchment";

export class BlockquoteContentBlot extends Block {

    static formats(domNode) {
        return domNode.tagName === this.tagName ? undefined : super.formats(domNode);
    }

    static blotName = "blockquote-content";
    static className = "blockquote-content";
    static tagName = 'div';

    static create() {
        const domNode = super.create();
        domNode.classList.add('blockquote-content');
        domNode.classList.add('blockquote-main');
        return domNode;
    }

    remove() {
        if (this.prev == null && this.next == null) {
            this.parent.remove();
        } else {
            super.remove();
        }
    }

    replaceWith(name, value) {
        this.parent.isolate(this.offset(this.parent), this.length());
        if (name === this.parent.statics.blotName) {
            this.parent.replaceWith(name, value);
            return this;
        } else {
            this.parent.unwrap();
            return super.replaceWith(name, value);
        }
    }
}

export default class BlockquoteBlot extends Container {

    static blotName = "blockquote";
    static tagName = 'BLOCKQUOTE';
    static className = 'blockquote';
    static scope = Parchment.Scope.BLOCK_BLOT;
    static defaultChild = 'blockquote-content';
    static allowedChildren = [BlockquoteContentBlot];

    static create() {
        const domNode = super.create();
        domNode.classList.add('blockquote');
        return domNode;
    }

    static formats(domNode) {
        if (domNode.tagName.toLowerCase() === BlockquoteBlot.blotName) {
            return true;
        }

        return undefined;
    }

    formats() {
        return {
            [this.statics.blotName]: this.statics.formats(this.domNode),
        };
    }

    insertBefore(blot, ref) {
        if (blot instanceof BlockquoteContentBlot) {
            super.insertBefore(blot, ref);
        } else {
            const index = ref == null ? this.length() : ref.offset(this);
            const after = this.split(index);
            after.parent.insertBefore(blot, after);
        }
    }

    optimize(context) {
        super.optimize(context);
        const prev = this.prev;
        if (prev != null && prev.next === this &&
            prev.statics.blotName === this.statics.blotName &&
            prev.domNode.tagName === this.domNode.tagName) {
            prev.moveChildren(this);
            prev.remove();
        }
    }

    replace(target) {
        if (target.statics.blotName !== this.statics.blotName) {
            const item = Parchment.create(this.statics.defaultChild);
            target.moveChildren(item);
            this.appendChild(item);
        }
        super.replace(target);
    }
}
