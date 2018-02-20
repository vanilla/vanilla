import Block from "quill/blots/block";

export default class BlockquoteContentBlot extends Block {

    /**
     * Get the formats for this block.
     *
     * We want to NOT return the format of this Blot. This blot should never be created on its own. Only through its
     * parent blot.
     *
     * @see {makeWrapperBlot()}
     *
     * @param {Node} domNode - the DOM Node to check.
     *
     * @returns {undefined|string} - A format for the blot.
     */
    static formats(domNode) {
        return domNode.tagName === this.tagName ? undefined : super.formats(domNode);
    }

    static tagName = 'div';

    /**
     * If this is the only child blot we want to delete the parent with it.
     */
    remove() {
        if (this.prev == null && this.next == null) {
            this.parent.remove();
        } else {
            super.remove();
        }
    }

    /**
     * Replace this blot with another blot.
     *
     * Attempts to work through the parent if it's a 1:1 replacement,
     * otherwise unwraps the parent and replaces the child.
     *
     * @param {string} name - The name of the replacement Blot.
     * @param {any} value - The value for the replacement Blot.
     *
     * @returns {Blot} - The blot to replace this one.
     */
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
