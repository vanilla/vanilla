/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Block from "quill/blots/block";

/**
 * A Block Blot implementing class matching functionality and representation in the outputted delta.
 */
export default class ClassFormatBlot extends Block {

    static create() {
        const domNode = super.create();

        if (this.className) {
            domNode.classList.add(this.className);
        }
        return domNode;
    }

    constructor(domNode) {
        super(domNode);

        if (!this.constructor.className) {
            throw new Error("Attempted to initialize a ClassFormatBlot without setting the static className");
        }
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
    static formats(domNode) {
        const classMatch = this.className && domNode.classList.contains(this.className);
        const tagMatch = domNode.tagName.toLowerCase() === this.tagName.toLowerCase();

        return this.className ? classMatch && tagMatch : tagMatch;
    }

    /**
     * Get the formats out of the Blot instance's DOM Node.
     *
     * @returns {Object} - The Formats for the Blot.
     */
    formats() {
        return {
            [this.constructor.blotName]: this.constructor.formats(this.domNode),
        };
    }
}
