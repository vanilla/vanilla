import Block from "quill/blots/block";

export default class BlockquoteContentBlot extends Block {

    static formats(domNode) {
        return domNode.tagName === this.tagName ? undefined : super.formats(domNode);
    }

    static tagName = 'div';

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
