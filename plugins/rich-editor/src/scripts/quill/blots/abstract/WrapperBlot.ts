/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Container from "quill/blots/container";
import Parchment from "parchment";

/**
 * A Blot implementing functions necessary to wrap another Blot as a "Dump" DOM Element.
 *
 * The wrapped blots should additionally use the `wrappedBlot` Higher-order function in `quill-utilities`.
 */
export default class WrapperBlot extends Container {
    // This cannot be Parchment.Scope.BLOCK or it will match and attributor and break pasting.
    public static scope = Parchment.Scope.BLOCK_BLOT;
    public static tagName = "div";
    public static allowedChildren = [WrapperBlot];
    public static className: string;

    /**
     * We want to NOT return the format of this Blot. This blot should never be created on its own. Only through its
     * child blot. Always return undefined.
     */
    public static formats() {
        return;
    }

    /**
     * Apply className if applicable.
     *
     * @returns {Node} - The DOM Node for the Blot.
     */
    public static create(value) {
        const domNode = super.create(value) as HTMLElement;

        if (this.className) {
            domNode.classList.add(this.className);
        }
        return domNode;
    }

    /** @type {Node} */
    public domNode;

    /**
     * Join the children elements together where possible.
     *
     * @param {any} context -
     */
    public optimize(context) {
        super.optimize(context);
        const next = this.next;
        if (
            next instanceof WrapperBlot &&
            next.prev === this &&
            next.statics.blotName === (this.constructor as any).blotName &&
            next.domNode.tagName === this.domNode.tagName
        ) {
            next.moveChildren(this);
            next.remove();
        }
    }
}
