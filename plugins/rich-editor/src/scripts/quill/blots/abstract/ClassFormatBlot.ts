/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import BlockBlot from "quill/blots/block";

/**
 * A Block Blot implementing class matching functionality and representation in the outputted delta.
 */
export default class ClassFormatBlot extends BlockBlot {
    public static create(value) {
        const domNode = super.create(value) as HTMLElement;

        if (this.className) {
            domNode.classList.add(this.className);
        }
        return domNode;
    }

    /**
     * Return the formats for the Blot. Check matching of the tag as well as classname if applicable.
     *
     * This is necessary for copy/paste to work.
     *
     * @param {Node} domNode - The DOM Node to check.
     *
     * @returns {boolean} Whether or a not a DOM Node represents this format.
     */
    public static formats(domNode) {
        const classMatch = this.className && domNode.classList.contains(this.className);
        const tagMatch = domNode.tagName.toLowerCase() === this.tagName.toLowerCase();

        return this.className ? classMatch && tagMatch : tagMatch;
    }

    constructor(domNode) {
        super(domNode);

        if (!this.statics.className) {
            throw new Error("Attempted to initialize a ClassFormatBlot without setting the static className");
        }
    }

    /**
     * Get the formats out of the Blot instance's DOM Node.
     *
     * @returns The Formats for the Blot.
     */
    public formats() {
        return {
            [this.statics.blotName]: this.statics.formats(this.domNode),
        };
    }
}
