/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Container from "quill/blots/container";
import Parchment from "parchment";
import ClassFormatBlot from "./ClassFormatBlot";

/**
 * A Blot implementing functions necessary to wrap another Blot as a "Dump" DOM Element.
 *
 * The wrapped blots should additionally use the `wrappedBlot` Higher-order function in `quill-utilities`.
 */
export default class WrapperBlot extends Container {

    // This cannot be Parchment.Scope.BLOCK or it will match and attributor and break pasting.
    static scope = Parchment.Scope.BLOCK_BLOT;
    static tagName = "div";
    static allowedChildren = [WrapperBlot];

    /** @type {Node} */
    domNode;

    /**
     * We want to NOT return the format of this Blot. This blot should never be created on its own. Only through its
     * child blot. Always return undefined.
     */
    static formats() {
        return;
    }

    /**
     * Apply className if applicable.
     *
     * @returns {Node} - The DOM Node for the Blot.
     */
    static create() {
        const domNode = super.create();

        if (this.className) {
            domNode.classList.add(this.className);
        }
        return domNode;
    }

    /**
     * Join the children elements together where possible.
     *
     * @param {any} context -
     */
    optimize(context) {
        super.optimize(context);
        const next = this.next;
        if (next != null && next.prev === this &&
            next.constructor.blotName === this.constructor.blotName &&
            next.domNode.tagName === this.domNode.tagName) {
            next.moveChildren(this);
            next.remove();
        }
    }
}


/**
 * Higher-order function to create a "wrapped" blot.
 *
 * Takes an existing Blot class and implements methods necessary to properly instantiate and cleanup it's parent Blot.
 * the passed Blot class must implement the static property parentName, which should reference a register Blot that is
 * and instance of WrapperBlot.
 *
 * @param {BlotConstructor} BlotConstructor - The Blot constructor to wrap.
 *
 * @returns {BlotConstructor} -
 */
export function wrappedBlot(BlotConstructor) {
    return class extends BlotConstructor {

        constructor(domNode) {
            super(domNode);

            if (!this.constructor.parentName) {
                throw new Error("Attempted to instantiate wrapped Blot without setting static value parentName");
            }
        }

        attach() {
            super.attach();
            if (this.parent.constructor.blotName !== this.statics.parentName) {
                const Wrapper = Parchment.create(this.statics.parentName);

                if (!(Wrapper instanceof WrapperBlot)) {
                    throw new Error("The provided static parentName did not instantiate an instance of a WrapperBlot.");
                }

                this.wrap(Wrapper);
            }
        }

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
         * Delete this blot it has no children. Wrap it if it doesn't have it's proper parent name.
         *
         * @param {Object} context - A shared context that is passed through all updated Blots.
         */
        optimize(context) {
            super.optimize(context);
            if (this.children.length === 0) {
                this.remove();
            }
        }

        /**
         * Replace this blot with another blot.
         *
         * @param {string} name - The name of the replacement Blot.
         * @param {any} value - The value for the replacement Blot.
         */
        replaceWith(name, value) {
            const topLevelWrapper = this.getWrapperBlot();
            const immediateWrapper = this.parent;

            immediateWrapper.children.forEach(child => {
                child.replaceWithIntoScroll(name, value, topLevelWrapper);
            });
            topLevelWrapper.remove();
        }

        /**
         * Replace this ContainerBlot with another one.
         *
         * Then attach that new Blot to the scroll in before the passed insertBefore Blot.
         * This is needed because we a normal replaceWith doesn't work (cyclicly recreates it's parents).
         *
         * @param {string} name - The name of the Blot to replace this one with.
         * @param {string} value - The initial value of the new blot.
         * @param {Blot} insertBefore - The Blot to insert this blot before in the ScrollBlot.
         */
        replaceWithIntoScroll(name, value, insertBefore) {
            const newBlot = Parchment.create(name, value);
            this.moveChildren(newBlot);

            newBlot.insertInto(this.scroll, insertBefore);
        }
    };
}

/**
 * A Content blot is both a WrappedBlot and a WrapperBlot.
 */
const ContentBlot = wrappedBlot(WrapperBlot);

/**
 * A Line blot is responsible for recreating it's wrapping Blots.
 *
 * Always has a WrapperBlot around it.
 */
class TempLineBlot extends ClassFormatBlot {

    /**
     * @returns {ContentBlot} - The parent blot of this Blot.
     */
    getContentBlot() {
        return this.parent;
    }

    /**
     * @returns {WrapperBlot} - The parent blot of this Blot.
     */
    getWrapperBlot() {
        return this.parent.parent;
    }
}

export const LineBlot = wrappedBlot(TempLineBlot);

ContentBlot.allowedChildren = [LineBlot];

export { ContentBlot };
